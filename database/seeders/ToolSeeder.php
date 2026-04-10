<?php

namespace Database\Seeders;

use App\Models\Tool;
use Illuminate\Database\Seeder;

class ToolSeeder extends Seeder
{
    public function run(): void
    {
        $tools = [
            [
                'tool_name' => '_bot_config',
                'display_name' => 'Bot Config',
                'description' => 'General bot configuration',
                'class_name' => '',
                'slug' => '_bot-config',
                'is_enabled' => false,
                'meta' => ['bot_name' => 'xoneBot'],
            ],
            [
                'tool_name' => 'resetPassword',
                'display_name' => 'Reset Password',
                'description' => 'Tool untuk verifikasi data rekening dan reset password player.',
                'class_name' => 'App\\Services\\Tools\\ResetPasswordTool',
                'slug' => 'reset-password',
                'is_enabled' => true,
                'meta' => [
                    'icon' => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
                ],
            ],
            [
                'tool_name' => 'checkSuspend',
                'display_name' => 'Check Suspend',
                'description' => 'Tool untuk cek status suspend akun player.',
                'class_name' => 'App\\Services\\Tools\\CheckSuspendTool',
                'slug' => 'check-suspend',
                'is_enabled' => true,
                'meta' => [
                    'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
                ],
            ],
            [
                'tool_name' => 'register',
                'display_name' => 'Register',
                'description' => 'Tool untuk registrasi akun player baru.',
                'class_name' => 'App\\Services\\Tools\\RegisterTool',
                'slug' => 'register',
                'is_enabled' => true,
                'meta' => [
                    'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
                ],
            ],
        ];

        foreach ($tools as $tool) {
            Tool::query()->updateOrCreate(
                ['tool_name' => $tool['tool_name']],
                $tool
            );
        }
    }
}
