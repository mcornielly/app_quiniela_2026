<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Group;
use App\Models\PoolEntry;
use App\Models\Prediction;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\Tournament\PredictionScoringService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_predictions_are_scored_and_pool_entries_are_updated()
    {
        $tournament = Tournament::create([
            'name' => 'World Cup',
            'year' => 2026,
            'host_countries' => ['USA'],
            'status' => 'upcoming',
            'type' => 'international',
        ]);

        $group = Group::create([
            'tournament_id' => $tournament->id,
            'name' => 'A',
        ]);

        $homeTeam = Team::create([
            'name' => 'Mexico',
            'group_id' => $group->id,
            'type' => 'international',
        ]);

        $awayTeam = Team::create([
            'name' => 'USA',
            'group_id' => $group->id,
            'type' => 'international',
        ]);

        $game = Game::create([
            'tournament_id' => $tournament->id,
            'match_number' => 1,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'stage' => 'group',
            'venue' => 'MetLife Stadium',
            'match_date' => Carbon::parse('2026-06-01'),
            'match_time' => '18:00',
            'status' => 'scheduled',
        ]);

        $entryOne = PoolEntry::create([
            'tournament_id' => $tournament->id,
            'user_id' => User::factory()->create()->id,
            'name' => 'Entry One',
        ]);

        $entryTwo = PoolEntry::create([
            'tournament_id' => $tournament->id,
            'user_id' => User::factory()->create()->id,
            'name' => 'Entry Two',
        ]);

        $entryThree = PoolEntry::create([
            'tournament_id' => $tournament->id,
            'user_id' => User::factory()->create()->id,
            'name' => 'Entry Three',
            'total_points' => 99,
            'exact_hits' => 2,
            'correct_results' => 3,
        ]);

        $predictionOne = Prediction::create([
            'pool_entry_id' => $entryOne->id,
            'game_id' => $game->id,
            'home_score' => 2,
            'away_score' => 1,
        ]);

        $predictionTwo = Prediction::create([
            'pool_entry_id' => $entryTwo->id,
            'game_id' => $game->id,
            'home_score' => 3,
            'away_score' => 2,
        ]);

        $game->update([
            'home_score' => 2,
            'away_score' => 1,
            'winner_team_id' => $homeTeam->id,
            'result_type' => 'home',
            'status' => 'finished',
        ]);

        $service = app(PredictionScoringService::class);
        $service->scoreGame($game->fresh('predictions'));

        $this->assertDatabaseHas('predictions', [
            'id' => $predictionOne->id,
            'points' => 5,
        ]);

        $this->assertDatabaseHas('predictions', [
            'id' => $predictionTwo->id,
            'points' => 3,
        ]);

        $this->assertDatabaseHas('pool_entries', [
            'id' => $entryOne->id,
            'total_points' => 5,
            'exact_hits' => 1,
            'correct_results' => 0,
        ]);

        $this->assertDatabaseHas('pool_entries', [
            'id' => $entryTwo->id,
            'total_points' => 3,
            'exact_hits' => 0,
            'correct_results' => 1,
        ]);

        $this->assertDatabaseHas('pool_entries', [
            'id' => $entryThree->id,
            'total_points' => 99,
            'exact_hits' => 2,
            'correct_results' => 3,
        ]);
    }
}
