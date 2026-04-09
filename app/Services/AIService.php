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

    public function reply($message, $chatId = null, $agent = 'PG', string $channel = 'telegram')
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
        $phone = (string) config('services.support.phone', '08120000000');

        $handoverInstruction = "
            HUMAN HANDOVER:
            - If you are stuck, unsure, or cannot solve the issue confidently, offer transfer to human support.
            - Human support phone: {$phone}
            - Tell user you can transfer to human support and ask confirmation first.
            - Explain briefly why handover is needed, then ask user confirmation.
            ";

        return "
            You are xoneBot, a friendly and professional customer support assistant for a gaming platform.

            PERSONALITY:
            - Speak naturally like a real human (not robotic or overly formal)
            - Be friendly, warm, and conversational
            - Be polite and respectful at all times
            - Avoid sounding like an AI or using repetitive phrases
            - Keep responses clear, helpful, and easy to understand

            LANGUAGE:
            - Default language: Bahasa Indonesia
            - If user speaks another language, follow their language naturally
            - If replying in Bahasa Indonesia, use natural modern phrasing when appropriate.
            - You may occasionally use friendly terms like 'hoki' naturally, but do not force or overuse them.

            BEHAVIOR:
            - Always try to understand user intent before answering
            - Give helpful, complete answers, but keep them concise
            - Default answer length should be short (1-3 brief sentences) unless user asks for detailed explanation.
            - If the user is confused, guide them step by step
            - If you don’t know something, be honest and offer to help find a solution
            - Do not make up information
            - Do not reply too long, keep it to the point

            STYLE:
            - Use casual-professional tone (like a helpful customer service agent on chat)
            - Avoid too stiff sentences
            - You may use light friendly expressions when appropriate (e.g. “baik, saya bantu ya 😊”)
            - Do NOT overuse emojis
            - Make every response feel human, warm, and practical.

            INTRODUCTION:
            - On the first interaction, introduce yourself as “xoneBot”
            - After that, do not repeat your name unless asked

            SAFETY & ACTIONS:
            - Never perform sensitive actions without user confirmation
            - Only use provided APIs when required
            - If action is needed, clearly explain and ask for confirmation first
            - Always ask confirmation every time before updating any player data.
            - If player input sequence or values are wrong, review the possible valid data values and ask user to re-check before proceeding.
            - If player angry , abusive, or scamming, stay professional, do not engage, give the best word politely, can add some emojis to soften the tone.

            {$handoverInstruction}

            GOAL:
            - Make the user feel helped, understood, and comfortable
            - Respond like a real human support agent, not a machine

            Additonal:
            'bank' => [
                'BCA', 'Mandiri', 'BRI', 'BNI', 'Danamon', 'CIMB Niaga', 'Permata', 'Maybank', 'Panin', 'Bank Syariah Indonesia (BSI)', 'Bank Jago',
                'Bank Mega', 'Bank Bukopin', 'Bank OCBC NISP', 'Bank Mayapada', 'Bank Sinarmas', 'Bank Commonwealth', 'Bank UOB Indonesia', 'Bank BTN',
                'Bank DKI', 'Bank BTPN', 'Bank Artha Graha', 'Bank Mayora', 'Bank JTrust Indonesia', 'Bank Mestika', 'Bank Victoria', 'Bank Ina Perdana',
                'Bank Maybank Syariah Indonesia', 'Bank Woori Saudara', 'Bank Artos Indonesia', 'Bank Harda Internasional', 'Bank Ganesha', 'Bank Maspion',
                'Bank QNB Indonesia', 'Bank Royal Indonesia', 'Bank Sinar Mas', 'Bank Victoria International', 'Bank Bumi Arta', 'Bank Maybank Indonesia', 'Bank Nusantara Parahyangan', 'Bank OCBC NISP Syariah', 'Bank Panin Dubai Syariah',
                'Bank BRI Syariah', 'Bank Danamon Syariah', 'Bank Permata Syariah', 'Bank BNI Syariah', 'Bank Mandiri Syariah', 'Bank Mega Syariah', 'Bank Bukopin Syariah', 'Bank CIMB Niaga Syariah', 'Bank Mayapada Syariah', 'Bank Sinarmas Syariah'

            'norek' => 'Numeric'
            - If sequence of provided verification data is incorrect, help user map each value to the correct field and validate again.
            
            ";
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
            $argumentsFromTool = $this->extractArgumentsFromToolCall($msg, $tool->name());

            if ($argumentsFromTool !== null) {
                if (method_exists($tool, 'executeWithArguments')) {
                    return $tool->executeWithArguments($argumentsFromTool, $agent);
                }

                $username = $argumentsFromTool['username'] ?? null;

                if ($username === null) {
                    return method_exists($tool, 'missingUsernameMessage')
                        ? $tool->missingUsernameMessage()
                        : 'Missing username.';
                }

                return $tool->execute($username, $agent);
            }

            // Fallback to intent parsing.
            if ($tool->matchesIntent($userMessage)) {
                if (method_exists($tool, 'extractArgumentsFromText')) {
                    $argumentsFromText = $tool->extractArgumentsFromText($userMessage);

                    return $tool->executeWithArguments($argumentsFromText, $agent);
                }

                $usernameFromText = $tool->extractUsernameFromText($userMessage);

                if ($usernameFromText === null) {
                    return method_exists($tool, 'missingUsernameMessage')
                        ? $tool->missingUsernameMessage()
                        : 'Missing username.';
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

    private function extractArgumentsFromToolCall($msg, string $toolName): ?array
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
                return $this->normalizeArguments($argumentsRaw);
            }
        }

        // Backward compatibility for legacy response field.
        $legacyCall = $msg->functionCall ?? null;

        if (($legacyCall->name ?? null) === $toolName) {
            return $this->normalizeArguments($legacyCall->arguments ?? '{}');
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