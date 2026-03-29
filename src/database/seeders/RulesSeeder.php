<?php

namespace Database\Seeders;

use App\Models\Rule;
use App\Models\Tournament;
use Illuminate\Database\Seeder;

class RulesSeeder extends Seeder
{
    public function run(): void
    {
        $tournament = Tournament::query()
            ->where('year', 2026)
            ->orderByDesc('id')
            ->first();

        if (!$tournament) {
            return;
        }

        Rule::query()->updateOrCreate(
            [
                'tournament_id' => $tournament->id,
            ],
            [
                'name' => 'Regla base quiniela Mundial 2026',
                'tournament_starts_at' => '2026-06-11 00:00:00',
                'participation_closes_at' => '2026-06-10 23:59:59',
                'exact_score_points' => 5,
                'correct_result_points' => 3,
                'unpaid_after_window_action' => 'locked',
                'active' => true,
            ]
        );
    }
}

