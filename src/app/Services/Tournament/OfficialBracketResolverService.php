<?php

namespace App\Services\Tournament;

use App\Models\Game;
use App\Models\GroupStanding;
use App\Models\Tournament;
use Illuminate\Support\Collection;

class OfficialBracketResolverService
{
    public function __construct(
        private readonly BestThirdPlaceRankingService $bestThirdPlaceRankingService,
        private readonly ThirdPlaceAssignmentService $thirdPlaceAssignmentService,
    ) {
    }

    public function sync(Tournament $tournament): void
    {
        $games = $tournament->games()
            ->with([
                'homeTeam.group',
                'awayTeam.group',
                'winnerTeam.group',
            ])
            ->orderBy('match_number')
            ->get();

        $groupStandings = $this->loadGroupStandings($tournament);

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
            $homeTeam = $game->stage === 'group'
                ? $game->homeTeam
                : $this->resolveSlotTeam($game, 'home', $groupStandings, $thirdPlaceAssignments, $resolvedGamesByMatchNumber);

            $awayTeam = $game->stage === 'group'
                ? $game->awayTeam
                : $this->resolveSlotTeam($game, 'away', $groupStandings, $thirdPlaceAssignments, $resolvedGamesByMatchNumber);

            if ($game->stage !== 'group') {
                $this->syncTeamId($game, 'home', $homeTeam?->id);
                $this->syncTeamId($game, 'away', $awayTeam?->id);
            }

            if ($game->isDirty()) {
                $game->save();
            }

            $winnerTeam = $this->resolveWinnerTeam($game, $homeTeam, $awayTeam);
            $runnerUpTeam = $this->resolveRunnerUpTeam($game, $homeTeam, $awayTeam, $winnerTeam);

            $resolvedGamesByMatchNumber[(string) $game->match_number] = [
                'winner_team' => $winnerTeam,
                'runner_up_team' => $runnerUpTeam,
            ];
        }
    }

    private function loadGroupStandings(Tournament $tournament): Collection
    {
        $groups = $tournament->groups()
            ->with([
                'teams.group',
                'teams.country',
                'teams.tournamentEntries' => fn ($query) => $query->where('tournament_id', $tournament->id),
            ])
            ->orderBy('name')
            ->get();

        $standings = GroupStanding::query()
            ->with([
                'team.group',
                'team.country',
                'team.tournamentEntries' => fn ($query) => $query->where('tournament_id', $tournament->id),
            ])
            ->where('tournament_id', $tournament->id)
            ->orderBy('position')
            ->get()
            ->groupBy('group_id');

        return $groups->mapWithKeys(function ($group) use ($standings) {
            $rows = $standings->get($group->id, collect());

            if ($rows->isNotEmpty()) {
                return [
                    $group->name => $rows
                        ->map(fn (GroupStanding $row) => $this->mapStandingRow($row->team, [
                            'played' => $row->played,
                            'wins' => $row->wins,
                            'draws' => $row->draws,
                            'losses' => $row->losses,
                            'gf' => $row->gf,
                            'ga' => $row->ga,
                            'gd' => $row->gd,
                            'points' => $row->points,
                        ]))
                        ->values()
                        ->all(),
                ];
            }

            return [
                $group->name => $group->teams
                    ->sortBy([
                        ['group_position', 'asc'],
                        ['name', 'asc'],
                    ])
                    ->values()
                    ->map(fn ($team) => $this->mapStandingRow($team, [
                        'played' => 0,
                        'wins' => 0,
                        'draws' => 0,
                        'losses' => 0,
                        'gf' => 0,
                        'ga' => 0,
                        'gd' => 0,
                        'points' => 0,
                    ]))
                    ->all(),
            ];
        });
    }

    private function mapStandingRow($team, array $stats): array
    {
        $entry = $team?->tournamentEntries?->first();
        $fifaRanking = $entry?->fifa_ranking;

        return [
            ...$stats,
            'team' => $team,
            'fair_play' => (int) ($entry?->fair_play_points ?? 0),
            'fifa_ranking' => $fifaRanking,
            'ranking_score' => $fifaRanking ? (100 - (int) $fifaRanking) : 0,
        ];
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

    private function syncTeamId(Game $game, string $side, ?int $teamId): void
    {
        if ($side === 'home') {
            $game->home_team_id = $teamId;
            return;
        }

        $game->away_team_id = $teamId;
    }

    private function resolveWinnerTeam(Game $game, $homeTeam, $awayTeam)
    {
        if ($game->home_score === null || $game->away_score === null) {
            return null;
        }

        if ($game->home_score === $game->away_score) {
            return null;
        }

        return $game->home_score > $game->away_score ? $homeTeam : $awayTeam;
    }

    private function resolveRunnerUpTeam(Game $game, $homeTeam, $awayTeam, $winnerTeam)
    {
        if (!$winnerTeam) {
            return null;
        }

        return $winnerTeam->id === $homeTeam?->id ? $awayTeam : $homeTeam;
    }

    private function bracketKey(Tournament $tournament): string
    {
        return sprintf('%s_%s', $tournament->type, $tournament->year);
    }
}
