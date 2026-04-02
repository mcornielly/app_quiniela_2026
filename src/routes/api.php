<?php

use App\Http\Controllers\FootballController;
use Illuminate\Support\Facades\Route;

Route::prefix('football')->group(function () {
    Route::get('countries', [FootballController::class, 'countries']);
    Route::get('venues', [FootballController::class, 'venues']);
    Route::get('teams', [FootballController::class, 'teams']);
    Route::get('fixtures', [FootballController::class, 'fixtures']);
    Route::get('fixtures/live', [FootballController::class, 'live']);
    Route::get('fixtures/{id}', [FootballController::class, 'fixtureDetail'])->whereNumber('id');
    Route::get('leagues/{league}/seasons/{season}/standings', [FootballController::class, 'standings'])
        ->whereNumber('league')
        ->whereNumber('season');
});
