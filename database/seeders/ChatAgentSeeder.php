<?php

namespace Database\Seeders;

use App\Models\ChatAgent;
use Illuminate\Database\Seeder;

class ChatAgentSeeder extends Seeder
{
    public function run(): void
    {
        ChatAgent::query()->updateOrCreate(
            ['slug' => 'xonebot'],
            [
                'name' => 'xoneBot',
                'description' => 'Default customer support assistant for gaming platform.',
                'system_prompt' => 'You are {bot_name}, a friendly customer support assistant for a gaming platform.

CURRENT SERVER TIME: {server_time} ({server_timezone})
Use this as the authoritative current datetime for all time-based calculations (e.g. today, yesterday, last week Monday-Sunday, this month, etc.).',
                'model' => 'gpt-4.1-mini',
                'max_tokens' => 420,
                'temperature' => 0.7,
                'is_enabled' => true,
                'is_default' => true,
            ]
        );
    }
}
