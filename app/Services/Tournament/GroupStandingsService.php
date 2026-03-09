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

        $table = [];

        foreach ($teams as $team) {

            $games = Game::where(function ($q) use ($team) {
                    $q->where('home_team_id', $team->id)
                    ->orWhere('away_team_id', $team->id);
                })
                ->where('stage', 'group')
                ->whereNotNull('home_score')
                ->whereNotNull('away_score')
                ->get();

            $stats = [
                'team_id' => $team->id,
                'played' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'gf' => 0,
                'ga' => 0,
                'gd' => 0,
                'points' => 0
            ];

            foreach ($games as $game) {

                $stats['played']++;

                $home = $game->home_score;
                $away = $game->away_score;

                if ($game->home_team_id == $team->id) {

                    $stats['gf'] += $home;
                    $stats['ga'] += $away;

                    if ($home > $away) {
                        $stats['wins']++;
                        $stats['points'] += 3;
                    } elseif ($home < $away) {
                        $stats['losses']++;
                    } else {
                        $stats['draws']++;
                        $stats['points'] += 1;
                    }

                } else {

                    $stats['gf'] += $away;
                    $stats['ga'] += $home;

                    if ($away > $home) {
                        $stats['wins']++;
                        $stats['points'] += 3;
                    } elseif ($away < $home) {
                        $stats['losses']++;
                    } else {
                        $stats['draws']++;
                        $stats['points'] += 1;
                    }
                }
            }

            $stats['gd'] = $stats['gf'] - $stats['ga'];

            $table[] = $stats;
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
}
