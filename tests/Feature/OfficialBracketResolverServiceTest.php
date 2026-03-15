<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Group;
use App\Models\GroupStanding;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\Tournament\OfficialBracketResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficialBracketResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_assigns_the_official_best_third_place_teams_to_round_of_32_games(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'World Cup 2026',
            'year' => 2026,
            'host_countries' => ['USA', 'Mexico', 'Canada'],
            'status' => 'draft',
            'type' => 'world_cup',
        ]);

        $qualifiedThirdGroups = ['A', 'C', 'D', 'E', 'F', 'H', 'I', 'J'];
        $groupWinners = [];
        $groupRunnersUp = [];
        $groupThirds = [];

        foreach (range('A', 'L') as $groupLetter) {
            $group = Group::query()->create([
                'tournament_id' => $tournament->id,
                'name' => $groupLetter,
            ]);

            $teams = collect(range(1, 4))->map(function (int $position) use ($group, $groupLetter) {
                return Team::query()->create([
                    'group_id' => $group->id,
                    'group_position' => $position,
                    'name' => "Team {$groupLetter}{$position}",
                    'type' => 'international',
                ]);
            });

            $winner = $teams[0];
            $runnerUp = $teams[1];
            $third = $teams[2];
            $fourth = $teams[3];

            $groupWinners[$groupLetter] = $winner->id;
            $groupRunnersUp[$groupLetter] = $runnerUp->id;
            $groupThirds[$groupLetter] = $third->id;

            GroupStanding::query()->create([
                'tournament_id' => $tournament->id,
                'group_id' => $group->id,
                'team_id' => $winner->id,
                'played' => 3,
                'wins' => 3,
                'draws' => 0,
                'losses' => 0,
                'gf' => 6,
                'ga' => 1,
                'gd' => 5,
                'points' => 9,
                'position' => 1,
            ]);

            GroupStanding::query()->create([
                'tournament_id' => $tournament->id,
                'group_id' => $group->id,
                'team_id' => $runnerUp->id,
                'played' => 3,
                'wins' => 2,
                'draws' => 0,
                'losses' => 1,
                'gf' => 4,
                'ga' => 2,
                'gd' => 2,
                'points' => 6,
                'position' => 2,
            ]);

            GroupStanding::query()->create([
                'tournament_id' => $tournament->id,
                'group_id' => $group->id,
                'team_id' => $third->id,
                'played' => 3,
                'wins' => in_array($groupLetter, $qualifiedThirdGroups, true) ? 1 : 0,
                'draws' => in_array($groupLetter, $qualifiedThirdGroups, true) ? 1 : 0,
                'losses' => in_array($groupLetter, $qualifiedThirdGroups, true) ? 1 : 3,
                'gf' => in_array($groupLetter, $qualifiedThirdGroups, true) ? 2 : 0,
                'ga' => in_array($groupLetter, $qualifiedThirdGroups, true) ? 1 : 5,
                'gd' => in_array($groupLetter, $qualifiedThirdGroups, true) ? 1 : -5,
                'points' => in_array($groupLetter, $qualifiedThirdGroups, true) ? 4 : 0,
                'position' => 3,
            ]);

            GroupStanding::query()->create([
                'tournament_id' => $tournament->id,
                'group_id' => $group->id,
                'team_id' => $fourth->id,
                'played' => 3,
                'wins' => 0,
                'draws' => 0,
                'losses' => 3,
                'gf' => 0,
                'ga' => 6,
                'gd' => -6,
                'points' => 0,
                'position' => 4,
            ]);
        }

        $games = [
            74 => ['home_slot' => '1E', 'away_slot' => '3-ABCDF'],
            77 => ['home_slot' => '1I', 'away_slot' => '3-CDFGH'],
            79 => ['home_slot' => '1A', 'away_slot' => '3-CEFHI'],
            80 => ['home_slot' => '1L', 'away_slot' => '3-EHIJK'],
            81 => ['home_slot' => '1D', 'away_slot' => '3-BEFIJ'],
            82 => ['home_slot' => '1G', 'away_slot' => '3-AEHIJ'],
            85 => ['home_slot' => '1B', 'away_slot' => '3-EFGIJ'],
            87 => ['home_slot' => '1K', 'away_slot' => '3-DEIJL'],
        ];

        foreach ($games as $matchNumber => $slots) {
            Game::query()->create([
                'tournament_id' => $tournament->id,
                'match_number' => $matchNumber,
                'stage' => 'round_32',
                'venue' => 'Test venue',
                'match_date' => now()->toDateString(),
                'match_time' => '18:00',
                'status' => 'scheduled',
                'home_slot' => $slots['home_slot'],
                'away_slot' => $slots['away_slot'],
            ]);
        }

        app(OfficialBracketResolverService::class)->sync($tournament->fresh());

        $this->assertDatabaseHas('games', [
            'tournament_id' => $tournament->id,
            'match_number' => 74,
            'home_team_id' => $groupWinners['E'],
            'away_team_id' => $groupThirds['C'],
        ]);

        $this->assertDatabaseHas('games', [
            'tournament_id' => $tournament->id,
            'match_number' => 77,
            'home_team_id' => $groupWinners['I'],
            'away_team_id' => $groupThirds['F'],
        ]);

        $this->assertDatabaseHas('games', [
            'tournament_id' => $tournament->id,
            'match_number' => 79,
            'home_team_id' => $groupWinners['A'],
            'away_team_id' => $groupThirds['H'],
        ]);

        $this->assertDatabaseHas('games', [
            'tournament_id' => $tournament->id,
            'match_number' => 80,
            'home_team_id' => $groupWinners['L'],
            'away_team_id' => $groupThirds['I'],
        ]);

        $this->assertDatabaseHas('games', [
            'tournament_id' => $tournament->id,
            'match_number' => 81,
            'home_team_id' => $groupWinners['D'],
            'away_team_id' => $groupThirds['E'],
        ]);

        $this->assertDatabaseHas('games', [
            'tournament_id' => $tournament->id,
            'match_number' => 82,
            'home_team_id' => $groupWinners['G'],
            'away_team_id' => $groupThirds['A'],
        ]);

        $this->assertDatabaseHas('games', [
            'tournament_id' => $tournament->id,
            'match_number' => 85,
            'home_team_id' => $groupWinners['B'],
            'away_team_id' => $groupThirds['J'],
        ]);

        $this->assertDatabaseHas('games', [
            'tournament_id' => $tournament->id,
            'match_number' => 87,
            'home_team_id' => $groupWinners['K'],
            'away_team_id' => $groupThirds['D'],
        ]);
    }
}
