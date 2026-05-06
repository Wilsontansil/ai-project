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
                'password' => '12345678',
                'role'     => 'admin',
            ],
            [
                'username' => 'joyko',
                'name'     => 'joyko',
                'email'    => 'joyko@gmail.com',
                'password' => '12345678',
                'role'     => 'admin',
            ],
            [
                'username' => 'lord',
                'name'     => 'lord',
                'email'    => 'lord@gmail.com',
                'password' => '12345678',
                'role'     => 'admin',
            ],
            [
                'username' => 'mateo',
                'name'     => 'mateo',
                'email'    => 'mateo@gmail.com',
                'password' => '12345678',
                'role'     => 'admin',
            ],
            [
                'username' => 'operator',
                'name'     => 'operator',
                'email'    => 'op@gmail.com',
                'password' => '12345678',
                'role'     => 'operator',
            ],
            [
                'username' => 'operator1',
                'name'     => 'operator1',
                'email'    => 'operator1@gmail.com',
                'password' => '12345678',
                'role'     => 'operator',
            ],
            [
                'username' => 'operator2',
                'name'     => 'operator2',
                'email'    => 'operator2@gmail.com',
                'password' => '12345678',
                'role'     => 'operator',
            ],
            [
                'username' => 'operator3',
                'name'     => 'operator3',
                'email'    => 'operator3@gmail.com',
                'password' => '12345678',
                'role'     => 'operator',
            ],
            [
                'username' => 'operator4',
                'name'     => 'operator4',
                'email'    => 'operator4@gmail.com',
                'password' => '12345678',
                'role'     => 'operator',
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

