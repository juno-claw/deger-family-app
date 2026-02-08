<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Calendar Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for Google Calendar API integration.
    | Service Accounts are used for AI agents, OAuth2 for human users.
    |
    */

    'calendar' => [
        'service_account_credentials' => env('GOOGLE_SERVICE_ACCOUNT_CREDENTIALS', storage_path('app/google/service-account.json')),

        'oauth' => [
            'client_id' => env('GOOGLE_OAUTH_CLIENT_ID'),
            'client_secret' => env('GOOGLE_OAUTH_CLIENT_SECRET'),
            'redirect_uri' => env('GOOGLE_OAUTH_REDIRECT_URI', '/settings/google-calendar/callback'),
        ],

        'scopes' => [
            \Google\Service\Calendar::CALENDAR,
        ],
    ],

];
