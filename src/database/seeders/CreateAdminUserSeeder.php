<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class CreateAdminUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@example.com');
        $password = env('ADMIN_PASSWORD');

        if (! is_string($password) || trim($password) === '') {
            throw new RuntimeException('ADMIN_PASSWORD is required to seed the admin user.');
        }

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
