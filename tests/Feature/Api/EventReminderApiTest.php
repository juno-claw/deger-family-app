<?php

namespace Tests\Feature\Api;

use App\Models\CalendarEvent;
use App\Models\EventReminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventReminderApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private CalendarEvent $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->event = CalendarEvent::create([
            'title' => 'Test Event',
            'start_at' => '2026-04-15 10:00:00',
            'end_at' => '2026-04-15 12:00:00',
            'all_day' => false,
            'owner_id' => $this->user->id,
        ]);
    }

    // ── Index ──────────────────────────────────────────

    public function test_can_list_reminders_for_event(): void
    {
        EventReminder::create([
            'calendar_event_id' => $this->event->id,
            'user_id' => $this->user->id,
            'type' => 'relative',
            'minutes_before' => 60,
            'remind_at' => $this->event->start_at->subMinutes(60),
        ]);

        $response = $this->getJson("/api/v1/calendar/events/{$this->event->id}/reminders");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'relative')
            ->assertJsonPath('data.0.minutes_before', 60);
    }

    public function test_cannot_list_reminders_for_other_users_event(): void
    {
        $otherUser = User::factory()->create();
        $otherEvent = CalendarEvent::create([
            'title' => 'Private Event',
            'start_at' => '2026-04-20 10:00:00',
            'owner_id' => $otherUser->id,
        ]);

        $response = $this->getJson("/api/v1/calendar/events/{$otherEvent->id}/reminders");

        $response->assertForbidden();
    }

    // ── Store ──────────────────────────────────────────

    public function test_can_create_relative_reminder(): void
    {
        $response = $this->postJson("/api/v1/calendar/events/{$this->event->id}/reminders", [
            'user_id' => $this->user->id,
            'type' => 'relative',
            'minutes_before' => 1440,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'relative')
            ->assertJsonPath('data.minutes_before', 1440)
            ->assertJsonPath('data.user_id', $this->user->id);

        $this->assertDatabaseHas('event_reminders', [
            'calendar_event_id' => $this->event->id,
            'type' => 'relative',
            'minutes_before' => 1440,
        ]);
    }

    public function test_can_create_absolute_reminder(): void
    {
        $response = $this->postJson("/api/v1/calendar/events/{$this->event->id}/reminders", [
            'user_id' => $this->user->id,
            'type' => 'absolute',
            'remind_at' => '2026-04-14T09:00:00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'absolute')
            ->assertJsonPath('data.minutes_before', null);

        $this->assertDatabaseHas('event_reminders', [
            'calendar_event_id' => $this->event->id,
            'type' => 'absolute',
        ]);
    }

    public function test_relative_reminder_calculates_remind_at_from_event_start(): void
    {
        $this->postJson("/api/v1/calendar/events/{$this->event->id}/reminders", [
            'user_id' => $this->user->id,
            'type' => 'relative',
            'minutes_before' => 120,
        ]);

        $reminder = EventReminder::first();
        $expected = $this->event->start_at->copy()->subMinutes(120);
        $this->assertEquals($expected->toDateTimeString(), $reminder->remind_at->toDateTimeString());
    }

    public function test_cannot_create_reminder_for_other_users_event(): void
    {
        $otherUser = User::factory()->create();
        $otherEvent = CalendarEvent::create([
            'title' => 'Private Event',
            'start_at' => '2026-04-20 10:00:00',
            'owner_id' => $otherUser->id,
        ]);

        $response = $this->postJson("/api/v1/calendar/events/{$otherEvent->id}/reminders", [
            'user_id' => $this->user->id,
            'type' => 'relative',
            'minutes_before' => 60,
        ]);

        $response->assertForbidden();
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->postJson("/api/v1/calendar/events/{$this->event->id}/reminders", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'type']);
    }

    public function test_store_validates_minutes_before_for_relative(): void
    {
        $response = $this->postJson("/api/v1/calendar/events/{$this->event->id}/reminders", [
            'user_id' => $this->user->id,
            'type' => 'relative',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['minutes_before']);
    }

    public function test_store_validates_remind_at_for_absolute(): void
    {
        $response = $this->postJson("/api/v1/calendar/events/{$this->event->id}/reminders", [
            'user_id' => $this->user->id,
            'type' => 'absolute',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['remind_at']);
    }

    // ── Update ──────────────────────────────────────────

    public function test_can_update_reminder(): void
    {
        $reminder = EventReminder::create([
            'calendar_event_id' => $this->event->id,
            'user_id' => $this->user->id,
            'type' => 'relative',
            'minutes_before' => 60,
            'remind_at' => $this->event->start_at->subMinutes(60),
        ]);

        $response = $this->putJson(
            "/api/v1/calendar/events/{$this->event->id}/reminders/{$reminder->id}",
            ['minutes_before' => 1440]
        );

        $response->assertOk()
            ->assertJsonPath('data.minutes_before', 1440);

        $reminder->refresh();
        $expected = $this->event->start_at->copy()->subMinutes(1440);
        $this->assertEquals($expected->toDateTimeString(), $reminder->remind_at->toDateTimeString());
    }

    public function test_can_change_reminder_type(): void
    {
        $reminder = EventReminder::create([
            'calendar_event_id' => $this->event->id,
            'user_id' => $this->user->id,
            'type' => 'relative',
            'minutes_before' => 60,
            'remind_at' => $this->event->start_at->subMinutes(60),
        ]);

        $response = $this->putJson(
            "/api/v1/calendar/events/{$this->event->id}/reminders/{$reminder->id}",
            [
                'type' => 'absolute',
                'remind_at' => '2026-04-14T08:00:00',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.type', 'absolute');
    }

    // ── Destroy ──────────────────────────────────────────

    public function test_can_delete_reminder(): void
    {
        $reminder = EventReminder::create([
            'calendar_event_id' => $this->event->id,
            'user_id' => $this->user->id,
            'type' => 'relative',
            'minutes_before' => 60,
            'remind_at' => $this->event->start_at->subMinutes(60),
        ]);

        $response = $this->deleteJson(
            "/api/v1/calendar/events/{$this->event->id}/reminders/{$reminder->id}"
        );

        $response->assertNoContent();
        $this->assertDatabaseMissing('event_reminders', ['id' => $reminder->id]);
    }

    // ── Reminders in CalendarEventResource ─────────────

    public function test_event_response_includes_reminders(): void
    {
        EventReminder::create([
            'calendar_event_id' => $this->event->id,
            'user_id' => $this->user->id,
            'type' => 'relative',
            'minutes_before' => 60,
            'remind_at' => $this->event->start_at->subMinutes(60),
        ]);

        $response = $this->getJson("/api/v1/calendar/events/{$this->event->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.reminders')
            ->assertJsonPath('data.reminders.0.type', 'relative');
    }
}
