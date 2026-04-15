@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.forbidden.title'))
@section('page-title', __('backoffice.pages.forbidden.page_title'))

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">{{ __('backoffice.pages.forbidden.title') }}</h1>
            <p class="text-xs text-slate-400">{{ __('backoffice.pages.forbidden.subtitle') }}</p>
        </div>
        <a href="{{ route('backoffice.forbidden.create') }}"
            class="rounded-lg bg-cyan-400 px-4 py-2 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300 sm:text-sm">
            + {{ __('backoffice.pages.forbidden.new_rule') }}
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-xs text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    @if ($rules->isEmpty())
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-8 text-center">
            <p class="text-sm text-slate-400">{{ __('backoffice.pages.forbidden.no_rules') }}</p>
        </div>
    @else
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5">
            <h2 class="mb-4 text-sm font-semibold">{{ __('backoffice.pages.forbidden.rule_list') }}</h2>
            <div class="overflow-hidden rounded-xl border border-white/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.forbidden.rule_title') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.forbidden.instruction') }}</th>
                                <th class="px-3 py-2 font-medium text-center">{{ __('backoffice.pages.forbidden.level') }}
                                </th>
                                <th class="px-3 py-2 font-medium text-center">{{ __('backoffice.common.status') }}</th>
                                <th class="px-3 py-2 font-medium text-right">{{ __('backoffice.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($rules as $rule)
                                <tr class="transition hover:bg-white/5">
                                    <td class="px-3 py-2">
                                        <p class="font-medium text-white">{{ $rule->title }}</p>
                                        <p class="text-[11px] text-slate-500">{{ $rule->created_at->format('d M Y H:i') }}
                                        </p>
                                    </td>
                                    <td class="max-w-xs px-3 py-2">
                                        <p class="text-xs text-slate-300 line-clamp-2">{{ $rule->instruction }}</p>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if ($rule->level === 'danger')
                                            <span
                                                class="inline-flex items-center rounded-full bg-red-500/20 px-2.5 py-0.5 text-xs font-semibold text-red-300 ring-1 ring-red-400/30">
                                                {{ __('backoffice.pages.forbidden.danger') }}
                                            </span>
                                        @elseif ($rule->level === 'warning')
                                            <span
                                                class="inline-flex items-center rounded-full bg-amber-500/20 px-2.5 py-0.5 text-xs font-semibold text-amber-300 ring-1 ring-amber-400/30">
                                                {{ __('backoffice.pages.forbidden.warning') }}
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center rounded-full bg-blue-500/20 px-2.5 py-0.5 text-xs font-semibold text-blue-300 ring-1 ring-blue-400/30">
                                                {{ __('backoffice.pages.forbidden.info') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if ($rule->is_active)
                                            <span
                                                class="inline-flex items-center rounded-full bg-emerald-500/20 px-2.5 py-0.5 text-xs font-semibold text-emerald-300 ring-1 ring-emerald-400/30">
                                                {{ __('backoffice.common.on') }}
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center rounded-full bg-red-500/20 px-2.5 py-0.5 text-xs font-semibold text-red-300 ring-1 ring-red-400/30">
                                                {{ __('backoffice.common.off') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('backoffice.forbidden.edit', $rule) }}"
                                                class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-white/10">
                                                {{ __('backoffice.common.edit') }}
                                            </a>
                                            <form method="POST"
                                                action="{{ route('backoffice.forbidden.destroy', $rule) }}"
                                                onsubmit="return confirm('{{ __('backoffice.pages.forbidden.delete_confirm', ['title' => $rule->title]) }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="rounded-lg border border-red-400/20 bg-red-500/10 px-3 py-1.5 text-xs text-red-300 transition hover:bg-red-500/20">
                                                    {{ __('backoffice.common.delete') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
