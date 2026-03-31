<?php

namespace App\Observers;

use App\Events\GameStatusUpdated;
use App\Models\Game;
use App\Models\User;
use App\Notifications\UserGameStatusNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class GameObserver
{
    public function updated(Game $game): void
    {
        $statusChanged = $game->wasChanged('status');
        $scoreChanged = $game->wasChanged(['home_score', 'away_score']);
        $detailsChanged = $game->wasChanged([
            'match_number',
            'home_team_id',
            'away_team_id',
            'home_slot',
            'away_slot',
            'stage',
            'match_date',
            'match_time',
            'venue',
        ]);

        if (! $statusChanged && ! $scoreChanged && ! $detailsChanged) {
            return;
        }

        $notificationType = null;

        if (($statusChanged || $scoreChanged) && in_array($game->status, ['in_progress', 'finished'], true)) {
            $notificationType = $game->status === 'finished' ? 'result' : 'start';
        } elseif ($detailsChanged) {
            $notificationType = 'update';
        }

        if (! $notificationType) {
            return;
        }

        DB::afterCommit(function () use ($game, $notificationType) {
            try {
                $event = GameStatusUpdated::fromGame($game, $notificationType);
                broadcast($event);

                User::query()
                    ->where('is_admin', false)
                    ->select('id')
                    ->chunkById(200, function ($users) use ($event) {
                        Notification::send($users, new UserGameStatusNotification($event->payload));
                    });
            } catch (Throwable $e) {
                // Do not block score updates if websocket server is temporarily unavailable.
                Log::warning('Game broadcast failed', [
                    'game_id' => $game->id,
                    'status' => $game->status,
                    'notification_type' => $notificationType,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
}
