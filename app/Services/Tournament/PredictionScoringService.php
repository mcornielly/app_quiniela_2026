<?php

namespace App\Services\Tournament;

use App\Models\Game;
use App\Models\Prediction;
use Illuminate\Support\Facades\DB;

class PredictionScoringService
{
    public function scoreGame(Game $game)
    {
        if (!$game->hasResult()) {
            return;
        }

        // $predictions = $game->predictions()->with('poolEntry')->get();
        $predictions = $game->predictions()->get();

        foreach ($predictions as $prediction) {

            $points = $this->calculatePoints($game, $prediction);

            // actualizar puntos de la predicción
            $prediction->update([
                'points' => $points
            ]);

            /*
            |--------------------------------------------------------------------------
            | Recalculate pool entry total points
            |--------------------------------------------------------------------------
            */

            // $entry = $prediction->poolEntry;

            // if ($entry) {
            //     $entry->update([
            //         'total_points' => $entry->predictions()->sum('points')
            //     ]);
            // }
            // recalcular leaderboard una sola vez
            $this->recalculatePoolEntries($game->tournament_id);
        }
    }

    private function recalculatePoolEntries($tournamentId)
    {
        DB::statement("
            UPDATE pool_entries e
            SET total_points = (
                SELECT COALESCE(SUM(p.points),0)
                FROM predictions p
                WHERE p.pool_entry_id = e.id
            )
            WHERE e.tournament_id = ?
        ", [$tournamentId]);
    }

    private function calculatePoints(Game $game, Prediction $prediction)
    {
        /*
        |--------------------------------------------------------------------------
        | Exact score → 5 points
        |--------------------------------------------------------------------------
        */

        if (
            $prediction->home_score == $game->home_score &&
            $prediction->away_score == $game->away_score
        ) {
            return 5;
        }

        /*
        |--------------------------------------------------------------------------
        | Determine predicted result type
        |--------------------------------------------------------------------------
        */

        $predictedResult = $this->getResultType(
            $prediction->home_score,
            $prediction->away_score
        );

        $realResult = $game->result_type;

        /*
        |--------------------------------------------------------------------------
        | Correct result → 3 points
        |--------------------------------------------------------------------------
        */

        if ($predictedResult === $realResult) {
            return 3;
        }

        return 0;
    }

    private function getResultType($homeScore, $awayScore)
    {
        if ($homeScore > $awayScore) {
            return 'home';
        }

        if ($awayScore > $homeScore) {
            return 'away';
        }

        return 'draw';
    }
}
