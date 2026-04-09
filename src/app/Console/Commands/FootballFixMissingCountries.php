<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FootballFixMissingCountries extends Command
{
    protected $signature = 'football:fix:countries';
    protected $description = 'Try to link teams with missing country_id by name matching';

    public function handle(): int
    {
        $teams = Team::whereNull('country_id')->get();
        $countries = Country::all();

        $this->info("Checking {$teams->count()} teams with missing country_id...");

        $fixed = 0;
        foreach ($teams as $team) {
            $match = $this->findCountryMatch($team->name, $countries);
            
            if ($match) {
                $team->update(['country_id' => $match->id]);
                $this->line("  ✓ Linked {$team->name} to country {$match->name}");
                $success = true;
                $fixed++;
            } else {
                // Try to find country in the name of the team if it's longer
                // e.g. "Argentina U23" -> "Argentina"
                foreach ($countries as $country) {
                    if (Str::contains(Str::lower($team->name), Str::lower($country->name))) {
                        $team->update(['country_id' => $country->id]);
                        $this->line("  ✓ Linked {$team->name} to country {$country->name} (partial match)");
                        $fixed++;
                        continue 2;
                    }
                }
                $this->warn("  ✗ Could not find match for {$team->name}");
            }
        }

        $this->info("Repair complete! Linked {$fixed} teams.");
        return self::SUCCESS;
    }

    private function findCountryMatch(string $name, $countries): ?Country
    {
        $normalized = $this->normalize($name);

        foreach ($countries as $country) {
            if ($this->normalize($country->name) === $normalized) {
                return $country;
            }
            if ($country->code && strtolower($country->code) === $normalized) {
                return $country;
            }
        }

        // Hardcoded common aliases
        $aliases = [
            'bosniaherzegovina' => 'BA',
            'bosnia' => 'BA',
            'turkiye' => 'TR',
            'turkey' => 'TR',
            'southkorea' => 'KR',
            'korearepublic' => 'KR',
            'iran' => 'IR',
            'iriran' => 'IR',
            'usa' => 'US',
            'unitedstates' => 'US',
            'capeverdeislands' => 'CV',
            'capeverde' => 'CV',
            'caboverde' => 'CV',
            'congodr' => 'CD',
            'drcongo' => 'CD',
            'czechrepublic' => 'CZ',
            'czechia' => 'CZ',
        ];

        if (isset($aliases[$normalized])) {
            $code = $aliases[$normalized];
            return $countries->first(fn($c) => strtolower($c->code) === strtolower($code));
        }

        return null;
    }

    private function normalize(string $string): string
    {
        $string = Str::ascii($string);
        $string = preg_replace('/[^a-zA-Z0-9]/', '', $string);
        return strtolower($string);
    }
}
