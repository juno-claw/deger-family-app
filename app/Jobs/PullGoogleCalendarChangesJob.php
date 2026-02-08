<?php

namespace App\Jobs;

use App\Models\CalendarEvent;
use App\Models\GoogleCalendarConnection;
use App\Models\GoogleCalendarSyncMapping;
use App\Services\GoogleCalendarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PullGoogleCalendarChangesJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public GoogleCalendarConnection $googleConnection,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GoogleCalendarService $googleCalendarService): void
    {
        if (! $this->googleConnection->enabled) {
            return;
        }

        try {
            $result = $googleCalendarService->listChanges($this->googleConnection);

            CalendarEvent::$syncingFromGoogle = true;

            try {
                foreach ($result['events'] as $googleEvent) {
                    $this->processGoogleEvent($googleEvent, $googleCalendarService);
                }
            } finally {
                CalendarEvent::$syncingFromGoogle = false;
            }

            $this->googleConnection->update([
                'sync_token' => $result['nextSyncToken'],
                'last_synced_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Google Calendar pull failed', [
                'connection_id' => $this->googleConnection->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Process a single Google Calendar event change.
     */
    protected function processGoogleEvent(
        \Google\Service\Calendar\Event $googleEvent,
        GoogleCalendarService $service,
    ): void {
        $googleEventId = $googleEvent->getId();

        $mapping = GoogleCalendarSyncMapping::where('google_calendar_connection_id', $this->googleConnection->id)
            ->where('google_event_id', $googleEventId)
            ->first();

        // Event was cancelled (deleted) in Google
        if ($googleEvent->getStatus() === 'cancelled') {
            if ($mapping) {
                $mapping->calendarEvent?->delete();
                $mapping->delete();
            }

            return;
        }

        $eventData = $service->mapFromGoogleEvent($googleEvent);

        if ($mapping) {
            // Update existing event
            $mapping->calendarEvent?->update($eventData);
            $mapping->update(['last_synced_at' => now()]);
        } else {
            // Create new event from Google
            $calendarEvent = CalendarEvent::create([
                ...$eventData,
                'owner_id' => $this->googleConnection->user_id,
                'recurrence' => 'none',
            ]);

            GoogleCalendarSyncMapping::create([
                'calendar_event_id' => $calendarEvent->id,
                'google_calendar_connection_id' => $this->googleConnection->id,
                'google_event_id' => $googleEventId,
                'last_synced_at' => now(),
            ]);
        }
    }
}
