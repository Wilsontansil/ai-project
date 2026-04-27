@extends('backoffice.partials.layout')

@php
    $pageTitle =
        __('backoffice.pages.customer_chat.history') . ' — ' . ($customer->name ?: $customer->platform_user_id);
    $boActive = 'customer';
@endphp

@section('title', $pageTitle)
@section('page-title', __('backoffice.pages.customer_chat.page_title'))

@section('content')
    @php
        $currentUserId = auth()->id();
        $assignedUserName = $customer->assignedUser?->name ?: $customer->assignedUser?->username;
        $isOwnedByCurrentUser =
            $customer->assigned_user_id !== null && (int) $customer->assigned_user_id === (int) $currentUserId;
        $isAssignedToOther = $customer->assigned_user_id !== null && !$isOwnedByCurrentUser;
    @endphp

    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5 sm:p-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">{{ __('backoffice.pages.customer_chat.history') }}</h1>
                <p class="mt-1 text-sm text-slate-300">
                    {{ $customer->name ?: '-' }}
                    <span class="text-slate-400">•</span>
                    {{ ucfirst($customer->platform) }}
                    <span class="text-slate-400">•</span>
                    {{ $customer->platform_user_id }}
                    <span class="text-slate-400">•</span>
                    @if ($customer->mode === 'waiting')
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                            style="background:rgba(251,191,36,0.2);color:#fbbf24;">{{ __('backoffice.pages.dashboard.mode_waiting') }}</span>
                    @elseif ($customer->mode === 'human')
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                            style="background:rgba(34,211,238,0.2);color:#22d3ee;">{{ __('backoffice.pages.dashboard.mode_human') }}</span>
                    @else
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                            style="background:rgba(52,211,153,0.2);color:#34d399;">{{ __('backoffice.pages.dashboard.mode_bot') }}</span>
                    @endif

                    @if ($assignedUserName)
                        <span class="text-slate-400">•</span>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                            style="background:rgba(56,189,248,0.15);color:#7dd3fc;">
                            {{ __('backoffice.pages.customer_chat.assigned_to') }}: {{ $assignedUserName }}
                        </span>
                    @endif
                </p>
            </div>
            <div style="display:flex;align-items:center;gap:0.5rem;">
                @if ($customer->mode === 'bot')
                    <form method="POST" action="{{ route('backoffice.customer.takeover', $customer->id) }}">
                        @csrf
                        <button type="submit" class="rounded-2xl px-4 py-2 text-sm font-semibold transition"
                            style="background:rgba(251,191,36,0.15);color:#fbbf24;border:1px solid rgba(251,191,36,0.3);"
                            onmouseover="this.style.background='rgba(251,191,36,0.25)'"
                            onmouseout="this.style.background='rgba(251,191,36,0.15)'"
                            onfocus="this.style.background='rgba(251,191,36,0.25)'"
                            onblur="this.style.background='rgba(251,191,36,0.15)'">{{ __('backoffice.pages.escalation.takeover') }}</button>
                    </form>
                @elseif ($customer->mode === 'human')
                    <form method="POST" action="{{ route('backoffice.customer.release', $customer->id) }}">
                        @csrf
                        <button type="submit" class="rounded-2xl px-4 py-2 text-sm font-semibold transition"
                            @if (!$isOwnedByCurrentUser) disabled @endif
                            style="background:rgba(52,211,153,0.15);color:#34d399;border:1px solid rgba(52,211,153,0.3);"
                            onmouseover="this.style.background='rgba(52,211,153,0.25)'"
                            onmouseout="this.style.background='rgba(52,211,153,0.15)'"
                            onfocus="this.style.background='rgba(52,211,153,0.25)'"
                            onblur="this.style.background='rgba(52,211,153,0.15)'">{{ __('backoffice.pages.escalation.release') }}</button>
                    </form>
                @elseif ($customer->mode === 'waiting')
                    <form method="POST" action="{{ route('backoffice.customer.takeover', $customer->id) }}">
                        @csrf
                        <button type="submit" class="rounded-2xl px-4 py-2 text-sm font-semibold transition"
                            @if ($isAssignedToOther) disabled @endif
                            style="background:rgba(34,211,238,0.15);color:#22d3ee;border:1px solid rgba(34,211,238,0.3);"
                            onmouseover="this.style.background='rgba(34,211,238,0.25)'"
                            onmouseout="this.style.background='rgba(34,211,238,0.15)'"
                            onfocus="this.style.background='rgba(34,211,238,0.25)'"
                            onblur="this.style.background='rgba(34,211,238,0.15)'">{{ __('backoffice.pages.escalation.takeover') }}</button>
                    </form>
                @endif
                <a href="{{ route('backoffice.dashboard') }}"
                    class="rounded-2xl border border-white/10 px-4 py-2 text-sm text-slate-300 transition hover:bg-white/5">
                    ← {{ __('backoffice.pages.customer_chat.back') }}
                </a>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5 sm:p-6">
        <form method="GET" action="{{ route('backoffice.customer.chat', $customer->id) }}"
            class="flex flex-wrap items-end gap-4">
            <div>
                <label for="start_date"
                    class="mb-1 block text-xs text-slate-400">{{ __('backoffice.pages.customer_chat.start_date') }}</label>
                <input id="start_date" type="date" name="start_date" value="{{ $startDate }}"
                    class="rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm text-slate-200 outline-none [color-scheme:dark] focus:border-cyan-400" />
            </div>
            <div>
                <label for="end_date"
                    class="mb-1 block text-xs text-slate-400">{{ __('backoffice.pages.customer_chat.end_date') }}</label>
                <input id="end_date" type="date" name="end_date" value="{{ $endDate }}"
                    class="rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm text-slate-200 outline-none [color-scheme:dark] focus:border-cyan-400" />
            </div>
            <button type="submit"
                class="rounded-xl bg-cyan-400 px-5 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                {{ __('backoffice.pages.customer_chat.filter') }}
            </button>
        </form>
    </div>

    <div id="chat-messages-wrap" class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5 sm:p-6">
        @if (empty($messages))
            <p id="chat-empty" class="py-8 text-center text-sm text-slate-400">
                {{ __('backoffice.pages.customer_chat.empty') }}</p>
        @else
            <div id="chat-messages" class="space-y-4">
                @php $lastDate = null; @endphp
                @foreach ($messages as $msg)
                    @php $msgDate = $msg['date'] ?? null; @endphp
                    @if ($msgDate !== $lastDate)
                        <div class="flex items-center gap-3 py-2">
                            <div class="h-px flex-1 bg-white/10"></div>
                            <span
                                class="rounded-full bg-white/10 px-3 py-1 text-xs text-slate-400">{{ $msgDate }}</span>
                            <div class="h-px flex-1 bg-white/10"></div>
                        </div>
                        @php $lastDate = $msgDate; @endphp
                    @endif

                    @if (($msg['role'] ?? '') === 'user')
                        <div class="flex justify-start">
                            <div class="inline-flex w-auto max-w-[50%] flex-col break-words rounded-2xl rounded-bl-sm border border-white/10 bg-slate-800 px-4 py-3 shadow-lg shadow-black/20"
                                style="max-width: 50%;">
                                <p class="mb-1 text-[10px] font-semibold text-amber-400">
                                    {{ __('backoffice.pages.customer_chat.customer') }}</p>
                                @if (!empty($msg['meta']['attachment']))
                                    @php
                                        $att = $msg['meta']['attachment'];
                                        $attPath = $att['path'] ?? '';
                                    @endphp
                                    @php $attUrl = parse_url(route('backoffice.chat-attachment'), PHP_URL_PATH) . '?path=' . urlencode($attPath); @endphp
                                    @if (($att['type'] ?? '') === 'image')
                                        <a href="{{ $attUrl }}" target="_blank" class="mb-1 block">
                                            <img src="{{ $attUrl }}"
                                                alt="{{ $att['original_name'] ?? 'attachment' }}"
                                                class="max-w-full rounded-xl" style="max-height:200px;object-fit:cover;" />
                                        </a>
                                    @else
                                        <a href="{{ $attUrl }}" target="_blank"
                                            class="mb-1 flex items-center gap-2 rounded-xl border border-white/10 px-3 py-2 text-xs text-slate-300 hover:bg-white/5">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                                class="h-4 w-4 shrink-0 text-slate-400">
                                                <path fill-rule="evenodd"
                                                    d="M5.625 1.5H9a3.75 3.75 0 0 1 3.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 0 1 3.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 0 1-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875Zm6.905 9.97a.75.75 0 0 0-1.06 0l-3 3a.75.75 0 1 0 1.06 1.06l1.72-1.72V18a.75.75 0 0 0 1.5 0v-4.19l1.72 1.72a.75.75 0 1 0 1.06-1.06l-3-3Z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            {{ $att['original_name'] ?? 'download' }}
                                        </a>
                                    @endif
                                @endif
                                <p class="whitespace-pre-wrap break-words text-sm text-slate-100">{{ $msg['message'] }}</p>
                                <p class="mt-1.5 text-[10px] text-slate-500">{{ $msg['time'] ?? '' }}</p>
                            </div>
                        </div>
                    @else
                        <div class="flex justify-end">
                            @php
                                $senderLabel =
                                    data_get($msg, 'meta.sent_by_user_name') ?:
                                    (($msg['role'] ?? '') === 'assistant'
                                        ? __('backoffice.pages.customer_chat.assistant')
                                        : $msg['role'] ?? __('backoffice.pages.customer_chat.assistant'));
                            @endphp
                            <div class="inline-flex w-auto max-w-[50%] flex-col break-words rounded-2xl rounded-br-sm border border-cyan-500/20 bg-cyan-600/25 px-4 py-3 shadow-lg shadow-cyan-900/20"
                                style="max-width: 50%;">
                                <p class="mb-1 text-[10px] font-semibold text-cyan-400">
                                    {{ $senderLabel }}
                                </p>
                                @if (!empty($msg['meta']['attachment']))
                                    @php
                                        $att = $msg['meta']['attachment'];
                                        $attPath = $att['path'] ?? '';
                                    @endphp
                                    @php $attUrl = parse_url(route('backoffice.chat-attachment'), PHP_URL_PATH) . '?path=' . urlencode($attPath); @endphp
                                    @if (($att['type'] ?? '') === 'image')
                                        <a href="{{ $attUrl }}" target="_blank" class="mb-1 block">
                                            <img src="{{ $attUrl }}"
                                                alt="{{ $att['original_name'] ?? 'attachment' }}"
                                                class="max-w-full rounded-xl"
                                                style="max-height:200px;object-fit:cover;" />
                                        </a>
                                    @else
                                        <a href="{{ $attUrl }}" target="_blank"
                                            class="mb-1 flex items-center gap-2 rounded-xl border border-cyan-500/20 px-3 py-2 text-xs text-cyan-300 hover:bg-cyan-500/10">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                fill="currentColor" class="h-4 w-4 shrink-0">
                                                <path fill-rule="evenodd"
                                                    d="M5.625 1.5H9a3.75 3.75 0 0 1 3.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 0 1 3.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 0 1-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875Zm6.905 9.97a.75.75 0 0 0-1.06 0l-3 3a.75.75 0 1 0 1.06 1.06l1.72-1.72V18a.75.75 0 0 0 1.5 0v-4.19l1.72 1.72a.75.75 0 1 0 1.06-1.06l-3-3Z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            {{ $att['original_name'] ?? 'download' }}
                                        </a>
                                    @endif
                                @endif
                                <p class="whitespace-pre-wrap break-words text-sm leading-6 text-white">
                                    {{ $msg['message'] }}</p>
                                <p class="mt-1.5 text-right text-[10px] text-cyan-300/60">{{ $msg['time'] ?? '' }}</p>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    <script>
        (function() {
            const POLL_MS = 4000;
            const customerId = {{ $customer->id }};
            const startDate = document.getElementById('start_date')?.value || '{{ $startDate }}';
            const endDate = document.getElementById('end_date')?.value || '{{ $endDate }}';
            const pollUrl = '{{ parse_url(route('backoffice.customer.messages', $customer->id), PHP_URL_PATH) }}';

            // Snapshot of last known message list for change detection
            const initialMessages = @json($messages);
            let lastSignature = buildMessagesSignature(initialMessages);
            let polling = true;
            let pollInFlight = false;

            // ---- Renderers ----
            const attBase = '{{ parse_url(route('backoffice.chat-attachment'), PHP_URL_PATH) }}';

            function buildAttachmentHtml(meta, isUser) {
                if (!meta || !meta.attachment) return '';
                const att = meta.attachment;
                if (!att.path) return '';
                const url = attBase + '?path=' + encodeURIComponent(att.path);
                const name = escHtml(att.original_name || 'download');
                if (att.type === 'image') {
                    return `<a href="${url}" target="_blank" class="mb-1 block"><img src="${url}" alt="${name}" class="max-w-full rounded-xl" style="max-height:200px;object-fit:cover;"/></a>`;
                }
                const borderCls = isUser ? 'border-white/10 text-slate-300 hover:bg-white/5' :
                    'border-cyan-500/20 text-cyan-300 hover:bg-cyan-500/10';
                return `<a href="${url}" target="_blank" class="mb-1 flex items-center gap-2 rounded-xl border ${borderCls} px-3 py-2 text-xs"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 shrink-0"><path fill-rule="evenodd" d="M5.625 1.5H9a3.75 3.75 0 0 1 3.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 0 1 3.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 0 1-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875Zm6.905 9.97a.75.75 0 0 0-1.06 0l-3 3a.75.75 0 1 0 1.06 1.06l1.72-1.72V18a.75.75 0 0 0 1.5 0v-4.19l1.72 1.72a.75.75 0 1 0 1.06-1.06l-3-3Z" clip-rule="evenodd" /></svg>${name}</a>`;
            }

            function buildDateSep(date) {
                return `<div class="flex items-center gap-3 py-2">
            <div class="h-px flex-1 bg-white/10"></div>
            <span class="rounded-full bg-white/10 px-3 py-1 text-xs text-slate-400">${date}</span>
            <div class="h-px flex-1 bg-white/10"></div>
        </div>`;
            }

            function buildUserBubble(msg) {
                return `<div class="flex justify-start">
            <div class="inline-flex w-auto max-w-[50%] flex-col break-words rounded-2xl rounded-bl-sm border border-white/10 bg-slate-800 px-4 py-3 shadow-lg shadow-black/20" style="max-width:50%;">
                <p class="mb-1 text-[10px] font-semibold text-amber-400">{{ __('backoffice.pages.customer_chat.customer') }}</p>
                ${buildAttachmentHtml(msg.meta, true)}
                <p class="whitespace-pre-wrap break-words text-sm text-slate-100">${escHtml(msg.message)}</p>
                <p class="mt-1.5 text-[10px] text-slate-500">${msg.time ?? ''}</p>
            </div>
        </div>`;
            }

            function buildAssistantBubble(msg) {
                const label = msg?.meta?.sent_by_user_name ||
                    ((msg.role === 'assistant' || !msg.role) ? '{{ __('backoffice.pages.customer_chat.assistant') }}' :
                        msg.role);
                return `<div class="flex justify-end">
            <div class="inline-flex w-auto max-w-[50%] flex-col break-words rounded-2xl rounded-br-sm border border-cyan-500/20 bg-cyan-600/25 px-4 py-3 shadow-lg shadow-cyan-900/20" style="max-width:50%;">
                <p class="mb-1 text-[10px] font-semibold text-cyan-400">${escHtml(label)}</p>
                ${buildAttachmentHtml(msg.meta, false)}
                <p class="whitespace-pre-wrap break-words text-sm leading-6 text-white">${escHtml(msg.message)}</p>
                <p class="mt-1.5 text-right text-[10px] text-cyan-300/60">${msg.time ?? ''}</p>
            </div>
        </div>`;
            }

            function escHtml(str) {
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function renderMessages(messages) {
                const wrap = document.getElementById('chat-messages-wrap');
                if (!wrap) return;

                if (messages.length === 0) {
                    wrap.innerHTML =
                        `<p class="py-8 text-center text-sm text-slate-400">{{ __('backoffice.pages.customer_chat.empty') }}</p>`;
                    return;
                }

                let html = '<div id="chat-messages" class="space-y-4">';
                let lastDate = null;
                messages.forEach(msg => {
                    const d = msg.date ?? null;
                    if (d !== lastDate) {
                        html += buildDateSep(d);
                        lastDate = d;
                    }
                    html += (msg.role === 'user') ? buildUserBubble(msg) : buildAssistantBubble(msg);
                });
                html += '</div>';
                wrap.innerHTML = html;
            }

            function buildMessagesSignature(messages) {
                if (!Array.isArray(messages)) {
                    return '';
                }

                return messages.map(msg => {
                    const attachmentPath = msg?.meta?.attachment?.path ?? '';
                    return [
                        msg?.date ?? '',
                        msg?.time ?? '',
                        msg?.role ?? '',
                        msg?.message ?? '',
                        attachmentPath,
                    ].join('~');
                }).join('||');
            }

            function scrollToBottom() {
                const content = document.getElementById('bo-content');
                if (content) content.scrollTop = content.scrollHeight;
            }

            // ---- Polling ----
            async function poll() {
                if (!polling || document.visibilityState === 'hidden' || pollInFlight) return;
                pollInFlight = true;
                try {
                    const qs = new URLSearchParams();
                    const sdEl = document.getElementById('start_date');
                    const edEl = document.getElementById('end_date');
                    qs.set('start_date', sdEl ? sdEl.value : startDate);
                    qs.set('end_date', edEl ? edEl.value : endDate);

                    const res = await fetch(`${pollUrl}?${qs}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    const nextSignature = buildMessagesSignature(data.messages);

                    if (nextSignature !== lastSignature) {
                        lastSignature = nextSignature;
                        renderMessages(data.messages);
                        scrollToBottom();
                    }
                } catch (_) {
                    /* network error — silently skip */
                } finally {
                    pollInFlight = false;
                }
            }

            // Scroll to bottom on initial load
            document.addEventListener('DOMContentLoaded', function() {
                scrollToBottom();

                const sendForm = document.getElementById('chat-send-form');
                const sendTextarea = document.getElementById('chat-send-textarea');
                if (sendForm && sendTextarea) {
                    sendTextarea.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
                            e.preventDefault();
                            sendForm.requestSubmit();
                        }
                    });
                }
            });

            // Pause/resume when tab visibility changes
            document.addEventListener('visibilitychange', () => {
                polling = document.visibilityState !== 'hidden';

                if (polling) {
                    poll();
                }
            });

            setInterval(poll, POLL_MS);
        }());
    </script>

    @php
        $canSend =
            $customer->mode === 'human' &&
            $isOwnedByCurrentUser &&
            in_array($customer->platform, ['telegram', 'whatsapp', 'livechat']);
    @endphp

    <div id="chat-send-bar" class="rounded-2xl border border-slate-700/70 bg-slate-950 p-4"
        style="position:sticky;bottom:0;z-index:20;">
        @if ($canSend)
            <form id="chat-send-form" method="POST" enctype="multipart/form-data"
                action="{{ route('backoffice.customer.send-message', $customer->id) }}">
                @csrf
                <input id="chat-send-attachment" type="file" name="attachment" class="hidden"
                    accept="image/jpeg,image/png,image/gif,image/webp,application/pdf,text/plain,text/csv,application/zip,application/x-zip-compressed,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,video/mp4,video/webm,audio/mpeg,audio/mp4,audio/ogg,audio/wav" />
                <div class="flex items-end gap-3">
                    <textarea id="chat-send-textarea" name="message" rows="1"
                        placeholder="{{ __('backoffice.pages.customer_chat.type_message') }}"
                        class="flex-1 resize-none rounded-xl border border-white/10 bg-slate-800 px-4 py-3 text-sm text-slate-100 placeholder-slate-500 outline-none focus:border-cyan-400"
                        style="max-height:140px;overflow-y:auto;"></textarea>
                    <div class="flex shrink-0 items-center gap-1">
                        <label id="chat-attach-label" for="chat-send-attachment"
                            class="flex cursor-pointer items-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold transition"
                            style="background:rgba(255,255,255,0.08);color:#e2e8f0;border:1px solid rgba(255,255,255,0.12);">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                class="h-4 w-4">
                                <path
                                    d="M16.5 6v11.25a4.125 4.125 0 1 1-8.25 0V7.5a2.625 2.625 0 1 1 5.25 0v8.625a1.125 1.125 0 1 1-2.25 0V9.75a.75.75 0 0 0-1.5 0v6.375a2.625 2.625 0 1 0 5.25 0V7.5a4.125 4.125 0 1 0-8.25 0v9.75a5.625 5.625 0 1 0 11.25 0V6a.75.75 0 0 0-1.5 0Z" />
                            </svg>
                            {{ __('backoffice.pages.customer_chat.attach_file') }}
                        </label>
                        <button id="chat-send-attachment-clear" type="button"
                            class="hidden items-center justify-center rounded-full px-2 py-2 text-xs text-slate-300 transition hover:bg-white/10"
                            title="{{ __('backoffice.pages.customer_chat.clear_file') }}"
                            style="border:1px solid rgba(255,255,255,0.12);line-height:1;">
                            ×
                        </button>
                    </div>
                    <button type="submit"
                        class="flex shrink-0 items-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold transition"
                        style="background:rgba(34,211,238,0.25);color:#22d3ee;border:1px solid rgba(34,211,238,0.5);">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                            <path
                                d="M3.478 2.405a.75.75 0 0 0-.926.94l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.405Z" />
                        </svg>
                        {{ __('backoffice.pages.customer_chat.send') }}
                    </button>
                </div>
                <p class="mt-2 text-[11px] text-slate-500">
                    {{ __('backoffice.pages.customer_chat.send_hint_active', ['platform' => ucfirst($customer->platform)]) }}
                </p>
            </form>
        @else
            <div class="flex items-end gap-3">
                <textarea rows="1" placeholder="{{ __('backoffice.pages.customer_chat.type_message') }}"
                    class="flex-1 resize-none rounded-xl border border-white/10 bg-slate-800 px-4 py-3 text-sm text-slate-100 placeholder-slate-500 outline-none"
                    style="max-height:140px;overflow-y:auto;cursor:not-allowed;opacity:0.4;" disabled></textarea>
                <button type="button" class="flex shrink-0 items-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold"
                    style="background:rgba(255,255,255,0.08);color:#e2e8f0;border:1px solid rgba(255,255,255,0.12);cursor:not-allowed;opacity:0.4;"
                    disabled>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                        <path
                            d="M16.5 6v11.25a4.125 4.125 0 1 1-8.25 0V7.5a2.625 2.625 0 1 1 5.25 0v8.625a1.125 1.125 0 1 1-2.25 0V9.75a.75.75 0 0 0-1.5 0v6.375a2.625 2.625 0 1 0 5.25 0V7.5a4.125 4.125 0 1 0-8.25 0v9.75a5.625 5.625 0 1 0 11.25 0V6a.75.75 0 0 0-1.5 0Z" />
                    </svg>
                    {{ __('backoffice.pages.customer_chat.attach_file') }}
                </button>
                <button type="button" class="flex shrink-0 items-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold"
                    style="background:rgba(34,211,238,0.15);color:#22d3ee;border:1px solid rgba(34,211,238,0.3);cursor:not-allowed;opacity:0.4;"
                    disabled>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                        <path
                            d="M3.478 2.405a.75.75 0 0 0-.926.94l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.405Z" />
                    </svg>
                    {{ __('backoffice.pages.customer_chat.send') }}
                </button>
            </div>
            <p class="mt-2 text-[11px] text-slate-500">
                @if ($isAssignedToOther)
                    {{ __('backoffice.pages.customer_chat.reply_locked_by', ['name' => $assignedUserName]) }}
                @else
                    {{ __('backoffice.pages.customer_chat.send_hint') }}
                @endif
            </p>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        (function() {
            const MAX_BYTES = 10 * 1024 * 1024; // 10 MB

            // ── SweetAlert2 Toast helper ──
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                }
            });

            // ── Flash messages from server ──
            @if (session('send_success'))
                Toast.fire({
                    icon: 'success',
                    title: @json(session('send_success'))
                });
            @endif

            @if (session('send_error'))
                Toast.fire({
                    icon: 'error',
                    title: @json(session('send_error'))
                });
            @endif

            // ── Client-side file size guard ──
            const fileInput = document.getElementById('chat-send-attachment');
            const sendForm = document.getElementById('chat-send-form');

            const attachLabel = document.getElementById('chat-attach-label');
            const clearBtn = document.getElementById('chat-send-attachment-clear');

            function setAttachActive(active) {
                if (!attachLabel) return;
                if (active) {
                    attachLabel.style.border = '1px solid rgba(34,211,238,0.6)';
                    attachLabel.style.background = 'rgba(34,211,238,0.12)';
                    attachLabel.style.color = '#22d3ee';
                    if (clearBtn) clearBtn.classList.remove('hidden');
                    if (clearBtn) clearBtn.classList.add('flex');
                } else {
                    attachLabel.style.border = '1px solid rgba(255,255,255,0.12)';
                    attachLabel.style.background = 'rgba(255,255,255,0.08)';
                    attachLabel.style.color = '#e2e8f0';
                    if (clearBtn) clearBtn.classList.add('hidden');
                    if (clearBtn) clearBtn.classList.remove('flex');
                }
            }

            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    const file = fileInput.files && fileInput.files[0];
                    if (file && file.size > MAX_BYTES) {
                        Toast.fire({
                            icon: 'error',
                            title: 'File too large. Maximum size is 10 MB.'
                        });
                        fileInput.value = '';
                        setAttachActive(false);
                        return;
                    }
                    setAttachActive(!!file);
                });
            }

            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    if (fileInput) fileInput.value = '';
                    setAttachActive(false);
                });
            }

            if (sendForm) {
                sendForm.addEventListener('submit', function(e) {
                    const file = fileInput && fileInput.files && fileInput.files[0];
                    if (file && file.size > MAX_BYTES) {
                        e.preventDefault();
                        Toast.fire({
                            icon: 'error',
                            title: 'File too large. Maximum size is 10 MB.'
                        });
                    }
                });
            }
        })();
    </script>
@endsection
