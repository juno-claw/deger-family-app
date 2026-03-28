<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\EventReminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EventReminderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        config([
            "services.telegram.users.{$this->user->id}" => [
                'bot_token' => 'test-token',
                'chat_id' => '123456',
            ],
        ]);
    }

    // ── Command: app:send-event-reminders ──────────────

    public function test_command_dispatches_jobs_for_due_reminders(): void
    {
        Queue::fake();

        $event = CalendarEvent::create([
            'title' => 'Past Reminder Event',
            'start_at' => now()->addHour(),
            'owner_id' => $this->user->id,
        ]);

        EventReminder::create([
            'calendar_event_id' => $event->id,
            'user_id' => $this->user->id,
            'type' => 'relative',
            'minutes_before' => 120,
            'remind_at' => now()->subMinute(),
        ]);

        $this->artisan('app:send-event-reminders')
            ->assertExitCode(0);

        Queue::assertPushed(\App\Jobs\SendEventReminderJob::class);
    }

    public function test_command_ignores_already_sent_reminders(): void
    {
        Queue::fake();

        $event = CalendarEvent::create([
            'title' => 'Already Sent',
            'start_at' => now()->addHour(),
            'owner_id' => $this->user->id,
        ]);

        EventReminder::create([
            'calendar_event_id' => $event->id,
            'user_id' => $this->user->id,
            'type' => 'relative',
            'minutes_before' => 120,
            'remind_at' => now()->subMinute(),
            'sent_at' => now(),
        ]);

        $this->artisan('app:send-event-reminders')
            ->assertExitCode(0);

        Queue::assertNotPushed(\App\Jobs\SendEventReminderJob::class);
    }

    public function test_command_ignores_future_reminders(): void
    {
        Queue::fake();

        $event = CalendarEvent::create([
            'title' => 'Future Reminder',
            'start_at' => now()->addDays(2),
            'owner_id' => $this->user->id,
        ]);

        EventReminder::create([
            'calendar_event_id' => $event->id,
            'user_id' => $this->user->id,
            'type' => 'relative',
            'minutes_before' => 60,
            'remind_at' => now()->addDay(),
        ]);

        $this->artisan('app:send-event-reminders')
            ->assertExitCode(0);

        Queue::assertNotPushed(\App\Jobs\SendEventReminderJob::class);
    }

    // ── Job: SendEventReminderJob ──────────────────────

    public function test_job_sends_notification_and_marks_as_sent(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $event = CalendarEvent::create([
            'title' => 'Zahnarzt',
            'start_at' => now()->addHour(),
            'all_day' => false,
            'owner_id' => $this->user->id,
        ]);

        $reminder = EventReminder::create([
            'calendar_event_id' => $event->id,
            'user_id' => $this->user->id,
            'type' => 'relative',
            'minutes_before' => 60,
            'remind_at' => now()->subMinute(),
        ]);

        $job = new \App\Jobs\SendEventReminderJob($reminder);
        $job->handle(app(\App\Services\NotificationService::class));

        $reminder->refresh();
        $this->assertNotNull($reminder->sent_at);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'event_reminder',
        ]);
    }

    public function test_job_skips_already_sent_reminder(): void
    {
        $event = CalendarEvent::create([
            'title' => 'Already Sent',
            'start_at' => now()->addHour(),
            'owner_id' => $this->user->id,
        ]);

        $reminder = EventReminder::create([
            'calendar_event_id' => $event->id,
            'user_id' => $this->user->id,
            'type' => 'relative',
            'minutes_before' => 60,
            'remind_at' => now()->subMinute(),
            'sent_at' => now(),
        ]);

        $job = new \App\Jobs\SendEventReminderJob($reminder);
        $job->handle(app(\App\Services\NotificationService::class));

        $this->assertDatabaseCount('notifications', 0);
    }

    // ── Recalculation on Event Update ──────────────────

    public function test_relative_reminders_recalculate_when_event_start_changes(): void
    {
        $event = CalendarEvent::create([
            'title' => 'Recalc Test',
            'start_at' => '2026-05-01 10:00:00',
            'owner_id' => $this->user->id,
        ]);

        $reminder = EventReminder::create([
            'calendar_event_id' => $event->id,
            'user_id' => $this->user->id,
            'type' => 'relative',
            'minutes_before' => 60,
            'remind_at' => '2026-05-01 09:00:00',
        ]);

        $event->update(['start_at' => '2026-05-02 14:00:00']);

        $reminder->refresh();
        $this->assertEquals('2026-05-02 13:00:00', $reminder->remind_at->toDateTimeString());
    }

    public function test_absolute_reminders_not_affected_by_event_update(): void
    {
        $event = CalendarEvent::create([
            'title' => 'Absolute Test',
            'start_at' => '2026-05-01 10:00:00',
            'owner_id' => $this->user->id,
        ]);

        $reminder = EventReminder::create([
            'calendar_event_id' => $event->id,
            'user_id' => $this->user->id,
            'type' => 'absolute',
            'remind_at' => '2026-04-30 08:00:00',
        ]);

        $event->update(['start_at' => '2026-05-02 14:00:00']);

        $reminder->refresh();
        $this->assertEquals('2026-04-30 08:00:00', $reminder->remind_at->toDateTimeString());
    }

    public function test_sent_reminders_not_recalculated(): void
    {
        $event = CalendarEvent::create([
            'title' => 'Sent Test',
            'start_at' => '2026-05-01 10:00:00',
            'owner_id' => $this->user->id,
        ]);

        $reminder = EventReminder::create([
            'calendar_event_id' => $event->id,
            'user_id' => $this->user->id,
            'type' => 'relative',
            'minutes_before' => 60,
            'remind_at' => '2026-05-01 09:00:00',
            'sent_at' => now(),
        ]);

        $event->update(['start_at' => '2026-05-02 14:00:00']);

        $reminder->refresh();
        $this->assertEquals('2026-05-01 09:00:00', $reminder->remind_at->toDateTimeString());
    }

    // ── Cascade Delete ─────────────────────────────────

    public function test_reminders_deleted_when_event_is_deleted(): void
    {
        $event = CalendarEvent::create([
            'title' => 'Delete Test',
            'start_at' => now()->addDay(),
            'owner_id' => $this->user->id,
        ]);

        EventReminder::create([
            'calendar_event_id' => $event->id,
            'user_id' => $this->user->id,
            'type' => 'relative',
            'minutes_before' => 60,
            'remind_at' => now()->addDay()->subMinutes(60),
        ]);

        $this->assertDatabaseCount('event_reminders', 1);

        $event->delete();

        $this->assertDatabaseCount('event_reminders', 0);
    }
}
