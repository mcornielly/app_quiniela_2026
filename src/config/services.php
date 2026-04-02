<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'football_api' => [
        'key' => env('API_FOOTBALL_KEY'),
        'url' => env('API_FOOTBALL_URL', 'https://v3.football.api-sports.io'),
        'timeout' => (int) env('API_FOOTBALL_TIMEOUT', 15),
        'retry_times' => (int) env('API_FOOTBALL_RETRY_TIMES', 2),
        'retry_sleep_ms' => (int) env('API_FOOTBALL_RETRY_SLEEP_MS', 250),
        'cache' => [
            'countries' => (int) env('API_FOOTBALL_CACHE_COUNTRIES', 86400),
            'venues' => (int) env('API_FOOTBALL_CACHE_VENUES', 86400),
            'teams' => (int) env('API_FOOTBALL_CACHE_TEAMS', 43200),
            'fixtures' => (int) env('API_FOOTBALL_CACHE_FIXTURES', 1800),
            'live' => (int) env('API_FOOTBALL_CACHE_LIVE', 15),
            'events' => (int) env('API_FOOTBALL_CACHE_EVENTS', 60),
            'lineups' => (int) env('API_FOOTBALL_CACHE_LINEUPS', 600),
            'statistics' => (int) env('API_FOOTBALL_CACHE_STATISTICS', 300),
            'leagues' => (int) env('API_FOOTBALL_CACHE_LEAGUES', 86400),
            'standings' => (int) env('API_FOOTBALL_CACHE_STANDINGS', 3600),
        ],
    ],

];
