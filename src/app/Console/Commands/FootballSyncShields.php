<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FootballSyncShields extends Command
{
    protected $signature = 'football:sync:shields 
                            {--team= : Specific Team ID} 
                            {--force : Redownload existing shields}
                            {--dry-run : Print actions without persisting changes}';

    protected $description = 'Download team shields (logos) from API-Football and store them locally';

    public function handle(): int
    {
        $teamId = $this->option('team');
        $force = $this->option('force');

        $teams = Team::whereNotNull('api_team_logo_url')
            ->when($teamId, fn($q) => $q->where('id', $teamId))
            ->get();

        if ($teams->isEmpty()) {
            $this->warn('No teams with API logo URLs found.');
            return self::SUCCESS;
        }

        $this->info("Downloading shields for {$teams->count()} teams...");

        $success = 0;
        foreach ($teams as $team) {
            if ($team->shield_path && !$force) {
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  - [Dry-run] Would download shield for {$team->name}");
                continue;
            }

            $extension = pathinfo(parse_url($team->api_team_logo_url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
            $filename = "shields/{$team->api_team_id}.{$extension}";

            try {
                $response = Http::get($team->api_team_logo_url);
                if ($response->successful()) {
                    Storage::disk('public')->put($filename, $response->body());
                    $team->update(['shield_path' => $filename]);
                    $this->line("  ✓ {$team->name} logo saved.");
                    $success++;
                } else {
                    $this->error("  ✗ Failed to download logo for {$team->name}");
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Error for {$team->name}: {$e->getMessage()}");
            }
        }

        $this->info("Complete! {$success} logos downloaded.");
        return self::SUCCESS;
    }
}
