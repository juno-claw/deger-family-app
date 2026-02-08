<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\GoogleCalendarConnection;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendarService_SDK;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    /**
     * Get an authenticated Google Client for the given connection.
     */
    public function getClient(GoogleCalendarConnection $connection): GoogleClient
    {
        $client = new GoogleClient;
        $client->setApplicationName(config('app.name'));
        $client->setScopes(config('google.calendar.scopes'));

        if ($connection->isServiceAccount()) {
            $credentialsPath = config('google.calendar.service_account_credentials');
            $client->setAuthConfig($credentialsPath);
        } else {
            $client->setClientId(config('google.calendar.oauth.client_id'));
            $client->setClientSecret(config('google.calendar.oauth.client_secret'));
            $client->setAccessType('offline');

            $client->setAccessToken([
                'access_token' => $connection->access_token,
                'refresh_token' => $connection->refresh_token,
                'expires_in' => $connection->token_expires_at?->diffInSeconds(now()),
            ]);

            if ($connection->isTokenExpired()) {
                $this->refreshToken($connection, $client);
            }
        }

        return $client;
    }

    /**
     * Refresh the OAuth2 access token.
     */
    public function refreshToken(GoogleCalendarConnection $connection, ?GoogleClient $client = null): void
    {
        $client = $client ?? $this->getClient($connection);

        $newToken = $client->fetchAccessTokenWithRefreshToken($connection->refresh_token);

        if (isset($newToken['error'])) {
            Log::error('Google Calendar token refresh failed', [
                'connection_id' => $connection->id,
                'error' => $newToken['error'],
            ]);

            return;
        }

        $connection->update([
            'access_token' => $newToken['access_token'],
            'refresh_token' => $newToken['refresh_token'] ?? $connection->refresh_token,
            'token_expires_at' => now()->addSeconds($newToken['expires_in'] ?? 3600),
        ]);
    }

    /**
     * Create an event in Google Calendar.
     */
    public function createEvent(GoogleCalendarConnection $connection, CalendarEvent $calendarEvent): string
    {
        $client = $this->getClient($connection);
        $service = new GoogleCalendarService_SDK($client);

        $googleEvent = $this->mapToGoogleEvent($calendarEvent);

        $createdEvent = $service->events->insert($connection->calendar_id, $googleEvent);

        return $createdEvent->getId();
    }

    /**
     * Update an event in Google Calendar.
     */
    public function updateEvent(GoogleCalendarConnection $connection, CalendarEvent $calendarEvent, string $googleEventId): void
    {
        $client = $this->getClient($connection);
        $service = new GoogleCalendarService_SDK($client);

        $googleEvent = $this->mapToGoogleEvent($calendarEvent);

        $service->events->update($connection->calendar_id, $googleEventId, $googleEvent);
    }

    /**
     * Delete an event from Google Calendar.
     */
    public function deleteEvent(GoogleCalendarConnection $connection, string $googleEventId): void
    {
        $client = $this->getClient($connection);
        $service = new GoogleCalendarService_SDK($client);

        try {
            $service->events->delete($connection->calendar_id, $googleEventId);
        } catch (\Google\Service\Exception $e) {
            if ($e->getCode() !== 410) {
                throw $e;
            }
            // 410 Gone - event already deleted, ignore
        }
    }

    /**
     * List changes from Google Calendar since the last sync.
     *
     * @return array{events: array<GoogleEvent>, nextSyncToken: string|null}
     */
    public function listChanges(GoogleCalendarConnection $connection): array
    {
        $client = $this->getClient($connection);
        $service = new GoogleCalendarService_SDK($client);

        $params = [
            'singleEvents' => true,
            'showDeleted' => true,
        ];

        if ($connection->sync_token) {
            $params['syncToken'] = $connection->sync_token;
        } else {
            // Initial sync: only get events from the last 30 days forward
            $params['timeMin'] = now()->subDays(30)->toRfc3339String();
        }

        $events = [];
        $pageToken = null;

        do {
            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            try {
                $results = $service->events->listEvents($connection->calendar_id, $params);
            } catch (\Google\Service\Exception $e) {
                if ($e->getCode() === 410) {
                    // Sync token expired, do a full sync
                    $connection->update(['sync_token' => null]);

                    return $this->listChanges($connection);
                }

                throw $e;
            }

            foreach ($results->getItems() as $event) {
                $events[] = $event;
            }

            $pageToken = $results->getNextPageToken();
        } while ($pageToken);

        return [
            'events' => $events,
            'nextSyncToken' => $results->getNextSyncToken(),
        ];
    }

    /**
     * Convert a CalendarEvent to a Google Calendar Event.
     */
    public function mapToGoogleEvent(CalendarEvent $calendarEvent): GoogleEvent
    {
        $googleEvent = new GoogleEvent;
        $googleEvent->setSummary($calendarEvent->title);
        $googleEvent->setDescription($calendarEvent->description);

        if ($calendarEvent->all_day) {
            $start = new EventDateTime;
            $start->setDate($calendarEvent->start_at->toDateString());
            $googleEvent->setStart($start);

            $end = new EventDateTime;
            $end->setDate(
                $calendarEvent->end_at
                    ? $calendarEvent->end_at->addDay()->toDateString()
                    : $calendarEvent->start_at->addDay()->toDateString()
            );
            $googleEvent->setEnd($end);
        } else {
            $start = new EventDateTime;
            $start->setDateTime($calendarEvent->start_at->toRfc3339String());
            $googleEvent->setStart($start);

            $end = new EventDateTime;
            $end->setDateTime(
                $calendarEvent->end_at
                    ? $calendarEvent->end_at->toRfc3339String()
                    : $calendarEvent->start_at->addHour()->toRfc3339String()
            );
            $googleEvent->setEnd($end);
        }

        return $googleEvent;
    }

    /**
     * Convert a Google Calendar Event to app data.
     *
     * @return array{title: string, description: string|null, start_at: Carbon, end_at: Carbon|null, all_day: bool}
     */
    public function mapFromGoogleEvent(GoogleEvent $googleEvent): array
    {
        $isAllDay = $googleEvent->getStart()->getDate() !== null;

        if ($isAllDay) {
            $startAt = Carbon::parse($googleEvent->getStart()->getDate())->startOfDay();
            $endAt = $googleEvent->getEnd()->getDate()
                ? Carbon::parse($googleEvent->getEnd()->getDate())->subDay()->endOfDay()
                : null;
        } else {
            $startAt = Carbon::parse($googleEvent->getStart()->getDateTime());
            $endAt = $googleEvent->getEnd()->getDateTime()
                ? Carbon::parse($googleEvent->getEnd()->getDateTime())
                : null;
        }

        return [
            'title' => $googleEvent->getSummary() ?? '(Kein Titel)',
            'description' => $googleEvent->getDescription(),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'all_day' => $isAllDay,
        ];
    }

    /**
     * Build a Google Client configured for OAuth2 authorization flow.
     */
    public function getOAuthClient(): GoogleClient
    {
        $client = new GoogleClient;
        $client->setApplicationName(config('app.name'));
        $client->setClientId(config('google.calendar.oauth.client_id'));
        $client->setClientSecret(config('google.calendar.oauth.client_secret'));
        $client->setRedirectUri(url(config('google.calendar.oauth.redirect_uri')));
        $client->setScopes(config('google.calendar.scopes'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }
}
