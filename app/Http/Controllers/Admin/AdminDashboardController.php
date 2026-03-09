<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Group;
use App\Models\GroupStanding;
use App\Models\Tournament;
use App\Services\Tournament\PoolRankingService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(PoolRankingService $rankingService): Response
    {
        $today = Carbon::today();

        // Resultados del día
        $todayResults = Game::with(['homeTeam','awayTeam'])
            ->whereDate('match_date', $today)
            ->where('status','finished')
            ->get();

        // Próximos juegos
        $upcoming = Game::with(['homeTeam','awayTeam'])
            ->where('status','scheduled')
            ->orderBy('match_date')
            ->limit(6)
            ->get();

        // Grupos
        $groups = Group::with('teams')
            ->orderBy('name')
            ->get();

        // Tabla grupo A por defecto
        $standings = GroupStanding::with('team')
            ->where('group_id', $groups->first()->id ?? null)
            ->orderBy('position')
            ->get();

        // Ranking quiniela
        $ranking = $rankingService->getRanking(1, 15);

        $tournament = Tournament::first();

        return Inertia::render('Admin/Dashboard', [
            'todayResults' => $todayResults,
            'upcomingGames' => $upcoming,
            'groups' => $groups,
            'standings' => $standings,
            'ranking' => $ranking,
            'tournament' => $tournament,
        ]);
    }
}
