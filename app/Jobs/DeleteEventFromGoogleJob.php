<?php

namespace App\Jobs;

use App\Models\GoogleCalendarConnection;
use App\Services\GoogleCalendarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DeleteEventFromGoogleJob implements ShouldQueue
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
     *
     * Uses primitive values so the job works even after the CalendarEvent is deleted.
     */
    public function __construct(
        public string $googleEventId,
        public GoogleCalendarConnection $googleConnection,
        public int $syncMappingId,
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
            $googleCalendarService->deleteEvent($this->googleConnection, $this->googleEventId);

            \App\Models\GoogleCalendarSyncMapping::where('id', $this->syncMappingId)->delete();
        } catch (\Exception $e) {
            Log::error('Google Calendar delete sync failed', [
                'google_event_id' => $this->googleEventId,
                'connection_id' => $this->googleConnection->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
