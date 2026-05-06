@extends('backoffice.partials.layout')

@section('title', 'Agent Rules')
@section('page-title', 'Agent Rules')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">Agent Rules</h1>
            <p class="text-xs text-slate-400">Guidelines and restrictions injected into AI agent behavior.</p>
        </div>
        @can('manage agent-rules')
            <a href="{{ route('backoffice.agent-rules.create') }}"
                class="rounded-lg bg-cyan-400 px-4 py-2 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300 sm:text-sm">
                + New Rule
            </a>
        @endcan
    </div>

    @if ($rules->isEmpty())
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-8 text-center">
            <p class="text-sm text-slate-400">No agent rules yet.</p>
        </div>
    @else
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5">
            <div class="overflow-hidden rounded-xl border border-white/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Title</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Level</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3 text-center">Priority</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($rules as $rule)
                                <tr class="transition hover:bg-white/3">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-white">{{ $rule->title }}</p>
                                        <p class="mt-0.5 text-[11px] text-slate-400 line-clamp-1">{{ $rule->instruction }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($rule->type === 'forbidden')
                                            <span
                                                class="rounded-full bg-rose-500/20 px-2 py-0.5 text-[10px] font-semibold text-rose-300 ring-1 ring-rose-400/30">FORBIDDEN</span>
                                        @else
                                            <span
                                                class="rounded-full bg-blue-500/20 px-2 py-0.5 text-[10px] font-semibold text-blue-300 ring-1 ring-blue-400/30">GUIDELINE</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($rule->level === 'danger')
                                            <span
                                                class="rounded-full bg-rose-500/20 px-2 py-0.5 text-[10px] font-semibold text-rose-300 ring-1 ring-rose-400/30">DANGER</span>
                                        @elseif ($rule->level === 'warning')
                                            <span
                                                class="rounded-full bg-amber-500/20 px-2 py-0.5 text-[10px] font-semibold text-amber-300 ring-1 ring-amber-400/30">WARNING</span>
                                        @else
                                            <span
                                                class="rounded-full bg-blue-500/20 px-2 py-0.5 text-[10px] font-semibold text-blue-300 ring-1 ring-blue-400/30">INFO</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-300">{{ $rule->category }}</td>
                                    <td class="px-4 py-3 text-center text-slate-300">{{ $rule->priority }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($rule->is_active)
                                            <span
                                                class="rounded-full bg-emerald-500/20 px-2 py-0.5 text-[10px] font-semibold text-emerald-300 ring-1 ring-emerald-400/30">Active</span>
                                        @else
                                            <span
                                                class="rounded-full bg-rose-500/20 px-2 py-0.5 text-[10px] font-semibold text-rose-300 ring-1 ring-rose-400/30">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            @can('manage agent-rules')
                                                <a href="{{ route('backoffice.agent-rules.edit', $rule) }}"
                                                    class="rounded-lg border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-medium text-slate-200 transition hover:bg-white/10">
                                                    Edit
                                                </a>
                                                <form method="POST"
                                                    action="{{ route('backoffice.agent-rules.destroy', $rule) }}"
                                                    onsubmit="return confirm('Delete rule \'{{ addslashes($rule->title) }}\'?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="rounded-lg border border-rose-400/20 bg-rose-500/10 px-3 py-1 text-[11px] font-medium text-rose-300 transition hover:bg-rose-500/20">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($rules->hasPages())
                <div class="mt-4">
                    {{ $rules->links() }}
                </div>
            @endif
        </div>
    @endif
@endsection
