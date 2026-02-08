<?php

namespace Tests\Feature;

use App\Jobs\DeleteEventFromGoogleJob;
use App\Jobs\SyncEventToGoogleJob;
use App\Models\CalendarEvent;
use App\Models\GoogleCalendarConnection;
use App\Models\GoogleCalendarSyncMapping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GoogleCalendarObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_creating_event_dispatches_sync_job_for_owner_with_connection(): void
    {
        $user = User::factory()->create();
        $connection = GoogleCalendarConnection::factory()->create([
            'user_id' => $user->id,
            'enabled' => true,
        ]);

        CalendarEvent::create([
            'title' => 'Test Event',
            'start_at' => now()->addDay(),
            'owner_id' => $user->id,
        ]);

        Queue::assertPushed(SyncEventToGoogleJob::class, function ($job) use ($connection) {
            return $job->action === 'create'
                && $job->googleConnection->id === $connection->id;
        });
    }

    public function test_creating_event_does_not_dispatch_when_no_connection(): void
    {
        $user = User::factory()->create();

        CalendarEvent::create([
            'title' => 'Test Event',
            'start_at' => now()->addDay(),
            'owner_id' => $user->id,
        ]);

        Queue::assertNotPushed(SyncEventToGoogleJob::class);
    }

    public function test_creating_event_does_not_dispatch_for_disabled_connection(): void
    {
        $user = User::factory()->create();
        GoogleCalendarConnection::factory()->disabled()->create([
            'user_id' => $user->id,
        ]);

        CalendarEvent::create([
            'title' => 'Test Event',
            'start_at' => now()->addDay(),
            'owner_id' => $user->id,
        ]);

        Queue::assertNotPushed(SyncEventToGoogleJob::class);
    }

    public function test_updating_event_dispatches_sync_job(): void
    {
        $user = User::factory()->create();
        GoogleCalendarConnection::factory()->create([
            'user_id' => $user->id,
            'enabled' => true,
        ]);

        $event = CalendarEvent::create([
            'title' => 'Test Event',
            'start_at' => now()->addDay(),
            'owner_id' => $user->id,
        ]);

        Queue::fake(); // Reset after create dispatch

        $event->update(['title' => 'Updated Title']);

        Queue::assertPushed(SyncEventToGoogleJob::class, function ($job) {
            return $job->action === 'update';
        });
    }

    public function test_deleting_event_dispatches_delete_job(): void
    {
        $user = User::factory()->create();
        $connection = GoogleCalendarConnection::factory()->create([
            'user_id' => $user->id,
            'enabled' => true,
        ]);

        $event = CalendarEvent::create([
            'title' => 'Test Event',
            'start_at' => now()->addDay(),
            'owner_id' => $user->id,
        ]);

        // Create a sync mapping as if the event was previously synced
        GoogleCalendarSyncMapping::create([
            'calendar_event_id' => $event->id,
            'google_calendar_connection_id' => $connection->id,
            'google_event_id' => 'google-event-123',
            'last_synced_at' => now(),
        ]);

        Queue::fake(); // Reset after create dispatch

        $event->delete();

        Queue::assertPushed(DeleteEventFromGoogleJob::class, function ($job) {
            return $job->googleEventId === 'google-event-123';
        });
    }

    public function test_observer_skips_dispatch_when_syncing_from_google(): void
    {
        $user = User::factory()->create();
        GoogleCalendarConnection::factory()->create([
            'user_id' => $user->id,
            'enabled' => true,
        ]);

        CalendarEvent::$syncingFromGoogle = true;

        try {
            CalendarEvent::create([
                'title' => 'From Google',
                'start_at' => now()->addDay(),
                'owner_id' => $user->id,
            ]);

            Queue::assertNotPushed(SyncEventToGoogleJob::class);
        } finally {
            CalendarEvent::$syncingFromGoogle = false;
        }
    }

    public function test_dispatches_sync_for_shared_users_with_connections(): void
    {
        $owner = User::factory()->create();
        $sharedUser = User::factory()->create();

        GoogleCalendarConnection::factory()->create([
            'user_id' => $owner->id,
            'enabled' => true,
        ]);
        GoogleCalendarConnection::factory()->create([
            'user_id' => $sharedUser->id,
            'enabled' => true,
        ]);

        $event = CalendarEvent::create([
            'title' => 'Shared Event',
            'start_at' => now()->addDay(),
            'owner_id' => $owner->id,
        ]);

        $event->sharedWith()->attach($sharedUser->id, ['status' => 'accepted']);

        Queue::fake(); // Reset to count only update dispatches

        $event->update(['title' => 'Updated Shared']);

        Queue::assertPushed(SyncEventToGoogleJob::class, 2);
    }
}
