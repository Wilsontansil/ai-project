<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\BotMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MetricsController extends Controller
{
    public function index(Request $request): View
    {
        $range = $request->query('range', 'today');
        $from = match ($range) {
            '7d' => now()->subDays(7)->startOfDay(),
            '30d' => now()->subDays(30)->startOfDay(),
            'today' => now()->startOfDay(),
            default => now()->startOfDay(),
        };

        // ── Throughput ─────────────────────────────────────────
        $throughput = BotMetric::query()
            ->where('metric_type', 'request')
            ->where('created_at', '>=', $from)
            ->select('channel', DB::raw('COUNT(*) as total'))
            ->groupBy('channel')
            ->pluck('total', 'channel')
            ->toArray();

        $throughputTotal = array_sum($throughput);

        // ── Avg latency per channel ────────────────────────────
        $latencyRows = BotMetric::query()
            ->where('metric_type', 'request')
            ->where('created_at', '>=', $from)
            ->get(['channel', 'meta']);

        $latencyByChannel = [];
        foreach ($latencyRows as $row) {
            $ch = $row->channel;
            $ms = (float) ($row->meta['latency_ms'] ?? 0);
            $latencyByChannel[$ch][] = $ms;
        }

        $avgLatency = [];
        foreach ($latencyByChannel as $ch => $values) {
            $avgLatency[$ch] = round(array_sum($values) / count($values));
        }

        // ── OpenAI costs ───────────────────────────────────────
        $openaiRows = BotMetric::query()
            ->where('metric_type', 'openai_call')
            ->where('created_at', '>=', $from)
            ->get(['meta']);

        $totalTokens = 0;
        $totalCost = 0.0;
        $openaiCalls = $openaiRows->count();
        $openaiByPurpose = [];
        $openaiAvgLatency = [];

        foreach ($openaiRows as $row) {
            $meta = $row->meta ?? [];
            $totalTokens += (int) ($meta['total_tokens'] ?? 0);
            $totalCost += (float) ($meta['estimated_cost_usd'] ?? 0);

            $purpose = $meta['purpose'] ?? 'unknown';
            $openaiByPurpose[$purpose] = ($openaiByPurpose[$purpose] ?? 0) + 1;
            $openaiAvgLatency[$purpose][] = (float) ($meta['latency_ms'] ?? 0);
        }

        foreach ($openaiAvgLatency as $purpose => $values) {
            $openaiAvgLatency[$purpose] = round(array_sum($values) / count($values));
        }

        // ── Tool executions ────────────────────────────────────
        $toolRows = BotMetric::query()
            ->where('metric_type', 'tool_exec')
            ->where('created_at', '>=', $from)
            ->get(['meta']);

        $toolStats = [];
        foreach ($toolRows as $row) {
            $meta = $row->meta ?? [];
            $name = $meta['tool_name'] ?? 'unknown';
            if (!isset($toolStats[$name])) {
                $toolStats[$name] = ['total' => 0, 'failures' => 0, 'latencies' => [], 'tool_type' => $meta['tool_type'] ?? ''];
            }
            $toolStats[$name]['total']++;
            if (!($meta['success'] ?? true)) {
                $toolStats[$name]['failures']++;
            }
            $toolStats[$name]['latencies'][] = (float) ($meta['latency_ms'] ?? 0);
        }

        foreach ($toolStats as $name => &$stat) {
            $stat['avg_latency'] = round(array_sum($stat['latencies']) / count($stat['latencies']));
            $stat['failure_rate'] = round(($stat['failures'] / $stat['total']) * 100, 1);
            unset($stat['latencies']);
        }
        unset($stat);

        // ── Outbound HTTP ──────────────────────────────────────
        $httpRows = BotMetric::query()
            ->where('metric_type', 'outbound_http')
            ->where('created_at', '>=', $from)
            ->get(['channel', 'meta']);

        $httpByService = [];
        foreach ($httpRows as $row) {
            $svc = $row->channel;
            if (!isset($httpByService[$svc])) {
                $httpByService[$svc] = ['total' => 0, 'failures' => 0, 'latencies' => []];
            }
            $httpByService[$svc]['total']++;
            if (!($row->meta['success'] ?? true)) {
                $httpByService[$svc]['failures']++;
            }
            $httpByService[$svc]['latencies'][] = (float) ($row->meta['latency_ms'] ?? 0);
        }

        foreach ($httpByService as $svc => &$stat) {
            $stat['avg_latency'] = round(array_sum($stat['latencies']) / count($stat['latencies']));
            unset($stat['latencies']);
        }
        unset($stat);

        // ── Throughput timeline (hourly buckets) ───────────────
        $timelineRows = BotMetric::query()
            ->where('metric_type', 'request')
            ->where('created_at', '>=', $from)
            ->orderBy('created_at')
            ->get(['channel', 'created_at']);

        $timeline = [];
        foreach ($timelineRows as $row) {
            // SQLite's CURRENT_TIMESTAMP stores UTC; shift to app timezone for display.
            $bucket = \Illuminate\Support\Carbon::parse($row->created_at)
                ->shiftTimezone('UTC')
                ->setTimezone(config('app.timezone'))
                ->format('Y-m-d H:00');
            $ch = $row->channel;
            $timeline[$bucket][$ch] = ($timeline[$bucket][$ch] ?? 0) + 1;
        }

        return view('backoffice.metrics', [
            'range' => $range,
            'throughput' => $throughput,
            'throughputTotal' => $throughputTotal,
            'avgLatency' => $avgLatency,
            'totalTokens' => $totalTokens,
            'totalCost' => $totalCost,
            'openaiCalls' => $openaiCalls,
            'openaiByPurpose' => $openaiByPurpose,
            'openaiAvgLatency' => $openaiAvgLatency,
            'toolStats' => $toolStats,
            'httpByService' => $httpByService,
            'timeline' => $timeline,
        ]);
    }
}
