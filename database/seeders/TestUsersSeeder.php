<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with many non-admin users.
     */
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

        // create 200 test participants
        User::factory()->count(200)->create([
            'is_admin' => false,
        ]);
    }
}
