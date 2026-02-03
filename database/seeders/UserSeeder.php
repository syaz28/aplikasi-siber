<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

/**
 * UserSeeder - Shared Account Setup
 * 
 * Creates shared accounts for different roles:
 * - admin: Full system access
 * - petugas: Regular case entry
 * - pimpinan: Executive dashboard only
 * - admin_subdit: Subdit-specific administrators
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('👤 Seeding users...');

        $users = [
            [
                'username' => 'admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'subdit_access' => null,
            ],
            [
                'username' => 'petugas',
                'password' => Hash::make('password'),
                'role' => 'petugas',
                'subdit_access' => null,
            ],
            [
                'username' => 'pimpinan',
                'password' => Hash::make('password'),
                'role' => 'pimpinan',
                'subdit_access' => null,
            ],
            [
                'username' => 'subdit1',
                'password' => Hash::make('password'),
                'role' => 'admin_subdit',
                'subdit_access' => 1, // Siber Ekonomi
            ],
            [
                'username' => 'subdit2',
                'password' => Hash::make('password'),
                'role' => 'admin_subdit',
                'subdit_access' => 2, // Siber Sosial
            ],
            [
                'username' => 'subdit3',
                'password' => Hash::make('password'),
                'role' => 'admin_subdit',
                'subdit_access' => 3, // Siber Khusus
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
            $this->command->info("   ✓ Created user: {$userData['username']} (role: {$userData['role']})");
        }

        $this->command->info("   📊 Total users created: " . count($users));
    }
}
