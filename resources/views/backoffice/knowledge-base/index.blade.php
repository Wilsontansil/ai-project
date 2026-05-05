@extends('backoffice.partials.layout')

@section('title', 'Knowledge Base')
@section('page-title', 'Knowledge Base')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">Knowledge Base</h1>
            <p class="text-xs text-slate-400">Reference text injected into every AI conversation as background knowledge.</p>
        </div>
        <a href="{{ route('backoffice.knowledge-base.create') }}"
            class="rounded-lg bg-cyan-400 px-4 py-2 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300 sm:text-sm">
            + New Entry
        </a>
    </div>

    @if ($entries->isEmpty())
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-8 text-center">
            <p class="text-sm text-slate-400">No knowledge base entries yet.</p>
        </div>
    @else
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5">
            <div class="overflow-hidden rounded-xl border border-white/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Title</th>
                                <th class="px-4 py-3">Source</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Updated</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($entries as $entry)
                                <tr class="transition hover:bg-white/3">
                                    <td class="px-4 py-3 font-medium text-white">
                                        {{ $entry->title }}
                                        @if ($entry->file_name)
                                            <span class="ml-1 text-[10px] text-slate-400">({{ $entry->file_name }})</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[10px] font-semibold
                                            {{ $entry->source === 'file' ? 'bg-violet-500/20 text-violet-300 ring-1 ring-violet-400/30' : ($entry->source === 'website' ? 'bg-cyan-500/20 text-cyan-300 ring-1 ring-cyan-400/30' : 'bg-slate-500/20 text-slate-300 ring-1 ring-slate-400/30') }}">
                                            {{ $entry->source }}
                                        </span>
                                        @if ($entry->source === 'website')
                                            <div class="mt-1 text-[10px] text-slate-400">
                                                Sync: {{ $entry->last_sync_status ?? '-' }}
                                                @if ($entry->last_synced_at)
                                                    · {{ $entry->last_synced_at->format('d M H:i') }}
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($entry->is_active)
                                            <span
                                                class="rounded-full bg-emerald-500/20 px-2 py-0.5 text-[10px] font-semibold text-emerald-300 ring-1 ring-emerald-400/30">Active</span>
                                        @else
                                            <span
                                                class="rounded-full bg-rose-500/20 px-2 py-0.5 text-[10px] font-semibold text-rose-300 ring-1 ring-rose-400/30">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-400">{{ $entry->updated_at->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('backoffice.knowledge-base.edit', $entry) }}"
                                                class="rounded-lg border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-medium text-slate-200 transition hover:bg-white/10">
                                                Edit
                                            </a>
                                            <form method="POST"
                                                action="{{ route('backoffice.knowledge-base.destroy', $entry) }}"
                                                onsubmit="return confirm('Delete this entry?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="rounded-lg border border-rose-400/20 bg-rose-500/10 px-3 py-1 text-[11px] font-medium text-rose-300 transition hover:bg-rose-500/20">
                                                    Delete
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

            @if ($entries->hasPages())
                <div class="mt-4">
                    {{ $entries->links() }}
                </div>
            @endif
        </div>
    @endif
@endsection
