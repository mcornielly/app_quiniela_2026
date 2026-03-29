<?php

namespace Tests\Unit;

use App\Models\Game;
use App\Models\Group;
use App\Models\Team;
use App\Services\Tournament\ThirdPlaceAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ThirdPlaceAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_assigns_each_qualified_third_place_team_once(): void
    {
        $service = new ThirdPlaceAssignmentService();

        $qualifiedThirdRows = collect(['A', 'C', 'D', 'E', 'F', 'H', 'I', 'J'])
            ->values()
            ->map(function (string $groupLetter, int $index) {
                $group = new Group(['name' => $groupLetter]);
                $team = new Team([
                    'id' => $index + 1,
                    'name' => "Third {$groupLetter}",
                    'group_position' => 3,
                ]);
                $team->setRelation('group', $group);

                return [
                    'team' => $team,
                    'points' => 4,
                    'gd' => 1,
                    'gf' => 2,
                ];
            });

        $round32Games = new Collection([
            new Game(['match_number' => 74, 'home_slot' => '1E', 'away_slot' => '3-ABCDF']),
            new Game(['match_number' => 77, 'home_slot' => '1I', 'away_slot' => '3-CDFGH']),
            new Game(['match_number' => 79, 'home_slot' => '1A', 'away_slot' => '3-CEFHI']),
            new Game(['match_number' => 80, 'home_slot' => '1L', 'away_slot' => '3-EHIJK']),
            new Game(['match_number' => 81, 'home_slot' => '1D', 'away_slot' => '3-BEFIJ']),
            new Game(['match_number' => 82, 'home_slot' => '1G', 'away_slot' => '3-AEHIJ']),
            new Game(['match_number' => 85, 'home_slot' => '1B', 'away_slot' => '3-EFGIJ']),
            new Game(['match_number' => 87, 'home_slot' => '1K', 'away_slot' => '3-DEIJL']),
        ]);

        $assignment = $service->assign($qualifiedThirdRows, $round32Games);

        $this->assertCount(8, $assignment);

        $assignedGroups = collect($assignment)
            ->map(fn (array $row) => $row['team']->group->name)
            ->values();

        $this->assertCount(8, $assignedGroups->unique());
        $this->assertEqualsCanonicalizing(
            ['A', 'C', 'D', 'E', 'F', 'H', 'I', 'J'],
            $assignedGroups->all()
        );

        $this->assertSame('A', $assignment['74:away']['team']->group->name);
        $this->assertSame('C', $assignment['77:away']['team']->group->name);
        $this->assertSame('E', $assignment['79:away']['team']->group->name);
        $this->assertSame('H', $assignment['80:away']['team']->group->name);
        $this->assertSame('F', $assignment['81:away']['team']->group->name);
        $this->assertSame('I', $assignment['82:away']['team']->group->name);
        $this->assertSame('J', $assignment['85:away']['team']->group->name);
        $this->assertSame('D', $assignment['87:away']['team']->group->name);
    }

    public function test_it_uses_the_configured_matrix_when_available(): void
    {
        config()->set('tournament_brackets.world_cup_2026.third_place_matrix', [
            'ACDEFHIJ' => [
                74 => 'D',
                77 => 'C',
                79 => 'I',
                80 => 'H',
                81 => 'F',
                82 => 'A',
                85 => 'E',
                87 => 'J',
            ],
        ]);

        $service = new ThirdPlaceAssignmentService();
        $qualifiedThirdRows = collect(['A', 'C', 'D', 'E', 'F', 'H', 'I', 'J'])
            ->values()
            ->map(function (string $groupLetter, int $index) {
                $group = new Group(['name' => $groupLetter]);
                $team = new Team([
                    'id' => $index + 1,
                    'name' => "Third {$groupLetter}",
                    'group_position' => 3,
                ]);
                $team->setRelation('group', $group);

                return [
                    'team' => $team,
                    'points' => 4,
                    'gd' => 1,
                    'gf' => 2,
                    'ga' => 2,
                ];
            });

        $round32Games = new Collection([
            new Game(['match_number' => 74, 'home_slot' => '1E', 'away_slot' => '3-ABCDF']),
            new Game(['match_number' => 77, 'home_slot' => '1I', 'away_slot' => '3-CDFGH']),
            new Game(['match_number' => 79, 'home_slot' => '1A', 'away_slot' => '3-CEFHI']),
            new Game(['match_number' => 80, 'home_slot' => '1L', 'away_slot' => '3-EHIJK']),
            new Game(['match_number' => 81, 'home_slot' => '1D', 'away_slot' => '3-BEFIJ']),
            new Game(['match_number' => 82, 'home_slot' => '1G', 'away_slot' => '3-AEHIJ']),
            new Game(['match_number' => 85, 'home_slot' => '1B', 'away_slot' => '3-EFGIJ']),
            new Game(['match_number' => 87, 'home_slot' => '1K', 'away_slot' => '3-DEIJL']),
        ]);

        $assignment = $service->assign($qualifiedThirdRows, $round32Games, 'world_cup_2026');

        $this->assertSame('D', $assignment['74:away']['team']->group->name);
        $this->assertSame('I', $assignment['79:away']['team']->group->name);
        $this->assertSame('J', $assignment['87:away']['team']->group->name);
    }
}
