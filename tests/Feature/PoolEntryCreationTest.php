<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Group;
use App\Models\PoolEntry;
use App\Models\Prediction;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoolEntryCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_store_a_complete_prediction_set(): void
    {
        $user = User::factory()->create();
        [$tournament, $games] = $this->createTournamentWithGames();

        $response = $this
            ->actingAs($user)
            ->post(route('pools.store'), [
                'tournament_id' => $tournament->id,
                'predictions' => [
                    [
                        'game_id' => $games[0]->id,
                        'home_score' => 2,
                        'away_score' => 1,
                    ],
                    [
                        'game_id' => $games[1]->id,
                        'home_score' => 1,
                        'away_score' => 0,
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('predictions.worldcup'))
            ->assertSessionHas('success', 'La quiniela fue registrada exitosamente.')
            ->assertSessionHas('created_pool_entry')
            ->assertSessionHas('created_pool_entry.predictedChampionName', 'Finalist A');

        $this->assertDatabaseCount('pool_entries', 1);
        $this->assertDatabaseCount('predictions', 2);

        $entry = PoolEntry::query()->firstOrFail();

        $this->assertSame($user->id, $entry->user_id);
        $this->assertSame($tournament->id, $entry->tournament_id);
        $this->assertSame('complete', $entry->status);
        $this->assertSame(100, $entry->completion_percent);
        $this->assertSame("Quiniela #{$entry->id}", $entry->name);

        $this->assertDatabaseHas('predictions', [
            'pool_entry_id' => $entry->id,
            'game_id' => $games[0]->id,
            'home_score' => 2,
            'away_score' => 1,
        ]);
    }

    public function test_a_user_can_create_multiple_pool_entries_for_the_same_tournament(): void
    {
        $user = User::factory()->create();
        [$tournament, $games] = $this->createTournamentWithGames();

        $firstPayload = [
            'tournament_id' => $tournament->id,
            'predictions' => [
                [
                    'game_id' => $games[0]->id,
                    'home_score' => 1,
                    'away_score' => 1,
                ],
                [
                    'game_id' => $games[1]->id,
                    'home_score' => 2,
                    'away_score' => 1,
                ],
            ],
        ];

        $secondPayload = [
            'tournament_id' => $tournament->id,
            'predictions' => [
                [
                    'game_id' => $games[0]->id,
                    'home_score' => 3,
                    'away_score' => 0,
                ],
                [
                    'game_id' => $games[1]->id,
                    'home_score' => 0,
                    'away_score' => 1,
                ],
            ],
        ];

        $this->actingAs($user)->post(route('pools.store'), $firstPayload);
        $this->actingAs($user)->post(route('pools.store'), $secondPayload);

        $this->assertSame(2, $user->fresh()->poolEntries()->count());
        $this->assertDatabaseCount('pool_entries', 2);
        $this->assertDatabaseCount('predictions', 4);
    }

    public function test_a_user_cannot_store_a_knockout_draw_prediction(): void
    {
        $user = User::factory()->create();
        [$tournament, $games] = $this->createTournamentWithGames();

        $response = $this
            ->actingAs($user)
            ->post(route('pools.store'), [
                'tournament_id' => $tournament->id,
                'predictions' => [
                    [
                        'game_id' => $games[0]->id,
                        'home_score' => 2,
                        'away_score' => 2,
                    ],
                    [
                        'game_id' => $games[1]->id,
                        'home_score' => 1,
                        'away_score' => 1,
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('predictions.worldcup'))
            ->assertSessionHas('error', 'En fases eliminatorias debes definir un ganador. Revisa los empates de tu quiniela.');

        $this->assertDatabaseCount('pool_entries', 0);
        $this->assertDatabaseCount('predictions', 0);
    }

    /**
     * @return array{0: \App\Models\Tournament, 1: array<int, \App\Models\Game>}
     */
    private function createTournamentWithGames(): array
    {
        $tournament = Tournament::query()->create([
            'name' => 'Test World Cup',
            'year' => 2026,
            'host_countries' => ['Testland'],
            'deadline_at' => now()->addDay(),
            'status' => 'draft',
            'type' => 'world_cup',
        ]);

        $group = Group::query()->create([
            'tournament_id' => $tournament->id,
            'name' => 'A',
        ]);

        $homeTeam = Team::query()->create([
            'group_id' => $group->id,
            'group_position' => 1,
            'name' => 'Home Team',
            'type' => 'international',
        ]);

        $awayTeam = Team::query()->create([
            'group_id' => $group->id,
            'group_position' => 2,
            'name' => 'Away Team',
            'type' => 'international',
        ]);

        $otherHomeTeam = Team::query()->create([
            'group_id' => $group->id,
            'group_position' => 3,
            'name' => 'Finalist A',
            'type' => 'international',
        ]);

        $otherAwayTeam = Team::query()->create([
            'group_id' => $group->id,
            'group_position' => 4,
            'name' => 'Finalist B',
            'type' => 'international',
        ]);

        $groupGame = Game::query()->create([
            'tournament_id' => $tournament->id,
            'match_number' => 1,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'stage' => 'group',
            'venue' => 'Test Stadium',
            'match_date' => now()->toDateString(),
            'match_time' => '18:00',
            'status' => 'scheduled',
        ]);

        $finalGame = Game::query()->create([
            'tournament_id' => $tournament->id,
            'match_number' => 2,
            'home_team_id' => $otherHomeTeam->id,
            'away_team_id' => $otherAwayTeam->id,
            'stage' => 'final',
            'venue' => 'Final Stadium',
            'match_date' => now()->addDay()->toDateString(),
            'match_time' => '20:00',
            'status' => 'scheduled',
        ]);

        return [$tournament, [$groupGame, $finalGame]];
    }
}
