<?php

namespace App\Services\Tools;

use App\Models\Player;
use Illuminate\Support\Facades\Log;

class CheckSuspendTool
{
    // Command example:
    // cek suspend
    // Username: chowyunfat

    /**
     * Get tool definition for OpenAI.
     */
    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'checkSuspend',
                'description' => 'Check if a player account is suspended',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'username' => ['type' => 'string', 'description' => 'Username to check suspend status']
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
        return 'checkSuspend';
    }

    /**
     * Check if message intent matches this tool.
     */
    public function matchesIntent(string $message): bool
    {
        $keywords = ['suspend', 'check suspend', 'status', 'suspended', 'cek suspend', 'suspend status'];

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
     * Execute tool: check suspend status for player.
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
            $isSuspended = $player->is_suspended ?? false;

            if ($isSuspended) {
                $suspendReason = $player->suspend_reason ?? 'Tidak ada alasan yang diberikan';
                return "Akun {$username} (agent {$agent}) sedang di-suspend. Alasan: {$suspendReason}";
            }

            return "Akun {$username} (agent {$agent}) aktif dan tidak di-suspend.";
        } catch (\Throwable $e) {
            Log::error('Failed to check player suspend status', [
                'username' => $username,
                'agent' => $agent,
                'error' => $e->getMessage(),
            ]);

            return "Gagal memeriksa status suspend untuk username {$username} (agent {$agent}).";
        }
    }

    /**
     * Get fallback message when username is missing.
      * Also used as command template shown to user.
     */
    public function missingUsernameMessage(): string
    {
        return "Untuk cek status suspend, mohon kirim username dengan format: username: namakamu";
    }
}
