<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\PoolEntry;
use App\Models\Prediction;
use App\Services\Tournament\PredictionBracketResolverService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PredictionsSeeder extends Seeder
{
    public function run(): void
    {
        /** @var PredictionBracketResolverService $resolver */
        $resolver = app(PredictionBracketResolverService::class);
        $hasPredictedColumns = Schema::hasColumn('predictions', 'predicted_home_team_id')
            && Schema::hasColumn('predictions', 'predicted_away_team_id')
            && Schema::hasColumn('predictions', 'predicted_winner_team_id');

        $gamesByTournament = Game::query()
            ->orderBy('match_number')
            ->get()
            ->groupBy('tournament_id');

        $entries = PoolEntry::query()
            ->with('tournament:id,type,year')
            ->get();

        foreach ($entries as $entry) {
            $games = $gamesByTournament->get($entry->tournament_id, collect())->values();

            if ($games->isEmpty() || !$entry->tournament) {
                $entry->update([
                    'completion_percent' => 0,
                ]);
                continue;
            }

            $predictionPayloads = $games->map(function (Game $game) {
                $homeScore = fake()->numberBetween(0, 4);
                $awayScore = fake()->numberBetween(0, 4);

                if ($game->stage !== 'group' && $homeScore === $awayScore) {
                    $awayScore = ($awayScore + 1) % 5;
                }

                return [
                    'game_id' => $game->id,
                    'home_score' => $homeScore,
                    'away_score' => $awayScore,
                ];
            })->values();

            $resolvedTeamsByGameId = [];
            if ($hasPredictedColumns) {
                try {
                    $resolvedBracket = $resolver->resolve($entry->tournament, $predictionPayloads);
                    $resolvedTeamsByGameId = $this->resolvedBracketTeamsByGameId($resolvedBracket);
                } catch (Throwable $exception) {
                    report($exception);
                    $resolvedTeamsByGameId = [];
                }
            }

            foreach ($predictionPayloads as $predictionPayload) {
                $resolvedTeams = $resolvedTeamsByGameId[$predictionPayload['game_id']] ?? [];
                $attributes = [
                    'home_score' => $predictionPayload['home_score'],
                    'away_score' => $predictionPayload['away_score'],
                    'points' => 0,
                ];

                if ($hasPredictedColumns) {
                    $attributes['predicted_home_team_id'] = $resolvedTeams['predicted_home_team_id'] ?? null;
                    $attributes['predicted_away_team_id'] = $resolvedTeams['predicted_away_team_id'] ?? null;
                    $attributes['predicted_winner_team_id'] = $resolvedTeams['predicted_winner_team_id'] ?? null;
                }

                Prediction::query()->updateOrCreate(
                    [
                        'pool_entry_id' => $entry->id,
                        'game_id' => $predictionPayload['game_id'],
                    ],
                    $attributes
                );
            }

            $entry->update([
                'completion_percent' => $games->isNotEmpty() ? 100 : 0,
            ]);
        }
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
