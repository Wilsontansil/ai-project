<?php

namespace App\Services;

use App\Models\ToolSetting;
use App\Services\Tools\CheckSuspendTool;
use App\Services\Tools\ResetPasswordTool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use OpenAI;

class AIService
{
    private int $maxHistoryMessages = 20;

    private int $historyTtlHours = 12;

    private int $debounceSeconds = 2;

    /**
     * Main AI entrypoint.
     * Builds context, executes model call, runs tool logic, and returns formatted reply.
     */
    public function reply($message, $chatId = null, $agent = 'PG', string $channel = 'telegram', array $agentContext = [])
    {
        $apiKey = (string) config('services.openai.api_key', '');

        if ($apiKey === '') {
            return $this->formatReply('OpenAI API key is not configured. Please set OPENAI_API_KEY on server .env.');
        }

        $client = OpenAI::client($apiKey);
        $systemPrompt = $this->getSystemPrompt();
        $tools = $this->getTools();

        $history = $this->loadConversationHistory($chatId);
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        $contextPrompt = $this->buildAgentContextPrompt($agentContext);
        if ($contextPrompt !== null) {
            $messages[] = ['role' => 'system', 'content' => $contextPrompt];
        }

        $messages = array_merge($messages, $history, [['role' => 'user', 'content' => $message]]);

        // Send to OpenAI
        try {
            $payload = [
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                // Allow fuller answers to avoid confusing, cut-off responses.
                'max_tokens' => 420,
            ];

            if ($tools !== []) {
                $payload['tools'] = $tools;
                $payload['tool_choice'] = 'auto';
            }

            $response = $client->chat()->create($payload);

            $msg = $response->choices[0]->message;
            $finishReason = (string) ($response->choices[0]->finishReason ?? '');

            // Try to handle tool call or local intent.
            $assistantReply = $this->handleToolCallOrIntent($msg, $message, $agent);

            if ($assistantReply !== null) {
                $assistantReply = $this->prepareAssistantReply($history, $assistantReply);
                $this->saveConversationTurn($chatId, $history, $message, $assistantReply);
                return $assistantReply;
            }

            // Normal AI reply
            $assistantReply = $msg->content ?? "Sorry, I couldn't understand.";

            if ($finishReason === 'length') {
                $assistantReply .= "\n\nJika jawaban ini masih terpotong, balas: lanjut.";
            }

            $assistantReply = $this->prepareAssistantReply($history, $assistantReply);
            $this->saveConversationTurn($chatId, $history, $message, $assistantReply);
            return $assistantReply;

        } catch (\OpenAI\Exceptions\RateLimitException $e) {
            return $this->formatReply("⚠️ System busy, please try again...");
        } catch (\Exception $e) {
            return $this->formatReply("⚠️ Error: " . $e->getMessage());
        }
    }

