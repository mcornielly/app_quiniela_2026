<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Group;
use App\Models\Team;
use Illuminate\Database\Seeder;

class NormalizeWorldCupSpecialSlotsSeeder extends Seeder
{
    public function run(): void
    {
        $fifaCountry = Country::updateOrCreate(
            ['code' => 'fifa'],
            [
                'name' => 'FIFA',
                'flag_path' => 'flags/fifa.png',
            ]
        );

        $this->reorderGroup('B', [
            'Canada' => 1,
            'Qatar' => 2,
            'Switzerland' => 3,
            'Play-Off A' => 4,
        ]);

        $this->reorderGroup('F', [
            'Netherlands' => 1,
            'Japan' => 2,
            'Tunisia' => 3,
            'Play-Off B' => 4,
        ]);

        $this->reorderGroup('I', [
            'Play-Off 2' => 1,
            'France' => 2,
            'Senegal' => 3,
            'Norway' => 4,
        ]);

        $this->reorderGroup('K', [
            'Play-Off 1' => 1,
            'Portugal' => 2,
            'Uzbekistan' => 3,
            'Colombia' => 4,
        ]);

        $specialSlots = [
            ['group' => 'A', 'position' => 4, 'name' => 'DEN/MKD/CZE/IRL'],
            ['group' => 'B', 'position' => 4, 'name' => 'ITA/NIR/WAL/BIH'],
            ['group' => 'D', 'position' => 4, 'name' => 'TUR/ROU/SVK/KOS'],
            ['group' => 'F', 'position' => 4, 'name' => 'UKR/SWE/POL/ALB'],
            ['group' => 'I', 'position' => 1, 'name' => 'BOL/SUR/IRQ'],
            ['group' => 'K', 'position' => 1, 'name' => 'NCL/JAM/COD'],
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
                ->where('name', $teamName)
                ->update(['group_position' => $position]);
        }
    }
}
