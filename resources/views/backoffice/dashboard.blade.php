@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.dashboard.title'))
@section('page-title', __('backoffice.pages.dashboard.page_title'))

@php($boActive = 'customer')

@section('content')
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">{{ __('backoffice.pages.dashboard.title') }}</h1>
            <p class="text-xs text-slate-400">{{ __('backoffice.pages.dashboard.subtitle') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-cyan-400/20 bg-cyan-400/10 px-4 py-3 sm:px-5 sm:py-4"
            style="background-color:rgba(34,211,238,0.08);border:1px solid rgba(34,211,238,0.25);border-radius:12px">
            <p class="text-[11px] text-cyan-200/70" style="color:rgba(34,211,238,0.7);font-size:11px">
                {{ __('backoffice.pages.dashboard.stats_total') }}</p>
            <p class="text-lg font-bold text-white" style="color:#fff;font-size:18px;font-weight:700">
                {{ number_format($stats['total_customers']) }}</p>
        </div>
        <div class="rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 sm:px-5 sm:py-4"
            style="background-color:rgba(52,211,153,0.08);border:1px solid rgba(52,211,153,0.25);border-radius:12px">
            <p class="text-[11px] text-emerald-200/70" style="color:rgba(52,211,153,0.7);font-size:11px">Telegram</p>
            <p class="text-lg font-bold text-white" style="color:#fff;font-size:18px;font-weight:700">
                {{ number_format($stats['telegram_customers']) }}</p>
        </div>
        <div class="rounded-xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 sm:px-5 sm:py-4"
            style="background-color:rgba(251,191,36,0.08);border:1px solid rgba(251,191,36,0.25);border-radius:12px">
            <p class="text-[11px] text-amber-200/70" style="color:rgba(251,191,36,0.7);font-size:11px">WhatsApp</p>
            <p class="text-lg font-bold text-white" style="color:#fff;font-size:18px;font-weight:700">
                {{ number_format($stats['whatsapp_customers']) }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5">
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <h2 class="text-sm font-semibold">{{ __('backoffice.pages.dashboard.customers_title') }}</h2>
            <form method="GET" action="{{ route('backoffice.dashboard') }}"
                class="flex w-full max-w-full gap-2 sm:max-w-md">
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="{{ __('backoffice.pages.dashboard.search_placeholder') }}"
                    style="background-color:rgba(15,23,42,0.7);color:#e2e8f0"
                    class="w-full rounded-lg border border-white/10 bg-slate-900/70 px-3 py-1.5 text-xs text-white outline-none transition focus:border-cyan-400" />
                <button type="submit"
                    class="rounded-lg bg-cyan-400 px-3 py-1.5 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300">{{ __('backoffice.pages.dashboard.search') }}</button>
            </form>
        </div>

        <div class="overflow-hidden rounded-xl border border-white/10">
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.dashboard.name') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.dashboard.platform') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.dashboard.phone') }}</th>
                            <th class="px-3 py-2 font-medium text-center">{{ __('backoffice.pages.dashboard.msgs') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.dashboard.last_seen') }}</th>
                            <th class="px-3 py-2 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($customers as $customer)
                            <tr class="hover:bg-white/5">
                                <td class="px-3 py-2 text-white">
                                    {{ $customer->name ?: '-' }}
                                </td>
                                <td class="px-3 py-2 text-slate-300">{{ ucfirst($customer->platform) }}</td>
                                <td class="px-3 py-2 text-slate-400">{{ $customer->phone_number ?: '-' }}</td>
                                <td class="px-3 py-2 text-center text-slate-400">{{ $customer->total_messages }}</td>
                                <td class="px-3 py-2 text-slate-400">{{ $customer->last_seen_at?->diffForHumans() ?: '-' }}
                                </td>
                                <td class="px-3 py-2">
                                    <a href="{{ route('backoffice.customer.chat', $customer->id) }}"
                                        class="rounded bg-cyan-400/20 px-2 py-1 text-[10px] font-semibold text-cyan-300 transition hover:bg-cyan-400/30">{{ __('backoffice.pages.dashboard.chat') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-6 text-center text-slate-500">
                                    {{ __('backoffice.pages.dashboard.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto">
            {{ $customers->links() }}
        </div>
    </div>
@endsection
