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
        $functions = [
            [
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
                'functions' => $functions,
                'function_call' => 'auto'
            ]);

            $msg = $response->choices[0]->message;

            // The SDK returns objects, not arrays.
            $functionCall = $msg->functionCall ?? null;

            if ($functionCall && ($functionCall->name ?? null) === 'resetPassword') {
                $argumentsRaw = $functionCall->arguments ?? '{}';
                $arguments = is_string($argumentsRaw)
                    ? json_decode($argumentsRaw, true)
                    : (array) $argumentsRaw;

                $username = $arguments['username'] ?? null;

                if ($username) {
                    $player = Player::where('username', $username)
                        ->where('agent', $agent)
                        ->first();
                    Log::info('Player ' . json_encode($player));
                    if (!$player) {
                        $assistantReply = "Username {$username} tidak ditemukan untuk agent {$agent}.";
                        $this->saveConversationTurn($chatId, $history, $message, $assistantReply);
                        return $assistantReply;
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

                        $assistantReply = "Gagal reset password untuk username {$username} (agent {$agent}).";
                        $this->saveConversationTurn($chatId, $history, $message, $assistantReply);
                        return $assistantReply;
                    }

                    $assistantReply = "Password untuk username {$username} (agent {$agent}) berhasil direset ke 1234567.";
                    $this->saveConversationTurn($chatId, $history, $message, $assistantReply);
                    return $assistantReply;
                }

                $assistantReply = "Missing username for reset password ⚠️";
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
}