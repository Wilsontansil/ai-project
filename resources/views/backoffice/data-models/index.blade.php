@extends('backoffice.partials.layout')

@section('title', 'Data Models')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">Data Models</h1>
            <p class="text-xs text-slate-400">Kelola struktur field model berbasis JSON untuk referensi AI/backoffice.</p>
        </div>
        <a href="{{ route('backoffice.data-models.create') }}"
            class="rounded-lg bg-cyan-400 px-4 py-2 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300 sm:text-sm">
            + New Data Model
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-xs text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    @if ($dataModels->isEmpty())
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-8 text-center">
            <p class="text-sm text-slate-400">Belum ada data model. Tambahkan model pertama, misalnya <span
                    class="font-mono text-cyan-300">Player</span>.</p>
        </div>
    @else
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5">
            <h2 class="mb-4 text-sm font-semibold">Model List</h2>
            <div class="overflow-hidden rounded-xl border border-white/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-3 py-2 font-medium">Model Name</th>
                                <th class="px-3 py-2 font-medium">Table</th>
                                <th class="px-3 py-2 font-medium">Connection</th>
                                <th class="px-3 py-2 font-medium">Description</th>
                                <th class="px-3 py-2 font-medium">Fields</th>
                                <th class="px-3 py-2 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($dataModels as $model)
                                @php
                                    $fieldCount = count($model->fields ?? []);
                                @endphp
                                <tr class="transition hover:bg-white/5">
                                    <td class="px-3 py-2">
                                        <div>
                                            <p class="font-semibold text-white">{{ $model->model_name }}</p>
                                            <p class="font-mono text-[11px] text-slate-400">{{ $model->slug }}</p>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs text-slate-300">{{ $model->table_name ?: '-' }}
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs text-slate-300">
                                        {{ $model->connection_name ?: 'mysqlgame' }}</td>
                                    <td class="px-3 py-2 text-slate-300">{{ $model->description ?: '-' }}</td>
                                    <td class="px-3 py-2">
                                        <span class="font-mono text-xs text-slate-300">{{ $fieldCount }}
                                            field{{ $fieldCount === 1 ? '' : 's' }}</span>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('backoffice.data-models.edit', $model) }}"
                                                class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-white/10">
                                                Edit
                                            </a>
                                            <form method="POST"
                                                action="{{ route('backoffice.data-models.destroy', $model) }}"
                                                onsubmit="return confirm('Hapus data model {{ $model->model_name }}?')">
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
