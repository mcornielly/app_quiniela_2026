<?php

namespace App\Console\Commands;

use App\Models\Stadium;
use App\Models\Tournament;
use App\Services\FootballApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FootballSyncVenues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'football:sync-venues 
                            {--fetch : Download stadium data from API for host countries and cache it}
                            {--match : Attempt to link local stadiums with cached API results}
                            {--list : List current venues cached}
                            {--dry-run : Print actions without persisting changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync stadiums from API-Football using an aggressive caching strategy for host countries';

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
        $tournament = Tournament::query()->first();

        if (! $tournament) {
            $this->error('No tournament found in the database. Call WorldCupSeeder first?');
            return 1;
        }

        $hosts = $tournament->host_countries ?? [];

        // Manual decode fallback if cast didn't work as expected
        if (is_string($hosts)) {
            $hosts = json_decode($hosts, true) ?? [];
        }

        if ($this->option('fetch')) {
            return $this->doFetch($hosts);
        }

        if ($this->option('match')) {
            return $this->doMatch($hosts);
        }

        if ($this->option('list')) {
            return $this->doList($hosts);
        }

        $this->info('Please use --fetch to download data or --match to link it.');
        return 0;
    }

    private function doFetch(array $hosts): int
    {
        $this->info('Starting fetch for host countries: ' . implode(', ', $hosts));

        foreach ($hosts as $country) {
            $apiCountry = $country;
            if ($country === 'United States') {
                $apiCountry = 'USA';
            }

            $this->comment("Fetching venues for {$apiCountry} (matching source: {$country})...");
            
            try {
                // We use Fresh to bypass cache and REFILL it with latest actual data
                // This call only happens once per country per fetch run
                $response = $this->api->getVenuesFresh(['country' => $apiCountry]);
                $venues = $response['response'] ?? [];
                
                if (!$this->option('dry-run')) {
                    $cacheKey = "football.venues_storage." . Str::slug($country);
                    Cache::forever($cacheKey, $venues);
                }
                
                $this->info("Successfully fetched " . count($venues) . " venues for {$country}.");
            } catch (\Exception $e) {
                $this->error("Error fetching {$country}: " . $e->getMessage());
            }
        }

        return 0;
    }

    private function doList(array $hosts): int
    {
        foreach ($hosts as $country) {
            $cacheKey = "football.venues_storage." . Str::slug($country);
            $venues = Cache::get($cacheKey, []);
            
            $this->info("=== {$country} (" . count($venues) . " venues cached) ===");
            foreach (array_slice($venues, 0, 5) as $v) {
                $this->line("- [ID: {$v['id']}] {$v['name']} ({$v['city']})");
            }
            if (count($venues) > 5) $this->line("... and " . (count($venues)-5) . " more.");
        }
        return 0;
    }

    private function doMatch(array $hosts): int
    {
        $stadiums = Stadium::query()->whereNull('api_venue_id')->get();

        if ($stadiums->isEmpty()) {
            $this->info('No stadiums need matching (all have api_venue_id).');
            return 0;
        }

        // Flatten all cached venues from all hosts
        $allApiVenues = [];
        foreach ($hosts as $country) {
            $cacheKey = "football.venues_storage." . Str::slug($country);
            $venues = Cache::get($cacheKey, []);
            $allApiVenues = array_merge($allApiVenues, $venues);
        }

        if (empty($allApiVenues)) {
            $this->error('No cached venues found. Please run --fetch first.');
            return 1;
        }

        $this->info("Attempting to match " . $stadiums->count() . " stadiums...");

        $successCount = 0;
        foreach ($stadiums as $stadium) {
            $foundId = $this->findMatch($stadium, $allApiVenues);

            if ($foundId) {
                if (!$this->option('dry-run')) {
                    $stadium->update(['api_venue_id' => $foundId]);
                }
                $this->info("Matched: {$stadium->name} -> API ID {$foundId}");
                $successCount++;
            } else {
                $this->warn("Unmatched: {$stadium->name} ({$stadium->city})");
            }
        }

        $this->info("Match process complete. {$successCount} stadiums updated.");
        return 0;
    }

    private function findMatch(Stadium $stadium, array $apiVenues): ?int
    {
        $localName = Str::lower($stadium->name);
        $localCity = Str::lower($stadium->city ?? '');

        // 1. Exact match by name and city
        foreach ($apiVenues as $v) {
            if (Str::lower($v['name']) === $localName && Str::lower($v['city']) === $localCity) {
                return $v['id'];
            }
        }

        // 2. Exact match by name only
        foreach ($apiVenues as $v) {
            if (Str::lower($v['name']) === $localName) {
                return $v['id'];
            }
        }

        // 3. Partial match (slug contains slug)
        $localSlug = Str::slug($stadium->name);
        foreach ($apiVenues as $v) {
            $apiSlug = Str::slug($v['name']);
            if ($localSlug === $apiSlug || str_contains($apiSlug, $localSlug) || str_contains($localSlug, $apiSlug)) {
                return $v['id'];
            }
        }

        return null;
    }
}
