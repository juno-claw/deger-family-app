<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\GoogleCalendarConnection;
use App\Services\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GoogleCalendarController extends Controller
{
    /**
     * Show the Google Calendar settings page.
     */
    public function show(Request $request): Response
    {
        $connection = $request->user()->googleCalendarConnection;

        return Inertia::render('settings/google-calendar', [
            'connection' => $connection ? [
                'connected' => true,
                'connection_type' => $connection->connection_type,
                'calendar_id' => $connection->calendar_id,
                'enabled' => $connection->enabled,
                'last_synced_at' => $connection->last_synced_at?->toIso8601String(),
            ] : [
                'connected' => false,
            ],
            'oauthConfigured' => ! empty(config('google.calendar.oauth.client_id')),
        ]);
    }

    /**
     * Redirect the user to Google's OAuth2 consent screen.
     */
    public function redirect(GoogleCalendarService $googleCalendarService): RedirectResponse
    {
        $client = $googleCalendarService->getOAuthClient();
        $authUrl = $client->createAuthUrl();

        return redirect()->away($authUrl);
    }

    /**
     * Handle the OAuth2 callback from Google.
     */
    public function callback(Request $request, GoogleCalendarService $googleCalendarService): RedirectResponse
    {
        if ($request->has('error')) {
            return redirect()->route('google-calendar.show')
                ->with('error', 'Google Calendar Verbindung abgelehnt.');
        }

        $client = $googleCalendarService->getOAuthClient();
        $token = $client->fetchAccessTokenWithAuthCode($request->query('code'));

        if (isset($token['error'])) {
            return redirect()->route('google-calendar.show')
                ->with('error', 'Fehler bei der Google Calendar Verbindung: '.$token['error']);
        }

        // Get the primary calendar ID
        $service = new \Google\Service\Calendar($client);
        $calendarId = 'primary';

        try {
            $calendar = $service->calendars->get('primary');
            $calendarId = $calendar->getId();
        } catch (\Exception $e) {
            // Fall back to 'primary'
        }

        GoogleCalendarConnection::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'connection_type' => 'oauth2',
                'calendar_id' => $calendarId,
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds($token['expires_in'] ?? 3600),
                'enabled' => true,
            ]
        );

        return redirect()->route('google-calendar.show')
            ->with('status', 'Google Calendar erfolgreich verbunden!');
    }

    /**
     * Disconnect the Google Calendar connection.
     */
    public function disconnect(Request $request): RedirectResponse
    {
        $connection = $request->user()->googleCalendarConnection;

        if ($connection) {
            $connection->syncMappings()->delete();
            $connection->delete();
        }

        return redirect()->route('google-calendar.show')
            ->with('status', 'Google Calendar Verbindung getrennt.');
    }
}
