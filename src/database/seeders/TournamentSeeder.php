<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tournament;
use Carbon\Carbon;

class TournamentSeeder extends Seeder
{
    public function run(): void
    {
        Tournament::create([
            'name' => 'FIFA World Cup',
            'year' => 2026,
            'host_countries' => json_encode([
                'United States',
                'Mexico',
                'Canada'
            ]),
            'deadline_at' => '2026-06-10 23:59:59',
            'status' => 'draft',
            'type' => 'world_cup'
        ]);
    }
}
