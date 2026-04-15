<?php

namespace Database\Seeders;

use Database\Seeders\DataModelSeeder;
use Database\Seeders\ForbiddenBehaviourSeeder;
use Database\Seeders\ProjectSettingSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            DataModelSeeder::class,
            ToolSeeder::class,
            ProjectSettingSeeder::class,
            ChatAgentSeeder::class,
            ForbiddenBehaviourSeeder::class,
            DatabaseConnectionSeeder::class,
        ]);
    }
}
