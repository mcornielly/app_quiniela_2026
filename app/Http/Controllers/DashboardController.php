<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $tournament = Tournament::query()
            ->where('type', 'world_cup')
            ->orderByDesc('year')
            ->first();

        return Inertia::render('Dashboard', [
            'tournament' => $tournament ? [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'year' => $tournament->year,
                'logo' => $tournament->logo,
            ] : null,
        ]);
    }
}
