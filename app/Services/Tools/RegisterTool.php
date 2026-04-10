<?php

namespace App\Services\Tools;

use App\Models\Agent;
use App\Models\Player;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @deprecated Managed dynamically via App\Models\Tool. Do not delete — kept for reference.
 */
class RegisterTool
{
    // Command example:
    // register
    // Username(username): chowyunfat
    // Email(email): chow@gmail.com
    // Telepon(hp): 081234567890
    // Bank(bank): BCA
    // Nama Rekening(namarek): Chow Yun Fat
    // Nomor Rekening(norek): 1234567890

    /**
     * Get tool definition for OpenAI.
     */
    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'register',
                'description' => 'Register a new player account. Requires username, email, phone number (telepon/hp), bank name, account holder name (nama rekening), and account number (nomor rekening).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'username' => ['type' => 'string', 'description' => 'Username untuk akun baru'],
                        'email' => ['type' => 'string', 'description' => 'Email untuk akun baru'],
                        'hp' => ['type' => 'string', 'description' => 'Nomor telepon/HP'],
                        'bank' => ['type' => 'string', 'description' => 'Nama bank'],
                        'namarek' => ['type' => 'string', 'description' => 'Nama rekening'],
                        'norek' => ['type' => 'string', 'description' => 'Nomor rekening'],
                    ],
                    'required' => ['username', 'email', 'hp', 'bank', 'namarek', 'norek'],
                ],
            ],
        ];
    }

    /**
     * Get tool name identifier.
     */
    public function name(): string
    {
        return 'register';
    }

    /**
     * Check if message intent matches this tool.
     */
    public function matchesIntent(string $message): bool
    {
        $keywords = ['register', 'daftar', 'registrasi', 'buat akun', 'sign up', 'signup', 'mendaftar'];

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
     * Extract all registration fields from user message.
     */
    public function extractArgumentsFromText(string $message): array
    {
        return [
            'username' => $this->extractField($message, 'username'),
            'email' => $this->extractField($message, 'email'),
            'hp' => $this->extractField($message, 'hp') ?: $this->extractField($message, 'telepon'),
            'bank' => $this->extractField($message, 'bank'),
            'namarek' => $this->extractField($message, 'namarek'),
            'norek' => $this->extractField($message, 'norek'),
        ];
    }

    /**
     * Execute tool: register player (simple entry point).
     */
    public function execute(string $username, ?Agent $agent): string
    {
        return $this->executeWithArguments(['username' => $username], $agent);
    }

    /**
     * Execute tool: register new player with full data and duplicate checks.
     */
    public function executeWithArguments(array $arguments, ?Agent $agent): string
    {
        $agentKode = $agent ? $agent->kode : (string) config('services.agent.kode', 'PG');
        $username = mb_strtolower(trim((string) ($arguments['username'] ?? '')));
        $email = mb_strtolower(trim((string) ($arguments['email'] ?? '')));
        $hp = trim((string) ($arguments['hp'] ?? ''));
        $bank = trim((string) ($arguments['bank'] ?? ''));
        $namarek = trim((string) ($arguments['namarek'] ?? ''));
        $norek = trim((string) ($arguments['norek'] ?? ''));

        if ($username === '' || $email === '' || $hp === '' || $bank === '' || $namarek === '' || $norek === '') {
            return $this->missingUsernameMessage();
        }

        // Duplicate checks
        $duplicates = [];

        if (Player::whereRaw('LOWER(username) = ?', [$username])->where('agent', $agentKode)->exists()) {
            $duplicates[] = "Username \"{$username}\" sudah terdaftar";
        }

        if (Player::whereRaw('LOWER(email) = ?', [$email])->where('agent', $agentKode)->exists()) {
            $duplicates[] = "Email \"{$email}\" sudah terdaftar";
        }

        if (Player::where('hp', $hp)->where('agent', $agentKode)->exists()) {
            $duplicates[] = "Nomor telepon \"{$hp}\" sudah terdaftar";
        }

        if (Player::where('norek', $norek)->where('agent', $agentKode)->exists()) {
            $duplicates[] = "Nomor rekening \"{$norek}\" sudah terdaftar";
        }

        if ($duplicates !== []) {
            return "Registrasi gagal, data berikut sudah digunakan:\n- " . implode("\n- ", $duplicates)
                . "\n\nMohon gunakan data yang berbeda.";
        }

        try {
            if (!$agent) {
                return "Agent tidak ditemukan di sistem.";
            }

            Player::create([
                'name' => $username,
                'username' => $username,
                'email' => $email,
                'hp' => $hp,
                'bank' => $bank,
                'namarek' => $namarek,
                'norek' => $norek,
                'password' => Hash::make('1234567'),
                'playercode' => self::generateUniqueCode($agent->prefix),
                'agents_id' => $agent->id,
                'agent' => $agent->kode,
                'useragent' => 'AI-Bot',
                'browser' => 'AI-Bot',
                'os' => 'Server',
                'device' => 'Other',
                'devicefamily' => 'Other',
                'referer' => 'ai-agent',
                'status' => 1,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to register new player', [
                'username' => $username,
                'agent' => $agentKode,
                'error' => $e->getMessage(),
            ]);

            return "Gagal mendaftarkan akun untuk username {$username} (agent {$agentKode}). Silakan coba lagi.";
        }

        return "Registrasi berhasil! Akun baru telah dibuat:\n"
            . "Username: {$username}\n"
            . "Email: {$email}\n"
            . "Telepon: {$hp}\n"
            . "Bank: {$bank}\n"
            . "Nama Rekening: {$namarek}\n"
            . "Nomor Rekening: {$norek}\n"
            . "Password default: 1234567\n\n"
            . "Silakan login dan segera ganti password.";
    }

    /**
     * Get fallback message when fields are missing.
     */
    public function missingUsernameMessage(): string
    {
        return "Untuk registrasi akun baru, mohon kirim data berikut:\n"
            . "Username(username) :\n"
            . "Email(email) :\n"
            . "Telepon(hp) :\n"
            . "Bank(bank) :\n"
            . "Nama Rekening(namarek) :\n"
            . "Nomor Rekening(norek) :";
    }

    /**
     * Extract a single field value from free-form message text.
     */
    private function extractField(string $message, string $field): string
    {
        $pattern = '/(?:' . preg_quote($field, '/') . '(?:\s*\(' . preg_quote($field, '/') . '\))?)\s*:\s*(.+)/i';

        if (preg_match($pattern, $message, $matches) === 1) {
            return trim((string) ($matches[1] ?? ''));
        }

        return '';
    }

    private static function generateUniqueCode($agent)
    {
        $code = Str::upper($agent . Str::random(9));

        if (Player::where('playercode', $code)->exists()) {
            return self::generateUniqueCode($agent);
        }

        return $code;

    }
}
