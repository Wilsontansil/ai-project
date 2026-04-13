<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\ForbiddenBehaviour;
use App\Models\ProjectSetting;
use App\Models\Tool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use OpenAI;

class AIService
{
    private int $maxHistoryMessages = 20;

    private int $historyTtlHours = 12;

    private int $debounceSeconds = 2;

    // private string $model = 'gpt-4o-mini';
    private string $model = 'gpt-4.1-mini';

    /**
     * Main AI entrypoint.
     * Builds context, executes model call, runs tool logic, and returns formatted reply.
     */
    public function reply($message, $chatId = null, ?Agent $agent = null, string $channel = 'telegram', array $agentContext = [])
    {
        $apiKey = (string) ProjectSetting::getValue('openai_api_key', config('services.openai.api_key', ''));

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
                'model' => $this->model,
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
            $assistantReply = $this->handleToolCallOrIntent(
                $client,
                $msg,
                $message,
                $systemPrompt,
                $contextPrompt,
                $history
            );

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
            return $this->formatReply("âš ï¸ System busy, please try again...");
        } catch (\Exception $e) {
            return $this->formatReply("âš ï¸ Error: " . $e->getMessage());
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

        $parts = [
            'Customer context (internal only — do not expose to user):',
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

        return implode("\n\n", $parts);
    }

    /**
     * Get the system prompt for xoneBot.
     */
    private function getSystemPrompt(): string
    {
        $botName = $this->getBotName();

        $basePrompt = "You are {$botName}, a friendly customer support assistant for a gaming platform.

        RULES:
        - Default language: Bahasa Indonesia. Follow user's language if different.
        - Speak naturally, warm, casual-professional — like a real CS agent on chat.
        - Never make up information. Be honest if unsure.
        - If a user asks about account status, suspend status, verification, or any action covered by a configured tool, you MUST use the relevant tool and never guess the answer.
        - For tools linked to a data model, treat database lookup results as the only source of truth.
        - Always confirm before performing any sensitive action or updating player data.
        - If input values seem wrong, suggest valid options and ask user to re-check.[IMPORTANT]
        - Stay professional with angry/abusive users — respond politely, add emoji to soften tone.
        - Introduce yourself as {$botName} on first interaction only.
        - Format replies cleanly — no messy line breaks or long unbroken text.

        TOOL DATA:
        - 'bank': BCA, Mandiri, BRI, BNI, Danamon, CIMB Niaga, Permata, Maybank, Panin, BSI, Bank Jago, Bank Mega, Bank Bukopin, OCBC NISP, Mayapada, Sinarmas, Commonwealth, UOB Indonesia, BTN, Bank DKI, BTPN, Artha Graha, Mayora, JTrust Indonesia, Mestika, Victoria, Ina Perdana, Woori Saudara, Artos Indonesia, Harda Internasional, Ganesha, Maspion, QNB Indonesia, Royal Indonesia, Bumi Arta, Nusantara Parahyangan, and their Syariah variants.
        - 'norek': Numeric only.
        ";
        // Append active case instructions from database
        $caseInstructions = $this->getCaseInstructions();
        if ($caseInstructions !== '') {
            return $basePrompt . "\n\n" . $caseInstructions;
        }

        return $basePrompt;
    }

    /**
     * Get the configurable bot name from DB, fallback to default.
     */
    private function getBotName(): string
    {
        if (!Schema::hasTable('tools')) {
            return 'xoneBot';
        }

        $config = Tool::query()->where('tool_name', '_bot_config')->first();

        return trim((string) ($config?->meta['bot_name'] ?? 'xoneBot')) ?: 'xoneBot';
    }

    /**
     * Build additional instructions from active forbidden behaviour rules.
     */
    private function getCaseInstructions(): string
    {
        if (!Schema::hasTable('forbidden_behaviours')) {
            return '';
        }

        $rules = ForbiddenBehaviour::query()
            ->where('is_active', true)
            ->orderByRaw("FIELD(level, 'danger', 'warning', 'info')")
            ->get();

        if ($rules->isEmpty()) {
            return '';
        }

        $lines = ["FORBIDDEN BEHAVIOURS (strictly prohibited — never violate):"];

        foreach ($rules as $rule) {
            $levelTag = strtoupper($rule->level);
            $lines[] = "- [{$levelTag}] {$rule->instruction}";
        }

        return implode("\n", $lines);
    }

    /**
     * Get available tools/functions for OpenAI.
     * Definitions are built from DB columns — no PHP class needed.
     */
    private function getTools(): array
    {
        return $this->getEnabledTools()
            ->map(fn (Tool $tool) => $tool->getDefinition())
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Get enabled tools from the database (excludes config rows).
     */
    private function getEnabledTools(): \Illuminate\Support\Collection
    {
        if (!Schema::hasTable('tools')) {
            return collect();
        }

        return Tool::query()
            ->with('dataModel')
            ->where('is_enabled', true)
            ->where('tool_name', '!=', '_bot_config')
            ->get();
    }

    /**
     * Handle tool call or fallback to local intent parsing.
     * Returns null if tool/intent not matched.
     */
    private function handleToolCallOrIntent($client, $msg, string $userMessage, string $systemPrompt, ?string $contextPrompt, array $history): ?string
    {
        $tools = $this->getEnabledTools();

        // 1. Check if OpenAI explicitly called a tool (highest priority).
        foreach ($tools as $tool) {
            $arguments = $this->extractArgumentsFromToolCall($msg, $tool->tool_name);

            if ($arguments !== null) {
                return $this->resolveToolExecutionReply(
                    $client,
                    $tool,
                    $arguments,
                    $systemPrompt,
                    $contextPrompt,
                    $history,
                    $userMessage
                );
            }
        }

        $bestTool = null;
        $bestScore = 0;

        foreach ($tools as $tool) {
            $score = $tool->matchScore($userMessage);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestTool = $tool;
            }
        }

        if ($bestTool !== null && $bestScore > 0) {
            $arguments = $bestTool->getDefinition() !== null
                ? $this->forceToolArgumentsFromMessage($client, $bestTool, $systemPrompt, $contextPrompt, $history, $userMessage)
                : [];

            if ($bestTool->getDefinition() !== null && $arguments === null) {
                return $bestTool->getMissingMessage();
            }

            return $this->resolveToolExecutionReply(
                $client,
                $bestTool,
                $arguments ?? [],
                $systemPrompt,
                $contextPrompt,
                $history,
                $userMessage
            );
        }

        return null;
    }

    private function resolveToolExecutionReply($client, Tool $tool, array $arguments, string $systemPrompt, ?string $contextPrompt, array $history, string $userMessage): ?string
    {
        $execution = $this->executeTool($tool, $arguments);

        if (($execution['mode'] ?? 'direct') === 'model') {
            return $this->generateAssistantReplyFromToolResult(
                $client,
                $systemPrompt,
                $contextPrompt,
                $history,
                $userMessage,
                $execution['tool_context'] ?? []
            );
        }

        return $execution['reply'] ?? null;
    }

    /**
     * Execute a tool with extracted arguments.
     * For data-model tools, resolve data directly from configured database table.
     */
    private function executeTool(Tool $tool, array $arguments): array
    {
        if ($tool->dataModel !== null) {
            return $this->executeDataModelLookup($tool, $arguments);
        }

        if (!empty($tool->information_text)) {
            return [
                'mode' => 'direct',
                'reply' => $tool->information_text,
            ];
        }

        return [
            'mode' => 'direct',
            'reply' => "Tool {$tool->display_name} belum dikonfigurasi.",
        ];
    }

    private function executeDataModelLookup(Tool $tool, array $arguments): array
    {
        $dataModel = $tool->dataModel;

        if ($dataModel === null) {
            return [
                'mode' => 'direct',
                'reply' => $tool->getMissingMessage(),
            ];
        }

        $tableName = trim((string) ($dataModel->table_name ?? ''));
        $connectionName = trim((string) ($dataModel->connection_name ?? 'mysqlgame'));
        $connectionName = $connectionName === '' ? 'mysqlgame' : $connectionName;
        $allowedFields = array_keys((array) ($dataModel->fields ?? []));

        if ($tableName === '') {
            return [
                'mode' => 'direct',
                'reply' => 'Data model table belum dikonfigurasi.',
            ];
        }

        if ($allowedFields === []) {
            return [
                'mode' => 'direct',
                'reply' => 'Field data model belum dikonfigurasi.',
            ];
        }

        $requiredFields = (array) data_get($tool->parameters, 'required', []);
        foreach ($requiredFields as $requiredField) {
            $value = trim((string) ($arguments[$requiredField] ?? ''));
            if ($value === '') {
                return [
                    'mode' => 'direct',
                    'reply' => $tool->getMissingMessage(),
                ];
            }
        }

        try {
            $query = DB::connection($connectionName)->table($tableName)->select($allowedFields);
            $lookupFilters = [];

            foreach ($arguments as $field => $value) {
                if (!in_array($field, $allowedFields, true)) {
                    continue;
                }

                $normalizedValue = is_string($value) ? trim($value) : $value;
                if ($normalizedValue === '' || $normalizedValue === null) {
                    continue;
                }

                $query->where($field, $normalizedValue);
                $lookupFilters[$field] = $normalizedValue;
            }

            $row = $query->first();

            if ($row === null) {
                return [
                    'mode' => 'direct',
                    'reply' => 'Data tidak ditemukan.',
                ];
            }

            $resolvedData = $this->normalizeToolData((array) $row);

            return [
                'mode' => 'model',
                'tool_context' => [
                    'tool_name' => $tool->tool_name,
                    'tool_display_name' => $tool->display_name,
                    'tool_description' => $tool->description,
                    'data_model' => [
                        'model_name' => $dataModel->model_name,
                        'table_name' => $tableName,
                        'connection_name' => $connectionName,
                        'allowed_fields' => $allowedFields,
                    ],
                    'lookup_filters' => $lookupFilters,
                    'resolved_data' => $resolvedData,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('AI data model lookup failed', [
                'tool_name' => $tool->tool_name,
                'table_name' => $tableName,
                'connection_name' => $connectionName,
                'filters' => $arguments,
                'error' => $e->getMessage(),
            ]);

            return [
                'mode' => 'direct',
                'reply' => 'Terjadi kesalahan saat mengambil data.',
            ];
        }
    }

    private function forceToolArgumentsFromMessage($client, Tool $tool, string $systemPrompt, ?string $contextPrompt, array $history, string $userMessage): ?array
    {
        $definition = $tool->getDefinition();

        if ($definition === null) {
            return [];
        }

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        if ($contextPrompt !== null) {
            $messages[] = ['role' => 'system', 'content' => $contextPrompt];
        }

        $messages[] = [
            'role' => 'system',
            'content' => "Choose the matched tool and extract arguments from the user's latest message. If the message does not contain enough data for a required field, still call the tool with whatever arguments are available so the application can return the configured missing-data message.",
        ];

        foreach (array_slice($history, -6) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $role = $item['role'] ?? null;
            $content = $item['content'] ?? null;

            if (!in_array($role, ['user', 'assistant'], true) || !is_string($content) || trim($content) === '') {
                continue;
            }

            $messages[] = [
                'role' => $role,
                'content' => $content,
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $response = $client->chat()->create([
                'model' => $this->model,
                'messages' => $messages,
                'tools' => [$definition],
                'tool_choice' => [
                    'type' => 'function',
                    'function' => ['name' => $tool->tool_name],
                ],
                'max_tokens' => 120,
            ]);

            $message = $response->choices[0]->message ?? null;

            return $message !== null
                ? $this->extractArgumentsFromToolCall($message, $tool->tool_name)
                : null;
        } catch (\Throwable $e) {
            Log::warning('AI forced tool argument extraction failed', [
                'tool_name' => $tool->tool_name,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function normalizeToolData($value)
    {
        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeToolData($item);
            }

            return $normalized;
        }

        if (!is_string($value)) {
            return $value;
        }

        $normalized = trim($value);
        $lower = mb_strtolower($normalized);

        return match ($lower) {
            'true', 'yes', 'y', '1' => true,
            'false', 'no', 'n', '0' => false,
            default => $value,
        };
    }

    private function generateAssistantReplyFromToolResult($client, string $systemPrompt, ?string $contextPrompt, array $history, string $userMessage, array $toolContext): string
    {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        if ($contextPrompt !== null) {
            $messages[] = ['role' => 'system', 'content' => $contextPrompt];
        }

        $messages[] = [
            'role' => 'system',
            'content' => "Internal tool result already fetched. Use it to answer naturally like a human customer service agent. Do not mention internal tools, SQL, database query details, or raw response structure. Do not copy data literally as JSON. Use the resolved_data and tool_description as the source of truth. If resolved_data is empty, say the data was not found and ask the user to re-check their input.

Tool context:\n" . json_encode($toolContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ];

        foreach (array_slice($history, -6) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $role = $item['role'] ?? null;
            $content = $item['content'] ?? null;

            if (!in_array($role, ['user', 'assistant'], true) || !is_string($content) || trim($content) === '') {
                continue;
            }

            $messages[] = [
                'role' => $role,
                'content' => $content,
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $response = $client->chat()->create([
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => 220,
            ]);

            $reply = trim((string) ($response->choices[0]->message->content ?? ''));

            if ($reply !== '') {
                return $reply;
            }
        } catch (\Throwable $e) {
            Log::warning('AI tool reply generation failed', [
                'tool_name' => $toolContext['tool_name'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        return 'Data berhasil dicek. Saya bantu jelaskan hasilnya ya.';
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
