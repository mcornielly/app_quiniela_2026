<?php

namespace App\Console\Commands;

use App\Events\GameStatusUpdated;
use App\Models\Game;
use App\Services\FootballApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FootballSyncLive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'football:sync:live {--league= : Filter by league ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch live match data from API-Football and update local games and broadcast changes';

    private FootballApiService $api;

    public function __construct(FootballApiService $api)
    {
        parent::__construct();
        $this->api = $api;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Fetching live fixtures from API-Football...');

        try {
            // Get all live fixtures
            $response = $this->api->getLiveFixtures();
            $apiFixtures = $response['response'] ?? [];

            if (empty($apiFixtures)) {
                $this->info('No live fixtures currently active on API.');
                return self::SUCCESS;
            }

            // Get tracked league IDs from config
            $trackedLeagues = collect(config('football.tournaments'))
                ->pluck('api_league_id')
                ->filter()
                ->toArray();

            $updatedCount = 0;

            foreach ($apiFixtures as $fixtureRow) {
                $apiLeagueId = (int) data_get($fixtureRow, 'league.id');

                // If league option is provided, filter by it, otherwise use trackedLeagues
                $requestLeague = (int) $this->option('league');
                if ($requestLeague > 0) {
                    if ($apiLeagueId !== $requestLeague) continue;
                } elseif (!empty($trackedLeagues)) {
                    if (!in_array($apiLeagueId, $trackedLeagues, true)) continue;
                }

                $fixtureId = (int) data_get($fixtureRow, 'fixture.id');
                $statusShort = (string) data_get($fixtureRow, 'fixture.status.short');
                $homeScore = data_get($fixtureRow, 'goals.home');
                $awayScore = data_get($fixtureRow, 'goals.away');

                // Find local game by API Fixture ID
                $game = Game::where('api_fixture_id', $fixtureId)->first();

                if (!$game) {
                    // Try to find by teams and date if ID is missing (robustness)
                    // ... (Implementation omitted for brevity, but recommended in full plan)
                    continue;
                }

                $this->updateAndBroadcast($game, $fixtureRow);
                $updatedCount++;
            }

            $this->info("Successfully updated {$updatedCount} live matches.");
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error syncing live data: ' . $e->getMessage());
            Log::error('FootballSyncLive failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }
    }

    private function updateAndBroadcast(Game $game, array $fixtureRow): void
    {
        $statusShort = (string) data_get($fixtureRow, 'fixture.status.short');
        $homeScore = data_get($fixtureRow, 'goals.home');
        $awayScore = data_get($fixtureRow, 'goals.away');

        $status = $this->mapFixtureStatus($statusShort);
        
        $changes = [];
        if ($game->status !== $status) $changes['status'] = $status;
        if (is_numeric($homeScore) && (int)$game->home_score !== (int)$homeScore) $changes['home_score'] = (int)$homeScore;
        if (is_numeric($awayScore) && (int)$game->away_score !== (int)$awayScore) $changes['away_score'] = (int)$awayScore;

        if (!empty($changes)) {
            $game->update($changes);
            
            // Broadcast the update
            try {
                broadcast(GameStatusUpdated::fromGame($game, 'update'));
            } catch (\Exception $e) {
                Log::warning('Broadcast failed for live sync', ['game' => $game->id, 'error' => $e->getMessage()]);
            }

            $this->line("Match #{$game->match_number} updated: {$game->home_score} - {$game->away_score} ({$statusShort})");
        }
    }

    private function mapFixtureStatus(string $short): string
    {
        $short = strtoupper(trim($short));

        if (in_array($short, ['FT', 'AET', 'PEN'], true)) {
            return 'finished';
        }

        if (in_array($short, ['1H', '2H', 'HT', 'ET', 'BT', 'P', 'INT', 'LIVE'], true)) {
            return 'in_progress';
        }

        return 'scheduled';
    }
}
