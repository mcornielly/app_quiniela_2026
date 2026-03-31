<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserGameStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly array $payload,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->payload['type'] ?? 'update',
            'gameId' => $this->payload['gameId'] ?? null,
            'tournamentId' => $this->payload['tournamentId'] ?? null,
            'stage' => $this->payload['stage'] ?? null,
            'stageLabel' => $this->payload['stageLabel'] ?? 'Mundial 2026',
            'status' => $this->payload['status'] ?? null,
            'homeTeam' => $this->payload['homeTeam'] ?? 'Local',
            'awayTeam' => $this->payload['awayTeam'] ?? 'Visitante',
            'homeCode' => $this->payload['homeCode'] ?? null,
            'awayCode' => $this->payload['awayCode'] ?? null,
            'homeFlagUrl' => $this->payload['homeFlagUrl'] ?? null,
            'awayFlagUrl' => $this->payload['awayFlagUrl'] ?? null,
            'homeScore' => $this->payload['homeScore'] ?? 0,
            'awayScore' => $this->payload['awayScore'] ?? 0,
            'matchDate' => $this->payload['matchDate'] ?? null,
            'matchTime' => $this->payload['matchTime'] ?? null,
            'venue' => $this->payload['venue'] ?? null,
            'message' => $this->payload['message'] ?? null,
            'occurredAt' => $this->payload['occurredAt'] ?? now()->toIso8601String(),
        ];
    }
}
