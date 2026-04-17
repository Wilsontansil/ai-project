<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Support\MetricsCollector;

/**
 * Outbound HTTP helper with timeout, retry/backoff, and circuit breaker.
 */
class ResilientHttp
{
    private const MAX_ATTEMPTS = 3;
    private const RETRYABLE_STATUS_CODES = [408, 425, 429, 500, 502, 503, 504];
    private const BACKOFF_MS = [200, 500, 1000];
    private const FAILURE_THRESHOLD = 5;
    private const CIRCUIT_OPEN_SECONDS = 60;
    private const FAILURE_TTL_MINUTES = 10;

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $payload
     */
    public static function post(
        string $service,
        string $url,
        array $payload,
        array $headers = [],
        int $timeoutSeconds = 10
    ): ?Response {
        if (self::isCircuitOpen($service)) {
            Log::warning('Outbound HTTP blocked by open circuit breaker', [
                'service' => $service,
                'url' => $url,
            ]);
            MetricsCollector::recordOutboundHttp($service, 0, null, false);

            return null;
        }

        $response = null;
        $lastError = null;
        $callStart = MetricsCollector::startTimer();

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $response = Http::withHeaders($headers)
                    ->timeout($timeoutSeconds)
                    ->post($url, $payload);
            } catch (ConnectionException $e) {
                $lastError = $e;
                self::recordFailure($service);

                if ($attempt < self::MAX_ATTEMPTS) {
                    usleep(self::backoffForAttempt($attempt) * 1000);
                    continue;
                }

                break;
            }

            if ($response->successful()) {
                self::resetFailures($service);
                MetricsCollector::recordOutboundHttp($service, MetricsCollector::elapsed($callStart), $response->status());

                return $response;
            }

            self::recordFailure($service);

            if ($attempt < self::MAX_ATTEMPTS && self::shouldRetryStatus($response->status())) {
                usleep(self::backoffForAttempt($attempt) * 1000);
                continue;
            }

            MetricsCollector::recordOutboundHttp($service, MetricsCollector::elapsed($callStart), $response->status(), false);

            return $response;
        }

        if ($lastError !== null) {
            Log::error('Outbound HTTP failed after retries', [
                'service' => $service,
                'url' => $url,
                'error' => $lastError->getMessage(),
            ]);
            MetricsCollector::recordOutboundHttp($service, MetricsCollector::elapsed($callStart), null, false);
        }

        return $response;
    }

    private static function shouldRetryStatus(int $statusCode): bool
    {
        return in_array($statusCode, self::RETRYABLE_STATUS_CODES, true);
    }

    private static function backoffForAttempt(int $attempt): int
    {
        $lastBackoff = self::BACKOFF_MS[count(self::BACKOFF_MS) - 1];

        return self::BACKOFF_MS[$attempt - 1] ?? $lastBackoff;
    }

    private static function isCircuitOpen(string $service): bool
    {
        $openUntil = Cache::get(self::circuitOpenUntilKey($service));

        if (!is_int($openUntil)) {
            return false;
        }

        return $openUntil > now()->timestamp;
    }

    private static function recordFailure(string $service): void
    {
        $failures = (int) Cache::get(self::failureCountKey($service), 0) + 1;
        Cache::put(self::failureCountKey($service), $failures, now()->addMinutes(self::FAILURE_TTL_MINUTES));

        if ($failures < self::FAILURE_THRESHOLD) {
            return;
        }

        Cache::put(
            self::circuitOpenUntilKey($service),
            now()->addSeconds(self::CIRCUIT_OPEN_SECONDS)->timestamp,
            now()->addSeconds(self::CIRCUIT_OPEN_SECONDS)
        );

        Log::warning('Outbound HTTP circuit breaker opened', [
            'service' => $service,
            'failures' => $failures,
            'open_for_seconds' => self::CIRCUIT_OPEN_SECONDS,
        ]);
    }

    private static function resetFailures(string $service): void
    {
        Cache::forget(self::failureCountKey($service));
        Cache::forget(self::circuitOpenUntilKey($service));
    }

    private static function failureCountKey(string $service): string
    {
        return 'http:resilience:' . $service . ':failures';
    }

    private static function circuitOpenUntilKey(string $service): string
    {
        return 'http:resilience:' . $service . ':open_until';
    }
}
