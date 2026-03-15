<?php

namespace Tests\Unit;

use App\Models\Group;
use App\Models\Team;
use App\Services\Tournament\BestThirdPlaceRankingService;
use Tests\TestCase;

class BestThirdPlaceRankingServiceTest extends TestCase
{
    public function test_it_ranks_third_place_rows_using_fair_play_and_ranking_after_goals_for(): void
    {
        $service = new BestThirdPlaceRankingService();

        $groupStandings = collect([
            'A' => [
                null,
                null,
                $this->makeRow('A', 'Third A', 4, 1, 2, -4, 70),
            ],
            'B' => [
                null,
                null,
                $this->makeRow('B', 'Third B', 4, 1, 2, -2, 60),
            ],
            'C' => [
                null,
                null,
                $this->makeRow('C', 'Third C', 4, 1, 2, -2, 80),
            ],
        ]);

        $ranked = $service->rank($groupStandings);

        $this->assertSame('C', $ranked[0]['team']->group->name);
        $this->assertSame('B', $ranked[1]['team']->group->name);
        $this->assertSame('A', $ranked[2]['team']->group->name);
    }

    private function makeRow(
        string $groupLetter,
        string $teamName,
        int $points,
        int $gd,
        int $gf,
        int $fairPlay,
        int $rankingScore
    ): array
    {
        $group = new Group(['name' => $groupLetter]);
        $team = new Team([
            'name' => $teamName,
            'group_position' => 3,
        ]);
        $team->setRelation('group', $group);

        return [
            'team' => $team,
            'points' => $points,
            'gd' => $gd,
            'gf' => $gf,
            'fair_play' => $fairPlay,
            'ranking_score' => $rankingScore,
        ];
    }
}
