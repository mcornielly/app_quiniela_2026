<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Group;
use App\Models\GroupStanding;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\Tournament\BestThirdPlaceRankingService;
use App\Services\Tournament\GroupStandingsService;
use App\Services\Tournament\OfficialBracketResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
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

        $groupMatchNumber = 1;

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

    public function test_group_stage_results_fill_round_of_32_with_valid_teams_from_each_group(): void
    {
        mt_srand(20260315);

        $tournament = Tournament::query()->create([
            'name' => 'World Cup 2026',
            'year' => 2026,
            'host_countries' => ['USA', 'Mexico', 'Canada'],
            'status' => 'draft',
            'type' => 'world_cup',
        ]);

        $groups = collect();
        $groupMatchNumber = 1;

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

            $groups->put($groupLetter, [
                'group' => $group,
                'teams' => $teams,
            ]);

            $groupMatchNumber = $this->createGroupStageGames($tournament, $teams, $groupLetter, $groupMatchNumber);
        }

        foreach ($this->roundOf32Slots() as $matchNumber => $slots) {
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

        foreach ($groups as $groupData) {
            app(GroupStandingsService::class)->calculate($groupData['group']->id);
        }

        app(OfficialBracketResolverService::class)->sync($tournament->fresh());

        $standingsByGroup = GroupStanding::query()
            ->with('team.group')
            ->where('tournament_id', $tournament->id)
            ->orderBy('position')
            ->get()
            ->groupBy(fn (GroupStanding $standing) => $standing->group->name)
            ->map(fn (Collection $rows) => $rows->values());

        $qualifiedThirdRows = app(BestThirdPlaceRankingService::class)
            ->rank($standingsByGroup->map(function (Collection $rows) {
                return $rows->map(function (GroupStanding $standing) {
                    return [
                        'team' => $standing->team,
                        'played' => $standing->played,
                        'wins' => $standing->wins,
                        'draws' => $standing->draws,
                        'losses' => $standing->losses,
                        'gf' => $standing->gf,
                        'ga' => $standing->ga,
                        'gd' => $standing->gd,
                        'points' => $standing->points,
                        'fair_play' => 0,
                        'ranking_score' => 0,
                    ];
                })->all();
            }))
            ->take(8)
            ->values();

        $qualifiedThirdTeamIds = $qualifiedThirdRows
            ->map(fn (array $row) => $row['team']->id)
            ->all();

        $usedThirdTeamIds = [];

        $games = Game::query()
            ->where('tournament_id', $tournament->id)
            ->where('stage', 'round_32')
            ->orderBy('match_number')
            ->get();

        foreach ($games as $game) {
            $this->assertResolvedSlotUsesAValidTeam($game->home_slot, $game->home_team_id, $standingsByGroup, $qualifiedThirdTeamIds);
            $this->assertResolvedSlotUsesAValidTeam($game->away_slot, $game->away_team_id, $standingsByGroup, $qualifiedThirdTeamIds);

            if (preg_match('/^3\-([A-Z]+)$/', (string) $game->home_slot)) {
                $usedThirdTeamIds[] = $game->home_team_id;
            }

            if (preg_match('/^3\-([A-Z]+)$/', (string) $game->away_slot)) {
                $usedThirdTeamIds[] = $game->away_team_id;
            }
        }

        $this->assertCount(8, array_filter($usedThirdTeamIds));
        $this->assertCount(8, array_unique($usedThirdTeamIds));
    }

    private function createGroupStageGames(Tournament $tournament, Collection $teams, string $groupLetter, int $startingMatchNumber): int
    {
        $pairs = [
            [0, 1],
            [0, 2],
            [0, 3],
            [1, 2],
            [1, 3],
            [2, 3],
        ];

        $matchNumber = $startingMatchNumber;

        foreach ($pairs as $index => [$homeIndex, $awayIndex]) {
            $groupWeight = ord($groupLetter) - 64;
            $homeScore = ($groupWeight + $homeIndex + ($index * 2) + mt_rand(0, 2)) % 5;
            $awayScore = ($groupWeight + $awayIndex + $index + mt_rand(0, 2)) % 4;

            if ($homeScore === $awayScore) {
                $homeScore = min(5, $homeScore + 1);
            }

            Game::query()->create([
                'tournament_id' => $tournament->id,
                'match_number' => $matchNumber,
                'stage' => 'group',
                'venue' => "Venue {$groupLetter}",
                'match_date' => now()->toDateString(),
                'match_time' => '12:00',
                'status' => 'finished',
                'home_team_id' => $teams[$homeIndex]->id,
                'away_team_id' => $teams[$awayIndex]->id,
                'home_score' => $homeScore,
                'away_score' => $awayScore,
            ]);

            $matchNumber++;
        }

        return $matchNumber;
    }

    private function roundOf32Slots(): array
    {
        return [
            74 => ['home_slot' => '1E', 'away_slot' => '3-ABCDF'],
            75 => ['home_slot' => '2A', 'away_slot' => '2B'],
            76 => ['home_slot' => '1F', 'away_slot' => '2C'],
            77 => ['home_slot' => '1I', 'away_slot' => '3-CDFGH'],
            78 => ['home_slot' => '2D', 'away_slot' => '2E'],
            79 => ['home_slot' => '1A', 'away_slot' => '3-CEFHI'],
            80 => ['home_slot' => '1L', 'away_slot' => '3-EHIJK'],
            81 => ['home_slot' => '1D', 'away_slot' => '3-BEFIJ'],
            82 => ['home_slot' => '1G', 'away_slot' => '3-AEHIJ'],
            83 => ['home_slot' => '2F', 'away_slot' => '2G'],
            84 => ['home_slot' => '1H', 'away_slot' => '2I'],
            85 => ['home_slot' => '1B', 'away_slot' => '3-EFGIJ'],
            86 => ['home_slot' => '2J', 'away_slot' => '2K'],
            87 => ['home_slot' => '1K', 'away_slot' => '3-DEIJL'],
            88 => ['home_slot' => '1C', 'away_slot' => '2L'],
            89 => ['home_slot' => '1J', 'away_slot' => '2H'],
        ];
    }

    private function assertResolvedSlotUsesAValidTeam(
        ?string $slot,
        ?int $resolvedTeamId,
        Collection $standingsByGroup,
        array $qualifiedThirdTeamIds
    ): void {
        $this->assertNotNull($resolvedTeamId, "El slot {$slot} no resolvio un equipo.");

        if (preg_match('/^([12])([A-Z])$/', (string) $slot, $matches)) {
            $position = (int) $matches[1];
            $groupLetter = $matches[2];
            $expectedTeamId = $standingsByGroup[$groupLetter][$position - 1]->team_id ?? null;

            $this->assertSame($expectedTeamId, $resolvedTeamId, "El slot {$slot} no resolvio al equipo correcto del grupo {$groupLetter}.");
            return;
        }

        if (preg_match('/^3\-([A-Z]+)$/', (string) $slot, $matches)) {
            $allowedGroups = str_split($matches[1]);
            $validThirdTeamIds = $standingsByGroup
                ->only($allowedGroups)
                ->map(fn (Collection $rows) => $rows[2]?->team_id)
                ->filter()
                ->values()
                ->all();

            $this->assertContains($resolvedTeamId, $qualifiedThirdTeamIds, "El slot {$slot} no uso un tercero clasificado valido.");
            $this->assertContains($resolvedTeamId, $validThirdTeamIds, "El slot {$slot} no uso un tercero permitido por su regla.");
        }
    }
}
