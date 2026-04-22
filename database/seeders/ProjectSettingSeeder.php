<?php

namespace Database\Seeders;

use App\Models\ProjectSetting;
use Illuminate\Database\Seeder;

class ProjectSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Webhook
            [
                'key' => 'webhook_base_url',
                'value' => env('WEBHOOK_BASE_URL'),
                'label' => 'Base URL',
                'group' => 'webhook',
                'type' => 'url',
            ],

            // OpenAI
            [
                'key' => 'openai_api_key',
                'value' => env('OPENAI_API_KEY'),
                'label' => 'API Key',
                'group' => 'openai',
                'type' => 'secret',
            ],

            // Telegram
            [
                'key' => 'telegram_bot_token',
                'value' => env('TELEGRAM_BOT_TOKEN'),
                'label' => 'Bot Token',
                'group' => 'telegram',
                'type' => 'secret',
            ],

            // LiveChat
            [
                'key' => 'livechat_verify_token',
                'value' => env('LIVECHAT_VERIFY_TOKEN'),
                'label' => 'Verify Token',
                'group' => 'livechat',
                'type' => 'secret',
            ],
            [
                'key' => 'livechat_basic_token',
                'value' => env('LIVECHAT_BASIC_TOKEN'),
                'label' => 'API Basic Token (base64)',
                'group' => 'livechat',
                'type' => 'secret',
            ],
            [
                'key' => 'livechat_agent_id',
                'value' => env('LIVECHAT_AGENT_ID'),
                'label' => 'Agent ID (email)',
                'group' => 'livechat',
                'type' => 'text',
            ],

            // WhatsApp (WAHA)
            [
                'key' => 'whatsapp_base_url',
                'value' => env('WAHA_BASE_URL'),
                'label' => 'Base URL',
                'group' => 'whatsapp',
                'type' => 'url',
            ],
            [
                'key' => 'whatsapp_session',
                'value' => env('WAHA_SESSION', 'default'),
                'label' => 'Session',
                'group' => 'whatsapp',
                'type' => 'text',
            ],
            [
                'key' => 'whatsapp_api_key',
                'value' => env('WAHA_API_KEY'),
                'label' => 'API Key',
                'group' => 'whatsapp',
                'type' => 'secret',
            ],

            // Retention
            [
                'key' => 'conversation_retention_days',
                'value' => env('CONVERSATION_RETENTION_DAYS', '90'),
                'label' => 'Conversation Retention (days)',
                'group' => 'retention',
                'type' => 'number',
            ],
            [
                'key' => 'customer_memory_retention_days',
                'value' => env('CUSTOMER_MEMORY_RETENTION_DAYS', '90'),
                'label' => 'Customer Memory Retention (days)',
                'group' => 'retention',
                'type' => 'number',
            ],
        ];

        foreach ($settings as $setting) {
            ProjectSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