    /**
     * Build compact system context for personalization from DB-backed profile/memory.
     */
    private function buildAgentContextPrompt(array $context): ?string
    {
        if ($context === []) {
            return null;
        }

        $profile = (array) ($context['customer_profile'] ?? []);
        $behavior = (array) ($context['behavior'] ?? []);
        $recentConversation = trim((string) ($context['recent_conversation'] ?? ''));
        $relevantKnowledge = trim((string) ($context['relevant_knowledge'] ?? ''));

        $parts = [
            'Customer context from internal CRM memory (do not expose raw internals to user):',
        ];

        if ($profile !== []) {
            $parts[] = 'Profile: ' . json_encode([
                'platform' => $profile['platform'] ?? null,
                'name' => $profile['name'] ?? null,
                'total_messages' => $profile['total_messages'] ?? null,
                'tags' => $profile['tags'] ?? [],
            ], JSON_UNESCAPED_UNICODE);
        }

        if ($behavior !== []) {
            $parts[] = 'Behavior: ' . json_encode([
                'intent' => $behavior['intent'] ?? null,
                'sentiment' => $behavior['sentiment'] ?? null,
                'frequency_score' => $behavior['frequency_score'] ?? null,
            ], JSON_UNESCAPED_UNICODE);
        }

        if ($recentConversation !== '') {
            $parts[] = "Recent conversation:\n" . mb_substr($recentConversation, 0, 1200);
        }

        if ($relevantKnowledge !== '') {
            $parts[] = "Relevant knowledge:\n" . mb_substr($relevantKnowledge, 0, 1000);
        }

        $parts[] = 'Use this only to personalize response, keep answer concise and natural.';

        return implode("\n\n", $parts);
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
            - Always check your reply make readable and tidy before sending, avoid messy formatting or long unbroken text blocks.

            STYLE:
            - Use casual-professional tone (like a helpful customer service agent on chat)
            - Avoid too stiff sentences
            - You may use light friendly expressions when appropriate (e.g. “baik, saya bantu ya 😊”)
            - Make every response feel human, warm, and practical.
            - Keep formatting tidy: proper spacing, no messy line breaks.

            INTRODUCTION:
            - On the first interaction, introduce yourself as “xoneBot”
            - After that, do not repeat your name unless asked

            SAFETY & ACTIONS:
            - Never perform sensitive actions without user confirmation
            - Only use provided APIs when required
            - If action is needed, clearly explain and ask for confirmation first
            - Always ask confirmation every time before updating any player data.
            - If player input sequence or values are wrong, review the possible valid data values and ask user to re-check before proceeding.[IMPORTANT]
            - If player angry , abusive, or scamming, stay professional, do not engage, give the best word politely, can add some emojis to soften the tone.

            {$handoverInstruction}

            GOAL:
            - Make the user feel helped, understood, and comfortable
            - Respond like a real human support agent, not a machine

            Additonal instructions for tools:
            'bank' => [
                'BCA', 'Mandiri', 'BRI', 'BNI', 'Danamon', 'CIMB Niaga', 'Permata', 'Maybank', 'Panin', 'Bank Syariah Indonesia (BSI)', 'Bank Jago',
                'Bank Mega', 'Bank Bukopin', 'Bank OCBC NISP', 'Bank Mayapada', 'Bank Sinarmas', 'Bank Commonwealth', 'Bank UOB Indonesia', 'Bank BTN',
                'Bank DKI', 'Bank BTPN', 'Bank Artha Graha', 'Bank Mayora', 'Bank JTrust Indonesia', 'Bank Mestika', 'Bank Victoria', 'Bank Ina Perdana',
                'Bank Maybank Syariah Indonesia', 'Bank Woori Saudara', 'Bank Artos Indonesia', 'Bank Harda Internasional', 'Bank Ganesha', 'Bank Maspion',
                'Bank QNB Indonesia', 'Bank Royal Indonesia', 'Bank Sinar Mas', 'Bank Victoria International', 'Bank Bumi Arta', 'Bank Maybank Indonesia', 'Bank Nusantara Parahyangan', 'Bank OCBC NISP Syariah', 'Bank Panin Dubai Syariah',
                'Bank BRI Syariah', 'Bank Danamon Syariah', 'Bank Permata Syariah', 'Bank BNI Syariah', 'Bank Mandiri Syariah', 'Bank Mega Syariah', 'Bank Bukopin Syariah', 'Bank CIMB Niaga Syariah', 'Bank Mayapada Syariah', 'Bank Sinarmas Syariah'

            'norek' => 'Numeric'
            ";
    }

    /**
     * Get available tools/functions for OpenAI.
     * Each tool service is registered here.
     *
     * Command map (human-readable):
     * - resetPassword:
     *   Username(username): <value>
     *   Nama rekening(namarek): <value>
     *   Nomor rekening(norek): <value>
     *   Nama Bank(bank): <value>
     * - checkSuspend:
     *   Username: <value>
     */
    private function getTools(): array
    {
        $tools = [];

        // Register all tool services (tool schema comes from each tool class).
        foreach ($this->getToolServices() as $toolService) {
            $tools[] = $toolService->definition();
        }

        return $tools;
    }

    /**
     * Get instances of all available tool services.
        * Add new tool classes here when introducing new commands.
     */
    private function getToolServices(): array
    {
        $catalog = [
            new ResetPasswordTool(),
            new CheckSuspendTool(),
        ];

        if (!Schema::hasTable('tool_settings')) {
            return $catalog;
        }

        $enabledMap = ToolSetting::query()->pluck('is_enabled', 'tool_name')->toArray();

        if ($enabledMap === []) {
            return $catalog;
        }

        $filtered = [];
        foreach ($catalog as $tool) {
            $name = $tool->name();
            $isEnabled = array_key_exists($name, $enabledMap)
                ? (bool) $enabledMap[$name]
                : true;

            if ($isEnabled) {
                $filtered[] = $tool;
            }
        }

        return $filtered;
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

    /**
     * Load cached conversation history for a chat.
     */
    private function loadConversationHistory($chatId): array
    {
        if (!$chatId) {
            return [];
        }

        $history = Cache::get($this->historyKey($chatId), []);

        return is_array($history) ? $history : [];
    }

    /**
     * Save user + assistant turn into cached history.
     */
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

    /**
     * Build cache key for per-chat context storage.
     */
    private function historyKey($chatId): string
    {
        return 'chat_context:' . $chatId;
    }

    /**
     * Extract tool call arguments from OpenAI response for a specific tool name.
     */
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

    /**
     * Normalize tool arguments to associative array.
     */
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

    /**
     * Debounce rapid incoming messages and combine them before AI processing.
     * Returns null for non-leader requests while waiting for the buffer window.
     */
    public function collectDebouncedMessage(string $chatId, string $message): ?string
    {
        $chatId = trim($chatId);

        if ($chatId === '') {
            return trim($message);
        }

        $text = trim($message);
        if ($text === '') {
            return null;
        }

        $bufferKey = 'chat:debounce:buffer:' . $chatId;
        $leaderKey = 'chat:debounce:leader:' . $chatId;

        $buffer = Cache::get($bufferKey, []);
        $buffer[] = [
            'message' => $text,
            'at' => now()->timestamp,
        ];

        Cache::put($bufferKey, $buffer, now()->addMinutes(2));

        $isLeader = Cache::add($leaderKey, 1, now()->addSeconds($this->debounceSeconds + 2));

        if (!$isLeader) {
            return null;
        }

        usleep($this->debounceSeconds * 1000000);

        $messages = Cache::get($bufferKey, []);
        Cache::forget($bufferKey);
        Cache::forget($leaderKey);

        if (!is_array($messages) || $messages === []) {
            return $text;
        }

        $parts = [];
        foreach ($messages as $item) {
            $part = trim((string) ($item['message'] ?? ''));
            if ($part !== '') {
                $parts[] = $part;
            }
        }

        $parts = array_values(array_unique($parts));

        return $parts === [] ? $text : implode("\n", $parts);
    }

    /**
     * Apply final formatting rules to outgoing assistant text.
     */
    private function formatReply(string $reply): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $reply);
        $normalized = $this->formatInlineVerificationList($normalized);
        $lines = array_map(static fn ($line) => trim((string) $line), explode("\n", $normalized));

        $tidyLines = [];
        $lastBlank = false;

        foreach ($lines as $line) {
            $line = preg_replace('/[ \t]+/', ' ', $line) ?? $line;
            $isBlank = $line === '';

            if ($isBlank) {
                if (!$lastBlank) {
                    $tidyLines[] = '';
                }
                $lastBlank = true;
                continue;
            }

            $tidyLines[] = $line;
            $lastBlank = false;
        }

        $tidy = trim(implode("\n", $tidyLines));

        if ($this->isStructuredDataRequest($tidy)) {
            return $tidy;
        }

        // Keep reply complete; only hard-limit extremely long output.
        if (mb_strlen($tidy) <= 1400) {
            return $tidy;
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', trim(preg_replace('/\s+/', ' ', $tidy) ?? $tidy)) ?: [];
        $sentences = array_values(array_filter(array_map('trim', $sentences), fn ($s) => $s !== ''));

        $chunks = [];
        $length = 0;

        foreach ($sentences as $sentence) {
            $segmentLength = mb_strlen($sentence) + ($length > 0 ? 1 : 0);

            if ($length + $segmentLength > 1400) {
                break;
            }

            $chunks[] = $sentence;
            $length += $segmentLength;
        }

        if ($chunks !== []) {
            return implode(' ', $chunks) . "\n\n(Pesan dipersingkat karena terlalu panjang.)";
        }

        return mb_substr($tidy, 0, 1400) . "\n\n(Pesan dipersingkat karena terlalu panjang.)";
    }

    /**
     * Prepare assistant reply with formatting and anti-repeat protection.
     */
    private function prepareAssistantReply(array $history, string $reply): string
    {
        $formatted = $this->formatReply($reply);

        if ($this->isRepeatedAssistantReply($history, $formatted)) {
            return 'Siap, saya lanjut dari data terbaru kamu ya.';
        }

        return $formatted;
    }

    /**
     * Detect whether the new reply is identical to last assistant message.
     */
    private function isRepeatedAssistantReply(array $history, string $reply): bool
    {
        for ($i = count($history) - 1; $i >= 0; $i--) {
            $item = $history[$i] ?? null;

            if (!is_array($item)) {
                continue;
            }

            if (($item['role'] ?? '') !== 'assistant') {
                continue;
            }

            $lastAssistant = trim((string) ($item['content'] ?? ''));

            return mb_strtolower($lastAssistant) === mb_strtolower(trim($reply));
        }

        return false;
    }

    /**
     * Convert inline numbered verification list into multiline format.
     */
    private function formatInlineVerificationList(string $text): string
    {
        // Convert one-line numbered verification fields into multiline list for readability.
        $patterns = [
            '/\s+(?=1\.\s*Username\s*:)/i',
            '/\s+(?=2\.\s*Nomor rekening\s*:)/i',
            '/\s+(?=3\.\s*Nama rekening\s*:)/i',
            '/\s+(?=4\.\s*Nama Bank\s*:)/i',
        ];

        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, "\n", $text) ?? $text;
        }

        return $text;
    }

    /**
     * Check whether text is a structured field request that should keep newlines.
     */
    private function isStructuredDataRequest(string $text): bool
    {
        $markers = [
            'Username:',
            'Nama rekening:',
            'Nomor rekening:',
            'Nama Bank:',
            '1. Username:',
            '2. Nomor rekening:',
            '3. Nama rekening:',
            '4. Nama Bank:',
        ];

        $markerHits = 0;
        foreach ($markers as $marker) {
            if (stripos($text, $marker) !== false) {
                $markerHits++;
            }
        }

        if ($markerHits >= 2) {
            return true;
        }

        $fieldLineCount = 0;
        foreach (explode("\n", $text) as $line) {
            if (preg_match('/^[^:\n]{2,}:\s*$/', trim($line)) === 1) {
                $fieldLineCount++;
            }
        }

        return $fieldLineCount >= 3;
    }
}