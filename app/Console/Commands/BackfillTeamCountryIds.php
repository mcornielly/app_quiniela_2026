<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillTeamCountryIds extends Command
{
    protected $signature = 'app:backfill-team-country-ids {--dry-run : Show changes without saving}';

    protected $description = 'Fill country_id on teams from team names';

    public function handle(): int
    {
        $teams = Team::query()->whereNull('country_id')->orderBy('name')->get();

        if ($teams->isEmpty()) {
            $this->info('No teams with null country_id were found.');
            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = [];
        $dryRun = (bool) $this->option('dry-run');

        foreach ($teams as $team) {
            $countryCode = $this->resolveCountryCodeFromTeamName($team->name);

            if (!$countryCode) {
                $skipped[] = "{$team->name} (no mapping)";
                continue;
            }

            $countryId = Country::where('code', $countryCode)->value('id');

            if (!$countryId) {
                $skipped[] = "{$team->name} (country code {$countryCode} not found)";
                continue;
            }

            if ($dryRun) {
                $this->line("DRY RUN: {$team->name} -> {$countryCode} (country_id={$countryId})");
            } else {
                $team->update(['country_id' => $countryId]);
                $this->line("UPDATED: {$team->name} -> {$countryCode} (country_id={$countryId})");
            }

            $updated++;
        }

        $this->newLine();
        $this->info(($dryRun ? 'Would update' : 'Updated') . " {$updated} teams.");

        if (!empty($skipped)) {
            $this->warn('Skipped teams:');
            foreach ($skipped as $item) {
                $this->line("- {$item}");
            }
        }

        return self::SUCCESS;
    }

    private function resolveCountryCodeFromTeamName(?string $teamName): ?string
    {
        if (!$teamName) {
            return null;
        }

        $map = [
            'algeria' => 'dz',
            'argentina' => 'ar',
            'australia' => 'au',
            'austria' => 'at',
            'belgium' => 'be',
            'brazil' => 'br',
            'canada' => 'ca',
            'cape verde' => 'cv',
            'colombia' => 'co',
            'croatia' => 'hr',
            'curacao' => 'cw',
            'curaçao' => 'cw',
            'ecuador' => 'ec',
            'egypt' => 'eg',
            'england' => 'gb-eng',
            'france' => 'fr',
            'germany' => 'de',
            'ghana' => 'gh',
            'haiti' => 'ht',
            'ir iran' => 'ir',
            'iran' => 'ir',
            'ivory coast' => 'ci',
            'japan' => 'jp',
            'jordan' => 'jo',
            'mexico' => 'mx',
            'morocco' => 'ma',
            'netherlands' => 'nl',
            'new zealand' => 'nz',
            'norway' => 'no',
            'panama' => 'pa',
            'paraguay' => 'py',
            'portugal' => 'pt',
            'qatar' => 'qa',
            'rep. of korea' => 'kr',
            'republic of korea' => 'kr',
            'south korea' => 'kr',
            'saudi arabia' => 'sa',
            'scotland' => 'gb-sct',
            'senegal' => 'sn',
            'south africa' => 'za',
            'spain' => 'es',
            'switzerland' => 'ch',
            'tunisia' => 'tn',
            'uruguay' => 'uy',
            'usa' => 'us',
            'united states' => 'us',
            'uzbekistan' => 'uz',
        ];

        $normalized = Str::lower(trim($teamName));

        return $map[$normalized] ?? null;
    }
}
