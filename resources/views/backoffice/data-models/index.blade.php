@extends('backoffice.partials.layout')

@section('title', 'Data Models')

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold">Data Models</h1>
                <p class="mt-2 text-sm text-slate-300">Kelola struktur field model berbasis JSON untuk referensi
                    AI/backoffice.</p>
            </div>
            <a href="{{ route('backoffice.data-models.create') }}"
                class="rounded-2xl bg-cyan-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                + New Data Model
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-sm text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    @if ($dataModels->isEmpty())
        <div class="rounded-3xl border border-white/10 bg-white/5 p-8 text-center backdrop-blur">
            <p class="text-slate-400">Belum ada data model. Tambahkan model pertama, misalnya <span
                    class="font-mono text-cyan-300">Player</span>.</p>
        </div>
    @else
        <div class="rounded-3xl border border-white/10 bg-white/5 backdrop-blur overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10 text-sm">
                    <thead class="bg-white/5 text-left text-slate-300">
                        <tr>
                            <th class="px-5 py-3.5 font-medium">Model Name</th>
                            <th class="px-5 py-3.5 font-medium">Description</th>
                            <th class="px-5 py-3.5 font-medium">Fields</th>
                            <th class="px-5 py-3.5 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 bg-slate-950/40">
                        @foreach ($dataModels as $model)
                            @php
                                $fieldCount = count($model->fields ?? []);
                            @endphp
                            <tr class="transition hover:bg-white/[0.03]">
                                <td class="px-5 py-3.5">
                                    <div>
                                        <p class="font-semibold text-white">{{ $model->model_name }}</p>
                                        <p class="text-xs font-mono text-slate-400">{{ $model->slug }}</p>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-slate-300">{{ $model->description ?: '-' }}</td>
                                <td class="px-5 py-3.5">
                                    <span class="font-mono text-xs text-slate-300">{{ $fieldCount }}
                                        field{{ $fieldCount === 1 ? '' : 's' }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('backoffice.data-models.edit', $model) }}"
                                            class="rounded-xl border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-white/10">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('backoffice.data-models.destroy', $model) }}"
                                            onsubmit="return confirm('Hapus data model {{ $model->model_name }}?')">
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
