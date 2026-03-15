<?php

namespace App\Services\Tournament;

use Illuminate\Support\Collection;

class StandingsTableService
{
    public function calculate(Collection $teams, Collection $games): array
    {
        $completedGames = $games
            ->filter(fn ($game) => $this->isCompletedGame($game))
            ->values();

        $table = $teams->mapWithKeys(function ($team) {
            $metrics = $this->resolveTournamentMetrics($team);

            return [
                $team->id => [
                    'team' => $team,
                    'played' => 0,
                    'wins' => 0,
                    'draws' => 0,
                    'losses' => 0,
                    'gf' => 0,
                    'ga' => 0,
                    'gd' => 0,
                    'points' => 0,
                    'fair_play' => $metrics['fair_play_points'],
                    'fifa_ranking' => $metrics['fifa_ranking'],
                    'ranking_score' => $metrics['ranking_score'],
                ],
            ];
        })->all();

        foreach ($completedGames as $game) {
            if (!isset($table[$game->home_team_id]) || !isset($table[$game->away_team_id])) {
                continue;
            }

            $homeRow = &$table[$game->home_team_id];
            $awayRow = &$table[$game->away_team_id];

            $homeRow['played']++;
            $awayRow['played']++;

            $homeRow['gf'] += $game->home_score;
            $homeRow['ga'] += $game->away_score;
            $awayRow['gf'] += $game->away_score;
            $awayRow['ga'] += $game->home_score;

            if ($game->home_score > $game->away_score) {
                $homeRow['wins']++;
                $awayRow['losses']++;
                $homeRow['points'] += 3;
            } elseif ($game->away_score > $game->home_score) {
                $awayRow['wins']++;
                $homeRow['losses']++;
                $awayRow['points'] += 3;
            } else {
                $homeRow['draws']++;
                $awayRow['draws']++;
                $homeRow['points']++;
                $awayRow['points']++;
            }

            unset($homeRow, $awayRow);
        }

        $rows = array_values(array_map(function (array $row) {
            $row['gd'] = $row['gf'] - $row['ga'];
            return $row;
        }, $table));

        return $this->rankRows($rows, $completedGames);
    }

    private function rankRows(array $rows, Collection $games): array
    {
        usort($rows, fn (array $left, array $right) => $this->compareOverall($left, $right));

        $rankedRows = [];

        foreach ($this->groupRowsByOverallTie($rows) as $groupedRows) {
            if (count($groupedRows) === 1) {
                $rankedRows[] = $groupedRows[0];
                continue;
            }

            $rankedRows = [
                ...$rankedRows,
                ...$this->breakTieByHeadToHead($groupedRows, $games),
            ];
        }

        return $rankedRows;
    }

    private function breakTieByHeadToHead(array $rows, Collection $games): array
    {
        $teamIds = array_map(fn (array $row) => $row['team']->id, $rows);
        $miniTable = $this->buildMiniTable($rows, $games, $teamIds);

        usort($rows, function (array $left, array $right) use ($miniTable) {
            $leftMini = $miniTable[$left['team']->id];
            $rightMini = $miniTable[$right['team']->id];

            return
                $rightMini['points'] <=> $leftMini['points']
                ?: $rightMini['gd'] <=> $leftMini['gd']
                ?: $rightMini['gf'] <=> $leftMini['gf']
                ?: $this->compareFallback($left, $right);
        });

        $rankedRows = [];

        foreach ($this->groupRowsByMiniTie($rows, $miniTable) as $groupedRows) {
            if (count($groupedRows) === 1) {
                $rankedRows[] = $groupedRows[0];
                continue;
            }

            if (count($groupedRows) === count($rows)) {
                usort($groupedRows, fn (array $left, array $right) => $this->compareFallback($left, $right));
                $rankedRows = [...$rankedRows, ...$groupedRows];
                continue;
            }

            $rankedRows = [
                ...$rankedRows,
                ...$this->breakTieByHeadToHead($groupedRows, $games),
            ];
        }

        return $rankedRows;
    }

