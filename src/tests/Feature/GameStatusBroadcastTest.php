<?php

namespace Tests\Feature;

use App\Events\GameStatusUpdated;
use App\Models\Country;
use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GameStatusBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_broadcasts_result_notification_when_score_and_status_change(): void
    {
        Cache::flush();
        Event::fake([GameStatusUpdated::class]);

        $game = $this->createGame();

        $game->update([
            'home_score' => 2,
            'away_score' => 1,
            'status' => 'finished',
        ]);

        Event::assertDispatched(GameStatusUpdated::class, function (GameStatusUpdated $event) use ($game) {
            return ($event->payload['gameId'] ?? null) === $game->id
                && ($event->payload['type'] ?? null) === 'result'
                && ($event->payload['status'] ?? null) === 'finished';
        });
    }

    public function test_it_does_not_broadcast_notification_when_only_metadata_changes(): void
    {
        Cache::flush();
        Event::fake([GameStatusUpdated::class]);

        $game = $this->createGame();

        $game->update([
            'venue' => 'Updated Venue',
            'match_time' => '21:30',
        ]);

        Event::assertNotDispatched(GameStatusUpdated::class);
    }

    private function createGame(): Game
    {
        $tournament = Tournament::query()->create([
            'name' => 'World Cup',
            'year' => 2026,
            'host_countries' => json_encode(['mx', 'us', 'ca']),
            'logo' => null,
            'deadline_at' => now()->addDay(),
            'status' => 'live',
            'type' => 'world_cup',
        ]);

        $countryA = Country::query()->create([
            'name' => 'Argentina',
            'code' => 'ar',
            'flag_path' => '/flags/ar.svg',
        ]);

        $countryB = Country::query()->create([
            'name' => 'Brazil',
            'code' => 'br',
            'flag_path' => '/flags/br.svg',
        ]);

        $homeTeam = Team::query()->create([
            'country_id' => $countryA->id,
            'group_id' => null,
            'name' => 'Argentina',
            'group_position' => 1,
            'type' => 'international',
        ]);

        $awayTeam = Team::query()->create([
            'country_id' => $countryB->id,
            'group_id' => null,
            'name' => 'Brazil',
            'group_position' => 2,
            'type' => 'international',
        ]);

        return Game::query()->create([
            'tournament_id' => $tournament->id,
            'match_number' => 1,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'home_slot' => null,
            'away_slot' => null,
            'home_score' => null,
            'away_score' => null,
            'winner_team_id' => null,
            'result_type' => null,
            'status' => 'scheduled',
            'stage' => 'group',
            'venue' => 'Initial Venue',
            'match_date' => now()->toDateString(),
            'match_time' => '20:00',
        ]);
    }
}
