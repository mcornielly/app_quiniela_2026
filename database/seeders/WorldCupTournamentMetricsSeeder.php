<?php

namespace Database\Seeders;

use App\Models\Tournament;
use App\Models\TournamentTeam;
use Illuminate\Database\Seeder;

class WorldCupTournamentMetricsSeeder extends Seeder
{
    public function run(): void
    {
        $tournament = Tournament::query()
            ->with('tournamentTeams.team')
            ->where('year', 2026)
            ->where('type', 'world_cup')
            ->first();

        if (!$tournament) {
            return;
        }

        $rankings = require config_path('generated/world_cup_2026_fifa_rankings.php');
        $aliases = $this->rankingAliases();

        foreach ($tournament->tournamentTeams as $entry) {
            $teamName = $entry->team?->name;

            if (!$teamName) {
                continue;
            }

            $rank = $rankings[$teamName]
                ?? ($aliases[$teamName] ?? null);

            $entry->update([
                'fifa_ranking' => $rank,
                'fair_play_points' => $entry->fair_play_points ?? 0,
            ]);
        }
    }

    private function rankingAliases(): array
    {
        $rankings = require config_path('generated/world_cup_2026_fifa_rankings.php');

        return [
            'DEN/MKD/CZE/IRL' => $rankings['Play-Off D'] ?? null,
            'ITA/NIR/WAL/BIH' => $rankings['Play-Off A'] ?? null,
            'UKR/SWE/POL/ALB' => $rankings['Play-Off B'] ?? null,
            'TUR/ROU/SVK/KOS' => $rankings['Play-Off C'] ?? null,
            'BOL/SUR/IRQ' => $rankings['Play-Off 2'] ?? null,
            'NCL/JAM/COD' => $rankings['Play-Off 1'] ?? null,
        ];
    }
}
