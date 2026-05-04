<?php

namespace App\Services\AI;

use App\Models\ChatAgent;
use App\Models\Customer;
use App\Models\Tool;
use App\Services\AI\ToolEngines\DataModelQueryEngine;
use App\Services\AI\ToolEngines\HttpToolEngine;
use App\Services\AI\ToolEngines\InfoToolEngine;
use App\Support\MetricsCollector;
use App\Services\AI\EscalationSummaryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Dispatches tool calls to the appropriate engine and generates the final reply.
 *
 * Flow:
 *   1. OpenAI returns a message with an explicit tool_call → extract args → run engine.
 *   2. No explicit call but keyword score matches a tool → force-extract args via a
 *      dedicated OpenAI call → run engine.
 *   3. Engine returns mode=direct → return text immediately.
 *   4. Engine returns mode=model  → call OpenAI again with the tool_context payload
 *      to produce a conversational reply.
 */
class ToolDispatcher
{
    public function __construct(
        private readonly InfoToolEngine $infoEngine,
        private readonly HttpToolEngine $httpEngine,
        private readonly DataModelQueryEngine $dataModelEngine,
    ) {}

    // ─── Public API ──────────────────────────────────────────────────────────

    /**
     * Return enabled tools excluding the internal _bot_config row.
     *
     * @return Collection<int, Tool>
     */
    public function getEnabledTools(?ChatAgent $chatAgent = null): Collection
    {
        try {
            return Tool::query()
                ->with('dataModel')
                ->where('tool_name', '!=', '_bot_config')
                ->where('is_enabled', true)
                ->orderBy('id')
                ->get()
                // Exclude chain-only tools: they must only run via a pending chain,
                // never through direct user intent or AI tool-choice.
                ->filter(fn (Tool $t) => ($t->meta['trigger_mode'] ?? null) !== 'chain_only')
                ->values();
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * Build the tools array for the OpenAI chat payload.
     * chain_only tools are excluded so OpenAI never knows they exist.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolDefinitions(?ChatAgent $chatAgent = null): array
    {
        return $this->getEnabledTools($chatAgent)
            ->map(fn (Tool $tool) => $tool->getDefinition())
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Try to handle a tool call or fallback to local intent parsing.
     *
     * Returns the final assistant reply string, or null if no tool matched.
     *
     * @param mixed $client  OpenAI client instance
     * @param mixed $msg     OpenAI message object from response->choices[0]->message
     * @param array<int, array<string, string>> $history
     */
    public function resolve(
        mixed $client,
        mixed $msg,
        string $userMessage,
        string $systemPrompt,
        ?string $contextPrompt,
        array $history,
        string $model,
        string $chatId = '',
        string $channel = '',
        ?ChatAgent $chatAgent = null,
        string|array $userContent = ''
    ): ?string {
        // If caller did not supply pre-built multimodal content, fall back to plain text.
        if ($userContent === '') {
            $userContent = $userMessage;
        }

        $tools = $this->getEnabledTools($chatAgent);
        $pendingKey = $this->pendingToolKey($chatId, $channel);

        // 1. OpenAI explicit tool call — highest priority.
        foreach ($tools as $tool) {
            $arguments = $this->extractArguments($msg, $tool->tool_name);
            if ($arguments !== null) {
                // Guard: if a chain-pending tool is waiting and OpenAI just called the
                // *source* tool again (not the pending target), ignore this explicit call
                // and fall through to Step 2 so the pending chain tool is executed instead.
                // This prevents the source tool from re-running when the user is responding
                // to a chain prompt (e.g. user sends deposit screenshot after resetPassword
                // triggered verifyDeposit — OpenAI sees history + data and re-calls
                // resetPassword, but we must redirect to verifyDeposit).
                if ($pendingKey !== null) {
                    $pendingTarget = Cache::get($pendingKey);
                    if ($pendingTarget !== null && $pendingTarget !== $tool->tool_name) {
                        Log::info('Skipped explicit tool_call: active chain pending for different tool', [
                            'called_tool'   => $tool->tool_name,
                            'pending_tool'  => $pendingTarget,
                        ]);
                        continue;
                    }
                }

                // Guard against hallucinated tool_calls: if OpenAI calls a tool
                // but the user's CURRENT message doesn't match any of its keywords
                // and clearly matches a different tool, skip this call and let
                // keyword matching (step 3) route to the correct tool.
                $toolKeywords = $tool->keywords ?? [];
                if (!empty($toolKeywords) && $tool->matchScore($userMessage) === 0) {
                    $otherToolMatches = false;
                    foreach ($tools as $otherTool) {
                        if ($otherTool->tool_name !== $tool->tool_name && $otherTool->matchScore($userMessage) > 0) {
                            $otherToolMatches = true;
                            break;
                        }
                    }
                    if ($otherToolMatches) {
                        Log::warning('Skipped hallucinated tool_call', [
                            'called_tool' => $tool->tool_name,
                            'user_message' => $userMessage,
                        ]);
                        continue;
                    }
                }

                // If this tool is the currently pending one, merge carry args BEFORE
                // checking for missing required fields — forwarded values may fill the gaps.
                if ($pendingKey !== null && Cache::get($pendingKey) === $tool->tool_name) {
                    $carryArgsKey = $this->carryArgsKey($chatId, $channel);
                    $carryArgs = (array) Cache::get($carryArgsKey, []);
                    foreach ($carryArgs as $key => $val) {
                        if (trim((string) ($arguments[$key] ?? '')) === '') {
                            $arguments[$key] = $val;
                        }
                    }
                }

                // If required fields are missing, arm the pending state so the next
                // message resumes this tool instead of starting fresh.
                if ($tool->needsArguments() && $this->hasMissingRequiredArgs($tool, $arguments)) {
                    if ($pendingKey !== null) {
                        Cache::put($pendingKey, $tool->tool_name, now()->addMinutes(5));
                    }

                    return $this->buildMissingDataMessage($tool);
                }

                if ($pendingKey !== null) {
                    Cache::forget($pendingKey);
                }

                // Clear carry args now that we are proceeding to dispatch.
                Cache::forget($this->carryArgsKey($chatId, $channel));

                return $this->dispatch(
                    $client, $tool, $arguments, $systemPrompt, $contextPrompt, $history, $userMessage, $model,
                    $chatId, $channel
                );
            }
        }

        // 2. Pending tool — resume a tool that previously asked for arguments.
        if ($pendingKey !== null) {
            $pendingToolName = Cache::get($pendingKey);

            if ($pendingToolName !== null) {
                // Bypass is_enabled for chain/pending targets — the tool was
                // already chosen by a chain rule, so we must still execute it.
                $pendingTool = $tools->firstWhere('tool_name', $pendingToolName)
                    ?? $this->findToolByName($pendingToolName);

                if ($pendingTool !== null) {
                    // If the user negates ANY of the pending tool's keywords, abandon it.
                    $rejected = false;
                    foreach (($pendingTool->keywords ?? []) as $kw) {
                        $kw = (string) $kw;
                        if ($kw !== '' && \App\Models\Tool::isNegated($kw, $userMessage)) {
                            $rejected = true;
                            break;
                        }
                    }

                    // Topic-switch detection: if the pending tool scores zero on the new message
                    // but a different tool matches, the user has changed subject — abandon pending.
                    if (!$rejected && $pendingTool->matchScore($userMessage) === 0) {
                        foreach ($tools as $otherTool) {
                            if ($otherTool->tool_name !== $pendingTool->tool_name && $otherTool->matchScore($userMessage) > 0) {
                                $rejected = true;
                                break;
                            }
                        }

                        // Also reject if message contains intent-opening words (new request),
                        // even when no other tool keyword matches. This prevents a pending tool
                        // from being force-dispatched when the customer clearly changes topic
                        // (e.g. "Mau minta jadwal togel" after a pending reject-deposit flow).
                        if (!$rejected && preg_match(
                            '/\b(mau|minta|ingin|pengen|tolong|bantu|cek|lihat|info|gimana|bagaimana|cara|kapan|berapa|kenapa|jadwal|daftar|register|tanya|nanya)\b/iu',
                            $userMessage
                        )) {
                            $rejected = true;
                        }
                    }

                    if ($rejected) {
                        Cache::forget($pendingKey);
                        // Fall through to keyword fallback so the correct tool can match.
                    } else {
                        Cache::forget($pendingKey);

                        // Load any carry args set by a previous tool's chain rule.
                        $carryArgsKey = $this->carryArgsKey($chatId, $channel);
                        $carryArgs = (array) Cache::get($carryArgsKey, []);

                        $arguments = $this->forceExtractArguments(
                            $client, $pendingTool, $contextPrompt, $history, $userMessage, $model, $userContent
                        );

                        if ($arguments !== null) {
                            // Merge carry args: pre-fill fields the AI left empty.
                            foreach ($carryArgs as $key => $val) {
                                if (trim((string) ($arguments[$key] ?? '')) === '') {
                                    $arguments[$key] = $val;
                                }
                            }
                            Cache::forget($carryArgsKey);

                            return $this->dispatch(
                                $client, $pendingTool, $arguments, $systemPrompt, $contextPrompt, $history, $userMessage, $model,
                                $chatId, $channel, $pendingKey, $pendingToolName
                            );
                        }

                        // Extraction still failed — re-store pending and ask again.
                        // Carry args are intentionally kept in cache so the next attempt can use them.
                        Cache::put($pendingKey, $pendingToolName, now()->addMinutes(5));

                        return $this->buildMissingDataMessage($pendingTool);
                    }
                }

                Cache::forget($pendingKey);
            }
        }

        // 3. Keyword-score fallback.
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
            // Guard: if a chain is pending, do not let a keyword match hijack it.
            // The user's message might partially match a tool keyword while they are
            // actually responding to a chain prompt (e.g. "ini bukti transfer" might
            // score on a keyword). Fall through to Step 2 (pending resume) instead.
            $pendingTarget = $pendingKey !== null ? Cache::get($pendingKey) : null;
            if ($pendingTarget !== null && $pendingTarget !== $bestTool->tool_name) {
                Log::info('Skipped keyword-match tool: active chain pending for different tool', [
                    'matched_tool'  => $bestTool->tool_name,
                    'pending_tool'  => $pendingTarget,
                ]);
                // $bestTool is not the chain target — fall through to intent router
                // which will also be blocked below, ultimately letting Step 2 handle it.
                goto skip_keyword_and_intent;
            }

            $arguments = $bestTool->needsArguments()
                ? $this->forceExtractArguments(
                    $client, $bestTool, $contextPrompt, $history, $userMessage, $model, $userContent
                )
                : [];

            if ($bestTool->needsArguments() && ($arguments === null || $this->hasMissingRequiredArgs($bestTool, $arguments))) {
                if ($pendingKey !== null) {
                    Cache::put($pendingKey, $bestTool->tool_name, now()->addMinutes(5));
                }

                return $this->buildMissingDataMessage($bestTool);
            }

            return $this->dispatch(
                $client, $bestTool, $arguments ?? [], $systemPrompt, $contextPrompt, $history, $userMessage, $model,
                $chatId, $channel
            );
        }

        // 4. AI intent-router fallback (no keyword dependency).
        $intentRoutedReply = $this->routeToolByIntent(
            $client,
            $tools,
            $systemPrompt,
            $contextPrompt,
            $history,
            $userMessage,
            $model,
            $chatId,
            $channel,
            $pendingKey
        );

        if ($intentRoutedReply !== null) {
            return $intentRoutedReply;
        }

        // Label used by the pending-chain guard in Step 3 above.
        skip_keyword_and_intent:

        return null;
    }

    /**
     * Final fallback router that selects a tool by intent using OpenAI tool_choice,
     * without relying on local keyword matching.
     *
     * @param Collection<int, Tool> $tools
     * @param array<int, array<string, string>> $history
     */
    private function routeToolByIntent(
        mixed $client,
        Collection $tools,
        string $systemPrompt,
        ?string $contextPrompt,
        array $history,
        string $userMessage,
        string $model,
        string $chatId,
        string $channel,
        ?string $pendingKey
    ): ?string {
        if ($tools->isEmpty()) {
            return null;
        }

        $toolDefinitions = $tools
            ->map(fn (Tool $tool) => $tool->getDefinition())
            ->filter()
            ->values()
            ->all();

        if ($toolDefinitions === []) {
            return null;
        }

        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
            [
                'role' => 'system',
                'content' => 'You are a tool-intent router. Choose a relevant enabled tool based on the user request and tool definitions. Do not hardcode tool names. If no tool is relevant, do not call any tool.',
            ],
        ];

        if ($contextPrompt !== null) {
            $messages[] = ['role' => 'system', 'content' => $contextPrompt];
        }

        foreach (array_slice($history, -6) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $role = $item['role'] ?? null;
            $content = $item['content'] ?? null;

            if (!in_array($role, ['user', 'assistant'], true) || !is_string($content) || trim($content) === '') {
                continue;
            }

            $messages[] = ['role' => $role, 'content' => $content];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $openaiStart = MetricsCollector::startTimer();
            $response = $client->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'tools' => $toolDefinitions,
                'tool_choice' => 'auto',
                'max_completion_tokens' => 120,
            ]);
            $openaiLatency = MetricsCollector::elapsed($openaiStart);

            $usage = [
                'prompt_tokens' => $response->usage->promptTokens ?? 0,
                'completion_tokens' => $response->usage->completionTokens ?? 0,
                'total_tokens' => $response->usage->totalTokens ?? 0,
            ];
            MetricsCollector::recordOpenAiCall('system', $model, 'intent_route', $openaiLatency, $usage);

            $message = $response->choices[0]->message ?? null;
            if ($message === null) {
                return null;
            }

            foreach ($tools as $tool) {
                $arguments = $this->extractArguments($message, $tool->tool_name);
                if ($arguments === null) {
                    continue;
                }

                if ($tool->needsArguments() && $this->hasMissingRequiredArgs($tool, $arguments)) {
                    if ($pendingKey !== null) {
                        Cache::put($pendingKey, $tool->tool_name, now()->addMinutes(5));
                    }

                    return $this->buildMissingDataMessage($tool);
                }

                if ($pendingKey !== null) {
                    Cache::forget($pendingKey);
                }

                return $this->dispatch(
                    $client,
                    $tool,
                    $arguments,
                    $systemPrompt,
                    $contextPrompt,
                    $history,
                    $userMessage,
                    $model,
                    $chatId,
                    $channel
                );
            }
        } catch (\Throwable $e) {
            MetricsCollector::recordOpenAiCall('system', $model, 'intent_route', 0, null, false);
            Log::warning('AI intent-router fallback failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Check whether any required tool parameters are absent or empty in $arguments.
     *
     * @param array<string, mixed> $arguments
     */
    private function hasMissingRequiredArgs(Tool $tool, array $arguments): bool
    {
        $required = (array) data_get($tool->parameters, 'required', []);
        foreach ($required as $field) {
            if (trim((string) ($arguments[$field] ?? '')) === '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Fetch a single tool by name regardless of is_enabled — used when resuming
     * a pending or chain-triggered tool that may itself be disabled in the list.
     */
    private function findToolByName(string $toolName): ?Tool
    {
        return Tool::query()->with('dataModel')->where('tool_name', $toolName)->first();
    }

    /**
     * Build the cache key for a pending tool, or null when chat identity is unknown.
     */
    private function pendingToolKey(string $chatId, string $channel): ?string
    {
        if ($chatId === '') {
            return null;
        }

        $prefix = $channel !== '' ? $channel . ':' : '';

        return "pending_tool:{$prefix}{$chatId}";
    }

    /**
     * Generate the user-facing missing-data prompt from the tool's parameter schema.
     */
    public function buildMissingDataMessage(Tool $tool): string
    {
        $properties = (array) data_get($tool->parameters, 'properties', []);
        if ($properties === []) {
            return 'Mohon lengkapi data yang diperlukan.';
        }

        $lines = ["Untuk {$tool->display_name}, mohon kirimkan data berikut:"];
        foreach ($properties as $name => $prop) {
            $desc = $prop['description'] ?? $name;
            $lines[] = "- {$desc} ({$name})";
        }

        return implode("\n", $lines);
    }

    // ─── Private dispatch helpers ────────────────────────────────────────────

    /**
     * Run the engine for the given tool + arguments and return the reply.
     *
     * @param array<string, mixed> $arguments
     * @param array<int, array<string, string>> $history
     */
    private function dispatch(
        mixed $client,
        Tool $tool,
        array $arguments,
        string $systemPrompt,
        ?string $contextPrompt,
        array $history,
        string $userMessage,
        string $model,
        string $chatId = '',
        string $channel = '',
        ?string $pendingKey = null,
        ?string $pendingToolName = null
    ): ?string {
        // Hard block: chain_only tools must never be dispatched unless they arrived
        // here via a pending chain (pendingToolName is set and matches this tool).
        // This is the last line of defence regardless of how the call reached dispatch().
        if (($tool->meta['trigger_mode'] ?? null) === 'chain_only'
            && $pendingToolName !== $tool->tool_name) {
            Log::warning('Blocked direct dispatch of chain_only tool', [
                'tool_name'    => $tool->tool_name,
                'pending_name' => $pendingToolName,
            ]);
            return null;
        }

        $engineStart = MetricsCollector::startTimer();
        $engineError = null;

        try {
            $execution = $this->runEngine($tool, $arguments);
        } catch (\Throwable $e) {
            $engineError = $e->getMessage();
            MetricsCollector::recordToolExecution(
                'system', $tool->tool_name, $tool->type, MetricsCollector::elapsed($engineStart), false, $engineError
            );
            throw $e;
        }

        MetricsCollector::recordToolExecution(
            'system',
            $tool->tool_name,
            $tool->type,
            MetricsCollector::elapsed($engineStart),
            ($execution['mode'] === 'direct' ? ($execution['reply'] !== null) : true)
        );

        if ($execution['mode'] === 'model') {
            $toolContext = $execution['tool_context'] ?? [];

            // Inject per-tool rules so the AI follows them when composing the reply.
            $rules = trim((string) ($tool->tool_rules ?? ''));
            if ($rules !== '') {
                $toolContext['tool_rules'] = $rules;
            }

            // Evaluate chain rules — if a rule fires, arm the next tool and return its prompt.
            $chainReply = $this->evaluateChainRules($tool, $toolContext, $arguments, $chatId, $channel);
            if ($chainReply !== null) {
                return $chainReply;
            }

            // If the HTTP tool returned a failure, re-arm the pending key so the
            // customer can re-send corrected data without repeating the keyword.
            if ($pendingKey !== null && ($toolContext['success'] ?? true) === false) {
                Cache::put($pendingKey, $pendingToolName ?? $tool->tool_name, now()->addMinutes(5));
            }

            return $this->generateReplyFromToolResult(
                $client, $systemPrompt, $contextPrompt, $history, $userMessage, $toolContext, $model
            );
        }

        return $execution['reply'] ?? null;
    }

    /**
     * Route to the correct engine based on tool type.
     *
     * @param array<string, mixed> $arguments
     * @return array{mode: string, reply?: string, tool_context?: array<string, mixed>}
     */
    private function runEngine(Tool $tool, array $arguments): array
    {
        return match ($tool->type) {
            'info' => $this->infoEngine->execute($tool),
            'get' => $this->dataModelEngine->executeSingle($tool, $arguments),
            'get_multiple' => $this->dataModelEngine->executeMultiple($tool, $arguments),
            'update' => $this->httpEngine->execute($tool, $arguments),
            default => [
                'mode' => 'direct',
                'reply' => "Tool {$tool->display_name} belum dikonfigurasi.",
            ],
        };
    }

    /**
     * Second OpenAI call: convert the raw tool_context payload into a
     * natural-language customer service reply.
     *
     * @param array<int, array<string, string>> $history
     * @param array<string, mixed> $toolContext
     */
    private function generateReplyFromToolResult(
        mixed $client,
        string $systemPrompt,
        ?string $contextPrompt,
        array $history,
        string $userMessage,
        array $toolContext,
        string $model
    ): string {
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        if ($contextPrompt !== null) {
            $messages[] = ['role' => 'system', 'content' => $contextPrompt];
        }

        $executionType = (string) ($toolContext['execution_type'] ?? '');
        $toolName = (string) ($toolContext['tool_display_name'] ?? $toolContext['tool_name'] ?? 'tool');
        $responseMessage = trim((string) ($toolContext['response_message'] ?? ''));
        $success = (bool) ($toolContext['success'] ?? true);
        $toolRulesInstruction = '';
        if (!empty($toolContext['tool_rules'])) {
            $toolRulesInstruction = "\n\nIMPORTANT — Follow these tool-specific rules strictly:\n" . $toolContext['tool_rules'];
        }

        // Remove fields already handled or internal-only to shrink the JSON payload.
        $cleanContext = $toolContext;
        unset($cleanContext['tool_rules']);

        $resultInstruction = 'Use the tool context as the only source of truth. Do not mention internal tools, SQL, database query details, or raw JSON.';

        if ($executionType === 'http_endpoint') {
            $resultInstruction .= ' This tool has already finished running. Do not ask to retry automatically and do not suggest that you are checking again.';

            if ($success) {
                $resultInstruction .= ' If response_message exists, use it as the main outcome and explain any useful response_data briefly.';
            } else {
                $resultInstruction .= ' The request failed. Explain the failure using response_message and status fields, tell the user what was rejected or why it failed, and only ask for corrected input if the tool result clearly indicates missing or invalid data.';
            }
        } else {
            $resultInstruction .= ' If the resolved result is empty, say the data was not found and ask the user to re-check their input.';
        }

        foreach (array_slice($history, -6) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $role = $item['role'] ?? null;
            $content = $item['content'] ?? null;

            if (!in_array($role, ['user', 'assistant'], true) || !is_string($content) || trim($content) === '') {
                continue;
            }

            $messages[] = ['role' => $role, 'content' => $content];
        }

        // Tool context is injected AFTER history so GPT anchors on the fresh result,
        // not the most recent history turn (which may be a different previous query).
        $messages[] = [
            'role' => 'system',
            'content' => "Internal tool result already fetched. Write the final reply to the user now. {$resultInstruction} Do not copy data literally as JSON.{$toolRulesInstruction}

Original user request:\n{$userMessage}

Tool context:\n" . json_encode($cleanContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ];

        $messages[] = [
            'role' => 'user',
            'content' => "Buat jawaban final untuk user berdasarkan tool context di atas. Jangan memanggil tool lagi. Jangan retry otomatis."
                . ($responseMessage !== '' ? " Utamakan penjelasan dari pesan ini: {$responseMessage}" : ''),
        ];

        try {
            $openaiStart = MetricsCollector::startTimer();
            $response = $client->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'max_completion_tokens' => 220,
            ]);
            $openaiLatency = MetricsCollector::elapsed($openaiStart);

            $usage = [
                'prompt_tokens' => $response->usage->promptTokens ?? 0,
                'completion_tokens' => $response->usage->completionTokens ?? 0,
                'total_tokens' => $response->usage->totalTokens ?? 0,
            ];
            MetricsCollector::recordOpenAiCall('system', $model, 'tool_reply', $openaiLatency, $usage);

            $reply = trim((string) ($response->choices[0]->message->content ?? ''));

            if ($reply !== '') {
                return $reply;
            }
        } catch (\Throwable $e) {
            MetricsCollector::recordOpenAiCall('system', $model, 'tool_reply', 0, null, false);
            Log::warning('AI tool reply generation failed', [
                'tool_name' => $toolContext['tool_name'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->buildToolReplyFallback($toolContext, $toolName, $responseMessage, $success);
    }

    /**
     * Build a deterministic fallback so HTTP tools still return a useful answer
     * when the model reply is empty or unavailable.
     *
     * @param array<string, mixed> $toolContext
     */
    private function buildToolReplyFallback(
        array $toolContext,
        string $toolName,
        string $responseMessage,
        bool $success
    ): string {
        $executionType = (string) ($toolContext['execution_type'] ?? '');

        if ($executionType === 'http_endpoint') {
            if ($responseMessage !== '') {
                return $success
                    ? $responseMessage
                    : "Permintaan {$toolName} belum berhasil diproses. {$responseMessage}";
            }

            return $success
                ? "Permintaan {$toolName} berhasil diproses."
                : "Permintaan {$toolName} belum berhasil diproses. Silakan cek kembali data yang dikirim.";
        }

        return 'Data berhasil dicek. Saya bantu jelaskan hasilnya ya.';
    }

    /**
     * Force OpenAI to extract tool arguments from the user's message via a
     * dedicated tool_choice call, for tools the model didn't call automatically.
     *
     * @param array<int, array<string, string>> $history
     * @return array<string, mixed>|null  null means extraction failed
     */
    private function forceExtractArguments(
        mixed $client,
        Tool $tool,
        ?string $contextPrompt,
        array $history,
        string $userMessage,
        string $model,
        string|array $userContent = ''
    ): ?array {
        $definition = $tool->getDefinition();

        if ($definition === null) {
            return [];
        }

        // Use a minimal prompt — only extraction instruction + context.
        // The full system prompt (agent rules, base prompt) is not needed here.
        $messages = [
            [
                'role' => 'system',
                'content' => "Extract arguments for the tool from the conversation and the user's latest message (which may include an image/screenshot). Be flexible: accept natural language, mixed order, shorthand, or abbreviations. Use the latest message first, then recent history for any missing text-based values. For bank account name (namarek), accept any string as-is. IMPORTANT: for fields that must be extracted from a visual source (e.g. depoamount, time from a screenshot), only populate them if the image is present in the current message and you can clearly read the value. Do NOT guess, invent, or use placeholder values for visual fields. Leave them as empty string \"\" if the image is absent or the value is not clearly visible — the application will then ask the user to resend the screenshot.",
            ],
        ];

        if ($contextPrompt !== null) {
            $messages[] = ['role' => 'system', 'content' => $contextPrompt];
        }

        foreach (array_slice($history, -6) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $role = $item['role'] ?? null;
            $content = $item['content'] ?? null;

            if (!in_array($role, ['user', 'assistant'], true) || !is_string($content) || trim($content) === '') {
                continue;
            }

            $messages[] = ['role' => $role, 'content' => $content];
        }

        // Use multimodal content (with image) when available so visual fields
        // like depoamount and time can be extracted from the screenshot.
        $messages[] = ['role' => 'user', 'content' => ($userContent !== '' ? $userContent : $userMessage)];

        try {
            $openaiStart = MetricsCollector::startTimer();
            $response = $client->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'tools' => [$definition],
                'tool_choice' => [
                    'type' => 'function',
                    'function' => ['name' => $tool->tool_name],
                ],
                'max_completion_tokens' => 120,
            ]);
            $openaiLatency = MetricsCollector::elapsed($openaiStart);

            $usage = [
                'prompt_tokens' => $response->usage->promptTokens ?? 0,
                'completion_tokens' => $response->usage->completionTokens ?? 0,
                'total_tokens' => $response->usage->totalTokens ?? 0,
            ];
            MetricsCollector::recordOpenAiCall('system', $model, 'force_extract', $openaiLatency, $usage);

            $message = $response->choices[0]->message ?? null;

            return $message !== null
                ? $this->extractArguments($message, $tool->tool_name)
                : null;
        } catch (\Throwable $e) {
            MetricsCollector::recordOpenAiCall('system', $model, 'force_extract', 0, null, false);
            Log::warning('AI forced tool argument extraction failed', [
                'tool_name' => $tool->tool_name,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Parse tool_calls (or legacy functionCall) from an OpenAI message object.
     * Returns the decoded arguments array, or null if the given tool wasn't called.
     *
     * @return array<string, mixed>|null
     */
    private function extractArguments(mixed $msg, string $toolName): ?array
    {
        $toolCalls = $msg->toolCalls ?? [];

        if (is_array($toolCalls)) {
            foreach ($toolCalls as $toolCall) {
                $function = $toolCall->function ?? null;
                $name = $function->name ?? null;

                if ($name !== $toolName) {
                    continue;
                }

                return $this->normalizeArguments($function->arguments ?? '{}');
            }
        }

        // Backward compatibility for legacy functionCall field.
        $legacyCall = $msg->functionCall ?? null;
        if (($legacyCall->name ?? null) === $toolName) {
            return $this->normalizeArguments($legacyCall->arguments ?? '{}');
        }

        return null;
    }

    /**
     * Cache key for carry args stored by a chain rule.
     */
    private function carryArgsKey(string $chatId, string $channel): string
    {
        $prefix = $channel !== '' ? $channel . ':' : '';
        return "chain_carry:{$prefix}{$chatId}";
    }

    /**
     * Evaluate chain_rules from tool endpoints config.
     * If a rule matches the tool_context, arm the chained tool as pending,
     * cache any carry args, and return the prompt/message for the user.
     *
     * @param array<string, mixed> $toolContext
     * @param array<string, mixed> $arguments
     */
    private function evaluateChainRules(
        Tool $tool,
        array $toolContext,
        array $arguments,
        string $chatId,
        string $channel
    ): ?string {
        $chainRules = (array) ($tool->endpoints['chain_rules'] ?? []);

        if (empty($chainRules) || $chatId === '') {
            return null;
        }

        $success = (bool) ($toolContext['success'] ?? true);

        foreach ($chainRules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $on = $rule['on'] ?? 'failure';
            if ($on === 'failure' && $success) {
                continue;
            }
            if ($on === 'success' && !$success) {
                continue;
            }

            $conditionField = $rule['field'] ?? 'response_message';
            $condition      = $rule['condition'] ?? 'contains';
            $matchValue     = strtolower(trim((string) ($rule['value'] ?? '')));
            $fieldValue     = strtolower(trim((string) ($toolContext[$conditionField] ?? '')));

            $matched = match ($condition) {
                'contains' => $matchValue !== '' && str_contains($fieldValue, $matchValue),
                'equals'   => $fieldValue === $matchValue,
                default    => false,
            };

            if (!$matched) {
                continue;
            }

            $chainToolName = trim((string) ($rule['chain_tool'] ?? ''));
            if ($chainToolName === '') {
                continue;
            }

            // ── Human support escalation ────────────────────────────────────
            if ($chainToolName === 'human_support') {
                // Move the customer to the waiting queue.
                try {
                    $customer = Customer::query()
                        ->where('platform_user_id', $chatId)
                        ->where('platform', $channel)
                        ->first();

                    if ($customer !== null) {
                        $customer->update(['mode' => 'waiting']);
                        Log::info('Customer escalated to waiting queue via chain rule', [
                            'source_tool' => $tool->tool_name,
                            'customer_id' => $customer->id,
                            'channel'     => $channel,
                            'chat_id'     => $chatId,
                        ]);

                        // Generate summary so backoffice agents see the escalation context.
                        app(EscalationSummaryService::class)->generate($customer, $chatId, $channel);
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed to escalate customer via chain rule', [
                        'source_tool' => $tool->tool_name,
                        'chat_id'     => $chatId,
                        'error'       => $e->getMessage(),
                    ]);
                }

                $customMessage = trim((string) ($rule['message'] ?? ''));
                return $customMessage !== ''
                    ? $customMessage
                    : 'Permintaan Anda sedang diteruskan ke agen kami. Mohon tunggu sebentar 🙏';
            }

            // ── Chain to another tool ───────────────────────────────────────
            $pendingKey = $this->pendingToolKey($chatId, $channel);
            if ($pendingKey !== null) {
                Cache::put($pendingKey, $chainToolName, now()->addMinutes(10));
            }

            // Cache carry args so the pending resume flow can inject them.
            $carryArgKeys = (array) ($rule['carry_args'] ?? []);
            if (!empty($carryArgKeys)) {
                $carry = [];
                foreach ($carryArgKeys as $argKey) {
                    $val = trim((string) ($arguments[$argKey] ?? ''));
                    if ($val !== '') {
                        $carry[(string) $argKey] = $arguments[$argKey];
                    }
                }
                if (!empty($carry)) {
                    Cache::put($this->carryArgsKey($chatId, $channel), $carry, now()->addMinutes(10));
                }
            }

            Log::info('Tool chain rule triggered', [
                'source_tool' => $tool->tool_name,
                'chain_tool'  => $chainToolName,
                'on'          => $on,
                'field'       => $conditionField,
                'matched'     => $rule['value'] ?? '',
            ]);

            // Return custom message if configured.
            $customMessage = trim((string) ($rule['message'] ?? ''));
            if ($customMessage !== '') {
                return $customMessage;
            }

            // Otherwise use the chained tool's missing-data prompt.
            try {
                $chainTool = Tool::query()->where('tool_name', $chainToolName)->first();
                if ($chainTool !== null) {
                    return $this->buildMissingDataMessage($chainTool);
                }
            } catch (\Throwable) {
                // Fallthrough to generic message.
            }

            return 'Mohon lengkapi data yang diperlukan untuk langkah berikutnya.';
        }

        return null;
    }

    /**
     * Normalise raw arguments (JSON string, array, or object) to a plain array.
     *
     * @return array<string, mixed>
     */
    private function normalizeArguments(mixed $argumentsRaw): array
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
