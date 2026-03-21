<?php

namespace App\Http\Controllers;

use App\Models\PoolEntry;
use App\Models\Tournament;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function participants(): Response
    {
        $tournament = Tournament::query()
            ->where('type', 'world_cup')
            ->orderByDesc('year')
            ->first();

        if (! $tournament) {
            return Inertia::render('Leaderboard/Participants', [
                'tournament' => null,
                'participants' => [],
            ]);
        }

        $participants = PoolEntry::query()
            ->selectRaw('
                pool_entries.id as pool_entry_id,
                pool_entries.user_id as user_id,
                pool_entries.name as pool_name,
                1 as pools_count,
                COALESCE(pool_entries.total_points, 0) as total_points,
                COALESCE(pool_entries.exact_hits, 0) as exact_hits,
                COALESCE(pool_entries.correct_results, 0) as correct_results,
                pool_entries.updated_at as updated_at
            ')
            ->where('pool_entries.tournament_id', $tournament->id)
            ->orderByDesc('total_points')
            ->orderByDesc('exact_hits')
            ->orderByDesc('correct_results')
            ->get()
            ->values()
            ->map(function ($row, int $index) {
                return [
                    'poolEntryId' => (int) $row->pool_entry_id,
                    'rank' => $index + 1,
                    'userId' => (int) $row->user_id,
                    'name' => $row->pool_name,
                    'email' => null,
                    'poolsCount' => (int) $row->pools_count,
                    'totalPoints' => (int) $row->total_points,
                    'exactHits' => (int) $row->exact_hits,
                    'correctResults' => (int) $row->correct_results,
                    'updatedAt' => $row->updated_at ? Carbon::parse($row->updated_at)->format('d/m/Y H:i') : null,
                ];
            })
            ->all();

        return Inertia::render('Leaderboard/Participants', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'year' => $tournament->year,
            ],
            'participants' => $participants,
        ]);
    }
}
