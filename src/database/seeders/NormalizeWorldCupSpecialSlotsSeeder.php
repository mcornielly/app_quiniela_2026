<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Group;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class NormalizeWorldCupSpecialSlotsSeeder extends Seeder
{
    public function run(): void
    {
        $fifaFlagPath = Storage::disk('public')->exists('flags/fifa.svg')
            ? 'flags/fifa.svg'
            : 'flags/fifa.png';

        $fifaCountry = Country::updateOrCreate(
            ['code' => 'fifa'],
            [
                'name' => 'FIFA',
                'flag_path' => $fifaFlagPath,
            ]
        );

        $this->reorderGroup('B', [
            'Canada' => 1,
            'Play-Off A' => 2,
            'Switzerland' => 3,
            'Qatar' => 4,
        ]);

        $this->reorderGroup('F', [
            'Netherlands' => 1,
            'Japan' => 2,
            'Play-Off B' => 3,
            'Tunisia' => 4,
        ]);

        $this->reorderGroup('I', [
            'France' => 1,
            'Senegal' => 2,
            'Play-Off 2' => 3,
            'Norway' => 4,
        ]);

        $this->reorderGroup('K', [
            'Portugal' => 1,
            'Play-Off 1' => 2,
            'Uzbekistan' => 3,
            'Colombia' => 4,
        ]);

        $specialSlots = [
            ['group' => 'A', 'position' => 4, 'name' => 'DEN/MKD/CZE/IRL'],
            ['group' => 'B', 'position' => 2, 'name' => 'ITA/NIR/WAL/BIH'],
            ['group' => 'D', 'position' => 4, 'name' => 'TUR/ROU/SVK/KOS'],
            ['group' => 'F', 'position' => 3, 'name' => 'UKR/SWE/POL/ALB'],
            ['group' => 'I', 'position' => 3, 'name' => 'BOL/SUR/IRQ'],
            ['group' => 'K', 'position' => 2, 'name' => 'NCL/JAM/COD'],
        ];

        foreach ($specialSlots as $slot) {
            $group = Group::where('name', $slot['group'])->first();

            if (!$group) {
                continue;
            }

            Team::where('group_id', $group->id)
                ->where('group_position', $slot['position'])
                ->update([
                    'name' => $slot['name'],
                    'country_id' => $fifaCountry->id,
                ]);
        }
    }

    private function reorderGroup(string $groupName, array $positionsByTeamName): void
    {
        $group = Group::where('name', $groupName)->first();

        if (!$group) {
            return;
        }

        foreach ($positionsByTeamName as $teamName => $position) {
            Team::where('group_id', $group->id)
                ->whereIn('name', $this->candidateNames($teamName))
                ->update(['group_position' => $position]);
        }
    }

    private function candidateNames(string $teamName): array
    {
        $aliases = [
            'Play-Off A' => 'ITA/NIR/WAL/BIH',
            'Play-Off B' => 'UKR/SWE/POL/ALB',
            'Play-Off C' => 'TUR/ROU/SVK/KOS',
            'Play-Off D' => 'DEN/MKD/CZE/IRL',
            'Play-Off 1' => 'NCL/JAM/COD',
            'Play-Off 2' => 'BOL/SUR/IRQ',
        ];

        foreach ($aliases as $playOffName => $resolvedName) {
            if ($teamName === $playOffName || $teamName === $resolvedName) {
                return [$playOffName, $resolvedName];
            }
        }

        return [$teamName];
    }
}
