<?php

namespace App\Notifications;

use App\Models\PoolEntry;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminPoolActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly PoolEntry $poolEntry,
        private readonly User $actor,
        private readonly string $action,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $poolName = $this->poolEntry->name ?: "Quiniela #{$this->poolEntry->id}";
        $actionLabel = match ($this->action) {
            'created' => 'creo',
            'inactivated' => 'inactivo',
            'reactivated' => 'reactivo',
            default => 'actualizo',
        };

        return [
            'action' => $this->action,
            'userId' => $this->actor->id,
            'userName' => $this->actor->name,
            'userEmail' => $this->actor->email,
            'poolEntryId' => $this->poolEntry->id,
            'poolEntryName' => $poolName,
            'messageSuffix' => sprintf('%s la quiniela "%s".', $actionLabel, $poolName),
            'occurredAt' => now()->toIso8601String(),
        ];
    }
}

