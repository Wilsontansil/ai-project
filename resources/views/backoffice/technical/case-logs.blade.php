@extends('backoffice.partials.layout')

@section('title', 'Case Log')
@section('page-title', 'Case Log')

@php $boActive = 'case-logs'; @endphp

@section('content')
    <style>
        .cl-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .cl-table th {
            text-align: left;
            padding: 8px 12px;
            color: rgba(148, 163, 184, 1);
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid rgba(51, 65, 85, 0.5);
        }

        .cl-table td {
            padding: 8px 12px;
            border-bottom: 1px solid rgba(51, 65, 85, 0.3);
            color: #e2e8f0;
            vertical-align: middle;
        }

        .cl-table tr:last-child td {
            border-bottom: none;
        }

        .cl-table tr:hover td {
            background: rgba(255, 255, 255, 0.03);
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        /* Channel badges */
        .ch-telegram {
            background: rgba(56, 189, 248, 0.15);
            color: rgba(56, 189, 248, 1);
        }

        .ch-whatsapp {
            background: rgba(52, 211, 153, 0.15);
            color: rgba(52, 211, 153, 1);
        }

        .ch-livechat {
            background: rgba(167, 139, 250, 0.15);
            color: rgba(167, 139, 250, 1);
        }

        .ch-waha {
            background: rgba(20, 184, 166, 0.15);
            color: rgba(20, 184, 166, 1);
        }

        .ch-default {
            background: rgba(100, 116, 139, 0.15);
            color: rgba(100, 116, 139, 1);
        }

        /* Trigger badges */
        .tr-openai {
            background: rgba(56, 189, 248, 0.15);
            color: rgba(56, 189, 248, 1);
        }

        .tr-chain {
            background: rgba(167, 139, 250, 0.15);
            color: rgba(167, 139, 250, 1);
        }

        .tr-keyword {
            background: rgba(251, 191, 36, 0.15);
            color: rgba(251, 191, 36, 1);
        }

        .tr-intent {
            background: rgba(20, 184, 166, 0.15);
            color: rgba(20, 184, 166, 1);
        }

        .tr-unknown {
            background: rgba(100, 116, 139, 0.15);
            color: rgba(100, 116, 139, 1);
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
        .cl-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.65);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .cl-modal-overlay.open {
            display: flex;
        }

        .cl-modal {
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

        .cl-modal-title {
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 14px;
        }

        .cl-modal pre {
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

        .cl-modal-close {
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

        .cl-modal-close:hover {
            color: #fff;
        }

        .section-label {
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
            <h1 style="font-size:20px;font-weight:700;color:#fff;margin:0 0 4px;">Case Log</h1>
            <p style="font-size:12px;color:rgba(148,163,184,1);margin:0">Business-level audit trail — tool executions by
                customer, channel &amp; trigger.</p>
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
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:18px;">
        <div class="stat-card">
            <div class="stat-value">{{ number_format($totalCount) }}</div>
            <div class="stat-label">Total Cases</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:rgba(56,189,248,1)">{{ number_format($attachmentCount) }}</div>
            <div class="stat-label">With Attachment</div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('backoffice.technical.case-logs') }}"
        style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:14px;">
        <input type="hidden" name="range" value="{{ $range }}">

        <select name="tool" class="filter-select" onchange="this.form.submit()">
            <option value="">All Tools</option>
            @foreach ($toolNames as $toolKey => $toolLabel)
                <option value="{{ $toolKey }}" {{ $toolFilter === $toolKey ? 'selected' : '' }}>{{ $toolLabel }}
                </option>
            @endforeach
        </select>

        <select name="channel" class="filter-select" onchange="this.form.submit()">
            <option value="">All Channels</option>
            @foreach ($channels as $ch)
                <option value="{{ $ch }}" {{ $channelFilter === $ch ? 'selected' : '' }}>{{ ucfirst($ch) }}
                </option>
            @endforeach
        </select>

        <select name="attachment" class="filter-select" onchange="this.form.submit()">
            <option value="">All Cases</option>
            <option value="yes" {{ $attachmentFilter === 'yes' ? 'selected' : '' }}>With Attachment</option>
        </select>

        <select name="trigger" class="filter-select" onchange="this.form.submit()">
            <option value="">All Triggers</option>
            <option value="openai_tool_call" {{ $triggerFilter === 'openai_tool_call' ? 'selected' : '' }}>OpenAI</option>
            <option value="chain_resume" {{ $triggerFilter === 'chain_resume' ? 'selected' : '' }}>Chain Resume
            </option>
            <option value="keyword" {{ $triggerFilter === 'keyword' ? 'selected' : '' }}>Keyword</option>
            <option value="intent" {{ $triggerFilter === 'intent' ? 'selected' : '' }}>Intent</option>
        </select>

        @if ($toolFilter || $channelFilter || $attachmentFilter || $triggerFilter)
            <a href="{{ route('backoffice.technical.case-logs', ['range' => $range]) }}" class="filter-btn"
                style="color:rgba(248,113,113,0.8);">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div style="background:rgba(15,23,42,0.6);border:1px solid rgba(51,65,85,0.5);border-radius:14px;overflow:hidden;">
        @if ($logs->isEmpty())
            <div style="padding:40px;text-align:center;color:rgba(100,116,139,1);">
                No case logs found for this period.
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="cl-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Customer</th>
                            <th>Tool</th>
                            <th>Trigger</th>
                            <th>Attachment</th>
                            <th>Reply</th>
                            <th style="text-align:center">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            @php
                                $chClass = match ($log->channel) {
                                    'telegram' => 'ch-telegram',
                                    'whatsapp' => 'ch-whatsapp',
                                    'livechat' => 'ch-livechat',
                                    'waha' => 'ch-waha',
                                    default => 'ch-default',
                                };
                                $trClass = match ($log->trigger_mode) {
                                    'openai_tool_call' => 'tr-openai',
                                    'chain_resume' => 'tr-chain',
                                    'keyword' => 'tr-keyword',
                                    'intent' => 'tr-intent',
                                    default => 'tr-unknown',
                                };
                                $trLabel = match ($log->trigger_mode) {
                                    'openai_tool_call' => 'OpenAI',
                                    'chain_resume' => 'Chain',
                                    'keyword' => 'Keyword',
                                    'intent' => 'Intent',
                                    default => $log->trigger_mode,
                                };
                            @endphp
                            <tr>
                                <td style="white-space:nowrap;color:rgba(100,116,139,1);font-size:12px;">
                                    {{ $log->created_at->format('d M H:i:s') }}
                                </td>
                                <td>
                                    <span
                                        style="font-family:monospace;font-size:12px;color:#e2e8f0;">{{ $log->chat_id }}</span>
                                    <br>
                                    <span class="badge {{ $chClass }}"
                                        style="margin-top:3px;">{{ ucfirst($log->channel) }}</span>
                                    @if ($log->customer_info && isset($log->customer_info['tags']) && count($log->customer_info['tags']))
                                        <br>
                                        @foreach (array_slice($log->customer_info['tags'], 0, 2) as $tag)
                                            <span
                                                style="font-size:10px;color:rgba(100,116,139,1);">{{ $tag }}</span>
                                        @endforeach
                                    @endif
                                </td>
                                <td>
                                    <span
                                        style="font-weight:600;color:#e2e8f0;">{{ $log->display_name ?? $log->tool_name }}</span>
                                    <br>
                                    <span
                                        style="font-size:11px;color:rgba(100,116,139,1);font-family:monospace;">{{ $log->tool_name }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $trClass }}">{{ $trLabel }}</span>
                                </td>
                                <td>
                                    @if ($log->has_attachment)
                                        @if ($log->attachment_url)
                                            <a href="{{ $log->attachment_url }}" target="_blank" rel="noopener noreferrer"
                                                style="color:rgba(56,189,248,1);font-size:12px;display:flex;align-items:center;gap:4px;text-decoration:none;"
                                                title="{{ $log->attachment_url }}">
                                                <svg width="14" height="14" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                                </svg>
                                                View
                                            </a>
                                        @else
                                            @php $mime = $log->attachment_meta['mime'] ?? 'image'; @endphp
                                            <span
                                                style="color:rgba(251,191,36,1);font-size:12px;display:flex;align-items:center;gap:4px;">
                                                <svg width="14" height="14" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                                </svg>
                                                Base64 ({{ $mime }})
                                            </span>
                                        @endif
                                    @else
                                        <span style="color:rgba(100,116,139,0.5);font-size:12px;">—</span>
                                    @endif
                                </td>
                                <td style="max-width:200px;">
                                    @if ($log->tool_reply)
                                        <span style="font-size:12px;color:rgba(148,163,184,1);"
                                            title="{{ $log->tool_reply }}">{{ mb_substr($log->tool_reply, 0, 80) }}{{ mb_strlen($log->tool_reply) > 80 ? '…' : '' }}</span>
                                        @if (mb_strlen($log->tool_reply) > 80)
                                            <br>
                                            <button class="btn-view"
                                                onclick="openModal('reply-{{ $log->id }}')">Full</button>
                                        @endif
                                    @else
                                        <span style="color:rgba(100,116,139,0.5);font-size:12px;">—</span>
                                    @endif
                                </td>
                                <td style="text-align:center;white-space:nowrap;">
                                    @if ($log->arguments)
                                        <button class="btn-view"
                                            onclick="openModal('args-{{ $log->id }}')">Args</button>
                                    @endif
                                    @if ($log->customer_info)
                                        <button class="btn-view"
                                            onclick="openModal('cust-{{ $log->id }}')">Info</button>
                                    @endif
                                </td>
                            </tr>

                            {{-- Full reply modal --}}
                            @if ($log->tool_reply && mb_strlen($log->tool_reply) > 80)
                                <div id="modal-reply-{{ $log->id }}" class="cl-modal-overlay"
                                    onclick="if(event.target===this)closeModal('reply-{{ $log->id }}')">
                                    <div class="cl-modal">
                                        <button class="cl-modal-close"
                                            onclick="closeModal('reply-{{ $log->id }}')">&times;</button>
                                        <div class="cl-modal-title">{{ $log->display_name ?? $log->tool_name }} — Reply
                                        </div>
                                        <div class="section-label">{{ $log->created_at->format('d M Y H:i:s') }} ·
                                            {{ ucfirst($log->channel) }} · {{ $log->chat_id }}</div>
                                        <pre>{{ $log->tool_reply }}</pre>
                                    </div>
                                </div>
                            @endif

                            {{-- Arguments modal --}}
                            @if ($log->arguments)
                                <div id="modal-args-{{ $log->id }}" class="cl-modal-overlay"
                                    onclick="if(event.target===this)closeModal('args-{{ $log->id }}')">
                                    <div class="cl-modal">
                                        <button class="cl-modal-close"
                                            onclick="closeModal('args-{{ $log->id }}')">&times;</button>
                                        <div class="cl-modal-title">{{ $log->display_name ?? $log->tool_name }} —
                                            Arguments</div>
                                        <div class="section-label">Trigger: {{ $log->trigger_mode }} ·
                                            {{ $log->created_at->format('d M Y H:i:s') }}</div>
                                        <pre>{{ json_encode($log->arguments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                </div>
                            @endif

                            {{-- Customer info modal --}}
                            @if ($log->customer_info)
                                <div id="modal-cust-{{ $log->id }}" class="cl-modal-overlay"
                                    onclick="if(event.target===this)closeModal('cust-{{ $log->id }}')">
                                    <div class="cl-modal">
                                        <button class="cl-modal-close"
                                            onclick="closeModal('cust-{{ $log->id }}')">&times;</button>
                                        <div class="cl-modal-title">Customer Profile</div>
                                        <div class="section-label">{{ $log->chat_id }} · {{ ucfirst($log->channel) }}
                                        </div>
                                        <pre>{{ json_encode($log->customer_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Pagination --}}
    @if ($logs->hasPages())
        <div style="margin-top:16px;display:flex;justify-content:center;">
            {{ $logs->links() }}
        </div>
    @endif

    {{-- Modal JS --}}
    <script>
        function openModal(key) {
            const el = document.getElementById('modal-' + key);
            if (el) {
                el.classList.add('open');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeModal(key) {
            const el = document.getElementById('modal-' + key);
            if (el) {
                el.classList.remove('open');
                document.body.style.overflow = '';
            }
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.cl-modal-overlay.open').forEach(function(el) {
                    el.classList.remove('open');
                });
                document.body.style.overflow = '';
            }
        });
    </script>
@endsection
