<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Football API Leagues Tracking
    |--------------------------------------------------------------------------
    |
    | Here you can define the leagues and tournaments you want to track
    | and synchronize with API-Football.
    |
    */

    'tournaments' => [
        'world_cup_2026' => [
            'name' => 'FIFA World Cup 2026',
            'api_league_id' => (int) env('API_FOOTBALL_WORLD_CUP_LEAGUE', 1),
            'api_season' => (int) env('API_FOOTBALL_WORLD_CUP_SEASON', 2026),
            'local_tournament_id' => 1, // ID in tournaments table
            'sync_fixtures' => true,
            'sync_standings' => true,
            'sync_players' => false, // Future expansion
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'timezone' => 'UTC',
    ],

];
