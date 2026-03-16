<?php

namespace Tests\Unit;

use App\Services\Tournament\StandingsTableService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class StandingsTableServiceTest extends TestCase
{
    public function test_it_uses_head_to_head_to_break_an_overall_tie_inside_a_group(): void
    {
        config()->set('tournament_rules.tiebreakers.use_fair_play', false);
        config()->set('tournament_rules.tiebreakers.use_fifa_ranking', false);

        $service = new StandingsTableService();

        $teams = collect([
            (object) ['id' => 1, 'name' => 'Alpha', 'group_position' => 1],
            (object) ['id' => 2, 'name' => 'Beta', 'group_position' => 2],
            (object) ['id' => 3, 'name' => 'Gamma', 'group_position' => 3],
            (object) ['id' => 4, 'name' => 'Delta', 'group_position' => 4],
        ]);

        $games = new Collection([
            $this->game(1, 2, 1, 0),
            $this->game(1, 3, 0, 1),
            $this->game(1, 4, 2, 0),
            $this->game(2, 3, 2, 0),
            $this->game(2, 4, 1, 0),
            $this->game(3, 4, 1, 1),
        ]);

        $table = $service->calculate($teams, $games);

        $this->assertSame('Alpha', $table[0]['team']->name);
        $this->assertSame('Beta', $table[1]['team']->name);
        $this->assertSame(6, $table[0]['points']);
        $this->assertSame(6, $table[1]['points']);
        $this->assertSame(2, $table[0]['gd']);
        $this->assertSame(2, $table[1]['gd']);
        $this->assertSame(3, $table[0]['gf']);
        $this->assertSame(3, $table[1]['gf']);
    }

    public function test_it_keeps_group_position_fallback_when_fifa_ranking_is_disabled(): void
    {
        config()->set('tournament_rules.tiebreakers.use_fair_play', false);
        config()->set('tournament_rules.tiebreakers.use_fifa_ranking', false);

        $service = new StandingsTableService();

        $teams = collect([
            $this->team(1, 'Alpha', 1, 25),
            $this->team(2, 'Beta', 2, 40),
        ]);

        $table = $service->calculate($teams, new Collection());

        $this->assertSame('Alpha', $table[0]['team']->name);
        $this->assertSame('Beta', $table[1]['team']->name);
        $this->assertSame(75, $table[0]['ranking_score']);
        $this->assertSame(60, $table[1]['ranking_score']);
    }

    public function test_it_can_use_tournament_fifa_ranking_as_final_fallback_when_enabled(): void
    {
        config()->set('tournament_rules.tiebreakers.use_fair_play', false);
        config()->set('tournament_rules.tiebreakers.use_fifa_ranking', true);

        $service = new StandingsTableService();

        $teams = collect([
            $this->team(1, 'Alpha', 2, 25),
            $this->team(2, 'Beta', 1, 40),
        ]);

        $table = $service->calculate($teams, new Collection());

        $this->assertSame('Alpha', $table[0]['team']->name);
        $this->assertSame('Beta', $table[1]['team']->name);
    }

    private function game(int $homeTeamId, int $awayTeamId, int $homeScore, int $awayScore): object
    {
        return (object) [
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
        ];
    }

    private function team(int $id, string $name, int $groupPosition, int $fifaRanking): object
    {
        return (object) [
            'id' => $id,
            'name' => $name,
            'group_position' => $groupPosition,
            'tournamentEntries' => collect([
                (object) [
                    'fifa_ranking' => $fifaRanking,
                    'fair_play_points' => 0,
                ],
            ]),
        ];
    }
}
