<?php

namespace Database\Seeders;

use App\Models\DatabaseConnection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;

class DatabaseConnectionSeeder extends Seeder
{
    public function run(): void
    {
        DatabaseConnection::updateOrCreate(
            ['name' => 'mysqlgame'],
            [
                'driver'    => 'mysql',
                'host'      => '192.168.158.118',
                'port'      => 3306,
                'database'  => 'pilar',
                'username'  => 'gamegg',
                'password'  => 'ZqRcN6FGWaT3dPyE',
                'is_active' => true,
            ]
        );

        DatabaseConnection::updateOrCreate(
            ['name' => 'pgsqlgame'],
            [
                'driver'    => 'pgsql',
                'host'      => '54.255.226.39',
                'port'      => 5432,
                'database'  => 'empatd',
                'username'  => 'postgres',
                'password'  => '1dQB5KzRWM8F',
                'is_active' => true,
            ]
        );
    }
}
