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

        $predictions = $game->predictions()->get();

        if ($predictions->isEmpty()) {
            return;
        }

        $affectedEntries = [];

        foreach ($predictions as $prediction) {

            $points = $this->calculatePoints($game, $prediction);

            // actualizar puntos de la predicción
            $prediction->update([
                'points' => $points
            ]);

            $affectedEntries[] = $prediction->pool_entry_id;
        }

        $this->recalculatePoolEntries($affectedEntries);
    }

    private function recalculatePoolEntries(array $entryIds)
    {
        $entryIds = array_values(array_unique(array_filter($entryIds)));

        if (empty($entryIds)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($entryIds), '?'));

        DB::statement("
            UPDATE pool_entries
            SET
                total_points = (
                    SELECT COALESCE(SUM(p.points),0)
                    FROM predictions p
                    WHERE p.pool_entry_id = pool_entries.id
                ),

                exact_hits = (
                    SELECT COUNT(*)
                    FROM predictions p
                    WHERE p.pool_entry_id = pool_entries.id
                    AND p.points = 5
                ),

                correct_results = (
                    SELECT COUNT(*)
                    FROM predictions p
                    WHERE p.pool_entry_id = pool_entries.id
                    AND p.points = 3
                )

            WHERE id IN ($placeholders)
        ", $entryIds);
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
