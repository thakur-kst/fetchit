<?php

namespace Modules\DBCore\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\DBCore\Models\Core\User;

/**
 * User Seeder
 *
 * Populates the users table with:
 * - Fixed dev users (admin@example.com, user@example.com) with password "password"
 * - Random users with password (for testing)
 * - Google OAuth users (no local password)
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedFixedDevUsers();
        $this->seedRandomUsers();
        $this->seedGoogleOAuthUsers();
    }

    /**
     * Create fixed dev users for local login.
     * Skipped if they already exist (idempotent).
     */
    protected function seedFixedDevUsers(): void
    {
        $fixed = [
            [
                'name' => 'Admin User',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin@example.com',
                'password' => 'password',
                'status' => 'active',
            ],
            [
                'name' => 'Test User',
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'user@example.com',
                'password' => 'password',
                'status' => 'active',
            ],
        ];

        foreach ($fixed as $attrs) {
            if (User::where('email', $attrs['email'])->exists()) {
                continue;
            }

            $user = User::factory()->create([
                'name' => $attrs['name'],
                'first_name' => $attrs['first_name'],
                'last_name' => $attrs['last_name'],
                'email' => $attrs['email'],
                'password' => $attrs['password'],
                'status' => $attrs['status'],
            ]);
            $user->email_verified_at = now();
            $user->saveQuietly();
        }
    }

    /**
     * Create random users with password (for testing).
     */
    protected function seedRandomUsers(): void
    {
        User::factory()->count(5)->create();
    }

    /**
     * Create Google OAuth users (no local password).
     */
    protected function seedGoogleOAuthUsers(): void
    {
        User::factory()->count(2)->withGoogle()->create();
    }
}
