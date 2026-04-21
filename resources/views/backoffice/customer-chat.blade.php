@extends('backoffice.partials.layout')

@php
    $pageTitle =
        __('backoffice.pages.customer_chat.history') . ' — ' . ($customer->name ?: $customer->platform_user_id);
    $boActive = 'customer';
@endphp

@section('title', $pageTitle)
@section('page-title', __('backoffice.pages.customer_chat.page_title'))

@section('content')
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
                </p>
            </div>
            <div style="display:flex;align-items:center;gap:0.5rem;">
                @if ($customer->mode === 'bot')
                    <form method="POST" action="{{ route('backoffice.customer.takeover', $customer->id) }}">
                        @csrf
                        <button type="submit" class="rounded-2xl px-4 py-2 text-sm font-semibold transition"
                            style="background:rgba(251,191,36,0.15);color:#fbbf24;border:1px solid rgba(251,191,36,0.3);"
                            onmouseover="this.style.background='rgba(251,191,36,0.25)'"
                            onmouseout="this.style.background='rgba(251,191,36,0.15)'">{{ __('backoffice.pages.escalation.takeover') }}</button>
                    </form>
                @elseif ($customer->mode === 'human')
                    <form method="POST" action="{{ route('backoffice.customer.release', $customer->id) }}">
                        @csrf
                        <button type="submit" class="rounded-2xl px-4 py-2 text-sm font-semibold transition"
                            style="background:rgba(52,211,153,0.15);color:#34d399;border:1px solid rgba(52,211,153,0.3);"
                            onmouseover="this.style.background='rgba(52,211,153,0.25)'"
                            onmouseout="this.style.background='rgba(52,211,153,0.15)'">{{ __('backoffice.pages.escalation.release') }}</button>
                    </form>
                @elseif ($customer->mode === 'waiting')
                    <form method="POST" action="{{ route('backoffice.customer.takeover', $customer->id) }}">
                        @csrf
                        <button type="submit" class="rounded-2xl px-4 py-2 text-sm font-semibold transition"
                            style="background:rgba(34,211,238,0.15);color:#22d3ee;border:1px solid rgba(34,211,238,0.3);"
                            onmouseover="this.style.background='rgba(34,211,238,0.25)'"
                            onmouseout="this.style.background='rgba(34,211,238,0.15)'">{{ __('backoffice.pages.escalation.takeover') }}</button>
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

    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5 sm:p-6">
        @if (empty($messages))
            <p class="py-8 text-center text-sm text-slate-400">{{ __('backoffice.pages.customer_chat.empty') }}</p>
        @else
            <div class="space-y-4">
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
                                <p class="whitespace-pre-wrap break-words text-sm text-slate-100">{{ $msg['message'] }}</p>
                                <p class="mt-1.5 text-[10px] text-slate-500">{{ $msg['time'] ?? '' }}</p>
                            </div>
                        </div>
                    @else
                        <div class="flex justify-end">
                            <div class="inline-flex w-auto max-w-[50%] flex-col break-words rounded-2xl rounded-br-sm border border-cyan-500/20 bg-cyan-600/25 px-4 py-3 shadow-lg shadow-cyan-900/20"
                                style="max-width: 50%;">
                                <p class="mb-1 text-[10px] font-semibold text-cyan-400">
                                    {{ $msg['role'] ?? __('backoffice.pages.customer_chat.assistant') }}
                                </p>
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

    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4">
        <div class="flex items-end gap-3">
            <textarea id="admin-message-input" rows="1" placeholder="{{ __('backoffice.pages.customer_chat.type_message') }}"
                class="flex-1 resize-none rounded-xl border border-white/10 bg-slate-800 px-4 py-3 text-sm text-slate-100 placeholder-slate-500 outline-none focus:border-cyan-400"
                style="max-height:140px;overflow-y:auto;" disabled></textarea>
            <button type="button"
                class="flex shrink-0 items-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold transition"
                style="background:rgba(34,211,238,0.15);color:#22d3ee;border:1px solid rgba(34,211,238,0.3);cursor:not-allowed;opacity:0.5;"
                disabled>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                    <path
                        d="M3.478 2.405a.75.75 0 0 0-.926.94l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.405Z" />
                </svg>
                {{ __('backoffice.pages.customer_chat.send') }}
            </button>
        </div>
        <p class="mt-2 text-[11px] text-slate-500">{{ __('backoffice.pages.customer_chat.send_hint') }}</p>
    </div>
@endsection
