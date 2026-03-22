<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\PoolEntry;
use App\Services\Tournament\PredictionBracketResolverService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BackfillPredictionBracketTeamsSeeder extends Seeder
{
    public function run(): void
    {
        $hasPredictedColumns = Schema::hasColumn('predictions', 'predicted_home_team_id')
            && Schema::hasColumn('predictions', 'predicted_away_team_id')
            && Schema::hasColumn('predictions', 'predicted_winner_team_id');

        if (!$hasPredictedColumns) {
            $this->command?->warn('Skipping backfill: predicted_* columns are missing on predictions table.');
            return;
        }

        /** @var PredictionBracketResolverService $resolver */
        $resolver = app(PredictionBracketResolverService::class);

        $entries = PoolEntry::query()
            ->with([
                'tournament:id,type,year',
                'predictions:id,pool_entry_id,game_id,home_score,away_score',
            ])
            ->get();

        $gamesByTournament = Game::query()
            ->select(['id', 'tournament_id', 'stage'])
            ->get()
            ->groupBy('tournament_id')
            ->map(fn (Collection $games) => $games->keyBy('id'));

        $updatedRows = 0;

        foreach ($entries as $entry) {
            if (!$entry->tournament || $entry->predictions->isEmpty()) {
                continue;
            }

            $gameMetaById = $gamesByTournament->get($entry->tournament_id, collect());

            $predictionPayloads = $entry->predictions
                ->map(fn ($prediction) => [
                    'game_id' => (int) $prediction->game_id,
                    'home_score' => $prediction->home_score === null ? null : (int) $prediction->home_score,
                    'away_score' => $prediction->away_score === null ? null : (int) $prediction->away_score,
                ])
                ->sortBy('game_id')
                ->values();

            $normalizedPredictionPayloads = $this->normalizeForBracketResolution(
                $predictionPayloads,
                $gameMetaById
            );

            try {
                $resolvedBracket = $resolver->resolve($entry->tournament, $normalizedPredictionPayloads);
            } catch (Throwable $exception) {
                report($exception);
                $this->command?->warn("Skipping pool_entry {$entry->id}: bracket resolver failed.");
                continue;
            }

            $resolvedByGameId = $this->resolvedBracketTeamsByGameId($resolvedBracket);

            DB::transaction(function () use ($entry, $resolvedByGameId, &$updatedRows) {
                foreach ($entry->predictions as $prediction) {
                    $resolved = $resolvedByGameId[(int) $prediction->game_id] ?? null;

                    if (!$resolved) {
                        continue;
                    }

                    $affected = DB::table('predictions')
                        ->where('id', $prediction->id)
                        ->update([
                            'predicted_home_team_id' => $resolved['predicted_home_team_id'],
                            'predicted_away_team_id' => $resolved['predicted_away_team_id'],
                            'predicted_winner_team_id' => $resolved['predicted_winner_team_id'],
                            'updated_at' => now(),
                        ]);

                    $updatedRows += (int) $affected;
                }
            });
        }

        $this->command?->info("Backfill completed. Updated prediction rows: {$updatedRows}");
    }

    private function normalizeForBracketResolution(Collection $predictionPayloads, Collection $gameMetaById): Collection
    {
        return $predictionPayloads->map(function (array $payload) use ($gameMetaById) {
            $game = $gameMetaById->get((int) $payload['game_id']);
            $stage = (string) ($game?->stage ?? '');
            $isKnockout = $stage !== '' && $stage !== 'group';
            $homeScore = $payload['home_score'];
            $awayScore = $payload['away_score'];

            if ($isKnockout) {
                if ($homeScore === null || $awayScore === null) {
                    $homeScore = 1;
                    $awayScore = 0;
                } elseif ($homeScore === $awayScore) {
                    $awayScore = $awayScore + 1;
                }
            }

            return [
                'game_id' => (int) $payload['game_id'],
                'home_score' => $homeScore,
                'away_score' => $awayScore,
            ];
        })->values();
    }

    private function resolvedBracketTeamsByGameId(array $resolvedBracket): array
    {
        $gamesByMatchNumber = $resolvedBracket['gamesByMatchNumber'] ?? [];
        $resolvedByGameId = [];

        foreach ($gamesByMatchNumber as $resolvedGame) {
            $gameId = (int) ($resolvedGame['id'] ?? 0);

            if ($gameId <= 0) {
                continue;
            }

            $resolvedByGameId[$gameId] = [
                'predicted_home_team_id' => $resolvedGame['home_team']?->id ?? null,
                'predicted_away_team_id' => $resolvedGame['away_team']?->id ?? null,
                'predicted_winner_team_id' => $resolvedGame['winner_team']?->id ?? null,
            ];
        }

        return $resolvedByGameId;
    }
}
