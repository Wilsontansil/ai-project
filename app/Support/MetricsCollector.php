<?php

namespace App\Support;

use App\Jobs\PersistBotMetric;
use App\Models\BotMetric;
use Illuminate\Support\Facades\Log;

/**
 * Lightweight observability collector.
 *
 * Records structured metric events to the bot_metrics table.
 * All methods are fire-and-forget — failures are logged but never bubble up.
 *
 * Metric types:
 *   request      — webhook request throughput + end-to-end latency
 *   openai_call  — individual OpenAI API call latency, token usage, estimated cost
 *   tool_exec    — tool engine execution latency + success/failure
 *   outbound_http — ResilientHttp call latency + status
 */
class MetricsCollector
{
    // ── OpenAI pricing (USD per 1K tokens) — gpt-4.1-mini defaults ──────
    private const DEFAULT_INPUT_COST_PER_1K = 0.0004;
    private const DEFAULT_OUTPUT_COST_PER_1K = 0.0016;

    /**
     * Start a high-resolution timer. Returns the start time.
     */
    public static function startTimer(): float
    {
        return microtime(true);
    }

    /**
     * Calculate elapsed milliseconds since $start.
     */
    public static function elapsed(float $start): float
    {
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * Record a webhook request (throughput + end-to-end latency).
     */
    public static function recordRequest(string $channel, float $latencyMs, bool $success = true): void
    {
        self::record('request', $channel, [
            'latency_ms' => $latencyMs,
            'success' => $success,
        ]);
    }

    /**
     * Record an OpenAI API call with token usage and estimated cost.
     *
     * @param array{prompt_tokens?: int, completion_tokens?: int, total_tokens?: int}|null $usage
     */
    public static function recordOpenAiCall(
        string $channel,
        string $model,
        string $purpose,
        float $latencyMs,
        ?array $usage = null,
        bool $success = true
    ): void {
        $promptTokens = $usage['prompt_tokens'] ?? 0;
        $completionTokens = $usage['completion_tokens'] ?? 0;
        $totalTokens = $usage['total_tokens'] ?? ($promptTokens + $completionTokens);

        $cost = self::estimateCost($promptTokens, $completionTokens);

        self::record('openai_call', $channel, [
            'model' => $model,
            'purpose' => $purpose,
            'latency_ms' => $latencyMs,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'estimated_cost_usd' => $cost,
            'success' => $success,
        ]);
    }

    /**
     * Record a tool engine execution.
     */
    public static function recordToolExecution(
        string $channel,
        string $toolName,
        string $toolType,
        float $latencyMs,
        bool $success = true,
        ?string $error = null
    ): void {
        $meta = [
            'tool_name' => $toolName,
            'tool_type' => $toolType,
            'latency_ms' => $latencyMs,
            'success' => $success,
        ];

        if ($error !== null) {
            $meta['error'] = mb_substr($error, 0, 200);
        }

        self::record('tool_exec', $channel, $meta);
    }

    /**
     * Record an outbound HTTP call (ResilientHttp).
     */
    public static function recordOutboundHttp(
        string $service,
        float $latencyMs,
        ?int $statusCode = null,
        bool $success = true
    ): void {
        self::record('outbound_http', $service, [
            'latency_ms' => $latencyMs,
            'status_code' => $statusCode,
            'success' => $success,
        ]);
    }

    /**
     * Estimate USD cost for an OpenAI call.
     */
    private static function estimateCost(int $promptTokens, int $completionTokens): float
    {
        $inputCost = ($promptTokens / 1000) * self::DEFAULT_INPUT_COST_PER_1K;
        $outputCost = ($completionTokens / 1000) * self::DEFAULT_OUTPUT_COST_PER_1K;

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Persist a metric row asynchronously. Falls back to sync write only
     * if queue dispatch fails, so no metric event is dropped.
     *
     * @param array<string, mixed> $meta
     */
    private static function record(string $metricType, string $channel, array $meta): void
    {
        try {
            PersistBotMetric::dispatch($metricType, $channel, $meta);
        } catch (\Throwable $e) {
            Log::debug('MetricsCollector queue dispatch failed, fallback to sync write', [
                'metric_type' => $metricType,
                'error' => $e->getMessage(),
            ]);

            self::writeSync($metricType, $channel, $meta);
        }
    }

    /**
     * Persist metric row synchronously.
     *
     * @param array<string, mixed> $meta
     */
    private static function writeSync(string $metricType, string $channel, array $meta): void
    {
        try {
            BotMetric::query()->create([
                'metric_type' => $metricType,
                'channel' => $channel,
                'meta' => $meta,
            ]);
        } catch (\Throwable $e) {
            Log::debug('MetricsCollector write failed', [
                'metric_type' => $metricType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
