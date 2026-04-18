<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'admin@xonebot.local'],
            [
                'name' => 'Backoffice Admin',
                'password' => Hash::make('admin12345'),
            ]
        );

        if (! $user->hasRole('admin')) {
            $user->assignRole('admin');
        }
    }
}
