<?php

namespace App\Services\Tournament;

use App\Models\Game;
use App\Models\Tournament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class PredictionBracketResolverService
{
    public function __construct(
        private readonly StandingsTableService $standingsTableService,
        private readonly BestThirdPlaceRankingService $bestThirdPlaceRankingService,
        private readonly ThirdPlaceAssignmentService $thirdPlaceAssignmentService,
    ) {
    }

    public function resolve(Tournament $tournament, Collection $predictionPayloads): array
    {
        $normalizedPredictionPayloads = $predictionPayloads
            ->map(fn (array $prediction) => [
                'game_id' => (int) $prediction['game_id'],
                'home_score' => (int) $prediction['home_score'],
                'away_score' => (int) $prediction['away_score'],
            ])
            ->sortBy('game_id')
            ->values();

        $cacheKey = sprintf(
            'prediction-bracket:%s:%s',
            $tournament->id,
            sha1(json_encode($normalizedPredictionPayloads->all(), JSON_THROW_ON_ERROR))
        );

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($tournament, $normalizedPredictionPayloads) {
            return $this->resolveBracket($tournament, $normalizedPredictionPayloads);
        });
    }

    private function resolveBracket(Tournament $tournament, Collection $predictionPayloads): array
    {
        $games = $tournament->games()
            ->with([
                'homeTeam.group',
                'awayTeam.group',
                'winnerTeam.group',
            ])
            ->orderBy('match_number')
            ->get();

        $groups = $tournament->groups()
            ->with([
                'teams.group',
                'teams.tournamentEntries' => fn ($query) => $query->where('tournament_id', $tournament->id),
            ])
            ->orderBy('name')
            ->get();

        $predictionsByGameId = $predictionPayloads
            ->mapWithKeys(fn (array $prediction) => [
                (int) $prediction['game_id'] => [
                    'home_score' => (int) $prediction['home_score'],
                    'away_score' => (int) $prediction['away_score'],
                ],
            ]);

        $groupStandings = $groups->mapWithKeys(function ($group) use ($games, $predictionsByGameId) {
            $groupGames = $games
                ->where('stage', 'group')
                ->filter(fn (Game $game) => $game->homeTeam?->group?->name === $group->name)
                ->map(fn (Game $game) => $this->applyPredictionToGameClone($game, $predictionsByGameId->get($game->id)))
                ->values();

            return [
                $group->name => $this->standingsTableService->calculate($group->teams, $groupGames),
            ];
        });

        $qualifiedThirdRows = $this->bestThirdPlaceRankingService
            ->rank($groupStandings)
            ->take(8)
            ->values();

        $thirdPlaceAssignments = $this->thirdPlaceAssignmentService->assign(
            $qualifiedThirdRows,
            $games->where('stage', 'round_32')->values(),
            $this->bracketKey($tournament)
        );

        $resolvedGamesByMatchNumber = [];

        foreach ($games as $game) {
            $prediction = $predictionsByGameId->get($game->id);

            $resolvedGame = [
                'id' => $game->id,
                'match_number' => $game->match_number,
                'stage' => $game->stage,
                'home_team' => $game->stage === 'group'
                    ? $game->homeTeam
                    : $this->resolveSlotTeam($game, 'home', $groupStandings, $thirdPlaceAssignments, $resolvedGamesByMatchNumber),
                'away_team' => $game->stage === 'group'
                    ? $game->awayTeam
                    : $this->resolveSlotTeam($game, 'away', $groupStandings, $thirdPlaceAssignments, $resolvedGamesByMatchNumber),
                'home_score' => $prediction['home_score'] ?? null,
                'away_score' => $prediction['away_score'] ?? null,
            ];

            if ($game->stage !== 'group' && (!$resolvedGame['home_team'] || !$resolvedGame['away_team'])) {
                throw new RuntimeException("No se pudo resolver el partido {$game->match_number} del bracket de la quiniela.");
            }

            $resolvedGame['winner_team'] = $this->resolveWinnerTeam($resolvedGame);
            $resolvedGame['runner_up_team'] = $this->resolveRunnerUpTeam($resolvedGame);

            $resolvedGamesByMatchNumber[(string) $game->match_number] = $resolvedGame;
        }

        $finalGame = $games->firstWhere('stage', 'final');
        $resolvedFinal = $finalGame ? ($resolvedGamesByMatchNumber[(string) $finalGame->match_number] ?? null) : null;

        return [
            'groupStandings' => $groupStandings,
            'qualifiedThirdRows' => $qualifiedThirdRows,
            'thirdPlaceAssignments' => $thirdPlaceAssignments,
            'gamesByMatchNumber' => $resolvedGamesByMatchNumber,
            'predictedChampion' => $resolvedFinal['winner_team'] ?? null,
        ];
    }

    private function bracketKey(Tournament $tournament): string
    {
        return sprintf('%s_%s', $tournament->type, $tournament->year);
    }

    private function applyPredictionToGameClone(Game $game, ?array $prediction): Game
    {
        $clone = clone $game;
        $clone->home_score = $prediction['home_score'] ?? null;
        $clone->away_score = $prediction['away_score'] ?? null;

        return $clone;
    }

    private function resolveSlotTeam(
        Game $game,
        string $side,
        Collection $groupStandings,
        array $thirdPlaceAssignments,
        array $resolvedGamesByMatchNumber
    ) {
        $slot = $side === 'home' ? $game->home_slot : $game->away_slot;

        if (!$slot) {
            return $side === 'home' ? $game->homeTeam : $game->awayTeam;
        }

        if (preg_match('/^([1-3])([A-Z])$/', $slot, $matches)) {
            $position = (int) $matches[1] - 1;
            $groupLetter = $matches[2];

            return $groupStandings->get($groupLetter)[$position]['team'] ?? null;
        }

        if (preg_match('/^3\-([A-Z]+)$/', $slot)) {
            $slotKey = $this->thirdPlaceAssignmentService->slotKey($game, $side);
            return $thirdPlaceAssignments[$slotKey]['team'] ?? null;
        }

        if (preg_match('/^W(\d+)$/', $slot, $matches)) {
            return $resolvedGamesByMatchNumber[$matches[1]]['winner_team'] ?? null;
        }

        if (preg_match('/^RU(\d+)$/', $slot, $matches)) {
            return $resolvedGamesByMatchNumber[$matches[1]]['runner_up_team'] ?? null;
        }

        return $side === 'home' ? $game->homeTeam : $game->awayTeam;
    }

    private function resolveWinnerTeam(array $resolvedGame)
    {
        if ($resolvedGame['home_score'] === null || $resolvedGame['away_score'] === null) {
            return null;
        }

        if ($resolvedGame['home_score'] === $resolvedGame['away_score']) {
            return null;
        }

        return $resolvedGame['home_score'] > $resolvedGame['away_score']
            ? $resolvedGame['home_team']
            : $resolvedGame['away_team'];
    }

    private function resolveRunnerUpTeam(array $resolvedGame)
    {
        $winnerTeam = $this->resolveWinnerTeam($resolvedGame);

        if (!$winnerTeam) {
            return null;
        }

        return $winnerTeam->id === $resolvedGame['home_team']?->id
            ? $resolvedGame['away_team']
            : $resolvedGame['home_team'];
    }
}
