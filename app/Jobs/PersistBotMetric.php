<?php

namespace App\Jobs;

use App\Models\BotMetric;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PersistBotMetric implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /**
     * @param array<int, array{metric_type: string, channel: string, meta: array<string, mixed>}> $records
     */
    public function __construct(
        public readonly array $records,
    ) {}

    public function handle(): void
    {
        if ($this->records === []) {
            return;
        }

        try {
            // Single multi-row INSERT — much cheaper than N individual queries.
            DB::table('bot_metrics')->insert(
                array_map(fn (array $r) => [
                    'metric_type' => $r['metric_type'],
                    'channel'     => $r['channel'],
                    'meta'        => json_encode($r['meta']),
                ], $this->records)
            );
        } catch (\Throwable $e) {
            Log::debug('PersistBotMetric batch insert failed, falling back one-by-one', [
                'count' => count($this->records),
                'error' => $e->getMessage(),
            ]);

            // Fallback: insert individually so partial success is possible.
            foreach ($this->records as $r) {
                try {
                    BotMetric::query()->create([
                        'metric_type' => $r['metric_type'],
                        'channel'     => $r['channel'],
                        'meta'        => $r['meta'],
                    ]);
                } catch (\Throwable) {
                    // Swallow — metrics must never crash the application.
                }
            }
        }
    }
}
