<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure all permissions exist
        $permissions = [
            'manage agents',
            'manage agent-rules',
            'manage tools',
            'manage data-models',
            'manage settings',
            'manage database-connections',
            'view metrics',
            'manage users',
            'manage roles',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Ensure admin role exists and has all permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions($permissions);

        // Create or update the admin user
        $user = User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name'     => 'Backoffice Admin',
                'email'    => 'admin@xonebot.local',
                'password' => Hash::make('admin12345'),
            ]
        );

        if (! $user->hasRole('admin')) {
            $user->assignRole('admin');
        }
    }
}

