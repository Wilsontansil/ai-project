<?php

namespace App\Services\Tools;

use App\Models\Player;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ResetPasswordTool
{
    /**
     * Get tool definition for OpenAI.
     */
    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'resetPassword',
                'description' => 'Reset user password to temporary password and display success message',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'username' => ['type' => 'string', 'description' => 'Username to reset password for']
                    ],
                    'required' => ['username']
                ]
            ]
        ];
    }

    /**
     * Get tool name identifier.
     */
    public function name(): string
    {
        return 'resetPassword';
    }

    /**
     * Check if message intent matches this tool.
     */
    public function matchesIntent(string $message): bool
    {
        $keywords = ['reset password', 'resetpass', 'kata sandi', 'password'];

        foreach ($keywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract username from user message.
     */
    public function extractUsernameFromText(string $message): ?string
    {
        if (preg_match('/username\s*[:=]?\s*([a-zA-Z0-9._-]+)/i', $message, $matches) === 1) {
            return $matches[1] ?? null;
        }

        return null;
    }

    /**
     * Execute tool: reset password for player.
     */
    public function execute(string $username, string $agent): string
    {
        $player = Player::where('username', $username)
            ->where('agent', $agent)
            ->first();

        if (!$player) {
            return "Username {$username} tidak ditemukan untuk agent {$agent}.";
        }

        try {
            $player->password = Hash::make('1234567');
            $player->save();
        } catch (\Throwable $e) {
            Log::error('Failed to reset player password', [
                'username' => $username,
                'agent' => $agent,
                'error' => $e->getMessage(),
            ]);

            return "Gagal reset password untuk username {$username} (agent {$agent}).";
        }

        return "Password untuk username {$username} (agent {$agent}) berhasil direset ke 1234567.";
    }

    /**
     * Get fallback message when username is missing.
     */
    public function missingUsernameMessage(): string
    {
        return "Untuk reset password, mohon kirim username dengan format: username: namakamu";
    }
}
