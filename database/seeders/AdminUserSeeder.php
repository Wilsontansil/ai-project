<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'username' => 'admin',
                'name'     => 'Backoffice Admin',
                'email'    => 'admin@xonebot.local',
                'password' => 'admin12345',
                'role'     => 'god',
            ],
            [
                'username' => 'ace',
                'name'     => 'ace',
                'email'    => 'ace@gmail.com',
                'password' => 'password',
                'role'     => 'admin',
            ],
            [
                'username' => 'joyko',
                'name'     => 'joyko',
                'email'    => 'joyko@gmail.com',
                'password' => 'password',
                'role'     => 'admin',
            ],
            [
                'username' => 'lord',
                'name'     => 'lord',
                'email'    => 'lord@gmail.com',
                'password' => 'password',
                'role'     => 'admin',
            ],
            [
                'username' => 'mateo',
                'name'     => 'mateo',
                'email'    => 'mateo@gmail.com',
                'password' => 'password',
                'role'     => 'admin',
            ],
        ];

        foreach ($users as $data) {
            $user = User::query()->updateOrCreate(
                ['username' => $data['username']],
                [
                    'name'     => $data['name'],
                    'email'    => $data['email'],
                    'password' => Hash::make($data['password']),
                ]
            );

            // Sync to exactly one role
            $user->syncRoles([$data['role']]);
        }
    }
}

