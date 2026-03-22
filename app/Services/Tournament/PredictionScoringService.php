<?php

namespace App\Services\Tournament;

use App\Models\Game;
use App\Models\Prediction;
use App\Models\Rule;
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

        $rule = Rule::query()
            ->where('active', true)
            ->where('tournament_id', $game->tournament_id)
            ->first();

        $exactScorePoints = (int) ($rule?->exact_score_points ?? 5);
        $correctResultPoints = (int) ($rule?->correct_result_points ?? 3);

        foreach ($predictions as $prediction) {

            $points = $this->calculatePoints($game, $prediction, $exactScorePoints, $correctResultPoints);

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
                    INNER JOIN games g ON g.id = p.game_id
                    WHERE p.pool_entry_id = pool_entries.id
                    AND g.home_score IS NOT NULL
                    AND g.away_score IS NOT NULL
                    AND p.home_score = g.home_score
                    AND p.away_score = g.away_score
                ),

                correct_results = (
                    SELECT COUNT(*)
                    FROM predictions p
                    INNER JOIN games g ON g.id = p.game_id
                    WHERE p.pool_entry_id = pool_entries.id
                    AND g.home_score IS NOT NULL
                    AND g.away_score IS NOT NULL
                    AND (
                        (p.home_score > p.away_score AND g.home_score > g.away_score)
                        OR (p.home_score < p.away_score AND g.home_score < g.away_score)
                        OR (p.home_score = p.away_score AND g.home_score = g.away_score)
                    )
                    AND NOT (p.home_score = g.home_score AND p.away_score = g.away_score)
                )

            WHERE id IN ($placeholders)
        ", $entryIds);
    }

    private function calculatePoints(Game $game, Prediction $prediction, int $exactScorePoints, int $correctResultPoints)
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
            return $exactScorePoints;
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
            return $correctResultPoints;
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
