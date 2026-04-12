<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\ForbiddenBehaviour;
use App\Models\Customer;
use App\Models\EscalationNotification;
use App\Models\ProjectSetting;
use App\Models\Tool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
            $assistantReply = $this->handleToolCallOrIntent($msg, $message, $agent);

            if ($assistantReply !== null) {
                $assistantReply = $this->prepareAssistantReply($history, $assistantReply);
                $this->saveConversationTurn($chatId, $history, $message, $assistantReply);
                return $this->stripEscalationMarker($assistantReply);
            }

            // Normal AI reply
            $assistantReply = $msg->content ?? "Sorry, I couldn't understand.";

            if ($finishReason === 'length') {
                $assistantReply .= "\n\nJika jawaban ini masih terpotong, balas: lanjut.";
            }

            $assistantReply = $this->prepareAssistantReply($history, $assistantReply);
            $this->saveConversationTurn($chatId, $history, $message, $assistantReply);
            return $this->stripEscalationMarker($assistantReply);

        } catch (\OpenAI\Exceptions\RateLimitException $e) {
            return $this->formatReply("âš ï¸ System busy, please try again...");
        } catch (\Exception $e) {
            return $this->formatReply("âš ï¸ Error: " . $e->getMessage());
        }
    }

    /**
     * Check if the AI reply contains an escalation marker [ESCALATE].
     */
    public function needsEscalation(string $reply): bool
    {
        return stripos($reply, '[ESCALATE]') !== false;
    }

    /**
     * Strip the [ESCALATE] marker from the reply before sending to user.
     */
    private function stripEscalationMarker(string $reply): string
    {
        return trim(preg_replace('/\s*\[ESCALATE\]\s*/i', '', $reply));
    }

    /**
     * Create an escalation notification for backoffice when AI cannot resolve.
     */
    public function createEscalation(?Customer $customer, string $channel, ?string $chatId, string $userMessage, string $aiReply): void
    {
        if ($customer === null) {
            return;
        }

        try {
            $customer->update([
                'needs_human' => true,
                'escalation_reason' => mb_substr($aiReply, 0, 500),
                'escalated_at' => now(),
                'resolved_at' => null,
            ]);

            EscalationNotification::create([
                'customer_id' => $customer->id,
                'channel' => $channel,
                'chat_id' => $chatId,
                'reason' => mb_substr($aiReply, 0, 500),
                'last_message' => mb_substr($userMessage, 0, 1000),
                'is_read' => false,
            ]);
        } catch (\Throwable $e) {
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
        $phone = (string) ProjectSetting::getValue('support_phone', config('services.support.phone', '08120000000'));
        $botName = $this->getBotName();

        $basePrompt = "You are {$botName}, a friendly customer support assistant for a gaming platform.

        RULES:
        - Default language: Bahasa Indonesia. Follow user's language if different.
        - Speak naturally, warm, casual-professional — like a real CS agent on chat.
        - Keep replies short (1-3 sentences) unless user asks for detail.
        - Never make up information. Be honest if unsure.
        - Always confirm before performing any sensitive action or updating player data.
        - If input values seem wrong, suggest valid options and ask user to re-check.[IMPORTANT]
        - Stay professional with angry/abusive users — respond politely, add emoji to soften tone.
        - Introduce yourself as {$botName} on first interaction only.
        - Format replies cleanly — no messy line breaks or long unbroken text.

        ESCALATION TO HUMAN SUPPORT:
        - If you cannot resolve the user's problem after 2-3 attempts, or the issue is outside your capabilities, you MUST escalate to human support.
        - Situations that REQUIRE escalation: payment/billing disputes, account security issues, technical bugs you cannot fix, repeated failed tool calls, user explicitly asks for a human agent, complaints about the bot itself, legal/refund matters.
        - When escalating, include the EXACT phrase '[ESCALATE]' (with brackets) at the END of your reply. This is a system marker — it will not be shown to the user.
        - Before escalating, apologize briefly and inform the user that you are connecting them to a human agent.
        - Example escalation reply: 'Mohon maaf, saya belum bisa membantu masalah ini. Saya akan sambungkan kamu ke tim support kami ya 🙏 [ESCALATE]'
        - Do NOT escalate for simple questions or issues you can handle with available tools.

        HANDOVER:
        - If stuck or unable to resolve, offer transfer to human support at {$phone}. Ask confirmation first.

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
            ->where('is_enabled', true)
            ->where('tool_name', '!=', '_bot_config')
            ->get();
    }

    /**
     * Handle tool call or fallback to local intent parsing.
     * Returns null if tool/intent not matched.
     */
    private function handleToolCallOrIntent($msg, string $userMessage, ?Agent $agent): ?string
    {
        $tools = $this->getEnabledTools();

        // 1. Check if OpenAI explicitly called a tool (highest priority).
        foreach ($tools as $tool) {
            $arguments = $this->extractArgumentsFromToolCall($msg, $tool->tool_name);

            if ($arguments !== null) {
                return $this->executeTool($tool, $arguments, $agent);
            }
        }

        // 2. Fallback: keyword match only for info-only tools (no OpenAI definition).
        //    Tools with parameters were already offered to OpenAI — trust its decision.
        $bestTool = null;
        $bestScore = 0;

        foreach ($tools as $tool) {
            if ($tool->getDefinition() !== null) {
                continue; // OpenAI already had a chance to call this tool.
            }

            $score = $tool->matchScore($userMessage);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestTool = $tool;
            }
        }

        if ($bestTool !== null && !empty($bestTool->information_text)) {
            return $bestTool->information_text;
        }

        return null;
    }

    /**
     * Execute a tool with extracted arguments.
     * Always uses webhook endpoint for tool information requests.
     */
    private function executeTool(Tool $tool, array $arguments, ?Agent $agent): string
    {
        $endpoints = $tool->endpoints;

        if (empty($endpoints)) {
            return "Endpoint webhook untuk tool {$tool->display_name} belum dikonfigurasi.";
        }

        return $this->callWebhookEndpoint($tool, $endpoints, $arguments);
    }

    /**
    * Call webhook_base_url + tool endpoint route.
    * For information requests, endpoint priority: 'get', then 'update'.
     */
    private function callWebhookEndpoint(Tool $tool, array $endpoints, array $arguments): string
    {
        $baseUrl = rtrim(ProjectSetting::getValue('webhook_base_url', ''), '/');

        if (empty($baseUrl)) {
            return 'Webhook base URL belum dikonfigurasi.';
        }

        // Determine which endpoint to use (prefer 'get', fallback 'update')
        $endpoint = $endpoints['get'] ?? $endpoints['update'] ?? null;

        if ($endpoint === null || empty($endpoint['route'])) {
            return !empty($tool->information_text) ? $tool->information_text : $tool->getMissingMessage();
        }

        $route = '/' . ltrim($endpoint['route'], '/');
        $url = $baseUrl . $route;

        // Build body: use fixed value if set, otherwise map from AI arguments
        $bodyFields = $endpoint['body'] ?? [];
        $body = [];
        foreach ($bodyFields as $key => $value) {
            if ($value !== '') {
                $body[$key] = $value; // fixed/default value
            } elseif (isset($arguments[$key])) {
                $body[$key] = $arguments[$key]; // from AI parameter
            }
        }

        try {
            $this->logAiHttpRequest('tool.webhook', 'POST', $url, [
                'tool_name' => $tool->tool_name,
                'body' => $body,
            ]);

            $response = Http::timeout(15)->post($url, $body);

            $this->logAiHttpResponse('tool.webhook', 'POST', $url, $response->status(), [
                'tool_name' => $tool->tool_name,
                'successful' => $response->successful(),
                'response_preview' => mb_substr($response->body(), 0, 1000),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return is_array($data) ? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (string) $response->body();
            }

            return "Gagal menghubungi server (HTTP {$response->status()}).";
        } catch (\Throwable $e) {
            $this->logAiHttpException('tool.webhook', 'POST', $url, $e->getMessage(), [
                'tool_name' => $tool->tool_name,
                'body' => $body,
            ]);

            return 'Terjadi kesalahan saat menghubungi server.';
        }
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

    private function logAiHttpRequest(string $channel, string $method, string $url, array $context = []): void
    {
        Log::info('AI HTTP Request', array_merge([
            'channel' => $channel,
            'method' => $method,
            'url' => $url,
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }

    private function logAiHttpResponse(string $channel, string $method, string $url, int $status, array $context = []): void
    {
        Log::info('AI HTTP Response', array_merge([
            'channel' => $channel,
            'method' => $method,
            'url' => $url,
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }

    private function logAiHttpException(string $channel, string $method, string $url, string $error, array $context = []): void
    {
        Log::error('AI HTTP Exception', array_merge([
            'channel' => $channel,
            'method' => $method,
            'url' => $url,
            'error' => $error,
            'timestamp' => now()->toIso8601String(),
        ], $context));
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