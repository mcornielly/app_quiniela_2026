<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Team;
use Illuminate\Database\Seeder;

class BackfillTeamCountryIdsSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'Algeria' => 'dz',
            'Argentina' => 'ar',
            'Australia' => 'au',
            'Austria' => 'at',
            'Belgium' => 'be',
            'Brazil' => 'br',
            'Canada' => 'ca',
            'Cape Verde' => 'cv',
            'Colombia' => 'co',
            'Croatia' => 'hr',
            'Curaçao' => 'cw',
            'Curacao' => 'cw',
            'Ecuador' => 'ec',
            'Egypt' => 'eg',
            'England' => 'gb-eng',
            'France' => 'fr',
            'Germany' => 'de',
            'Ghana' => 'gh',
            'Haiti' => 'ht',
            'IR Iran' => 'ir',
            'Iran' => 'ir',
            'Ivory Coast' => 'ci',
            'Japan' => 'jp',
            'Jordan' => 'jo',
            'Mexico' => 'mx',
            'Morocco' => 'ma',
            'Netherlands' => 'nl',
            'New Zealand' => 'nz',
            'Norway' => 'no',
            'Panama' => 'pa',
            'Paraguay' => 'py',
            'Portugal' => 'pt',
            'Qatar' => 'qa',
            'Rep. of Korea' => 'kr',
            'Republic of Korea' => 'kr',
            'South Korea' => 'kr',
            'Saudi Arabia' => 'sa',
            'Scotland' => 'gb-sct',
            'Senegal' => 'sn',
            'South Africa' => 'za',
            'Spain' => 'es',
            'Switzerland' => 'ch',
            'Tunisia' => 'tn',
            'Uruguay' => 'uy',
            'USA' => 'us',
            'United States' => 'us',
            'Uzbekistan' => 'uz',
        ];

        foreach ($map as $teamName => $countryCode) {
            $countryId = Country::where('code', $countryCode)->value('id');

            if (!$countryId) {
                continue;
            }

            Team::where('name', $teamName)
                ->whereNull('country_id')
                ->update(['country_id' => $countryId]);
        }
    }
}
