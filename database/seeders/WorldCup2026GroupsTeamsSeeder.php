<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Tournament;
use App\Models\WorldCupGroup;
use App\Models\Team;

class WorldCup2026GroupsTeamsSeeder extends Seeder
{
    public function run(): void
    {
        // Torneo
        $tournament = Tournament::query()->firstOrCreate(
            ['year' => 2026],
            [
                'name' => 'Copa Mundial de la FIFA 2026',
                // ajusta la fecha límite cuando la definas
                'deadline_at' => now()->addDays(30),
                'status' => 'draft',
            ]
        );

        // Grupos + Equipos (según tu imagen)
        $data = [
            'A' => ['México', 'Korea del Sur', 'Sudáfrica', 'Repechaje UEFA'],
            'B' => ['Canadá', 'Suiza', 'Qatar', 'Repechaje UEFA'],
            'C' => ['Brasil', 'Marruecos', 'Escocia', 'Haití'],
            'D' => ['Estados Unidos', 'Australia', 'Paraguay', 'Repechaje UEFA'],
            'E' => ['Alemania', 'Ecuador', 'Costa Marfil', 'Curazao'],
            'F' => ['Países Bajos', 'Japón', 'Túnez', 'Repechaje UEFA'],
            'G' => ['Bélgica', 'Irán', 'Egipto', 'Nueva Zelanda'],
            'H' => ['España', 'Uruguay', 'Arabia Saudita', 'Cabo Verde'],
            'I' => ['Francia', 'Senegal', 'Noruega', 'Repechaje FIFA'],
            'J' => ['Argentina', 'Austria', 'Argelia', 'Jordan'],
            'K' => ['Portugal', 'Colombia', 'Uzbekistan', 'Repechaje FIFA'],
            'L' => ['Inglaterra', 'Croacia', 'Panamá', 'Ghana'],
        ];

        foreach ($data as $groupCode => $teams) {
            $group = WorldCupGroup::query()->updateOrCreate(
                [
                    'tournament_id' => $tournament->id,
                    'code' => $groupCode,
                ],
                [
                    'name' => 'GRUPO ' . $groupCode,
                ]
            );

            foreach ($teams as $teamName) {
                $normalized = $this->normalizeTeam($teamName);

                Team::query()->updateOrCreate(
                    [
                        'tournament_id' => $tournament->id,
                        'name' => $normalized['name'],
                    ],
                    [
                        'world_cup_group_id' => $group->id,
                        'short_code' => $normalized['short_code'],
                        'is_placeholder' => $normalized['is_placeholder'],
                        'flag_url' => null, // luego lo llenamos desde API si quieres
                    ]
                );
            }
        }
    }

    private function normalizeTeam(string $name): array
    {
        $name = trim($name);
        $lower = Str::lower($name);

        $isPlaceholder = Str::contains($lower, 'repechaje');

        $short = match ($lower) {
            'méxico' => 'MEX',
            'korea del sur' => 'KOR',
            'sudáfrica' => 'RSA',
            'repechaje uefa' => 'UEFA-R',

            'canadá' => 'CAN',
            'suiza' => 'SUI',
            'qatar' => 'QAT',

            'brasil' => 'BRA',
            'marruecos' => 'MAR',
            'escocia' => 'SCO',
            'haití' => 'HAI',

            'estados unidos' => 'USA',
            'australia' => 'AUS',
            'paraguay' => 'PAR',

            'alemania' => 'GER',
            'ecuador' => 'ECU',
            'costa marfil' => 'CIV',
            'curazao' => 'CUW',

            'países bajos' => 'NED',
            'japón' => 'JPN',
            'túnez' => 'TUN',

            'bélgica' => 'BEL',
            'irán' => 'IRN',
            'egipto' => 'EGY',
            'nueva zelanda' => 'NZL',

            'españa' => 'ESP',
            'uruguay' => 'URU',
            'arabia saudita' => 'KSA',
            'cabo verde' => 'CPV',

            'francia' => 'FRA',
            'senegal' => 'SEN',
            'noruega' => 'NOR',
            'repechaje fifa' => 'FIFA-R',

            'argentina' => 'ARG',
            'austria' => 'AUT',
            'argelia' => 'ALG',
            'jordan' => 'JOR',

            'portugal' => 'POR',
            'colombia' => 'COL',
            'uzbekistan' => 'UZB',

            'inglaterra' => 'ENG',
            'croacia' => 'CRO',
            'panamá' => 'PAN',
            'ghana' => 'GHA',

            default => $isPlaceholder
                ? 'PLAYOFF'
                : Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 3)),
        };

        return [
            'name' => $name,
            'short_code' => $short,
            'is_placeholder' => $isPlaceholder,
        ];
    }
}
