<?php

namespace App\Services;

use App\Models\ChatAgent;
use App\Models\ProjectSetting;
use App\Services\AI\ConversationHistory;
use App\Services\AI\PromptBuilder;
use App\Services\AI\ReplyFormatter;
use App\Services\AI\ToolDispatcher;
use App\Support\MetricsCollector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenAI;

/**
 * AI orchestrator — thin entry point for all webhook channels.
 *
 * Delegates to focused collaborators:
 *   PromptBuilder        — system prompt + agent context injection
 *   ConversationHistory  — cache-backed history load/save
 *   ToolDispatcher       — tool call resolution, engine routing, AI follow-up calls
 *   ReplyFormatter       — normalise whitespace, truncate, anti-repeat guard
 */
class AIService
{
    private int $defaultDebounceSeconds = 2;

    private string $model = 'gpt-4.1-mini';

    public function __construct(
        private readonly PromptBuilder $promptBuilder,
        private readonly ConversationHistory $conversationHistory,
        private readonly ReplyFormatter $replyFormatter,
        private readonly ToolDispatcher $toolDispatcher,
    ) {}

    /**
     * Main AI entrypoint — called by all webhook controllers.
     *
     * @param array<string, mixed> $agentContext
     * @param array<string, mixed> $attachmentMeta  Optional attachment from ChatAttachmentStorageService.
     *                                               When type=image the image URL is sent as a vision block.
     */
    public function reply(mixed $message, mixed $chatId = null, string $channel = 'telegram', array $agentContext = [], array $attachmentMeta = []): string
    {
        $apiKey = (string) ProjectSetting::getValue('openai_api_key', config('services.openai.api_key', ''));

        if ($apiKey === '') {
            return $this->replyFormatter->format('OpenAI API key is not configured. Please set OPENAI_API_KEY on server .env.');
        }

        $client = OpenAI::client($apiKey);
        $chatAgent = ChatAgent::getDefault();
        $model = $chatAgent->model ?? $this->model;

        $systemPrompt = $this->promptBuilder->buildSystemPrompt($chatAgent);
        $toolDefinitions = $this->toolDispatcher->getToolDefinitions($chatAgent);
        $history = $this->conversationHistory->load($chatId, $channel, $chatAgent);
        $isNewSession = empty($history);
        $contextPrompt = $this->promptBuilder->buildAgentContextPrompt($agentContext, $channel);
        $activeHistory = $history;

        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        if ($contextPrompt !== null) {
            $messages[] = ['role' => 'system', 'content' => $contextPrompt];
        }

        if ($isNewSession) {
            $messages[] = ['role' => 'system', 'content' => 'STATUS SESI: percakapan BARU — tidak ada konteks percakapan sebelumnya. Sambut user secara natural dan tangani permintaannya dari awal.'];
        }

        // Estimate token usage and trim history if payload is too large.
        // gpt-4.1-mini supports 128k context, but keep requests under ~30k to stay safe.
        $maxChars = 80_000; // ~20k tokens rough estimate
        $systemChars = mb_strlen($systemPrompt) + mb_strlen($contextPrompt ?? '') + mb_strlen($message);
        $availableChars = max(0, $maxChars - $systemChars);

        $trimmedHistory = $activeHistory;
        $historyChars = array_sum(array_map(fn ($m) => mb_strlen($m['content'] ?? ''), $trimmedHistory));

        while ($historyChars > $availableChars && count($trimmedHistory) >= 2) {
            array_shift($trimmedHistory); // remove oldest user msg
            array_shift($trimmedHistory); // remove oldest assistant reply
            $historyChars = array_sum(array_map(fn ($m) => mb_strlen($m['content'] ?? ''), $trimmedHistory));
        }

        if (count($trimmedHistory) < count($activeHistory)) {
            Log::info('Trimmed conversation history', [
                'channel' => $channel,
                'chat_id' => $chatId,
                'from' => count($activeHistory),
                'to' => count($trimmedHistory),
            ]);
        }

        $userContent = $this->buildUserContent((string) $message, $attachmentMeta);
        $messages = array_merge($messages, $trimmedHistory, [['role' => 'user', 'content' => $userContent]]);

        try {
            $payload = [
                'model' => $model,
                'messages' => $messages,
                'max_completion_tokens' => $this->resolveMaxTokens((string) $message, $chatAgent->max_tokens),
            ];

            // Reasoning models (o1/o3/o4 series) only support temperature=1 (default).
            // Skip the parameter entirely for those models to avoid API errors.
            $isReasoningModel = preg_match('/^o\d/', $model);
            if (! $isReasoningModel && $chatAgent->temperature !== null) {
                $payload['temperature'] = $chatAgent->temperature;
            }

            if ($toolDefinitions !== []) {
                $payload['tools'] = $toolDefinitions;
                $payload['tool_choice'] = 'auto';
            }

            if (config('logging.ai_datamodel_query_log', false)) {
                $totalChars = array_sum(array_map(
                    fn ($m) => mb_strlen(is_string($m['content'] ?? '') ? ($m['content'] ?? '') : json_encode($m['content'])),
                    $payload['messages']
                ));
                $toolChars = mb_strlen(json_encode($payload['tools'] ?? []));
                Log::info('AI payload size', [
                    'channel'           => $channel,
                    'model'             => $model,
                    'messages_chars'    => $totalChars,
                    'tools_chars'       => $toolChars,
                    'total_chars'       => $totalChars + $toolChars,
                    'est_tokens'        => (int) round(($totalChars + $toolChars) / 4),
                    'max_completion_tokens' => $payload['max_completion_tokens'],
                    'messages_count'    => count($payload['messages']),
                    'tools_count'       => count($payload['tools'] ?? []),
                ]);
            }

            $openaiStart = MetricsCollector::startTimer();
            $response = $this->callOpenAiWithRetry($client, $payload);
            $openaiLatency = MetricsCollector::elapsed($openaiStart);

            $usage = [
                'prompt_tokens' => $response->usage->promptTokens ?? 0,
                'completion_tokens' => $response->usage->completionTokens ?? 0,
                'total_tokens' => $response->usage->totalTokens ?? 0,
            ];
            MetricsCollector::recordOpenAiCall($channel, $model, 'chat', $openaiLatency, $usage);

            $msg = $response->choices[0]->message;
            $finishReason = (string) ($response->choices[0]->finishReason ?? '');

            // Let the dispatcher handle tool calls and intent matching.
            // Pass $userContent (may include image) so forceExtractArguments can see the screenshot.
            $assistantReply = $this->toolDispatcher->resolve(
                $client, $msg, $message, $systemPrompt, $contextPrompt, $activeHistory, $model,
                (string) $chatId, $channel, $chatAgent, $userContent
            );

            if ($assistantReply !== null) {
                $assistantReply = $this->replyFormatter->prepare($activeHistory, $assistantReply);
                $this->conversationHistory->save($chatId, $activeHistory, $message, $assistantReply, $channel, $chatAgent);

                return $assistantReply;
            }

            // Normal conversational reply — no tool was triggered.
            $assistantReply = $msg->content ?? "Sorry, I couldn't understand.";

            if ($finishReason === 'length') {
                Log::info('OpenAI response truncated at token limit', [
                    'channel' => $channel,
                    'model'   => $model,
                    'max_completion_tokens' => $payload['max_completion_tokens'],
                ]);
            }

            $assistantReply = $this->replyFormatter->prepare($activeHistory, $assistantReply);
            $this->conversationHistory->save($chatId, $activeHistory, $message, $assistantReply, $channel, $chatAgent);

            return $assistantReply;
        } catch (\OpenAI\Exceptions\RateLimitException $e) {
            MetricsCollector::recordOpenAiCall($channel, $model, 'chat', 0, null, false);

            return $this->replyFormatter->format('⚠️ Sistem sedang sibuk, silakan coba beberapa saat lagi.');
        } catch (\Exception $e) {
            MetricsCollector::recordOpenAiCall($channel, $model, 'chat', 0, null, false);
            Log::error('AIService reply failed', [
                'channel' => $channel,
                'error' => $e->getMessage(),
                'payload_chars' => mb_strlen(json_encode($payload['messages'] ?? [])),
                'history_count' => count($trimmedHistory ?? $history),
                'tools_count' => count($payload['tools'] ?? []),
            ]);

            return $this->replyFormatter->format('⚠️ Terjadi error. Silakan coba beberapa saat lagi.');
        }
    }

