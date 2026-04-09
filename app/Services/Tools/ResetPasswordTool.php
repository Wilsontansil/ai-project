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
                'description' => 'Reset user password after account data verification',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'username' => ['type' => 'string', 'description' => 'Username akun'],
                        'namarek' => ['type' => 'string', 'description' => 'Nama rekening'],
                        'norek' => ['type' => 'string', 'description' => 'Nomor rekening'],
                        'bank' => ['type' => 'string', 'description' => 'Nama bank']
                    ],
                    'required' => ['username', 'namarek', 'norek', 'bank']
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
     * Extract all verification fields from user message.
     */
    public function extractArgumentsFromText(string $message): array
    {
        return [
            'username' => $this->extractField($message, 'username'),
            'namarek' => $this->extractField($message, 'namarek'),
            'norek' => $this->extractField($message, 'norek'),
            'bank' => $this->extractField($message, 'bank'),
        ];
    }

    /**
     * Execute tool: reset password for player.
     */
    public function execute(string $username, string $agent): string
    {
        return $this->executeWithArguments(['username' => $username], $agent);
    }

    /**
     * Execute tool: reset password with full account verification.
     */
    public function executeWithArguments(array $arguments, string $agent): string
    {
        $username = trim((string) ($arguments['username'] ?? ''));
        $namarek = trim((string) ($arguments['namarek'] ?? ''));
        $norek = trim((string) ($arguments['norek'] ?? ''));
        $bank = trim((string) ($arguments['bank'] ?? ''));

        $usernameLower = mb_strtolower($username);
        $namarekLower = mb_strtolower($namarek);
        $bankLower = mb_strtolower($bank);

        if ($username === '' || $namarek === '' || $norek === '' || $bank === '') {
            return $this->missingUsernameMessage();
        }

        $playerByUsername = Player::whereRaw('LOWER(username) = ?', [$usernameLower])
            ->where('agent', $agent)
            ->first();

        if (!$playerByUsername) {
            return "Username {$username} tidak ditemukan untuk agent {$agent}.";
        }

        $hasNullableVerificationData =
            is_null($playerByUsername->namarek) || trim((string) $playerByUsername->namarek) === '' ||
            is_null($playerByUsername->norek) || trim((string) $playerByUsername->norek) === '' ||
            is_null($playerByUsername->bank) || trim((string) $playerByUsername->bank) === '';

        if ($hasNullableVerificationData) {
            return "Data verifikasi rekening untuk username {$username} belum lengkap (nullable). Silakan transfer ke human support untuk proses reset password.";
        }

        $player = Player::whereRaw('LOWER(username) = ?', [$usernameLower])
            ->where('agent', $agent)
            ->whereRaw('LOWER(namarek) = ?', [$namarekLower])
            ->where('norek', $norek)
            ->whereRaw('LOWER(bank) = ?', [$bankLower])
            ->first();

        if (!$player) {
            return "Data verifikasi tidak cocok untuk username {$username} (agent {$agent}).";
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
        return "Untuk reset password, mohon kirim data berikut:\n"
            . "Username(username) :\n"
            . "Nama rekening(namarek) :\n"
            . "Nomor rekening(norek) :\n"
            . "Nama Bank(bank) :";
    }

    private function extractField(string $message, string $field): string
    {
        $pattern = '/(?:' . preg_quote($field, '/') . '(?:\s*\(' . preg_quote($field, '/') . '\))?)\s*:\s*(.+)/i';

        if (preg_match($pattern, $message, $matches) === 1) {
            return trim((string) ($matches[1] ?? ''));
        }

        return '';
    }
}
