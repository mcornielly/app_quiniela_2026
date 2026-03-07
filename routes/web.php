<?php

// use App\Http\Controllers\DashboardController;
// use App\Http\Controllers\PoolEntryController;
// use App\Http\Controllers\RankingController;
// use App\Http\Controllers\RulesController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\GameResultController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\MatchController;
use App\Http\Controllers\Admin\PoolEntriesController;
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
});


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

    /*
    |--------------------------------------------------------------------------
    | Pages (Inertia)
    |--------------------------------------------------------------------------
    */
    Route::delete('teams/bulk-delete', [TeamController::class, 'bulkDelete'])
        ->name('teams.bulkDelete');

    Route::resource('teams', TeamController::class)->only(['index','store','update','destroy']);

    Route::delete('countries/bulk-delete', [CountryController::class, 'bulkDelete'])
        ->name('countries.bulkDelete');

    Route::resource('countries', CountryController::class)->only(['index','store','update','destroy']);

    Route::delete('groups/bulk-delete', [GroupController::class, 'bulkDelete'])
        ->name('groups.bulkDelete');

    Route::resource('groups', GroupController::class)->only(['index','store','update','destroy']);


    Route::get('/calendar', fn () => Inertia::render('Admin/Calendar/Index'))
        ->name('calendar.index');

    Route::get('/pools', fn () => Inertia::render('Admin/Pools/Index'))
        ->name('pools.index');

    Route::get('/games', fn () => Inertia::render('Admin/Games/Index'))
        ->name('games.index');

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

// Route::middleware(['auth', 'verified'])->group(function () {
//     Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
//     Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
//     Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
//     Route::get('/rules', [RulesController::class, 'index'])->name('rules.index');
//     Route::get('/rankings', [RankingController::class, 'index'])->name('rankings.index');
//     Route::get('/my-pools', [PoolEntryController::class, 'index'])->name('pools.index');
//     Route::get('/pools/create', [PoolEntryController::class, 'create'])->name('pools.create');
// });

require __DIR__.'/auth.php';
