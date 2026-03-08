<?php

namespace App\Services\Tournament;

use App\Models\Game;

class BracketProgressionService
{
    protected $resolver;

    public function __construct(BracketResolverService $resolver)
    {
        $this->resolver = $resolver;
    }

    public function advance(Game $game)
    {
        if (!$game->canAdvanceBracket()) {
            return;
        }

        $matchNumber = $game->match_number;

        $winnerSlot = "W{$matchNumber}";
        $loserSlot  = "RU{$matchNumber}";

        /*
        |---------------------------------------------------------
        | Find next games referencing this match
        |---------------------------------------------------------
        */

        $nextGames = Game::where(function ($q) use ($winnerSlot, $loserSlot) {

            $q->where('home_slot', $winnerSlot)
                ->orWhere('away_slot', $winnerSlot)
                ->orWhere('home_slot', $loserSlot)
                ->orWhere('away_slot', $loserSlot);

        })->get();

        foreach ($nextGames as $nextGame) {

            $this->updateGameSlot($nextGame, $winnerSlot);
            $this->updateGameSlot($nextGame, $loserSlot);
        }
    }

    private function updateGameSlot(Game $game, string $slot)
    {
        $teamId = $this->resolver->resolveSlot($slot);

        if (!$teamId) {
            return;
        }

        if ($game->home_slot === $slot) {
            $game->home_team_id = $teamId;
        }

        if ($game->away_slot === $slot) {
            $game->away_team_id = $teamId;
        }

        if ($game->isDirty()) {
            $game->save();
        }
    }
}
