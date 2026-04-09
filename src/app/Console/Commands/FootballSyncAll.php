<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class FootballSyncAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'football:sync:all {--dry-run : Print actions without persisting changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Orchestrate all Football API synchronization tasks (Countries, Venues, Teams, Fixtures)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $dryRunFlag = $dryRun ? ' --dry-run' : '';

        $this->info('Starting Master Synchronization...');

        // 1. Sync Countries
        $this->info('Step 1: Syncing Countries...');
        Artisan::call('football:sync-countries' . $dryRunFlag, [], $this->output);

        // 2. Sync Venues/Stadiums
        $this->info('Step 2: Syncing Venues/Stadiums...');
        Artisan::call('football:sync-venues' . $dryRunFlag, [], $this->output);

        // 3. Sync Tournaments (Teams and Fixtures)
        $tournaments = config('football.tournaments', []);
        
        if (empty($tournaments)) {
            $this->warn('No tournaments defined in config/football.php');
        }

        foreach ($tournaments as $key => $config) {
            $this->info("Step 3: Syncing Tournament [{$config['name']}]...");
            
            // Sync Teams for this tournament
            $this->info("  - Syncing Teams for {$config['name']}...");
            Artisan::call('football:sync-teams', [
                '--tournament-year' => $config['api_season'],
                '--tournament-type' => 'world_cup', // Adjust as needed
                '--dry-run' => $dryRun,
            ], $this->output);

            // Sync Fixtures (World Cup specific for now, but can be generalized)
            $this->info("  - Syncing Fixtures for {$config['name']}...");
            Artisan::call('football:sync:world-cup', [
                '--league' => $config['api_league_id'],
                '--season' => $config['api_season'],
                '--all-pages' => true,
                '--dry-run' => $dryRun,
            ], $this->output);

            // Sync Squads
            if (!($config['sync_players'] ?? false)) {
                $this->info("  - Syncing Squads for {$config['name']}...");
                Artisan::call('football:sync:squads' . $dryRunFlag, [], $this->output);
            }
        }

        // 4. Localize Shields
        $this->info('Step 4: Localizing Team Shields...');
        Artisan::call('football:sync:shields' . $dryRunFlag, [], $this->output);

        $this->info('Master Synchronization complete!');

        return self::SUCCESS;
    }
}
