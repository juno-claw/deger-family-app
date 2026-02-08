<?php

namespace App\Observers;

use App\Jobs\DeleteEventFromGoogleJob;
use App\Jobs\SyncEventToGoogleJob;
use App\Models\CalendarEvent;
use App\Models\GoogleCalendarConnection;
use App\Models\GoogleCalendarSyncMapping;

class CalendarEventObserver
{
    /**
     * Handle the CalendarEvent "created" event.
     */
    public function created(CalendarEvent $calendarEvent): void
    {
        $this->dispatchSync($calendarEvent, 'create');
    }

    /**
     * Handle the CalendarEvent "updated" event.
     */
    public function updated(CalendarEvent $calendarEvent): void
    {
        $this->dispatchSync($calendarEvent, 'update');
    }

    /**
     * Handle the CalendarEvent "deleting" event (before DB delete).
     *
     * Uses deleting instead of deleted so we can still query sync mappings
     * and dispatch delete jobs with the Google Event ID before the model is gone.
     */
    public function deleting(CalendarEvent $calendarEvent): void
    {
        if (CalendarEvent::$syncingFromGoogle) {
            return;
        }

        $mappings = GoogleCalendarSyncMapping::where('calendar_event_id', $calendarEvent->id)
            ->with('connection')
            ->get();

        foreach ($mappings as $mapping) {
            if ($mapping->connection && $mapping->connection->enabled) {
                DeleteEventFromGoogleJob::dispatch(
                    $mapping->google_event_id,
                    $mapping->connection,
                    $mapping->id,
                );
            }
        }
    }

    /**
     * Dispatch sync jobs for all relevant Google Calendar connections.
     */
    protected function dispatchSync(CalendarEvent $calendarEvent, string $action): void
    {
        if (CalendarEvent::$syncingFromGoogle) {
            return;
        }

        $connections = $this->getRelevantConnections($calendarEvent);

        foreach ($connections as $connection) {
            SyncEventToGoogleJob::dispatch($calendarEvent, $connection, $action);
        }
    }

    /**
     * Get all enabled Google Calendar connections for users related to this event.
     *
     * @return \Illuminate\Support\Collection<int, GoogleCalendarConnection>
     */
    protected function getRelevantConnections(CalendarEvent $calendarEvent): \Illuminate\Support\Collection
    {
        $userIds = collect([$calendarEvent->owner_id]);

        if ($calendarEvent->relationLoaded('sharedWith')) {
            $userIds = $userIds->merge($calendarEvent->sharedWith->pluck('id'));
        } else {
            $userIds = $userIds->merge(
                $calendarEvent->sharedWith()->pluck('users.id')
            );
        }

        return GoogleCalendarConnection::enabled()
            ->whereIn('user_id', $userIds->unique())
            ->get();
    }
}
