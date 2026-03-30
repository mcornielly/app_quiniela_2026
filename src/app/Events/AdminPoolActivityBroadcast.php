<?php

namespace App\Events;

use App\Models\PoolEntry;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminPoolActivityBroadcast implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public array $payload)
    {
    }

    public static function fromPoolEntryAction(PoolEntry $poolEntry, User $actor, string $action): self
    {
        $poolName = $poolEntry->name ?: ("Quiniela #{$poolEntry->id}");
        $poolReference = sprintf('#%05d', (int) $poolEntry->id);
        $actionLabel = match ($action) {
            'created' => 'creo',
            'inactivated' => 'inactivo',
            'reactivated' => 'reactivo',
            default => 'actualizo',
        };
        $messageSuffix = sprintf('%s la quiniela "%s".', $actionLabel, $poolName);

        return new self([
            'type' => 'pool_activity',
            'action' => $action,
            'userId' => $actor->id,
            'userName' => $actor->name,
            'userEmail' => $actor->email,
            'poolEntryId' => $poolEntry->id,
            'poolEntryReference' => $poolReference,
            'registrationNumber' => $poolReference,
            'poolEntryName' => $poolName,
            'message' => sprintf('%s %s', $actor->name, $messageSuffix),
            'messageSuffix' => $messageSuffix,
            'occurredAt' => now()->toIso8601String(),
        ]);
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.activity')];
    }

    public function broadcastAs(): string
    {
        return 'admin.pool.activity';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
