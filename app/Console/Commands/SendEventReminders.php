<?php

namespace App\Console\Commands;

use App\Jobs\SendEventReminderJob;
use App\Models\EventReminder;
use Illuminate\Console\Command;

class SendEventReminders extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:send-event-reminders';

    /**
     * @var string
     */
    protected $description = 'Dispatch jobs for all due event reminders that have not been sent yet';

    public function handle(): int
    {
        $reminders = EventReminder::query()
            ->where('remind_at', '<=', now())
            ->whereNull('sent_at')
            ->with(['calendarEvent', 'user'])
            ->get();

        if ($reminders->isEmpty()) {
            $this->info('No pending reminders.');

            return self::SUCCESS;
        }

        $this->info("Dispatching {$reminders->count()} reminder(s)...");

        foreach ($reminders as $reminder) {
            SendEventReminderJob::dispatch($reminder);
            $this->line("  Dispatched reminder #{$reminder->id} for event \"{$reminder->calendarEvent->title}\"");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
