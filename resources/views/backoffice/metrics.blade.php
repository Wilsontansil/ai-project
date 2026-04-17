@extends('backoffice.partials.layout')

@section('title', 'Metrics')
@section('page-title', 'Metrics')

@php $boActive = 'metrics'; @endphp

@section('content')
    <style>
        .bo-metrics-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }

        @media (min-width: 768px) {
            .bo-metrics-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 1rem;
            }
        }

        .metrics-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .metrics-table th {
            text-align: left;
            padding: 8px 12px;
            color: rgba(148, 163, 184, 1);
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid rgba(51, 65, 85, 0.5);
        }

        .metrics-table td {
            padding: 8px 12px;
            border-bottom: 1px solid rgba(51, 65, 85, 0.3);
            color: #e2e8f0;
        }

        .metrics-table tr:last-child td {
            border-bottom: none;
        }

        .range-btn {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(51, 65, 85, 0.5);
            background: transparent;
            color: rgba(148, 163, 184, 1);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
        }

        .range-btn:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        .range-btn.active {
            background: rgba(34, 211, 238, 0.15);
            border-color: rgba(34, 211, 238, 0.4);
            color: rgba(34, 211, 238, 1);
        }

        .section-title {
            font-size: 15px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 12px;
        }

        .badge-success {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(52, 211, 153, 0.15);
            color: rgba(52, 211, 153, 1);
        }

        .badge-danger {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(248, 113, 113, 0.15);
            color: rgba(248, 113, 113, 1);
        }

        .timeline-bar {
            height: 24px;
            border-radius: 4px;
            min-width: 2px;
        }
    </style>

    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl" style="color:#fff;font-size:20px;font-weight:600">Observability &
                Metrics</h1>
            <p class="text-xs text-slate-400" style="color:rgba(148,163,184,1);font-size:12px">Throughput, latency, tool
                failures, and OpenAI cost tracking.</p>
        </div>
        <div style="display:flex;gap:6px;">
            <a href="{{ route('backoffice.metrics.index', ['range' => 'today']) }}"
                class="range-btn {{ $range === 'today' ? 'active' : '' }}">Today</a>
            <a href="{{ route('backoffice.metrics.index', ['range' => '7d']) }}"
                class="range-btn {{ $range === '7d' ? 'active' : '' }}">7 Days</a>
            <a href="{{ route('backoffice.metrics.index', ['range' => '30d']) }}"
                class="range-btn {{ $range === '30d' ? 'active' : '' }}">30 Days</a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="bo-metrics-grid">
        <div class="rounded-xl border border-cyan-400/20 bg-cyan-400/10 px-4 py-3 sm:px-5 sm:py-4"
            style="background-color:rgba(34,211,238,0.08);border:1px solid rgba(34,211,238,0.25);border-radius:12px">
            <p class="text-[11px] text-cyan-200/70" style="color:rgba(34,211,238,0.7);font-size:11px">Total Requests</p>
            <p class="text-lg font-bold text-white" style="color:#fff;font-size:18px;font-weight:700">
                {{ number_format($throughputTotal) }}</p>
        </div>
        <div class="rounded-xl border border-violet-400/20 bg-violet-400/10 px-4 py-3 sm:px-5 sm:py-4"
            style="background-color:rgba(168,85,247,0.08);border:1px solid rgba(168,85,247,0.25);border-radius:12px">
            <p class="text-[11px] text-violet-200/70" style="color:rgba(168,85,247,0.7);font-size:11px">OpenAI Calls</p>
            <p class="text-lg font-bold text-white" style="color:#fff;font-size:18px;font-weight:700">
                {{ number_format($openaiCalls) }}</p>
        </div>
        <div class="rounded-xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 sm:px-5 sm:py-4"
            style="background-color:rgba(251,191,36,0.08);border:1px solid rgba(251,191,36,0.25);border-radius:12px">
            <p class="text-[11px] text-amber-200/70" style="color:rgba(251,191,36,0.7);font-size:11px">Total Tokens</p>
            <p class="text-lg font-bold text-white" style="color:#fff;font-size:18px;font-weight:700">
                {{ number_format($totalTokens) }}</p>
        </div>
        <div class="rounded-xl border border-red-400/20 bg-red-400/10 px-4 py-3 sm:px-5 sm:py-4"
            style="background-color:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.25);border-radius:12px">
            <p class="text-[11px] text-red-200/70" style="color:rgba(248,113,113,0.7);font-size:11px">Estimated Cost (USD)
            </p>
            <p class="text-lg font-bold text-white" style="color:#fff;font-size:18px;font-weight:700">
                ${{ number_format($totalCost, 4) }}</p>
        </div>
    </div>

    {{-- Throughput Timeline --}}
    @if (count($timeline) > 0)
        <div style="border-radius:12px;border:1px solid rgba(51,65,85,0.5);background:rgba(15,23,42,0.85);padding:20px">
            <p class="section-title">Throughput Timeline (Hourly)</p>
            @php
                $channelDefs = [
                    'telegram' => ['color' => '#22d3ee', 'label' => 'Telegram'],
                    'whatsapp' => ['color' => '#34d399', 'label' => 'Whatsapp'],
                    'livechat' => ['color' => '#a855f7', 'label' => 'Livechat'],
                ];
                $bucketKeys = array_keys($timeline);
                $bucketCount = count($bucketKeys);

                // Find the max value across all channels for Y-axis scale
                $maxPerChannel = 0;
                foreach ($timeline as $counts) {
                    foreach ($channelDefs as $ch => $def) {
                        $maxPerChannel = max($maxPerChannel, $counts[$ch] ?? 0);
                    }
                }
                $maxPerChannel = max(1, $maxPerChannel);

                // Y-axis: round up to a nice number
                $ySteps = 4;
                $yStep = max(1, ceil($maxPerChannel / $ySteps));
                $yMax = $yStep * $ySteps;

                // SVG dimensions
                $svgW = 800;
                $svgH = 220;
                $padL = 45;
                $padR = 15;
                $padT = 15;
                $padB = 35;
                $chartW = $svgW - $padL - $padR;
                $chartH = $svgH - $padT - $padB;
            @endphp
            <div style="width:100%;overflow-x:auto;">
                <svg viewBox="0 0 {{ $svgW }} {{ $svgH }}" style="width:100%;min-width:400px;height:auto;"
                    xmlns="http://www.w3.org/2000/svg">
                    {{-- Grid lines + Y-axis labels --}}
                    @for ($i = 0; $i <= $ySteps; $i++)
                        @php
                            $yVal = $yStep * $i;
                            $y = $padT + $chartH - ($chartH * $yVal) / $yMax;
                        @endphp
                        <line x1="{{ $padL }}" y1="{{ $y }}" x2="{{ $svgW - $padR }}"
                            y2="{{ $y }}" stroke="rgba(51,65,85,0.4)" stroke-width="1" />
                        <text x="{{ $padL - 8 }}" y="{{ $y + 4 }}" text-anchor="end"
                            fill="rgba(148,163,184,0.7)" font-size="11">{{ $yVal }}</text>
                    @endfor

                    {{-- X-axis labels --}}
                    @foreach ($bucketKeys as $idx => $bucket)
                        @php
                            $x = $bucketCount > 1 ? $padL + ($chartW * $idx) / ($bucketCount - 1) : $padL + $chartW / 2;
                            $label = substr($bucket, 11, 5); // HH:MM
                        @endphp
                        <text x="{{ $x }}" y="{{ $svgH - 8 }}" text-anchor="middle"
                            fill="rgba(148,163,184,0.7)" font-size="10">{{ $label }}</text>
                    @endforeach

                    {{-- Lines + dots per channel --}}
                    @foreach ($channelDefs as $ch => $def)
                        @php
                            $points = [];
                            foreach ($bucketKeys as $idx => $bucket) {
                                $count = $timeline[$bucket][$ch] ?? 0;
                                $x =
                                    $bucketCount > 1
                                        ? $padL + ($chartW * $idx) / ($bucketCount - 1)
                                        : $padL + $chartW / 2;
                                $y = $padT + $chartH - ($chartH * $count) / $yMax;
                                $points[] = ['x' => round($x, 1), 'y' => round($y, 1), 'count' => $count];
                            }
                            $polyline = implode(' ', array_map(fn($p) => $p['x'] . ',' . $p['y'], $points));
                        @endphp
                        @if (count($points) > 1)
                            <polyline points="{{ $polyline }}" fill="none" stroke="{{ $def['color'] }}"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                        @endif
                        @foreach ($points as $p)
                            @if ($p['count'] > 0)
                                <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="4"
                                    fill="{{ $def['color'] }}" stroke="rgba(15,23,42,1)" stroke-width="2">
                                    <title>{{ $ch }}: {{ $p['count'] }}</title>
                                </circle>
                            @endif
                        @endforeach
                    @endforeach
                </svg>
            </div>
            <div style="display:flex;gap:16px;margin-top:10px;">
                @foreach ($channelDefs as $ch => $def)
                    <div style="display:flex;align-items:center;gap:5px;font-size:11px;color:rgba(148,163,184,1)">
                        <div style="width:10px;height:10px;border-radius:3px;background:{{ $def['color'] }}"></div>
                        {{ $def['label'] }}
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
        {{-- Throughput per Channel --}}
        <div style="border-radius:12px;border:1px solid rgba(51,65,85,0.5);background:rgba(15,23,42,0.85);padding:20px">
            <p class="section-title">Throughput by Channel</p>
            <table class="metrics-table">
                <thead>
                    <tr>
                        <th>Channel</th>
                        <th>Requests</th>
                        <th>Avg Latency</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($throughput as $ch => $count)
                        <tr>
                            <td>{{ ucfirst($ch) }}</td>
                            <td>{{ number_format($count) }}</td>
                            <td>{{ isset($avgLatency[$ch]) ? number_format($avgLatency[$ch]) . ' ms' : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align:center;color:rgba(148,163,184,0.5)">No request data yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- OpenAI Calls by Purpose --}}
        <div style="border-radius:12px;border:1px solid rgba(51,65,85,0.5);background:rgba(15,23,42,0.85);padding:20px">
            <p class="section-title">OpenAI Calls by Purpose</p>
            <table class="metrics-table">
                <thead>
                    <tr>
                        <th>Purpose</th>
                        <th>Calls</th>
                        <th>Avg Latency</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($openaiByPurpose as $purpose => $count)
                        <tr>
                            <td>{{ $purpose }}</td>
                            <td>{{ number_format($count) }}</td>
                            <td>{{ isset($openaiAvgLatency[$purpose]) ? number_format($openaiAvgLatency[$purpose]) . ' ms' : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align:center;color:rgba(148,163,184,0.5)">No OpenAI call data
                                yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tool Executions --}}
    <div style="border-radius:12px;border:1px solid rgba(51,65,85,0.5);background:rgba(15,23,42,0.85);padding:20px">
        <p class="section-title">Tool Execution Stats</p>
        <table class="metrics-table">
            <thead>
                <tr>
                    <th>Tool</th>
                    <th>Type</th>
                    <th>Total</th>
                    <th>Failures</th>
                    <th>Failure Rate</th>
                    <th>Avg Latency</th>
                </tr>
            </thead>
            <tbody>
                @forelse($toolStats as $name => $stat)
                    <tr>
                        <td>{{ $name }}</td>
                        <td>{{ $stat['tool_type'] ?? '—' }}</td>
                        <td>{{ $stat['total'] }}</td>
                        <td>{{ $stat['failures'] }}</td>
                        <td>
                            @if ($stat['failure_rate'] > 10)
                                <span class="badge-danger">{{ $stat['failure_rate'] }}%</span>
                            @else
                                <span class="badge-success">{{ $stat['failure_rate'] }}%</span>
                            @endif
                        </td>
                        <td>{{ number_format($stat['avg_latency']) }} ms</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;color:rgba(148,163,184,0.5)">No tool execution data yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Outbound HTTP --}}
    <div style="border-radius:12px;border:1px solid rgba(51,65,85,0.5);background:rgba(15,23,42,0.85);padding:20px">
        <p class="section-title">Outbound HTTP Stats</p>
        <table class="metrics-table">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Total</th>
                    <th>Failures</th>
                    <th>Avg Latency</th>
                </tr>
            </thead>
            <tbody>
                @forelse($httpByService as $svc => $stat)
                    <tr>
                        <td>{{ $svc }}</td>
                        <td>{{ $stat['total'] }}</td>
                        <td>{{ $stat['failures'] }}</td>
                        <td>{{ number_format($stat['avg_latency']) }} ms</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center;color:rgba(148,163,184,0.5)">No outbound HTTP data yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
