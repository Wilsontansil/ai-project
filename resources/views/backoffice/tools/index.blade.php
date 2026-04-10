@extends('backoffice.partials.layout')

@section('title', 'Tools')

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold">Tools</h1>
                <p class="mt-2 text-sm text-slate-300">Kelola tool yang tersedia untuk AI agent.</p>
            </div>
            <a href="{{ route('backoffice.tools.create') }}"
                class="rounded-2xl bg-cyan-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                + New Tool
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-sm text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    @if ($tools->isEmpty())
        <div class="rounded-3xl border border-white/10 bg-white/5 p-8 text-center backdrop-blur">
            <p class="text-slate-400">Belum ada tool. Tambahkan tool pertama untuk AI agent.</p>
        </div>
    @else
        <div class="rounded-3xl border border-white/10 bg-white/5 backdrop-blur overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10 text-sm">
                    <thead class="bg-white/5 text-left text-slate-300">
                        <tr>
                            <th class="px-5 py-3.5 font-medium">Tool Name</th>
                            <th class="px-5 py-3.5 font-medium">Display Name</th>
                            <th class="px-5 py-3.5 font-medium">Parameters</th>
                            <th class="px-5 py-3.5 font-medium text-center">Status</th>
                            <th class="px-5 py-3.5 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 bg-slate-950/40">
                        @foreach ($tools as $tool)
                            <tr class="transition hover:bg-white/[0.03]">
                                <td class="px-5 py-3.5">
                                    <span class="font-mono text-xs text-cyan-300">{{ $tool->tool_name }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-white/10">
                                            <svg class="h-4 w-4 text-white/70" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="{{ $tool->meta['icon'] ?? 'M13 10V3L4 14h7v7l9-11h-7z' }}" />
                                            </svg>
                                        </span>
                                        <div>
                                            <p class="font-medium text-white">{{ $tool->display_name }}</p>
                                            @if ($tool->description)
                                                <p class="text-xs text-slate-400 line-clamp-1">{{ $tool->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    @php
                                        $params = $tool->parameters['properties'] ?? [];
                                        $paramCount = count($params);
                                    @endphp
                                    @if ($paramCount > 0)
                                        <span class="font-mono text-xs text-slate-400">{{ $paramCount }}
                                            param{{ $paramCount > 1 ? 's' : '' }}</span>
                                    @else
                                        <span class="text-xs text-slate-500">–</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center">
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
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('backoffice.tools.edit', $tool) }}"
                                            class="rounded-xl border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-white/10">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('backoffice.tools.destroy', $tool) }}"
                                            onsubmit="return confirm('Hapus tool {{ $tool->display_name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="rounded-xl border border-red-400/20 bg-red-500/10 px-3 py-1.5 text-xs text-red-300 transition hover:bg-red-500/20">
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
    @endif
@endsection
