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
                            <div
                                class="max-w-[60%] break-words rounded-2xl rounded-bl-sm border border-white/10 bg-slate-800 px-4 py-3 shadow-lg shadow-black/20 sm:max-w-[52%] lg:max-w-[46%]">
                                <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-amber-400/90">
                                    {{ __('backoffice.pages.customer_chat.customer') }}</p>
                                <p class="whitespace-pre-wrap break-words text-sm text-slate-100">{{ $msg['message'] }}</p>
                                <p class="mt-1.5 text-[10px] text-slate-500">{{ $msg['time'] ?? '' }}</p>
                            </div>
                        </div>
                    @else
                        <div class="flex items-start gap-3">
                            <div
                                class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cyan-400/15 text-xs font-semibold uppercase tracking-[0.14em] text-cyan-300">
                                AI
                            </div>
                            <div class="max-w-[82%] break-words sm:max-w-[72%] lg:max-w-[58%]">
                                <p class="mb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-cyan-400">
                                    {{ $msg['role'] ?? __('backoffice.pages.customer_chat.assistant') }}
                                </p>
                                <p class="whitespace-pre-wrap break-words text-sm leading-7 text-slate-100">
                                    {{ $msg['message'] }}</p>
                                <p class="mt-2 text-[10px] text-slate-500">{{ $msg['time'] ?? '' }}</p>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
@endsection
