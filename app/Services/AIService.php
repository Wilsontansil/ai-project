<?php

namespace App\Services;

use OpenAI;

class AIService
{
    public function reply($message, $chatId = null)
    {
        $client = OpenAI::client(env('OPENAI_API_KEY'));

        // Define system prompt
        $systemPrompt = "You are a polite, professional customer service AI for a gaming platform.
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

        // Send to OpenAI
        try {
            $response = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $message]
                ],
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
                    return "Password reset for user ID {$userId} ✅";
                }

                return "Missing user_id for reset password ⚠️";
            }

            // Normal AI reply
            return $msg->content ?? "Sorry, I couldn't understand.";

        } catch (\OpenAI\Exceptions\RateLimitException $e) {
            return "⚠️ System busy, please try again...";
        } catch (\Exception $e) {
            return "⚠️ Error: " . $e->getMessage();
        }
    }
}