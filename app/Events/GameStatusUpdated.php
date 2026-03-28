<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GameStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public array $payload)
    {
    }

    public static function fromGame(Game $game, ?string $type = null): self
    {
        $game->loadMissing(['homeTeam.country', 'awayTeam.country']);

        $homeTeam = self::transformTeam($game->homeTeam, $game->home_slot);
        $awayTeam = self::transformTeam($game->awayTeam, $game->away_slot);
        $stageLabel = self::stageLabel((string) $game->stage);

        $resolvedType = in_array($type, ['result', 'start', 'update'], true)
            ? $type
            : ($game->status === 'finished' ? 'result' : ($game->status === 'in_progress' ? 'start' : 'update'));

        $homeScore = is_numeric($game->home_score) ? (int) $game->home_score : 0;
        $awayScore = is_numeric($game->away_score) ? (int) $game->away_score : 0;

        $message = match ($resolvedType) {
            'result' => sprintf('Resultado final: %s %d - %d %s', $homeTeam['name'], $homeScore, $awayScore, $awayTeam['name']),
            'start' => sprintf('En directo: %s vs %s (%s)', $homeTeam['name'], $awayTeam['name'], $stageLabel),
            default => sprintf('Partido actualizado: %s vs %s (%s)', $homeTeam['name'], $awayTeam['name'], $stageLabel),
        };

        return new self([
            'type' => $resolvedType,
            'gameId' => $game->id,
            'tournamentId' => $game->tournament_id,
            'stage' => $game->stage,
            'stageLabel' => $stageLabel,
            'status' => $game->status,
            'homeTeam' => $homeTeam['name'],
            'awayTeam' => $awayTeam['name'],
            'homeCode' => $homeTeam['code'],
            'awayCode' => $awayTeam['code'],
            'homeFlagUrl' => $homeTeam['flag_url'],
            'awayFlagUrl' => $awayTeam['flag_url'],
            'homeScore' => $homeScore,
            'awayScore' => $awayScore,
            'matchDate' => $game->match_date?->format('d/m/Y'),
            'matchTime' => $game->match_time ? Str::substr($game->match_time, 0, 5) : null,
            'venue' => $game->venue,
            'message' => $message,
            'occurredAt' => now()->toIso8601String(),
        ]);
    }

    public function broadcastOn(): array
    {
        return [new Channel('matches.live')];
    }

    public function broadcastAs(): string
    {
        return 'game.status.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }

    private static function stageLabel(string $stage): string
    {
        return match ($stage) {
            'group' => 'Fase de grupos',
            'round_32' => 'Ronda de 32',
            'round_16' => 'Round of 16',
            'quarter' => 'Quarter final',
            'semi' => 'Semi-Final',
            'third_place' => 'Third place',
            'final' => 'Final',
            default => $stage,
        };
    }

    private static function transformTeam($team, ?string $slot): array
    {
        if (! $team) {
            return [
                'name' => $slot ?: 'Por definir',
                'code' => self::fallbackCode($slot ?: 'TBD'),
                'flag_url' => null,
            ];
        }

        $countryCode = Str::upper($team->country?->code ?? '');

        return [
            'name' => $team->name,
            'code' => $countryCode ?: self::fallbackCode($team->name),
            'flag_url' => self::resolveFlagUrl($team->country?->flag_path),
        ];
    }

    private static function resolveFlagUrl(?string $flagPath): ?string
    {
        if (! $flagPath) {
            return null;
        }

        if (Str::startsWith($flagPath, ['http://', 'https://', '/storage/'])) {
            return $flagPath;
        }

        return Storage::url($flagPath);
    }

    private static function fallbackCode(string $value): string
    {
        $clean = preg_replace('/[^A-Za-z]/', '', $value) ?: 'TBD';

        return Str::upper(Str::substr($clean, 0, 3));
    }
}
