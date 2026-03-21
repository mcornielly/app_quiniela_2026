<?php

namespace App\Observers;

use App\Events\GameStatusUpdated;
use App\Models\Game;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GameObserver
{
    public function updated(Game $game): void
    {
        $statusChanged = $game->wasChanged('status');
        $scoreChanged = $game->wasChanged(['home_score', 'away_score']);

        if (! $statusChanged && ! $scoreChanged) {
            return;
        }

        if (! in_array($game->status, ['in_progress', 'finished'], true)) {
            return;
        }

        DB::afterCommit(function () use ($game) {
            try {
                broadcast(GameStatusUpdated::fromGame($game));
            } catch (Throwable $e) {
                // Do not block score updates if websocket server is temporarily unavailable.
                Log::warning('Game broadcast failed', [
                    'game_id' => $game->id,
                    'status' => $game->status,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
}
