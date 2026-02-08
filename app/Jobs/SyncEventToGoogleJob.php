<?php

namespace App\Jobs;

use App\Models\CalendarEvent;
use App\Models\GoogleCalendarConnection;
use App\Models\GoogleCalendarSyncMapping;
use App\Services\GoogleCalendarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncEventToGoogleJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CalendarEvent $calendarEvent,
        public GoogleCalendarConnection $googleConnection,
        public string $action,
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
            match ($this->action) {
                'create' => $this->handleCreate($googleCalendarService),
                'update' => $this->handleUpdate($googleCalendarService),
                'delete' => $this->handleDelete($googleCalendarService),
            };
        } catch (\Exception $e) {
            Log::error('Google Calendar sync failed', [
                'action' => $this->action,
                'event_id' => $this->calendarEvent->id,
                'connection_id' => $this->googleConnection->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle creating an event in Google Calendar.
     */
    protected function handleCreate(GoogleCalendarService $service): void
    {
        $googleEventId = $service->createEvent($this->googleConnection, $this->calendarEvent);

        GoogleCalendarSyncMapping::updateOrCreate(
            [
                'calendar_event_id' => $this->calendarEvent->id,
                'google_calendar_connection_id' => $this->googleConnection->id,
            ],
            [
                'google_event_id' => $googleEventId,
                'last_synced_at' => now(),
            ]
        );
    }

    /**
     * Handle updating an event in Google Calendar.
     */
    protected function handleUpdate(GoogleCalendarService $service): void
    {
        $mapping = GoogleCalendarSyncMapping::where('calendar_event_id', $this->calendarEvent->id)
            ->where('google_calendar_connection_id', $this->googleConnection->id)
            ->first();

        if (! $mapping) {
            // Event hasn't been synced yet, create it instead
            $this->handleCreate($service);

            return;
        }

        $service->updateEvent($this->googleConnection, $this->calendarEvent, $mapping->google_event_id);
        $mapping->update(['last_synced_at' => now()]);
    }

    /**
     * Handle deleting an event from Google Calendar.
     */
    protected function handleDelete(GoogleCalendarService $service): void
    {
        $mapping = GoogleCalendarSyncMapping::where('calendar_event_id', $this->calendarEvent->id)
            ->where('google_calendar_connection_id', $this->googleConnection->id)
            ->first();

        if (! $mapping) {
            return;
        }

        $service->deleteEvent($this->googleConnection, $mapping->google_event_id);
        $mapping->delete();
    }
}
