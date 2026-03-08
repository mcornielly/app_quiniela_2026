<?php

namespace App\Services\Tournament;

use App\Models\Game;
use App\Models\Prediction;

class PredictionScoringService
{
    public function scoreGame(Game $game)
    {
        if (!$game->hasResult()) {
            return;
        }

        $predictions = $game->predictions;

        foreach ($predictions as $prediction) {

            $points = $this->calculatePoints($game, $prediction);

            $prediction->points = $points;
            $prediction->save();
        }
    }

    private function calculatePoints(Game $game, Prediction $prediction)
    {
        /*
        |---------------------------------------------------------
        | Exact score (5 points)
        |---------------------------------------------------------
        */

        if (
            $prediction->home_score == $game->home_score &&
            $prediction->away_score == $game->away_score
        ) {
            return 5;
        }

        /*
        |---------------------------------------------------------
        | Determine predicted result type
        |---------------------------------------------------------
        */

        $predictedResult = $this->getResultType(
            $prediction->home_score,
            $prediction->away_score
        );

        $realResult = $this->getResultType(
            $game->home_score,
            $game->away_score
        );

        /*
        |---------------------------------------------------------
        | Correct result (winner or draw) -> 3 points
        |---------------------------------------------------------
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
