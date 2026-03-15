<?php

namespace App\Services\Tournament;

use App\Models\Game;
use App\Models\Tournament;

class BracketProgressionService
{
    public function __construct(
        private readonly OfficialBracketResolverService $officialBracketResolverService,
    ) {
    }

    public function advance(Game $game)
    {
        if (!$game->canAdvanceBracket()) {
            return;
        }

        $this->syncTournament($game->tournament);
    }

    public function syncTournament(Tournament $tournament): void
    {
        $this->officialBracketResolverService->sync($tournament);
    }
}
