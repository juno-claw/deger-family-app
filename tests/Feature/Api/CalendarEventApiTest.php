<?php

namespace Tests\Feature\Api;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CalendarEventApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    // ── Index ──────────────────────────────────────────

    public function test_can_list_events_for_current_month(): void
    {
        CalendarEvent::create([
            'title' => 'This Month',
            'start_at' => now()->startOfMonth()->addDays(5),
            'end_at' => now()->startOfMonth()->addDays(5)->addHours(2),
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/calendar/events');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'This Month');
    }

    public function test_can_filter_events_by_date_range(): void
    {
        CalendarEvent::create([
            'title' => 'In Range',
            'start_at' => '2026-03-15 10:00:00',
            'end_at' => '2026-03-15 12:00:00',
            'owner_id' => $this->user->id,
        ]);
        CalendarEvent::create([
            'title' => 'Out Of Range',
            'start_at' => '2026-05-01 10:00:00',
            'end_at' => '2026-05-01 12:00:00',
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/calendar/events?date_from=2026-03-01&date_to=2026-03-31');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'In Range');
    }

    public function test_can_filter_events_by_month_and_year(): void
    {
        CalendarEvent::create([
            'title' => 'June Event',
            'start_at' => '2026-06-10 10:00:00',
            'end_at' => '2026-06-10 12:00:00',
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/calendar/events?month=6&year=2026');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'June Event');
    }

    public function test_includes_shared_events(): void
    {
        $owner = User::factory()->create();
        $event = CalendarEvent::create([
            'title' => 'Shared Event',
            'start_at' => now()->startOfMonth()->addDays(3),
            'end_at' => now()->startOfMonth()->addDays(3)->addHours(1),
            'owner_id' => $owner->id,
        ]);
        $event->sharedWith()->attach($this->user->id, ['status' => 'accepted']);

        $response = $this->getJson('/api/v1/calendar/events');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Shared Event');
    }

    // ── Store ──────────────────────────────────────────

    public function test_can_create_event(): void
    {
        $response = $this->postJson('/api/v1/calendar/events', [
            'title' => 'Meeting',
            'start_at' => '2026-02-20 14:00:00',
            'end_at' => '2026-02-20 15:00:00',
            'all_day' => false,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Meeting')
            ->assertJsonPath('data.owner.id', $this->user->id);

        $this->assertDatabaseHas('calendar_events', [
            'title' => 'Meeting',
            'owner_id' => $this->user->id,
        ]);
    }

    public function test_cannot_create_event_without_title(): void
    {
        $response = $this->postJson('/api/v1/calendar/events', [
            'start_at' => '2026-02-20 14:00:00',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('title');
    }

    // ── Show ──────────────────────────────────────────

    public function test_can_show_own_event(): void
    {
        $event = CalendarEvent::create([
            'title' => 'My Event',
            'start_at' => '2026-02-20 14:00:00',
            'end_at' => '2026-02-20 15:00:00',
            'owner_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/calendar/events/{$event->id}");

        $response->assertOk()
            ->assertJsonPath('data.title', 'My Event')
            ->assertJsonStructure([
                'data' => ['id', 'title', 'description', 'start_at', 'end_at', 'all_day', 'owner', 'shared_with'],
            ]);
    }

    public function test_cannot_show_other_users_private_event(): void
    {
        $other = User::factory()->create();
        $event = CalendarEvent::create([
            'title' => 'Private',
            'start_at' => '2026-02-20 14:00:00',
            'owner_id' => $other->id,
        ]);

        $response = $this->getJson("/api/v1/calendar/events/{$event->id}");

        $response->assertForbidden();
    }

    // ── Update ─────────────────────────────────────────

    public function test_can_update_own_event(): void
    {
        $event = CalendarEvent::create([
            'title' => 'Old Title',
            'start_at' => '2026-02-20 14:00:00',
            'owner_id' => $this->user->id,
        ]);

        $response = $this->putJson("/api/v1/calendar/events/{$event->id}", [
            'title' => 'New Title',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'New Title');
    }

    // ── Destroy ────────────────────────────────────────

    public function test_can_delete_own_event(): void
    {
        $event = CalendarEvent::create([
            'title' => 'Delete Me',
            'start_at' => '2026-02-20 14:00:00',
            'owner_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/v1/calendar/events/{$event->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('calendar_events', ['id' => $event->id]);
    }

    public function test_cannot_delete_other_users_event(): void
    {
        $other = User::factory()->create();
        $event = CalendarEvent::create([
            'title' => 'Not Mine',
            'start_at' => '2026-02-20 14:00:00',
            'owner_id' => $other->id,
        ]);

        $response = $this->deleteJson("/api/v1/calendar/events/{$event->id}");

        $response->assertForbidden();
    }
}
