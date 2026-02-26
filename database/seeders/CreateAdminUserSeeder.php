<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CreateAdminUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@example.com');
        $password = env('ADMIN_PASSWORD', 'secret');

        // avoid duplicating admin user
        $admin = User::where('email', $email)->first();

        if (! $admin) {
            User::factory()->create([
                'name' => 'Administrator',
                'email' => $email,
                'password' => bcrypt($password),
                'is_admin' => true,
            ]);
        } else {
            $admin->update(['is_admin' => true]);
        }
    }
}
