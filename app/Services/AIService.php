<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use OpenAI;
use Illuminate\Support\Facades\Log;
use App\Services\Tools\ResetPasswordTool;
use App\Services\Tools\CheckSuspendTool;

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
        $systemPrompt = $this->getSystemPrompt();
        $tools = $this->getTools();

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

            // Try to handle tool call or local intent.
            $assistantReply = $this->handleToolCallOrIntent($msg, $message, $agent);

            if ($assistantReply !== null) {
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

    /**
     * Get the system prompt for xoneBot.
     */
    private function getSystemPrompt(): string
    {
        return "Your name is xoneBot, always introduce yourself as xoneBot on the beginning of the chat or when asked. You are a polite, professional customer service AI for a gaming platform.
        Answer in Bahasa Indonesia by default, unless the user explicitly asks for another language.
        Only use provided APIs for sensitive actions. Confirm with user before action.";
    }

    /**
     * Get available tools/functions for OpenAI.
     * Each tool service is registered here.
     */
    private function getTools(): array
    {
        $tools = [];

        // Register all tool services
        foreach ($this->getToolServices() as $toolService) {
            $tools[] = $toolService->definition();
        }

        return $tools;
    }

    /**
     * Get instances of all available tool services.
     */
    private function getToolServices(): array
    {
        return [
            new ResetPasswordTool(),
            new CheckSuspendTool(),
        ];
    }

    /**
     * Handle tool call or fallback to local intent parsing.
     * Returns null if tool/intent not matched.
     */
    private function handleToolCallOrIntent($msg, string $userMessage, string $agent): ?string
    {
        // Try to match tool by model call or local intent.
        foreach ($this->getToolServices() as $tool) {
            $usernameFromTool = $this->extractUsernameFromToolCall($msg, $tool->name());

            if ($usernameFromTool !== null) {
                return $tool->execute($usernameFromTool, $agent);
            }

            // Fallback to intent parsing.
            if ($tool->matchesIntent($userMessage)) {
                $usernameFromText = $tool->extractUsernameFromText($userMessage);

                if ($usernameFromText === null) {
                    return $tool->missingUsernameMessage();
                }

                return $tool->execute($usernameFromText, $agent);
            }
        }

        return null;
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

    private function extractUsernameFromToolCall($msg, string $toolName): ?string
    {
        $toolCalls = $msg->toolCalls ?? [];

        if (is_array($toolCalls)) {
            foreach ($toolCalls as $toolCall) {
                $function = $toolCall->function ?? null;
                $name = $function->name ?? null;

                if ($name !== $toolName) {
                    continue;
                }

                $argumentsRaw = $function->arguments ?? '{}';
                $arguments = $this->normalizeArguments($argumentsRaw);

                return $arguments['username'] ?? null;
            }
        }

        // Backward compatibility for legacy response field.
        $legacyCall = $msg->functionCall ?? null;

        if (($legacyCall->name ?? null) === $toolName) {
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
}