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
    private int $debounceSeconds = 2;

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
     */
    public function reply(mixed $message, mixed $chatId = null, string $channel = 'telegram', array $agentContext = []): string
    {
        $apiKey = (string) ProjectSetting::getValue('openai_api_key', config('services.openai.api_key', ''));

        if ($apiKey === '') {
            return $this->replyFormatter->format('OpenAI API key is not configured. Please set OPENAI_API_KEY on server .env.');
        }

        $client = OpenAI::client($apiKey);
        $chatAgent = ChatAgent::getDefault();
        $model = $chatAgent->model ?? $this->model;

        $systemPrompt = $this->promptBuilder->buildSystemPrompt($chatAgent);
        $toolDefinitions = $this->toolDispatcher->getToolDefinitions();
        $history = $this->conversationHistory->load($chatId);
        $contextPrompt = $this->promptBuilder->buildAgentContextPrompt($agentContext);

        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        if ($contextPrompt !== null) {
            $messages[] = ['role' => 'system', 'content' => $contextPrompt];
        }
        $messages = array_merge($messages, $history, [['role' => 'user', 'content' => $message]]);

        try {
            $payload = [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $chatAgent->max_tokens ?? 420,
            ];

            if ($chatAgent->temperature !== null) {
                $payload['temperature'] = $chatAgent->temperature;
            }

            if ($toolDefinitions !== []) {
                $payload['tools'] = $toolDefinitions;
                $payload['tool_choice'] = 'auto';
            }

            $openaiStart = MetricsCollector::startTimer();
            $response = $client->chat()->create($payload);
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
            $assistantReply = $this->toolDispatcher->resolve(
                $client, $msg, $message, $systemPrompt, $contextPrompt, $history, $model
            );

            if ($assistantReply !== null) {
                $assistantReply = $this->replyFormatter->prepare($history, $assistantReply);
                $this->conversationHistory->save($chatId, $history, $message, $assistantReply);

                return $assistantReply;
            }

            // Normal conversational reply — no tool was triggered.
            $assistantReply = $msg->content ?? "Sorry, I couldn't understand.";

            if ($finishReason === 'length') {
                $assistantReply .= "\n\nJika jawaban ini masih terpotong, balas: lanjut.";
            }

            $assistantReply = $this->replyFormatter->prepare($history, $assistantReply);
            $this->conversationHistory->save($chatId, $history, $message, $assistantReply);

            return $assistantReply;
        } catch (\OpenAI\Exceptions\RateLimitException $e) {
            MetricsCollector::recordOpenAiCall($channel, $model, 'chat', 0, null, false);

            return $this->replyFormatter->format('⚠️ Sistem sedang sibuk, silakan coba beberapa saat lagi.');
        } catch (\Exception $e) {
            MetricsCollector::recordOpenAiCall($channel, $model, 'chat', 0, null, false);
            Log::error('AIService reply failed', ['channel' => $channel, 'error' => $e->getMessage()]);

            return $this->replyFormatter->format('⚠️ Terjadi error. Silakan coba beberapa saat lagi.');
        }
    }

    /**
     * Collect and debounce rapid successive messages from the same chat.
     * Returns the merged message when the current process is elected leader,
     * or null when another process is already handling debounce for this chat.
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
}
