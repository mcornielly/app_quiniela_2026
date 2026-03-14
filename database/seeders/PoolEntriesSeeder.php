<?php

namespace Database\Seeders;

use App\Models\PoolEntry;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Seeder;

class PoolEntriesSeeder extends Seeder
{
    public function run(): void
    {
        $tournament = Tournament::query()->first();

        if (!$tournament) {
            return;
        }

        $users = User::query()
            ->where('is_admin', false)
            ->orderBy('id')
            ->take(20)
            ->get();

        foreach ($users as $index => $user) {
            $entriesToCreate = $index < 6 ? 2 : 1;

            for ($entryNumber = 1; $entryNumber <= $entriesToCreate; $entryNumber++) {
                $completionPercent = $entryNumber === 1 ? 100 : fake()->numberBetween(72, 100);

                PoolEntry::query()->firstOrCreate(
                    [
                        'tournament_id' => $tournament->id,
                        'user_id' => $user->id,
                        'name' => "User {$user->id} Pool #{$entryNumber}",
                    ],
                    [
                        'status' => 'complete',
                        'completion_percent' => $completionPercent,
                        'exact_hits' => 0,
                        'correct_results' => 0,
                        'total_points' => 0,
                        'entry_fee' => 15,
                        'paid_at' => now()->subDays(fake()->numberBetween(1, 10)),
                        'payment_ref' => '000000' . fake()->numberBetween(1000, 9999),
                    ]
                );
            }
        }
    }
}
