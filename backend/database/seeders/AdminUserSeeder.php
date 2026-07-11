<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * AdminUserSeeder
 * Creates the default super admin account.
 * Credentials should be changed immediately after first login.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@crm-social.com'],
            [
                'name'              => 'Super Admin',
                'password'          => Hash::make('Admin@12345'),
                'status'            => 'active',
                'timezone'          => 'UTC',
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('admin');

        // Demo user
        $user = User::firstOrCreate(
            ['email' => 'user@crm-social.com'],
            [
                'name'              => 'Demo User',
                'password'          => Hash::make('User@12345'),
                'status'            => 'active',
                'timezone'          => 'UTC',
                'email_verified_at' => now(),
            ]
        );

        $user->assignRole('user');

        $this->command->info('✓ Admin user: admin@crm-social.com / Admin@12345');
        $this->command->info('✓ Demo user:  user@crm-social.com / User@12345');
        $this->command->warn('⚠ Change these passwords before going to production!');
    }
}
