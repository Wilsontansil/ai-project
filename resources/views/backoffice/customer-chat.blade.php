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
                </p>
            </div>
            <a href="{{ route('backoffice.dashboard') }}"
                class="rounded-2xl border border-white/10 px-4 py-2 text-sm text-slate-300 transition hover:bg-white/5">
                ← {{ __('backoffice.pages.customer_chat.back') }}
            </a>
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

    <div class="rounded-[2rem] border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5 lg:p-6">
        @if (empty($messages))
            <p class="py-8 text-center text-sm text-slate-400">{{ __('backoffice.pages.customer_chat.empty') }}</p>
        @else
            <div
                class="space-y-4 rounded-[1.75rem] border border-white/5 bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.08),_transparent_30%),linear-gradient(180deg,rgba(15,23,42,0.98),rgba(17,24,39,0.98))] p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.04)] sm:p-5">
                @php $lastDate = null; @endphp
                @foreach ($messages as $msg)
                    @php $msgDate = $msg['date'] ?? null; @endphp
                    @if ($msgDate !== $lastDate)
                        <div class="flex items-center gap-3 py-2">
                            <div class="h-px flex-1 bg-white/10"></div>
                            <span
                                class="rounded-full border border-white/10 bg-white/10 px-3 py-1 text-[11px] font-medium tracking-[0.18em] text-slate-400">{{ $msgDate }}</span>
                            <div class="h-px flex-1 bg-white/10"></div>
                        </div>
                        @php $lastDate = $msgDate; @endphp
                    @endif

                    @if (($msg['role'] ?? '') === 'user')
                        <div class="flex items-end gap-3 justify-start">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-slate-800 text-xs font-semibold uppercase tracking-[0.2em] text-amber-300 shadow-lg shadow-black/20">
                                C
                            </div>
                            <div class="max-w-[82%] sm:max-w-[68%] lg:max-w-[48%]">
                                <div
                                    class="break-words rounded-[1.4rem] rounded-bl-md border border-white/10 bg-slate-800/95 px-4 py-3 shadow-lg shadow-black/20">
                                    <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-amber-400">
                                        {{ __('backoffice.pages.customer_chat.customer') }}</p>
                                    <p class="whitespace-pre-wrap break-words text-sm leading-6 text-slate-100">
                                        {{ $msg['message'] }}</p>
                                </div>
                                <p class="mt-1.5 pl-1 text-[10px] text-slate-500">{{ $msg['time'] ?? '' }}</p>
                            </div>
                        </div>
                    @else
                        <div class="flex items-end justify-end gap-3">
                            <div class="max-w-[82%] sm:max-w-[68%] lg:max-w-[48%]">
                                <div
                                    class="break-words rounded-[1.4rem] rounded-br-md border border-cyan-400/20 bg-gradient-to-br from-cyan-500/18 to-sky-500/12 px-4 py-3 shadow-lg shadow-cyan-950/25 backdrop-blur-sm">
                                    <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-cyan-300">
                                        {{ $msg['role'] ?? __('backoffice.pages.customer_chat.assistant') }}
                                    </p>
                                    <p class="whitespace-pre-wrap break-words text-sm leading-6 text-white">
                                        {{ $msg['message'] }}</p>
                                </div>
                                <p class="mt-1.5 pr-1 text-right text-[10px] text-cyan-300/60">{{ $msg['time'] ?? '' }}</p>
                            </div>
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-cyan-400/20 bg-cyan-400 text-xs font-bold uppercase tracking-[0.14em] text-slate-950 shadow-lg shadow-cyan-950/25">
                                AI
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
@endsection
