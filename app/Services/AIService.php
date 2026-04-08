<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use OpenAI;
use App\Models\Player;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class AIService
{
    private int $maxHistoryMessages = 20;

    private int $historyTtlHours = 12;

    public function reply($message, $chatId = null, $agent = 'PG')
    {
        $apiKey = (string) config('services.openai.api_key', '');

        if ($apiKey === '') {
            return 'OpenAI API key is not configured. Please set OPENAI_API_KEY on server .env.';
        }

        $client = OpenAI::client($apiKey);

        // Define system prompt
        $systemPrompt = "Your name is xoneBot, always introduce yourself as xoneBot on the beginning of the chat or when asked. You are a polite, professional customer service AI for a gaming platform.
        Answer in Bahasa Indonesia by default, unless the user explicitly asks for another language.
        Only use provided APIs for sensitive actions. Confirm with user before action.";

        // Define function/tool
        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'resetPassword',
                    'description' => 'Reset user password',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'username' => ['type' => 'string', 'description' => 'Username to reset password']
                        ],
                        'required' => ['username']
                    ]
                ]
            ]
        ];

        $history = $this->loadConversationHistory($chatId);
        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $history,
            [['role' => 'user', 'content' => $message]]
        );

        // Send to OpenAI
        try {
            $response = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'tools' => $tools,
                'tool_choice' => 'auto'
            ]);

            $msg = $response->choices[0]->message;

            // Prefer model tool call, then fallback to local intent parsing.
            $usernameFromTool = $this->extractUsernameFromToolCall($msg);

            if ($usernameFromTool !== null) {
                $assistantReply = $this->performPasswordReset($usernameFromTool, $agent);
                $this->saveConversationTurn($chatId, $history, $message, $assistantReply);
                return $assistantReply;
            }

            if ($this->isResetPasswordIntent($message)) {
                $usernameFromText = $this->extractUsernameFromText($message);

                if ($usernameFromText === null) {
                    $assistantReply = "Untuk reset password, mohon kirim username dengan format: username: namakamu";
                    $this->saveConversationTurn($chatId, $history, $message, $assistantReply);
                    return $assistantReply;
                }

                $assistantReply = $this->performPasswordReset($usernameFromText, $agent);
                $this->saveConversationTurn($chatId, $history, $message, $assistantReply);
                return $assistantReply;
            }

            // Normal AI reply
            $assistantReply = $msg->content ?? "Sorry, I couldn't understand.";
            $this->saveConversationTurn($chatId, $history, $message, $assistantReply);
            return $assistantReply;

        } catch (\OpenAI\Exceptions\RateLimitException $e) {
            return "⚠️ System busy, please try again...";
        } catch (\Exception $e) {
            return "⚠️ Error: " . $e->getMessage();
        }
    }

    private function loadConversationHistory($chatId): array
    {
        if (!$chatId) {
            return [];
        }

        $history = Cache::get($this->historyKey($chatId), []);

        return is_array($history) ? $history : [];
    }

    private function saveConversationTurn($chatId, array $history, string $userMessage, string $assistantReply): void
    {
        if (!$chatId) {
            return;
        }

        $history[] = ['role' => 'user', 'content' => $userMessage];
        $history[] = ['role' => 'assistant', 'content' => $assistantReply];

        // Keep only recent messages to control token usage.
        $history = array_slice($history, -$this->maxHistoryMessages);

        Cache::put($this->historyKey($chatId), $history, now()->addHours($this->historyTtlHours));
    }

    private function historyKey($chatId): string
    {
        return 'chat_context:' . $chatId;
    }

    private function extractUsernameFromToolCall($msg): ?string
    {
        $toolCalls = $msg->toolCalls ?? [];

        if (is_array($toolCalls)) {
            foreach ($toolCalls as $toolCall) {
                $function = $toolCall->function ?? null;
                $name = $function->name ?? null;

                if ($name !== 'resetPassword') {
                    continue;
                }

                $argumentsRaw = $function->arguments ?? '{}';
                $arguments = $this->normalizeArguments($argumentsRaw);

                return $arguments['username'] ?? null;
            }
        }

        // Backward compatibility for legacy response field.
        $legacyCall = $msg->functionCall ?? null;

        if (($legacyCall->name ?? null) === 'resetPassword') {
            $arguments = $this->normalizeArguments($legacyCall->arguments ?? '{}');

            return $arguments['username'] ?? null;
        }

        return null;
    }

    private function normalizeArguments($argumentsRaw): array
    {
        if (is_string($argumentsRaw)) {
            $decoded = json_decode($argumentsRaw, true);

            return is_array($decoded) ? $decoded : [];
        }

        if (is_array($argumentsRaw)) {
            return $argumentsRaw;
        }

        return (array) $argumentsRaw;
    }

    private function isResetPasswordIntent(string $message): bool
    {
        $keywords = ['reset password', 'resetpass', 'kata sandi', 'password'];

        foreach ($keywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private function extractUsernameFromText(string $message): ?string
    {
        if (preg_match('/username\s*[:=]?\s*([a-zA-Z0-9._-]+)/i', $message, $matches) === 1) {
            return $matches[1] ?? null;
        }

        return null;
    }

    private function performPasswordReset(string $username, string $agent): string
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
}