<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PoolEntry;
use Illuminate\Support\Str;

class PoolEntriesSeeder extends Seeder
{
    public function run(): void
    {
        $tournamentId = 1;

        for ($userId = 1; $userId <= 20; $userId++) {

            PoolEntry::create([
                'tournament_id' => $tournamentId,
                'user_id' => $userId,
                'name' => "User {$userId} Pool",
                'status' => 'active',
                'completion_percent' => rand(60, 100),
                'total_points' => 0,

                // payment simulation
                'entry_fee' => 15,
                'paid_at' => now()->subDays(rand(1,10)),
                'payment_ref' => '000000' . rand(1000,9999),

                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
