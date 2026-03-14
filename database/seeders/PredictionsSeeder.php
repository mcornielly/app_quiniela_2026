<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\PoolEntry;
use App\Models\Prediction;
use Illuminate\Database\Seeder;

class PredictionsSeeder extends Seeder
{
    public function run(): void
    {
        $gamesByTournament = Game::query()
            ->get()
            ->groupBy('tournament_id');

        $entries = PoolEntry::query()->get();

        foreach ($entries as $entry) {
            $games = $gamesByTournament->get($entry->tournament_id, collect());

            foreach ($games as $game) {
                Prediction::query()->updateOrCreate(
                    [
                        'pool_entry_id' => $entry->id,
                        'game_id' => $game->id,
                    ],
                    [
                        'home_score' => fake()->numberBetween(0, 4),
                        'away_score' => fake()->numberBetween(0, 4),
                        'points' => 0,
                    ]
                );
            }

            $entry->update([
                'completion_percent' => $games->isNotEmpty() ? 100 : 0,
            ]);
        }
    }
}