    /**
     * Determine max completion tokens adaptively based on message complexity.
     *
     * If the ChatAgent has an explicit max_tokens set (not the default 1000),
     * it is used as an absolute cap. Otherwise:
     *   - Short/simple messages (< 80 chars, no complex keywords) → 600
     *   - Long or complex messages (list, explain, panduan, etc.)  → 1400
     *   - Default                                                  → 1000
     */
    private function resolveMaxTokens(string $message, ?int $agentMaxTokens): int
    {
        $configured = $agentMaxTokens ?? 1000;

        $complexKeywords = [
            'list', 'daftar', 'jelaskan', 'explain', 'cara', 'panduan',
            'langkah', 'step', 'detail', 'lengkap', 'semua', 'all',
            'perbandingan', 'compare', 'tabel', 'table',
        ];

        $lower = mb_strtolower($message);
        $isComplex = false;
        foreach ($complexKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $isComplex = true;
                break;
            }
        }

        $isShort = mb_strlen(trim($message)) < 80;

        if ($isComplex) {
            $adaptive = 1400;
        } elseif ($isShort) {
            $adaptive = 600;
        } else {
            $adaptive = 1000;
        }

        // If agent has a non-default explicit cap, honour it as ceiling.
        return min($adaptive, $configured);
    }

    /**
     * Buffer a message for debouncing without blocking.
     *
     * Returns true if this process is the elected leader (first to buffer),
     * false if another process is already leading, or null on invalid input.
     */
    public function bufferDebouncedMessage(string $chatId, string $message, string $channel = ''): ?bool
    {
        $chatId = trim($chatId);
        if ($chatId === '' || trim($message) === '') {
            return null;
        }

        $debounceSeconds = $this->getMessageAwaitSeconds();

        $prefix = $channel !== '' ? $channel . ':' : '';
        $bufferKey = 'chat:debounce:buffer:' . $prefix . $chatId;
        $leaderKey = 'chat:debounce:leader:' . $prefix . $chatId;

        $lock = Cache::lock('lock:' . $bufferKey, 5);
        $lock->block(3);

        try {
            $buffer = Cache::get($bufferKey, []);
            $buffer[] = [
                'message' => trim($message),
                'at' => now()->timestamp,
            ];
            Cache::put($bufferKey, $buffer, now()->addMinutes(2));

            // If a reply is being generated, keep buffering but do not elect a new leader yet.
            if ($this->isAiProcessing($chatId, $channel)) {
                return false;
            }
        } finally {
            $lock->release();
        }

        return Cache::add($leaderKey, 1, now()->addSeconds($debounceSeconds + 2));
    }

    public function acquireAiProcessingLock(string $chatId, string $channel = '', int $ttlSeconds = 90): bool
    {
        $chatId = trim($chatId);
        if ($chatId === '') {
            return false;
        }

        return Cache::add($this->aiProcessingKey($chatId, $channel), 1, now()->addSeconds(max(10, $ttlSeconds)));
    }

    public function releaseAiProcessingLock(string $chatId, string $channel = ''): void
    {
        $chatId = trim($chatId);
        if ($chatId === '') {
            return;
        }

        Cache::forget($this->aiProcessingKey($chatId, $channel));
    }

    public function isAiProcessing(string $chatId, string $channel = ''): bool
    {
        $chatId = trim($chatId);
        if ($chatId === '') {
            return false;
        }

        return Cache::has($this->aiProcessingKey($chatId, $channel));
    }

    public function promoteBufferedMessagesToLeader(string $chatId, string $channel = ''): bool
    {
        $chatId = trim($chatId);
        if ($chatId === '') {
            return false;
        }

        $prefix = $channel !== '' ? $channel . ':' : '';
        $bufferKey = 'chat:debounce:buffer:' . $prefix . $chatId;
        $leaderKey = 'chat:debounce:leader:' . $prefix . $chatId;
        $lock = Cache::lock('lock:' . $bufferKey, 5);
        $lock->block(3);

        try {
            if ($this->isAiProcessing($chatId, $channel)) {
                return false;
            }

            $messages = Cache::get($bufferKey, []);
            if (!is_array($messages) || $messages === []) {
                return false;
            }

            foreach ($messages as $item) {
                $part = trim((string) ($item['message'] ?? ''));
                if ($part !== '') {
                    return Cache::add($leaderKey, 1, now()->addSeconds($this->getMessageAwaitSeconds() + 2));
                }
            }

            return false;
        } finally {
            $lock->release();
        }
    }

    /**
     * Collect and merge all buffered messages for a chat, then clear the buffer.
     */
    public function collectBufferedMessages(string $chatId, string $channel = ''): ?string
    {
        $prefix = $channel !== '' ? $channel . ':' : '';
        $bufferKey = 'chat:debounce:buffer:' . $prefix . $chatId;
        $leaderKey = 'chat:debounce:leader:' . $prefix . $chatId;

        $lock = Cache::lock('lock:' . $bufferKey, 5);
        $lock->block(3);

        try {
            $messages = Cache::get($bufferKey, []);
            Cache::forget($bufferKey);
            Cache::forget($leaderKey);
        } finally {
            $lock->release();
        }

        if (!is_array($messages) || $messages === []) {
            return null;
        }

        $parts = [];
        foreach ($messages as $item) {
            $part = trim((string) ($item['message'] ?? ''));
            if ($part !== '') {
                $parts[] = $part;
            }
        }

        $parts = array_values(array_unique($parts));

        return $parts === [] ? null : implode("\n", $parts);
    }

    /**
     * Collect and debounce rapid successive messages from the same chat (blocking).
     *
     * Used only by synchronous channels (LiveChat) where the reply must be
     * returned in the same HTTP response. Async channels should use
     * bufferDebouncedMessage() + delayed job + collectBufferedMessages().
     */
    public function collectDebouncedMessage(string $chatId, string $message, string $channel = ''): ?string
    {
        $chatId = trim($chatId);

        if ($chatId === '') {
            return trim($message);
        }

        $isLeader = $this->bufferDebouncedMessage($chatId, $message, $channel);

        if ($isLeader === null) {
            return trim($message) !== '' ? trim($message) : null;
        }

        if (!$isLeader) {
            return null;
        }

        $debounceSeconds = $this->getMessageAwaitSeconds();
        if ($debounceSeconds > 0) {
            usleep($debounceSeconds * 1000000);
        }

        return $this->collectBufferedMessages($chatId, $channel) ?? trim($message);
    }

    /**
     * Delay before AI replies after the latest user message.
     */
    public function getMessageAwaitSeconds(): int
    {
        $seconds = Cache::remember('ai:message_await_seconds:default_agent', now()->addSeconds(20), function (): int {
            $agent = ChatAgent::getDefault();

            return (int) ($agent?->message_await_seconds ?? $this->defaultDebounceSeconds);
        });

        return max(0, min(15, (int) $seconds));
    }

    private function aiProcessingKey(string $chatId, string $channel = ''): string
    {
        $prefix = $channel !== '' ? $channel . ':' : '';

        return 'chat:debounce:busy:' . $prefix . $chatId;
    }

    /**
     * Call OpenAI with retry on transient 500 errors.
     */
    /**
     * Build the user message content for OpenAI.
     *
     * If the attachment is an image, returns a multimodal vision array so the model
     * can actually see the image. For all other cases returns a plain string.
     *
     * @param  array<string, mixed>  $attachmentMeta
     * @return string|array<int, mixed>
     */
    private function buildUserContent(string $textMessage, array $attachmentMeta): string|array
    {
        if (
            empty($attachmentMeta['type']) ||
            $attachmentMeta['type'] !== 'image' ||
            empty($attachmentMeta['path'])
        ) {
            return $textMessage;
        }

        // Build the publicly accessible image URL from the SFTP disk's configured url.
        $baseUrl = rtrim((string) config('filesystems.disks.sftp.url', ''), '/');

        if ($baseUrl === '') {
            return $textMessage;
        }

        $imageUrl = $baseUrl . '/' . ltrim((string) $attachmentMeta['path'], '/');

        $content = [];

        if ($textMessage !== '' && $textMessage !== '[image]') {
            $content[] = ['type' => 'text', 'text' => $textMessage];
        }

        $content[] = [
            'type'      => 'image_url',
            'image_url' => ['url' => $imageUrl, 'detail' => 'auto'],
        ];

        return $content;
    }

    private function callOpenAiWithRetry(mixed $client, array $payload, int $maxRetries = 2): mixed
    {
        $lastException = null;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                return $client->chat()->create($payload);
            } catch (\OpenAI\Exceptions\ErrorException $e) {
                $lastException = $e;

                // Only retry on server errors (500), not client errors (400/401/etc.)
                if (str_contains($e->getMessage(), 'server had an error') && $attempt < $maxRetries) {
                    Log::warning('OpenAI 500 error, retrying', [
                        'attempt' => $attempt + 1,
                        'error' => $e->getMessage(),
                    ]);
                    usleep(500_000 * ($attempt + 1)); // 0.5s, 1s backoff
                    continue;
                }

                throw $e;
            }
        }

        throw $lastException;
    }
}
