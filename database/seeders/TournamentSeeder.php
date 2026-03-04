<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tournament;
use Carbon\Carbon;

class TournamentSeeder extends Seeder
{
    public function run(): void
    {
        Tournament::updateOrCreate(

            [
                'year' => 2026
            ],

            [
                'name' => 'FIFA World Cup 2026',
                'deadline_at' => Carbon::parse('2026-06-11 19:00:00'),
                'status' => 'live',
                'type' => 'world_cup'
            ]

        );
    }
}
