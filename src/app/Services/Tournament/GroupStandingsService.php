<?php

namespace App\Services\Tournament;

use App\Models\Game;
use App\Models\Group;
use App\Models\Team;
use App\Models\GroupStanding;

class GroupStandingsService
{
    public function __construct(
        private readonly StandingsTableService $standingsTableService,
    ) {
    }

    public function calculate($groupId)
    {
        $group = Group::findOrFail($groupId);

        $teams = Team::where('group_id', $groupId)
            ->with(['tournamentEntries' => fn ($query) => $query->where('tournament_id', $group->tournament_id)])
            ->get();

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

        $table = $this->standingsTableService->calculate($teams, $games);

        /*
        |--------------------------------------------------------------------------
        | Save standings
        |--------------------------------------------------------------------------
        */
        foreach ($table as $index => $row) {

            GroupStanding::updateOrCreate(

                [
                    'tournament_id' => $group->tournament_id,
                    'team_id' => $row['team']->id,
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
