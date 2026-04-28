@extends('backoffice.partials.layout')

@section('title', 'Request Log')
@section('page-title', 'Request Log')

@php $boActive = 'request-logs'; @endphp

@section('content')
    <style>
        .rl-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .rl-table th {
            text-align: left;
            padding: 8px 12px;
            color: rgba(148, 163, 184, 1);
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid rgba(51, 65, 85, 0.5);
        }

        .rl-table td {
            padding: 8px 12px;
            border-bottom: 1px solid rgba(51, 65, 85, 0.3);
            color: #e2e8f0;
            vertical-align: middle;
        }

        .rl-table tr:last-child td {
            border-bottom: none;
        }

        .rl-table tr:hover td {
            background: rgba(255, 255, 255, 0.03);
        }

        .badge-ok {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(52, 211, 153, 0.15);
            color: rgba(52, 211, 153, 1);
        }

        .badge-fail {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(248, 113, 113, 0.15);
            color: rgba(248, 113, 113, 1);
        }

        .badge-status {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
            font-family: monospace;
        }

        .filter-select,
        .filter-btn {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(51, 65, 85, 0.5);
            background: rgba(15, 23, 42, 0.8);
            color: rgba(148, 163, 184, 1);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
        }

        .filter-select:focus {
            outline: none;
            border-color: rgba(34, 211, 238, 0.4);
        }

        .filter-btn:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        .filter-btn.active {
            background: rgba(34, 211, 238, 0.15);
            border-color: rgba(34, 211, 238, 0.4);
            color: rgba(34, 211, 238, 1);
        }

        .stat-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(51, 65, 85, 0.5);
            border-radius: 12px;
            padding: 16px 20px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #fff;
            line-height: 1.1;
        }

        .stat-label {
            font-size: 11px;
            color: rgba(148, 163, 184, 1);
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .btn-view {
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid rgba(51, 65, 85, 0.6);
            background: transparent;
            color: rgba(148, 163, 184, 1);
            cursor: pointer;
            transition: all 0.15s;
        }

        .btn-view:hover {
            border-color: rgba(34, 211, 238, 0.5);
            color: rgba(34, 211, 238, 1);
        }

        /* Modal */
        .rl-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.65);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .rl-modal-overlay.open {
            display: flex;
        }

        .rl-modal {
            background: #0f172a;
            border: 1px solid rgba(51, 65, 85, 0.6);
            border-radius: 14px;
            padding: 24px;
            max-width: 680px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }

        .rl-modal-title {
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 14px;
        }

        .rl-modal pre {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(51, 65, 85, 0.4);
            border-radius: 8px;
            padding: 14px;
            font-size: 12px;
            color: #94a3b8;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .rl-modal-close {
            position: absolute;
            top: 14px;
            right: 16px;
            background: none;
            border: none;
            color: rgba(148, 163, 184, 1);
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
        }

        .rl-modal-close:hover {
            color: #fff;
        }

        .rl-section-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: rgba(100, 116, 139, 1);
            margin-bottom: 6px;
        }
    </style>

    {{-- Header --}}
    <div
        style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;
                background:rgba(15,23,42,0.85);border:1px solid rgba(51,65,85,0.5);
                border-radius:14px;padding:18px 22px;margin-bottom:18px;">
        <div>
            <h1 style="font-size:20px;font-weight:700;color:#fff;margin:0 0 4px;">Request Log</h1>
            <p style="font-size:12px;color:rgba(148,163,184,1);margin:0">Tool endpoint calls — payload, response, latency.
            </p>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
            <a href="{{ request()->fullUrlWithQuery(['range' => 'today']) }}"
                class="filter-btn {{ $range === 'today' ? 'active' : '' }}">Today</a>
            <a href="{{ request()->fullUrlWithQuery(['range' => '7d']) }}"
                class="filter-btn {{ $range === '7d' ? 'active' : '' }}">7 Days</a>
            <a href="{{ request()->fullUrlWithQuery(['range' => '30d']) }}"
                class="filter-btn {{ $range === '30d' ? 'active' : '' }}">30 Days</a>
        </div>
    </div>

    {{-- Stat cards --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:18px;">
        <div class="stat-card">
            <div class="stat-value">{{ number_format($totalCount) }}</div>
            <div class="stat-label">Total Calls</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:rgba(52,211,153,1)">{{ number_format($successCount) }}</div>
            <div class="stat-label">Success</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:rgba(248,113,113,1)">{{ number_format($failCount) }}</div>
            <div class="stat-label">Failed</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:rgba(34,211,238,1)">{{ number_format($avgLatency) }} ms</div>
            <div class="stat-label">Avg Latency</div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('backoffice.technical.request-logs') }}"
        style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:14px;">
        <input type="hidden" name="range" value="{{ $range }}">
        <select name="tool" class="filter-select" onchange="this.form.submit()">
            <option value="">All Tools</option>
            @foreach ($toolNames as $name => $label)
                <option value="{{ $name }}" {{ $toolFilter === $name ? 'selected' : '' }}>{{ $label }}
                </option>
            @endforeach
        </select>
        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="success" {{ $statusFilter === 'success' ? 'selected' : '' }}>Success</option>
            <option value="fail" {{ $statusFilter === 'fail' ? 'selected' : '' }}>Failed</option>
        </select>
        @if ($toolFilter || $statusFilter)
            <a href="{{ route('backoffice.technical.request-logs', ['range' => $range]) }}" class="filter-btn"
                style="color:rgba(248,113,113,0.8);">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div style="background:rgba(15,23,42,0.6);border:1px solid rgba(51,65,85,0.5);border-radius:14px;overflow:hidden;">
        @if ($logs->isEmpty())
            <div style="padding:40px;text-align:center;color:rgba(100,116,139,1);">
                No request logs found for this period.
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="rl-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Tool</th>
                            <th>Status</th>
                            <th>Latency</th>
                            <th>Result</th>
                            <th>Endpoint</th>
                            <th style="text-align:center">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td style="white-space:nowrap;color:rgba(100,116,139,1);font-size:12px;">
                                    {{ $log->created_at->format('d M H:i:s') }}
                                </td>
                                <td>
                                    <span
                                        style="font-weight:600;color:#e2e8f0;">{{ $log->display_name ?? $log->tool_name }}</span>
                                    <br>
                                    <span
                                        style="font-size:11px;color:rgba(100,116,139,1);font-family:monospace;">{{ $log->tool_name }}</span>
                                </td>
                                <td>
                                    @if ($log->response_status)
                                        @php
                                            $sc = $log->response_status;
                                            $scColor =
                                                $sc >= 200 && $sc < 300
                                                    ? 'background:rgba(52,211,153,0.12);color:rgba(52,211,153,1)'
                                                    : ($sc >= 400 && $sc < 500
                                                        ? 'background:rgba(251,191,36,0.12);color:rgba(251,191,36,1)'
                                                        : 'background:rgba(248,113,113,0.12);color:rgba(248,113,113,1)');
                                        @endphp
                                        <span class="badge-status" style="{{ $scColor }}">{{ $sc }}</span>
                                    @else
                                        <span style="color:rgba(100,116,139,1);font-size:12px;">—</span>
                                    @endif
                                </td>
                                <td
                                    style="font-family:monospace;font-size:12px;
                                    color:{{ $log->latency_ms > 3000 ? 'rgba(248,113,113,1)' : ($log->latency_ms > 1500 ? 'rgba(251,191,36,1)' : 'rgba(52,211,153,1)') }}">
                                    {{ number_format($log->latency_ms, 0) }} ms
                                </td>
                                <td>
                                    @if ($log->success)
                                        <span class="badge-ok">OK</span>
                                    @else
                                        <span class="badge-fail">Fail</span>
                                        @if ($log->error)
                                            <br><span
                                                style="font-size:11px;color:rgba(248,113,113,0.7);">{{ $log->error }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td
                                    style="font-size:11px;color:rgba(100,116,139,1);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <span title="{{ $log->endpoint_url }}">{{ $log->endpoint_url }}</span>
                                </td>
                                <td style="text-align:center;white-space:nowrap;">
                                    @if ($log->request_payload)
                                        <button class="btn-view"
                                            onclick="openModal('req-{{ $log->id }}')">Req</button>
                                    @endif
                                    @if ($log->response_body)
                                        <button class="btn-view"
                                            onclick="openModal('res-{{ $log->id }}')">Res</button>
                                    @endif
                                </td>
                            </tr>

                            {{-- Request payload modal --}}
                            @if ($log->request_payload)
                                <div id="modal-req-{{ $log->id }}" class="rl-modal-overlay"
                                    onclick="if(event.target===this)closeModal('req-{{ $log->id }}')">
                                    <div class="rl-modal">
                                        <button class="rl-modal-close"
                                            onclick="closeModal('req-{{ $log->id }}')">&times;</button>
                                        <div class="rl-modal-title">{{ $log->display_name ?? $log->tool_name }} — Request
                                            Payload</div>
                                        <div class="rl-section-label">Sent at {{ $log->created_at->format('d M Y H:i:s') }}
                                        </div>
                                        <pre>{{ json_encode($log->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                </div>
                            @endif

                            {{-- Response body modal --}}
                            @if ($log->response_body)
                                <div id="modal-res-{{ $log->id }}" class="rl-modal-overlay"
                                    onclick="if(event.target===this)closeModal('res-{{ $log->id }}')">
                                    <div class="rl-modal">
                                        <button class="rl-modal-close"
                                            onclick="closeModal('res-{{ $log->id }}')">&times;</button>
                                        <div class="rl-modal-title">{{ $log->display_name ?? $log->tool_name }} — Response
                                            Body</div>
                                        <div class="rl-section-label">HTTP {{ $log->response_status }} ·
                                            {{ number_format($log->latency_ms, 0) }} ms</div>
                                        <pre>{{ json_encode($log->response_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($logs->hasPages())
                <div style="padding:14px 16px;border-top:1px solid rgba(51,65,85,0.3);">
                    {{ $logs->links() }}
                </div>
            @endif
        @endif
    </div>

    <script>
        function openModal(id) {
            const el = document.getElementById('modal-' + id);
            if (el) el.classList.add('open');
        }

        function closeModal(id) {
            const el = document.getElementById('modal-' + id);
            if (el) el.classList.remove('open');
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.rl-modal-overlay.open')
                    .forEach(el => el.classList.remove('open'));
            }
        });
    </script>
@endsection
