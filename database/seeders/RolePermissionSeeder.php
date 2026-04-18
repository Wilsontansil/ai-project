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
            'manage agents',
            'manage tools',
            'manage data-models',
            'manage database-connections',
            'manage forbidden-behaviours',
            'manage settings',
            'view metrics',
            'view customers',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Reset cache after creating permissions so syncPermissions can find them
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Admin: full access
        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions($permissions);

        // Operator: read-only customers + view metrics
        $operator = Role::findOrCreate('operator', 'web');
        $operator->syncPermissions([
            'view customers',
            'view metrics',
        ]);
    }
}
