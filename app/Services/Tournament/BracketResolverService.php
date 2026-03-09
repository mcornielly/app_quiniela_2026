<?php

namespace App\Services\Tournament;

use App\Models\Game;
use App\Models\Group;
use App\Models\GroupStanding;

class BracketResolverService
{
    public function resolveSlot($slot)
    {
        if (!$slot) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Winner of match (W74)
        |--------------------------------------------------------------------------
        */

        if (preg_match('/^W(\d+)$/', $slot, $matches)) {

            $matchNumber = $matches[1];

            $game = Game::where('match_number', $matchNumber)->first();

            return $game?->winner_team_id;
        }

        /*
        |--------------------------------------------------------------------------
        | Runner-up of match (RU101)
        |--------------------------------------------------------------------------
        */

        if (preg_match('/^RU(\d+)$/', $slot, $matches)) {

            $matchNumber = $matches[1];

            $game = Game::where('match_number', $matchNumber)->first();

            if (!$game || $game->winner_team_id === null) {
                return null;
            }

            if ($game->home_team_id == $game->winner_team_id) {
                return $game->away_team_id;
            }

            return $game->home_team_id;
        }

        /*
        |--------------------------------------------------------------------------
        | Group slot (1A, 2B, 3C)
        |--------------------------------------------------------------------------
        */

        if (preg_match('/^([1-3])([A-Z])$/', $slot, $matches)) {

            $position = $matches[1];
            $groupLetter = $matches[2];

            $group = Group::where('name', $groupLetter)->first();

            if (!$group) {
                return null;
            }

            $standing = GroupStanding::where('group_id', $group->id)
                ->where('position', $position)
                ->first();

            return $standing?->team_id;
        }

        /*
        |--------------------------------------------------------------------------
        | Best third place selector (3-ABCDF)
        |--------------------------------------------------------------------------
        */

        if (preg_match('/^3\-([A-Z]+)/', $slot, $matches)) {

            $groups = str_split($matches[1]);

            return $this->resolveBestThirdPlace($groups);
        }

        return null;
    }

    private function resolveBestThirdPlace($groups)
    {
        $standings = GroupStanding::whereIn('group_id', function ($q) use ($groups) {

                $q->select('id')
                    ->from('groups')
                    ->whereIn('name', $groups);

            })
            ->where('position', 3)
            ->orderByDesc('points')
            ->orderByDesc('gd')
            ->orderByDesc('gf')
            ->get();

        if ($standings->isEmpty()) {
            return null;
        }

        return $standings->first()->team_id;
    }
}
