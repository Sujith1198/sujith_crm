<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * RolesPermissionsSeeder
 * Creates Admin and User roles with full permission sets.
 * Admin gets all permissions; User gets only content-related ones.
 */
class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ──────────────────────────────────────────────
        // Define all permissions (module → action)
        // ──────────────────────────────────────────────
        $permissions = [
            // Dashboard
            ['name' => 'view dashboard',     'module' => 'dashboard'],

            // Users
            ['name' => 'view users',         'module' => 'users'],
            ['name' => 'create users',       'module' => 'users'],
            ['name' => 'edit users',         'module' => 'users'],
            ['name' => 'delete users',       'module' => 'users'],
            ['name' => 'disable users',      'module' => 'users'],
            ['name' => 'reset user password','module' => 'users'],
            ['name' => 'assign roles',       'module' => 'users'],

            // Posts
            ['name' => 'view all posts',     'module' => 'posts'],
            ['name' => 'view own posts',     'module' => 'posts'],
            ['name' => 'create posts',       'module' => 'posts'],
            ['name' => 'edit posts',         'module' => 'posts'],
            ['name' => 'delete posts',       'module' => 'posts'],
            ['name' => 'schedule posts',     'module' => 'posts'],
            ['name' => 'publish posts',      'module' => 'posts'],

            // Social Accounts
            ['name' => 'connect facebook',   'module' => 'social'],
            ['name' => 'connect instagram',  'module' => 'social'],
            ['name' => 'disconnect accounts','module' => 'social'],

            // Analytics
            ['name' => 'view analytics',     'module' => 'analytics'],
            ['name' => 'view own analytics', 'module' => 'analytics'],

            // Reports
            ['name' => 'export reports',     'module' => 'reports'],

            // Settings
            ['name' => 'view settings',      'module' => 'settings'],
            ['name' => 'edit settings',      'module' => 'settings'],

            // Activity Logs
            ['name' => 'view activity logs', 'module' => 'logs'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'api'],
                ['module' => $permission['module']]
            );
        }

        // ──────────────────────────────────────────────
        // Create Roles
        // ──────────────────────────────────────────────

        // Admin — all permissions
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'api'],
            ['description' => 'Full system access', 'is_system' => true]
        );
        $adminRole->syncPermissions(Permission::where('guard_name', 'api')->get());

        // User — limited permissions
        $userRole = Role::firstOrCreate(
            ['name' => 'user', 'guard_name' => 'api'],
            ['description' => 'Standard user access', 'is_system' => true]
        );
        $userRole->syncPermissions([
            'view dashboard',
            'view own posts',
            'create posts',
            'edit posts',
            'delete posts',
            'schedule posts',
            'connect facebook',
            'connect instagram',
            'disconnect accounts',
            'view own analytics',
        ]);

        $this->command->info('✓ Roles and permissions seeded successfully.');
    }
}
