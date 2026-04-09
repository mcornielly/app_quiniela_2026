<?php

namespace App\Console\Commands;

use App\Models\Player;
use App\Models\Team;
use App\Services\FootballApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FootballSyncSquads extends Command
{
    protected $signature = 'football:sync:squads 
                            {--team= : Specific Team ID} 
                            {--download-photos : Download player and coach photos locally}
                            {--dry-run : Print actions without persisting changes}';

    protected $description = 'Sync squads (players and coaches) for international teams from API-Football';

    private FootballApiService $api;

    public function __construct(FootballApiService $api)
    {
        parent::__construct();
        $this->api = $api;
    }

    public function handle(): int
    {
        $teamId = $this->option('team');
        $downloadPhotos = $this->option('download-photos');

        $teams = Team::where('type', 'international')
            ->when($teamId, fn($q) => $q->where('id', $teamId))
            ->whereNotNull('api_team_id')
            ->get();

        if ($teams->isEmpty()) {
            $this->warn('No international teams with API IDs found.');
            return self::SUCCESS;
        }

        $this->info("Syncing squads for {$teams->count()} teams...");

        foreach ($teams as $team) {
            $this->info("Processing {$team->name}...");
            
            $this->syncCoach($team, $downloadPhotos);
            $this->syncPlayers($team, $downloadPhotos);
        }

        $this->info('Squad synchronization complete!');
        return self::SUCCESS;
    }

    private function syncCoach(Team $team, bool $downloadPhotos): void
    {
        try {
            $response = $this->api->getCoach($team->api_team_id);
            $coachData = $response['response'][0] ?? null;

            if ($coachData) {
                $name = $coachData['name'];
                $photo = $coachData['photo'];

                $localPhoto = null;
                if ($downloadPhotos && $photo) {
                    $localPhoto = $this->downloadImage($photo, "coaches/{$team->api_team_id}.jpg");
                }

                if ($this->option('dry-run')) {
                    $this->line("  - [Dry-run] Would update coach to {$name}");
                    return;
                }

                $team->update([
                    'coach_name' => $name,
                    'coach_photo' => $localPhoto ?: $photo,
                ]);

                $this->line("  - Coach: {$name}");
            }
        } catch (\Exception $e) {
            $this->error("  - Error syncing coach for {$team->name}: {$e->getMessage()}");
        }
    }

    private function syncPlayers(Team $team, bool $downloadPhotos): void
    {
        try {
            $response = $this->api->getSquad($team->api_team_id);
            $playersData = $response['response'][0]['players'] ?? [];

            if (empty($playersData)) {
                $this->warn("  - No players found for {$team->name}");
                return;
            }

            foreach ($playersData as $p) {
                $apiPlayerId = $p['id'];
                
                $localPhoto = null;
                if ($downloadPhotos && !empty($p['photo'])) {
                    $localPhoto = $this->downloadImage($p['photo'], "players/{$apiPlayerId}.jpg");
                }

                if ($this->option('dry-run')) {
                    $this->line("  - [Dry-run] Would sync player {$p['name']}");
                    continue;
                }

                Player::updateOrCreate(
                    ['api_player_id' => $apiPlayerId],
                    [
                        'team_id' => $team->id,
                        'name' => $p['name'],
                        'age' => $p['age'],
                        'number' => $p['number'],
                        'position' => $this->mapPosition($p['position']),
                        'photo_url' => $p['photo'],
                        'local_photo_path' => $localPhoto,
                    ]
                );
            }

            $this->line("  - Players: " . count($playersData) . " synced.");
        } catch (\Exception $e) {
            $this->error("  - Error syncing players for {$team->name}: {$e->getMessage()}");
        }
    }

    private function downloadImage(string $url, string $path): ?string
    {
        try {
            $content = Http::get($url)->body();
            Storage::disk('public')->put($path, $content);
            return $path;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function mapPosition(?string $pos): string
    {
        return match (Str::lower($pos)) {
            'goalkeeper' => 'goalkeeper',
            'defender' => 'defender',
            'midfielder' => 'midfielder',
            'attacker' => 'attacker',
            default => $pos ?: 'unknown',
        };
    }
}
