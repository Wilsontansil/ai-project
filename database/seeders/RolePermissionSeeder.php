<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage users',
            'manage roles',
            'manage agents',
            'manage tools',
            'manage data-models',
            'manage database-connections',
            'manage agent-rules',
            'manage settings',
            'view metrics',
            'view customers',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Reset cache after creating permissions so syncPermissions can find them
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // God: all permissions
        $god = Role::findOrCreate('god', 'web');
        $god->syncPermissions($permissions);

        // Admin: all permissions except user and role management
        $adminPermissions = array_values(array_filter($permissions, fn ($p) => ! in_array($p, [
            'manage users',
            'manage roles',
        ])));
        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions($adminPermissions);

        // Operator: read-only customers + view metrics
        $operator = Role::findOrCreate('operator', 'web');
        $operator->syncPermissions([
            'view customers',
            'view metrics',
        ]);
    }
}
