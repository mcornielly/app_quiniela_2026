<?php

use App\Http\Controllers\DashboardController;
// use App\Http\Controllers\PoolEntryController;
// use App\Http\Controllers\RankingController;
// use App\Http\Controllers\RulesController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\TournamentController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\GameController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\GameResultController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\MatchController;
use App\Http\Controllers\Admin\PoolEntriesController;
use App\Http\Controllers\Admin\PoolEntryController as AdminPoolEntryController;
use App\Http\Controllers\Admin\TournamentParticipantController;
use App\Http\Controllers\PoolEntryController;
use App\Http\Controllers\QuinielaWorldCupController;
use App\Http\Controllers\MatchesController;
use App\Http\Controllers\LeaderboardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Pages (Inertia)
    |--------------------------------------------------------------------------
    */
    Route::delete('tournaments/bulk-delete', [TournamentController::class, 'bulkDelete'])
        ->name('tournaments.bulkDelete');

    Route::resource('tournaments', TournamentController::class)->only(['index','store','update','destroy']);
    Route::get('tournaments/{tournament}/participants', [TournamentParticipantController::class, 'index'])
        ->name('tournaments.participants.index');
    Route::patch('tournaments/{tournament}/participants/{participant}', [TournamentParticipantController::class, 'update'])
        ->name('tournaments.participants.update');

    Route::delete('teams/bulk-delete', [TeamController::class, 'bulkDelete'])
        ->name('teams.bulkDelete');

    Route::resource('teams', TeamController::class)->only(['index','store','update','destroy']);

    Route::delete('countries/bulk-delete', [CountryController::class, 'bulkDelete'])
        ->name('countries.bulkDelete');

    Route::resource('countries', CountryController::class)->only(['index','store','update','destroy']);

    Route::delete('groups/bulk-delete', [GroupController::class, 'bulkDelete'])
        ->name('groups.bulkDelete');

    Route::resource('groups', GroupController::class)->only(['index','store','update','destroy']);

    Route::delete('games/bulk-delete', [GameController::class, 'bulkDelete'])
        ->name('games.bulkDelete');

            Route::post('/games/{game}/score', [GameResultController::class, 'updateScore'])
                        ->name('games.result.update');

    Route::resource('games', GameController::class)->only(['index','store','update','destroy']);

    Route::get('/calendar', [GameController::class, 'calendar'])->name('calendar.index');

    Route::resource('/pools', AdminPoolEntryController::class)->only(['index']);


    Route::get('/predictions', fn () => Inertia::render('Admin/Predictions/Index'))
        ->name('predictions.index');

    Route::get('/template', fn () => Inertia::render('Admin/GridTemplate'))
        ->name('template');

    /*
    |--------------------------------------------------------------------------
    | Game Management (API)
    |--------------------------------------------------------------------------
    */

    Route::get('/games/list', [GameResultController::class, 'index'])
        ->name('games.list');

    Route::get('/games/{id}', [GameResultController::class, 'show'])
        ->name('games.show');

    Route::post('/games/{id}/score', [GameResultController::class, 'updateScore'])
        ->name('games.score');

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    Route::resource('users', UserController::class)
        ->names('users');

});

Route::get('/quiniela-2026', function () {
    return Inertia::render('Quiniela/TournamentPredictionView');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::patch('/dashboard/favorite-team', [DashboardController::class, 'updateFavoriteTeam'])->name('dashboard.favorite-team.update');
    Route::get('/quiniela/matches', [MatchesController::class, 'calendar'])->name('matches.index');
    Route::get('/quiniela/calendar', [MatchesController::class, 'calendar'])->name('calendar.index');
    Route::get('/quiniela/results', [MatchesController::class, 'results'])->name('results.index');
    Route::get('/quiniela/live', [MatchesController::class, 'live'])->name('live.index');
    Route::get('/quiniela/groups', fn() => Inertia::render('Groups'))->name('groups.index');
    Route::get('/quiniela/leaderboard', [LeaderboardController::class, 'participants'])->name('leaderboard');
    Route::get('/quiniela/predictions', fn() => Inertia::render('Predictions/Index'))->name('predictions.index');
    Route::get('/quiniela/world-cup-template', QuinielaWorldCupController::class)->name('predictions.worldcup');
    Route::get('/pools', [PoolEntryController::class, 'index'])->name('pools.index');
    Route::post('/pools', [PoolEntryController::class, 'store'])->name('pools.store');
    Route::delete('/pools/{poolEntry}', [PoolEntryController::class, 'destroy'])->whereNumber('poolEntry')->name('pools.destroy');
    Route::post('/pools/{poolEntry}/restore', [PoolEntryController::class, 'restore'])->whereNumber('poolEntry')->name('pools.restore');
    Route::get('/pools/create', [PoolEntryController::class, 'create'])->name('pools.create');
    Route::get('/pools/{poolEntry}', [PoolEntryController::class, 'show'])->whereNumber('poolEntry')->name('pools.show');

    // Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    // Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    // Route::get('/rules', [RulesController::class, 'index'])->name('rules.index');
    // Route::get('/rankings', [RankingController::class, 'index'])->name('rankings.index');
    // Route::get('/my-pools', [PoolEntryController::class, 'index'])->name('pools.index');
});
require __DIR__.'/auth.php';
