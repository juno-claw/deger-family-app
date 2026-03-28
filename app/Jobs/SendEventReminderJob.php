<?php

namespace App\Jobs;

use App\Models\EventReminder;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendEventReminderJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public EventReminder $reminder) {}

    public function handle(NotificationService $notificationService): void
    {
        if ($this->reminder->sent_at !== null) {
            return;
        }

        $this->reminder->load(['calendarEvent', 'user']);

        $event = $this->reminder->calendarEvent;
        if (! $event) {
            Log::warning('Event reminder references missing event', ['reminder_id' => $this->reminder->id]);

            return;
        }

        $timeStr = $event->all_day
            ? $event->start_at->format('d.m.Y')
            : $event->start_at->format('d.m.Y H:i');

        $notificationService->notify(
            user: $this->reminder->user,
            fromUser: null,
            type: 'event_reminder',
            title: 'Erinnerung: '.$event->title,
            message: "Dein Termin \"{$event->title}\" ist am {$timeStr}.",
            data: ['event_id' => $event->id],
        );

        $this->reminder->update(['sent_at' => now()]);
    }
}
