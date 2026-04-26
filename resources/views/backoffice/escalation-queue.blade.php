@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.escalation.title'))
@section('page-title', __('backoffice.pages.escalation.page_title'))

@php($boActive = 'escalation')

@section('content')
    @php($currentUserId = auth()->id())

    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">{{ __('backoffice.pages.escalation.title') }}</h1>
            <p class="text-xs text-slate-400">{{ __('backoffice.pages.escalation.subtitle') }}</p>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;">
        <div
            style="background-color:rgba(251,191,36,0.08);border:1px solid rgba(251,191,36,0.25);border-radius:12px;padding:12px 20px;">
            <p style="color:rgba(251,191,36,0.7);font-size:11px">{{ __('backoffice.pages.escalation.waiting') }}</p>
            <p style="color:#fff;font-size:18px;font-weight:700">{{ $stats['waiting'] }}</p>
        </div>
        <div
            style="background-color:rgba(34,211,238,0.08);border:1px solid rgba(34,211,238,0.25);border-radius:12px;padding:12px 20px;">
            <p style="color:rgba(34,211,238,0.7);font-size:11px">{{ __('backoffice.pages.escalation.human') }}</p>
            <p style="color:#fff;font-size:18px;font-weight:700">{{ $stats['human'] }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5">
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <h2 class="text-sm font-semibold">{{ __('backoffice.pages.escalation.title') }}</h2>
            <form method="GET" action="{{ route('backoffice.escalation-queue') }}"
                class="flex w-full max-w-full gap-2 sm:max-w-md">
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="{{ __('backoffice.pages.escalation.search_placeholder') }}"
                    style="background-color:rgba(15,23,42,0.7);color:#e2e8f0"
                    class="w-full rounded-lg border border-white/10 bg-slate-900/70 px-3 py-1.5 text-xs text-white outline-none transition focus:border-cyan-400" />
                <button type="submit"
                    class="rounded-lg bg-cyan-400 px-3 py-1.5 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300">{{ __('backoffice.pages.escalation.search') }}</button>
            </form>
        </div>

        <div class="overflow-hidden rounded-xl border border-white/10">
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.escalation.name') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.escalation.platform') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.escalation.phone') }}</th>
                            <th class="px-3 py-2 font-medium text-center">{{ __('backoffice.pages.escalation.mode') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.customer_chat.assigned_to') }}</th>
                            <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.escalation.last_update') }}</th>
                            <th class="px-3 py-2 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($customers as $customer)
                            @php
                                $assignedUserName =
                                    $customer->assignedUser?->name ?: $customer->assignedUser?->username;
                                $isOwnedByCurrentUser =
                                    $customer->assigned_user_id !== null &&
                                    (int) $customer->assigned_user_id === (int) $currentUserId;
                                $isAssignedToOther = $customer->assigned_user_id !== null && !$isOwnedByCurrentUser;
                            @endphp
                            <tr class="hover:bg-white/5">
                                <td class="px-3 py-2 text-white">{{ $customer->name ?: '-' }}</td>
                                <td class="px-3 py-2 text-slate-300">{{ ucfirst($customer->platform) }}</td>
                                <td class="px-3 py-2 text-slate-400">{{ $customer->phone_number ?: '-' }}</td>
                                <td class="px-3 py-2 text-center">
                                    @if ($customer->mode === 'waiting')
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            style="background:rgba(251,191,36,0.2);color:#fbbf24;">{{ __('backoffice.pages.escalation.waiting') }}</span>
                                    @else
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            style="background:rgba(34,211,238,0.2);color:#22d3ee;">{{ __('backoffice.pages.escalation.human') }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-slate-300">
                                    @if ($assignedUserName)
                                        <span
                                            class="inline-flex rounded-full bg-cyan-500/10 px-2 py-0.5 text-[10px] font-semibold text-cyan-300">{{ $assignedUserName }}</span>
                                    @else
                                        <span class="text-slate-500">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-slate-400">{{ $customer->updated_at?->diffForHumans() ?: '-' }}
                                </td>
                                <td class="px-3 py-2">
                                    <div style="display:flex;align-items:center;gap:0.375rem;">
                                        @if ($customer->mode === 'waiting')
                                            <form method="POST"
                                                action="{{ route('backoffice.customer.takeover', $customer->id) }}">
                                                @csrf
                                                <button type="submit"
                                                    class="rounded px-2 py-1 text-[10px] font-semibold transition"
                                                    @if ($isAssignedToOther) disabled @endif
                                                    style="background:rgba(34,211,238,0.2);color:#22d3ee;"
                                                    onmouseover="this.style.background='rgba(34,211,238,0.3)'"
                                                    onmouseout="this.style.background='rgba(34,211,238,0.2)'"
                                                    onfocus="this.style.background='rgba(34,211,238,0.3)'"
                                                    onblur="this.style.background='rgba(34,211,238,0.2)'">{{ __('backoffice.pages.escalation.takeover') }}</button>
                                            </form>
                                        @endif
                                        <form method="POST"
                                            action="{{ route('backoffice.customer.release', $customer->id) }}">
                                            @csrf
                                            <button type="submit"
                                                class="rounded px-2 py-1 text-[10px] font-semibold transition"
                                                @if ($isAssignedToOther) disabled @endif
                                                style="background:rgba(52,211,153,0.2);color:#34d399;"
                                                onmouseover="this.style.background='rgba(52,211,153,0.3)'"
                                                onmouseout="this.style.background='rgba(52,211,153,0.2)'"
                                                onfocus="this.style.background='rgba(52,211,153,0.3)'"
                                                onblur="this.style.background='rgba(52,211,153,0.2)'">{{ __('backoffice.pages.escalation.remove_from_queue') }}</button>
                                        </form>
                                        <a href="{{ route('backoffice.customer.chat', $customer->id) }}"
                                            class="rounded px-2 py-1 text-[10px] font-semibold transition"
                                            style="background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.7);"
                                            onmouseover="this.style.background='rgba(255,255,255,0.15)'"
                                            onmouseout="this.style.background='rgba(255,255,255,0.1)'"
                                            onfocus="this.style.background='rgba(255,255,255,0.15)'"
                                            onblur="this.style.background='rgba(255,255,255,0.1)'">{{ __('backoffice.pages.escalation.chat') }}</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-6 text-center text-slate-500">
                                    {{ __('backoffice.pages.escalation.empty') }}</td>
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
