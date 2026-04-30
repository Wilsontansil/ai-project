<?php

namespace App\Jobs;

use App\Models\BotMetric;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PersistBotMetric implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public readonly string $metricType,
        public readonly string $channel,
        public readonly array $meta,
    ) {}

    public function handle(): void
    {
        try {
            BotMetric::query()->create([
                'metric_type' => $this->metricType,
                'channel' => $this->channel,
                'meta' => $this->meta,
            ]);
        } catch (\Throwable $e) {
            Log::debug('PersistBotMetric write failed', [
                'metric_type' => $this->metricType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
