<?php

namespace Tests\Feature;

use App\Jobs\PullGoogleCalendarChangesJob;
use App\Jobs\SyncEventToGoogleJob;
use App\Models\CalendarEvent;
use App\Models\GoogleCalendarConnection;
use App\Models\GoogleCalendarSyncMapping;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class GoogleCalendarSyncJobTest extends TestCase
{
    use RefreshDatabase;

    // ── SyncEventToGoogleJob ────────────────────────────

    public function test_sync_job_creates_event_in_google(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $connection = GoogleCalendarConnection::factory()->create([
            'user_id' => $user->id,
        ]);
        $event = CalendarEvent::factory()->create(['owner_id' => $user->id]);

        $mockService = Mockery::mock(GoogleCalendarService::class);
        $mockService->shouldReceive('createEvent')
            ->once()
            ->andReturn('google-event-id-123');

        $this->app->instance(GoogleCalendarService::class, $mockService);

        $job = new SyncEventToGoogleJob($event, $connection, 'create');
        $job->handle($mockService);

        $this->assertDatabaseHas('google_calendar_sync_mappings', [
            'calendar_event_id' => $event->id,
            'google_calendar_connection_id' => $connection->id,
            'google_event_id' => 'google-event-id-123',
        ]);
    }

    public function test_sync_job_updates_event_in_google(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $connection = GoogleCalendarConnection::factory()->create([
            'user_id' => $user->id,
        ]);
        $event = CalendarEvent::factory()->create(['owner_id' => $user->id]);

        GoogleCalendarSyncMapping::create([
            'calendar_event_id' => $event->id,
            'google_calendar_connection_id' => $connection->id,
            'google_event_id' => 'google-event-id-123',
            'last_synced_at' => now()->subHour(),
        ]);

        $mockService = Mockery::mock(GoogleCalendarService::class);
        $mockService->shouldReceive('updateEvent')
            ->once()
            ->with($connection, $event, 'google-event-id-123');

        $this->app->instance(GoogleCalendarService::class, $mockService);

        $job = new SyncEventToGoogleJob($event, $connection, 'update');
        $job->handle($mockService);
    }

    public function test_sync_job_creates_event_when_update_has_no_mapping(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $connection = GoogleCalendarConnection::factory()->create([
            'user_id' => $user->id,
        ]);
        $event = CalendarEvent::factory()->create(['owner_id' => $user->id]);

        $mockService = Mockery::mock(GoogleCalendarService::class);
        $mockService->shouldReceive('createEvent')
            ->once()
            ->andReturn('new-google-id');

        $this->app->instance(GoogleCalendarService::class, $mockService);

        $job = new SyncEventToGoogleJob($event, $connection, 'update');
        $job->handle($mockService);

        $this->assertDatabaseHas('google_calendar_sync_mappings', [
            'calendar_event_id' => $event->id,
            'google_event_id' => 'new-google-id',
        ]);
    }

    public function test_sync_job_deletes_event_from_google(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $connection = GoogleCalendarConnection::factory()->create([
            'user_id' => $user->id,
        ]);
        $event = CalendarEvent::factory()->create(['owner_id' => $user->id]);

        $mapping = GoogleCalendarSyncMapping::create([
            'calendar_event_id' => $event->id,
            'google_calendar_connection_id' => $connection->id,
            'google_event_id' => 'google-event-id-123',
            'last_synced_at' => now(),
        ]);

        $mockService = Mockery::mock(GoogleCalendarService::class);
        $mockService->shouldReceive('deleteEvent')
            ->once()
            ->with($connection, 'google-event-id-123');

        $this->app->instance(GoogleCalendarService::class, $mockService);

        $job = new SyncEventToGoogleJob($event, $connection, 'delete');
        $job->handle($mockService);

        $this->assertDatabaseMissing('google_calendar_sync_mappings', [
            'id' => $mapping->id,
        ]);
    }

    public function test_sync_job_skips_disabled_connection(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $connection = GoogleCalendarConnection::factory()->disabled()->create([
            'user_id' => $user->id,
        ]);
        $event = CalendarEvent::factory()->create(['owner_id' => $user->id]);

        $mockService = Mockery::mock(GoogleCalendarService::class);
        $mockService->shouldNotReceive('createEvent');
        $mockService->shouldNotReceive('updateEvent');
        $mockService->shouldNotReceive('deleteEvent');

        $this->app->instance(GoogleCalendarService::class, $mockService);

        $job = new SyncEventToGoogleJob($event, $connection, 'create');
        $job->handle($mockService);
    }

    // ── PullGoogleCalendarChangesJob ────────────────────

    public function test_pull_job_creates_new_events_from_google(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $connection = GoogleCalendarConnection::factory()->create([
            'user_id' => $user->id,
        ]);

        $googleEvent = $this->createMockGoogleEvent(
            id: 'goog-1',
            summary: 'Google Meeting',
            startDateTime: '2026-03-15T10:00:00+01:00',
            endDateTime: '2026-03-15T11:00:00+01:00',
        );

        $mockService = Mockery::mock(GoogleCalendarService::class);
        $mockService->shouldReceive('listChanges')
            ->once()
            ->with($connection)
            ->andReturn([
                'events' => [$googleEvent],
                'nextSyncToken' => 'new-sync-token',
            ]);
        $mockService->shouldReceive('mapFromGoogleEvent')
            ->once()
            ->andReturn([
                'title' => 'Google Meeting',
                'description' => null,
                'start_at' => now()->addDay(),
                'end_at' => now()->addDay()->addHour(),
                'all_day' => false,
            ]);

        $this->app->instance(GoogleCalendarService::class, $mockService);

        // Prevent observer from dispatching more jobs
        CalendarEvent::$syncingFromGoogle = true;

        try {
            $job = new PullGoogleCalendarChangesJob($connection);
            $job->handle($mockService);
        } finally {
            CalendarEvent::$syncingFromGoogle = false;
        }

        $this->assertDatabaseHas('calendar_events', [
            'title' => 'Google Meeting',
            'owner_id' => $user->id,
        ]);

        $this->assertDatabaseHas('google_calendar_sync_mappings', [
            'google_event_id' => 'goog-1',
            'google_calendar_connection_id' => $connection->id,
        ]);

        $connection->refresh();
        $this->assertEquals('new-sync-token', $connection->sync_token);
    }

    public function test_pull_job_updates_existing_events(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $connection = GoogleCalendarConnection::factory()->create([
            'user_id' => $user->id,
        ]);
        $event = CalendarEvent::factory()->create([
            'owner_id' => $user->id,
            'title' => 'Old Title',
        ]);

        GoogleCalendarSyncMapping::create([
            'calendar_event_id' => $event->id,
            'google_calendar_connection_id' => $connection->id,
            'google_event_id' => 'goog-existing',
            'last_synced_at' => now()->subHour(),
        ]);

        $googleEvent = $this->createMockGoogleEvent(
            id: 'goog-existing',
            summary: 'Updated Title',
            startDateTime: '2026-03-15T10:00:00+01:00',
            endDateTime: '2026-03-15T11:00:00+01:00',
        );

        $mockService = Mockery::mock(GoogleCalendarService::class);
        $mockService->shouldReceive('listChanges')
            ->once()
            ->andReturn([
                'events' => [$googleEvent],
                'nextSyncToken' => 'token-2',
            ]);
        $mockService->shouldReceive('mapFromGoogleEvent')
            ->once()
            ->andReturn([
                'title' => 'Updated Title',
                'description' => null,
                'start_at' => now()->addDay(),
                'end_at' => now()->addDay()->addHour(),
                'all_day' => false,
            ]);

        CalendarEvent::$syncingFromGoogle = true;

        try {
            $job = new PullGoogleCalendarChangesJob($connection);
            $job->handle($mockService);
        } finally {
            CalendarEvent::$syncingFromGoogle = false;
        }

        $event->refresh();
        $this->assertEquals('Updated Title', $event->title);
    }

    public function test_pull_job_deletes_cancelled_events(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $connection = GoogleCalendarConnection::factory()->create([
            'user_id' => $user->id,
        ]);
        $event = CalendarEvent::factory()->create([
            'owner_id' => $user->id,
        ]);

        GoogleCalendarSyncMapping::create([
            'calendar_event_id' => $event->id,
            'google_calendar_connection_id' => $connection->id,
            'google_event_id' => 'goog-deleted',
            'last_synced_at' => now(),
        ]);

        $googleEvent = $this->createMockGoogleEvent(
            id: 'goog-deleted',
            summary: 'Deleted Event',
            status: 'cancelled',
        );

        $mockService = Mockery::mock(GoogleCalendarService::class);
        $mockService->shouldReceive('listChanges')
            ->once()
            ->andReturn([
                'events' => [$googleEvent],
                'nextSyncToken' => 'token-3',
            ]);

        CalendarEvent::$syncingFromGoogle = true;

        try {
            $job = new PullGoogleCalendarChangesJob($connection);
            $job->handle($mockService);
        } finally {
            CalendarEvent::$syncingFromGoogle = false;
        }

        $this->assertDatabaseMissing('calendar_events', ['id' => $event->id]);
        $this->assertDatabaseMissing('google_calendar_sync_mappings', ['google_event_id' => 'goog-deleted']);
    }

    public function test_pull_job_skips_disabled_connection(): void
    {
        $user = User::factory()->create();
        $connection = GoogleCalendarConnection::factory()->disabled()->create([
            'user_id' => $user->id,
        ]);

        $mockService = Mockery::mock(GoogleCalendarService::class);
        $mockService->shouldNotReceive('listChanges');

        $job = new PullGoogleCalendarChangesJob($connection);
        $job->handle($mockService);
    }

    // ── Helpers ─────────────────────────────────────────

    /**
     * Create a mock Google Calendar Event.
     */
    protected function createMockGoogleEvent(
        string $id,
        string $summary = 'Test Event',
        ?string $startDateTime = null,
        ?string $endDateTime = null,
        string $status = 'confirmed',
    ): \Google\Service\Calendar\Event {
        $event = Mockery::mock(\Google\Service\Calendar\Event::class);
        $event->shouldReceive('getId')->andReturn($id);
        $event->shouldReceive('getSummary')->andReturn($summary);
        $event->shouldReceive('getStatus')->andReturn($status);
        $event->shouldReceive('getDescription')->andReturn(null);

        if ($startDateTime) {
            $start = Mockery::mock(\Google\Service\Calendar\EventDateTime::class);
            $start->shouldReceive('getDateTime')->andReturn($startDateTime);
            $start->shouldReceive('getDate')->andReturn(null);
            $event->shouldReceive('getStart')->andReturn($start);
        }

        if ($endDateTime) {
            $end = Mockery::mock(\Google\Service\Calendar\EventDateTime::class);
            $end->shouldReceive('getDateTime')->andReturn($endDateTime);
            $end->shouldReceive('getDate')->andReturn(null);
            $event->shouldReceive('getEnd')->andReturn($end);
        }

        return $event;
    }
}
