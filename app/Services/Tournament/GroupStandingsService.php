<?php

namespace App\Services\Tournament;

use App\Models\Game;
use App\Models\Group;
use App\Models\Team;
use App\Models\GroupStanding;

class GroupStandingsService
{
    public function calculate($groupId)
    {
        $teams = Team::where('group_id', $groupId)->get();

        if ($teams->isEmpty()) {
            return [];
        }

        $teamIds = $teams->pluck('id');

        $games = Game::where('stage', 'group')
            ->whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->where(function ($q) use ($teamIds) {
                $q->whereIn('home_team_id', $teamIds)
                    ->orWhereIn('away_team_id', $teamIds);
            })
            ->get();

        $gamesByTeam = $this->mapGamesByTeam($games);

        $table = [];

        foreach ($teams as $team) {
            $table[] = $this->calculateTeamStats($team->id, $gamesByTeam[$team->id] ?? []);
        }

        /*
        |--------------------------------------------------------------------------
        | Sort standings
        |--------------------------------------------------------------------------
        */

        usort($table, function ($a, $b) {
            return
                $b['points'] <=> $a['points']
                ?: $b['gd'] <=> $a['gd']
                ?: $b['gf'] <=> $a['gf'];
        });

        /*
        |--------------------------------------------------------------------------
        | Save standings
        |--------------------------------------------------------------------------
        */
        $group = Group::findOrFail($groupId);
        foreach ($table as $index => $row) {

            GroupStanding::updateOrCreate(

                [
                    'tournament_id' => $group->tournament_id,
                    'team_id' => $row['team_id'],
                    'group_id' => $groupId
                ],

                [
                    'played' => $row['played'],
                    'wins' => $row['wins'],
                    'draws' => $row['draws'],
                    'losses' => $row['losses'],
                    'gf' => $row['gf'],
                    'ga' => $row['ga'],
                    'gd' => $row['gd'],
                    'points' => $row['points'],
                    'position' => $index + 1
                ]
            );
        }

        return $table;
    }

    private function mapGamesByTeam($games): array
    {
        $map = [];

        foreach ($games as $game) {
            if ($game->home_team_id) {
                $map[$game->home_team_id][] = [
                    'game' => $game,
                    'is_home' => true,
                ];
            }

            if ($game->away_team_id) {
                $map[$game->away_team_id][] = [
                    'game' => $game,
                    'is_home' => false,
                ];
            }
        }

        return $map;
    }

    private function calculateTeamStats(int $teamId, array $games): array
    {
        $stats = [
            'team_id' => $teamId,
            'played' => 0,
            'wins' => 0,
            'draws' => 0,
            'losses' => 0,
            'gf' => 0,
            'ga' => 0,
            'gd' => 0,
            'points' => 0,
        ];

        foreach ($games as $entry) {
            $game = $entry['game'];
            $isHome = $entry['is_home'];

            $stats['played']++;

            $home = $game->home_score;
            $away = $game->away_score;

            $gf = $isHome ? $home : $away;
            $ga = $isHome ? $away : $home;

            $stats['gf'] += $gf;
            $stats['ga'] += $ga;

            if ($gf > $ga) {
                $stats['wins']++;
                $stats['points'] += 3;
            } elseif ($gf < $ga) {
                $stats['losses']++;
            } else {
                $stats['draws']++;
                $stats['points'] += 1;
            }
        }

        $stats['gd'] = $stats['gf'] - $stats['ga'];

        return $stats;
    }
}
