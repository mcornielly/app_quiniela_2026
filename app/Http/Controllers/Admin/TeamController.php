<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Team;
use Inertia\Response;
use App\Http\Controllers\Controller;

class TeamController extends Controller
{

    public function index(): Response
    {
        $teams = Team::with('group')
            ->orderBy('group_id')
            ->get();

        return Inertia::render('Admin/Teams/Index', [
            'teams' => $teams
        ]);
    }
}
