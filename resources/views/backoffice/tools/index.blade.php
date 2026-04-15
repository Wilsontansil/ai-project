@extends('backoffice.partials.layout')

@section('title', 'Tools')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">Tools</h1>
            <p class="text-xs text-slate-400">Kelola tool yang tersedia untuk AI agent.</p>
        </div>
        <a href="{{ route('backoffice.tools.create') }}"
            class="rounded-lg bg-cyan-400 px-4 py-2 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300 sm:text-sm">
            + New Tool
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-xs text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    @if ($tools->isEmpty())
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-8 text-center">
            <p class="text-sm text-slate-400">Belum ada tool. Tambahkan tool pertama untuk AI agent.</p>
        </div>
    @else
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5">
            <h2 class="mb-4 text-sm font-semibold">Tool List</h2>
            <div class="overflow-hidden rounded-xl border border-white/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-3 py-2 font-medium">Tool Name</th>
                                <th class="px-3 py-2 font-medium">Display Name</th>
                                <th class="px-3 py-2 font-medium">Type</th>
                                <th class="px-3 py-2 font-medium text-center">Status</th>
                                <th class="px-3 py-2 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($tools as $tool)
                                <tr class="transition hover:bg-white/5">
                                    <td class="px-3 py-2">
                                        <span class="font-mono text-xs text-cyan-300">{{ $tool->tool_name }}</span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div>
                                            <p class="font-medium text-white">{{ $tool->display_name }}</p>
                                            @if ($tool->description)
                                                <p class="text-[11px] text-slate-400 line-clamp-1">{{ $tool->description }}
                                                </p>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        @if ($tool->type === 'info')
                                            <span
                                                class="inline-flex items-center rounded-full bg-blue-500/20 px-2.5 py-0.5 text-xs font-semibold text-blue-300 ring-1 ring-blue-400/30">INFO</span>
                                        @elseif ($tool->type === 'get')
                                            <span
                                                class="inline-flex items-center rounded-full bg-amber-500/20 px-2.5 py-0.5 text-xs font-semibold text-amber-300 ring-1 ring-amber-400/30">GET</span>
                                        @elseif ($tool->type === 'update')
                                            <span
                                                class="inline-flex items-center rounded-full bg-purple-500/20 px-2.5 py-0.5 text-xs font-semibold text-purple-300 ring-1 ring-purple-400/30">UPDATE</span>
                                        @elseif ($tool->type === 'get_multiple')
                                            <span
                                                class="inline-flex items-center rounded-full bg-teal-500/20 px-2.5 py-0.5 text-xs font-semibold text-teal-300 ring-1 ring-teal-400/30">GET
                                                MULTIPLE</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if ($tool->is_enabled)
                                            <span
                                                class="inline-flex items-center rounded-full bg-emerald-500/20 px-2.5 py-0.5 text-xs font-semibold text-emerald-300 ring-1 ring-emerald-400/30">
                                                ON
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center rounded-full bg-red-500/20 px-2.5 py-0.5 text-xs font-semibold text-red-300 ring-1 ring-red-400/30">
                                                OFF
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('backoffice.tools.edit', $tool) }}"
                                                class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-white/10">
                                                Edit
                                            </a>
                                            <form method="POST" action="{{ route('backoffice.tools.destroy', $tool) }}"
                                                onsubmit="return confirm('Hapus tool {{ $tool->display_name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="rounded-lg border border-red-400/20 bg-red-500/10 px-3 py-1.5 text-xs text-red-300 transition hover:bg-red-500/20">
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
        </div>
    @endif
@endsection
