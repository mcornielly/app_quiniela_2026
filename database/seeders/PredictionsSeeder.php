<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Prediction;
use App\Models\PoolEntry;
use App\Models\Game;

class PredictionsSeeder extends Seeder
{
    public function run(): void
    {
        $games = Game::all();
        $entries = PoolEntry::all();

        foreach ($entries as $entry) {

            foreach ($games as $game) {

                Prediction::updateOrCreate(
                    [
                        'pool_entry_id' => $entry->id,
                        'game_id' => $game->id,
                    ],
                    [
                        'home_score' => rand(0,4),
                        'away_score' => rand(0,4),
                        'points' => 0,
                    ]
                );

            }

        }
    }
}