    private function buildMiniTable(array $rows, Collection $games, array $teamIds): array
    {
        $miniTable = [];

        foreach ($rows as $row) {
            $miniTable[$row['team']->id] = [
                'points' => 0,
                'gf' => 0,
                'ga' => 0,
                'gd' => 0,
            ];
        }

        foreach ($games as $game) {
            if (
                !in_array($game->home_team_id, $teamIds, true) ||
                !in_array($game->away_team_id, $teamIds, true)
            ) {
                continue;
            }

            $miniTable[$game->home_team_id]['gf'] += $game->home_score;
            $miniTable[$game->home_team_id]['ga'] += $game->away_score;
            $miniTable[$game->away_team_id]['gf'] += $game->away_score;
            $miniTable[$game->away_team_id]['ga'] += $game->home_score;

            if ($game->home_score > $game->away_score) {
                $miniTable[$game->home_team_id]['points'] += 3;
            } elseif ($game->away_score > $game->home_score) {
                $miniTable[$game->away_team_id]['points'] += 3;
            } else {
                $miniTable[$game->home_team_id]['points'] += 1;
                $miniTable[$game->away_team_id]['points'] += 1;
            }
        }

        foreach ($miniTable as $teamId => $stats) {
            $miniTable[$teamId]['gd'] = $stats['gf'] - $stats['ga'];
        }

        return $miniTable;
    }

    private function groupRowsByOverallTie(array $rows): array
    {
        $groups = [];
        $currentKey = null;

        foreach ($rows as $row) {
            $key = $this->overallTieKey($row);

            if ($key !== $currentKey) {
                $groups[] = [];
                $currentKey = $key;
            }

            $groups[array_key_last($groups)][] = $row;
        }

        return $groups;
    }

    private function groupRowsByMiniTie(array $rows, array $miniTable): array
    {
        $groups = [];
        $currentKey = null;

        foreach ($rows as $row) {
            $stats = $miniTable[$row['team']->id];
            $key = "{$stats['points']}:{$stats['gd']}:{$stats['gf']}";

            if ($key !== $currentKey) {
                $groups[] = [];
                $currentKey = $key;
            }

            $groups[array_key_last($groups)][] = $row;
        }

        return $groups;
    }

    private function overallTieKey(array $row): string
    {
        return "{$row['points']}:{$row['gd']}:{$row['gf']}";
    }

    private function compareOverall(array $left, array $right): int
    {
        return
            $right['points'] <=> $left['points']
            ?: $right['gd'] <=> $left['gd']
            ?: $right['gf'] <=> $left['gf']
            ?: 0;
    }

    private function compareFallback(array $left, array $right): int
    {
        return
            (($right['fair_play'] ?? 0) <=> ($left['fair_play'] ?? 0))
            ?: (($right['ranking_score'] ?? 0) <=> ($left['ranking_score'] ?? 0))
            ?: (($left['team']->group_position ?? 9) <=> ($right['team']->group_position ?? 9))
            ?: strcmp((string) ($left['team']->name ?? ''), (string) ($right['team']->name ?? ''));
    }

    private function isCompletedGame($game): bool
    {
        return !(
            $game->home_team_id === null ||
            $game->away_team_id === null ||
            $game->home_score === null ||
            $game->away_score === null
        );
    }

    private function resolveTournamentMetrics($team): array
    {
        $entries = property_exists($team, 'tournamentEntries') ? $team->tournamentEntries : null;
        $entry = $entries?->first();
        $fifaRanking = $entry?->fifa_ranking;

        return [
            'fair_play_points' => (int) ($entry?->fair_play_points ?? 0),
            'fifa_ranking' => $fifaRanking,
            'ranking_score' => $fifaRanking ? (100 - (int) $fifaRanking) : 0,
        ];
    }
}
