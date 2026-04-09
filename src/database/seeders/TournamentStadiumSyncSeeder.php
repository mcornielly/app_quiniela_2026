<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class TournamentStadiumSyncSeeder extends Seeder
{
    public function run(): void
    {
        $enabled = filter_var(env('AUTO_SYNC_TOURNAMENT_STADIUMS_ON_SEED', false), FILTER_VALIDATE_BOOL);
        $apiKey = trim((string) env('API_FOOTBALL_KEY', ''));

        if (! $enabled) {
            $this->command?->info('TournamentStadiumSyncSeeder skipped: AUTO_SYNC_TOURNAMENT_STADIUMS_ON_SEED=false');
            return;
        }

        if ($apiKey === '') {
            $this->command?->warn('TournamentStadiumSyncSeeder skipped: API_FOOTBALL_KEY is empty.');
            return;
        }

        $year = (int) env('AUTO_SYNC_TOURNAMENT_YEAR', env('API_FOOTBALL_WORLD_CUP_SEASON', 2026));

        try {
            Artisan::call('football:sync:tournament-stadiums', [
                '--tournament-year' => $year,
                '--refresh-cache' => true,
            ]);

            $this->command?->line(trim(Artisan::output()));
        } catch (\Throwable $e) {
            Log::warning('Tournament stadium auto-sync failed during seeding.', [
                'year' => $year,
                'error' => $e->getMessage(),
            ]);

            $this->command?->warn('TournamentStadiumSyncSeeder failed but seeding continues. Check logs for details.');
        }
    }
}

