<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Tool;
use App\Models\ToolCaseLog;
use App\Models\ToolRequestLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TechnicalController extends Controller
{
    public function requestLogs(Request $request): View
    {
        $range = $request->query('range', 'today');
        $toolFilter = $request->query('tool', '');
        $statusFilter = $request->query('status', '');

        $from = match ($range) {
            '7d'  => now()->subDays(7)->startOfDay(),
            '30d' => now()->subDays(30)->startOfDay(),
            default => now()->startOfDay(),
        };

        $query = ToolRequestLog::query()
            ->where('created_at', '>=', $from)
            ->orderByDesc('created_at');

        if ($toolFilter !== '') {
            $query->where('tool_name', $toolFilter);
        }

        if ($statusFilter === 'success') {
            $query->where('success', true);
        } elseif ($statusFilter === 'fail') {
            $query->where('success', false);
        }

        $logs = $query->paginate(30)->withQueryString();

        $toolNames = Tool::query()
            ->where('tool_name', '!=', '_bot_config')
            ->orderBy('display_name')
            ->pluck('display_name', 'tool_name');

        // Summary stats for the period
        $totalCount  = ToolRequestLog::query()->where('created_at', '>=', $from)->count();
        $successCount = ToolRequestLog::query()->where('created_at', '>=', $from)->where('success', true)->count();
        $failCount   = $totalCount - $successCount;
        $avgLatency  = $totalCount > 0
            ? round(ToolRequestLog::query()->where('created_at', '>=', $from)->avg('latency_ms') ?? 0)
            : 0;

        return view('backoffice.technical.request-logs', compact(
            'logs', 'range', 'toolFilter', 'statusFilter',
            'toolNames', 'totalCount', 'successCount', 'failCount', 'avgLatency'
        ));
    }

    public function caseLogs(Request $request): View
    {
        $range = $request->query('range', 'today');
        $toolFilter = $request->query('tool', '');
        $channelFilter = $request->query('channel', '');
        $attachmentFilter = $request->query('attachment', '');
        $triggerFilter = $request->query('trigger', '');

        $from = match ($range) {
            '7d'  => now()->subDays(7)->startOfDay(),
            '30d' => now()->subDays(30)->startOfDay(),
            default => now()->startOfDay(),
        };

        $query = ToolCaseLog::query()
            ->where('created_at', '>=', $from)
            ->orderByDesc('created_at');

        if ($toolFilter !== '') {
            $query->where('tool_name', $toolFilter);
        }
        if ($channelFilter !== '') {
            $query->where('channel', $channelFilter);
        }
        if ($attachmentFilter === 'yes') {
            $query->where('has_attachment', true);
        }
        if ($triggerFilter !== '') {
            $query->where('trigger_mode', $triggerFilter);
        }

        $logs = $query->paginate(30)->withQueryString();

        $toolNames = Tool::query()
            ->where('tool_name', '!=', '_bot_config')
            ->orderBy('display_name')
            ->pluck('display_name', 'tool_name');

        $channels = ToolCaseLog::query()
            ->where('created_at', '>=', $from)
            ->distinct()
            ->orderBy('channel')
            ->pluck('channel');

        $totalCount = ToolCaseLog::query()->where('created_at', '>=', $from)->count();
        $attachmentCount = ToolCaseLog::query()->where('created_at', '>=', $from)->where('has_attachment', true)->count();

        return view('backoffice.technical.case-logs', compact(
            'logs', 'range', 'toolFilter', 'channelFilter', 'attachmentFilter', 'triggerFilter',
            'toolNames', 'channels', 'totalCount', 'attachmentCount'
        ));
    }
}
