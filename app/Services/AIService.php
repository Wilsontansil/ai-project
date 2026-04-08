<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use OpenAI;

class AIService
{
    private int $maxHistoryMessages = 20;

    private int $historyTtlHours = 12;

    public function reply($message, $chatId = null)
    {
        $apiKey = (string) config('services.openai.api_key', '');

        if ($apiKey === '') {
            return 'OpenAI API key is not configured. Please set OPENAI_API_KEY on server .env.';
        }

        $client = OpenAI::client($apiKey);

        // Define system prompt
        $systemPrompt = "Your name is xoneBot, always introduce yourself as xoneBot on the beginning of the chat or when asked. You are a polite, professional customer service AI for a gaming platform.
        Only use provided APIs for sensitive actions. Confirm with user before action.";

        // Define function/tool
        $functions = [
            [
                'name' => 'resetPassword',
                'description' => 'Reset user password',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'user_id' => ['type' => 'string', 'description' => 'User ID to reset password']
                    ],
                    'required' => ['user_id']
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

                $userId = $arguments['user_id'] ?? null;

                if ($userId) {
                    // Example: call internal API
                    // $this->resetPassword($userId);
                    $assistantReply = "Password reset for user ID {$userId} ✅";
                    $this->saveConversationTurn($chatId, $history, $message, $assistantReply);
                    return $assistantReply;
                }

                $assistantReply = "Missing user_id for reset password ⚠️";
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