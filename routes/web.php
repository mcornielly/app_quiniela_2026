<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PoolEntryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\RulesController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\TeamController;
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
    ->group(function () {

    // ✅ Dashboard admin
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])
        ->name('admin.dashboard');

        // // ✅ Groups (Grupos) - Anidado bajo tournaments
        // Route::resource('tournaments.groups', GroupController::class)
        //     ->names('tournaments.groups')
        //     ->parameters(['groups' => 'group']);

        // // ✅ Matches (Encuentros) - Anidado bajo tournaments
        // Route::resource('tournaments.matches', MatchController::class)
        //     ->names('tournaments.matches')
        //     ->parameters(['matches' => 'match']);

        // // ✅ Calendar (Calendario) - Recurso adicional (solo index/show)
        // Route::resource('tournaments.calendar', CalendarController::class)
        //     ->names('tournaments.calendar')
        //     ->parameters(['calendar' => 'calendar'])
        //     ->only(['index', 'show']);

        // ✅ Teams
        Route::get('teams', fn () => Inertia::render('Admin/Teams/Index'))
            ->name('teams.index');

        // ✅ Matches
        Route::get('/flags', fn () => Inertia::render('Admin/Flags/Index'))
            ->name('flags.index');

        // ✅ Groups
        Route::get('groups', fn () => Inertia::render('Admin/Groups/Index'))
            ->name('groups.index');

        // ✅ Calendar
        Route::get('calendar', fn () => Inertia::render('Admin/Calendar/Index'))
            ->name('calendar.index');

        // ✅ Pools
        Route::get('pools', fn () => Inertia::render('Admin/Pools/Index'))
            ->name('pools.index');

        // ✅ Matches
        Route::get('/matches', fn () => Inertia::render('Admin/Matches/Index'))
            ->name('matches.index');

        // ✅ Matches
        Route::get('/predictions', fn () => Inertia::render('Admin/predictions/Index'))
            ->name('predictions.index');

        // ✅ Import schedule
        // Route::get('/import/schedule', fn () => Inertia::render('Admin/Import/Schedule'))
        //     ->name('import.schedule');

        // Admin users management
        Route::resource('users', UserController::class)->names('users');

        Route::get('template', fn () => Inertia::render('Admin/GridTemplate'))->name('template');
});


Route::middleware(['auth', 'verified'])->group(function () {
    // Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    // Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    // Route::get('/rules', [RulesController::class, 'index'])->name('rules.index');
    // Route::get('/rankings', [RankingController::class, 'index'])->name('rankings.index');
    // Route::get('/my-pools', [PoolEntryController::class, 'index'])->name('pools.index');
    // Route::get('/pools/create', [PoolEntryController::class, 'create'])->name('pools.create');
});

require __DIR__.'/auth.php';
