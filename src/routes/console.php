<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('football:sync:tournament-stadiums', [
    '--tournament-year' => (int) env('AUTO_SYNC_TOURNAMENT_YEAR', 2026),
])->dailyAt('03:15')
    ->withoutOverlapping()
    ->onOneServer()
    ->when(fn () => env('AUTO_SYNC_TOURNAMENT_STADIUMS', false) && env('API_FOOTBALL_KEY'));

// Global sync (everything) - Daily
Schedule::command('football:sync:all')
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->when(fn () => env('API_FOOTBALL_KEY'));

// Live Sync - Every minute
Schedule::command('football:sync:live')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer()
    ->when(fn () => env('API_FOOTBALL_KEY') && env('AUTO_SYNC_LIVE', true));
