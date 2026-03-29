<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'mcornielly@gmail.com'],
            [
                'name' => 'Mcornielly',
                'password' => Hash::make('Jrmagda15'),
                'is_admin' => false,
            ]
        );

        $targetParticipants = 200;

        $existingParticipants = User::query()
            ->where('is_admin', false)
            ->where('email', '!=', 'mcornielly@gmail.com')
            ->count();

        $missingParticipants = max(0, $targetParticipants - $existingParticipants);

        if ($missingParticipants === 0) {
            return;
        }

        User::factory()
            ->count($missingParticipants)
            ->sequence(fn ($sequence) => [
                'email' => sprintf('testuser%03d@example.test', $existingParticipants + $sequence->index + 1),
                'is_admin' => false,
            ])
            ->create();
    }
}