<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Services\FootballApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FootballSyncCountries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'football:sync-countries {--dry-run : Print actions without persisting changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync local countries with API-Football data (names, ISO codes, and flag URLs)';

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
        $dryRun = $this->option('dry-run');
        $this->info('Fetching countries from API-Football...');

        try {
            $response = $this->api->getCountries();
            $apiCountries = $response['response'] ?? [];

            if (empty($apiCountries)) {
                $this->error('No countries data received from the API.');
                return 1;
            }

            $this->info('Mapping ' . count($apiCountries) . ' countries from API to local database...');

            $updatedCount = 0;
            $newCount = 0;

            foreach ($apiCountries as $apiCountry) {
                if (empty($apiCountry['code'])) {
                    continue;
                }
                
                $code = $apiCountry['code']; // ISO alpha-2 code (eg. 'AR', 'US')
                $name = $apiCountry['name']; // API name (eg. 'USA')
                $flag = $apiCountry['flag']; // API SVG URL

                // Attempt to find existing country by its code
                $country = Country::query()
                    ->where('code', $code)
                    ->orWhere('name', $name)
                    ->first();

                if ($country) {
                    if (!$dryRun) {
                        $country->update([
                            'name' => $name,
                            'api_flag_url' => $flag,
                        ]);
                    }
                    $updatedCount++;
                } else {
                    if (!$dryRun) {
                        Country::create([
                            'name' => $name,
                            'code' => $code,
                            'api_flag_url' => $flag,
                            'flag_path' => $flag,
                        ]);
                    }
                    $newCount++;
                }
            }

            $this->info("Sync complete! Updated: {$updatedCount}, Created: {$newCount}.");
            return 0;

        } catch (\Exception $e) {
            $this->error('Error syncing countries: ' . $e->getMessage());
            return 1;
        }
    }
}
