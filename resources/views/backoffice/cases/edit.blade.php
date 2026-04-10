@extends('backoffice.partials.layout')

@section('title', 'Edit Case')

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <h1 class="text-3xl font-semibold">Edit Case</h1>
        <p class="mt-2 text-sm text-slate-300">Update case ini untuk menyesuaikan perilaku AI agent.</p>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-300/30 bg-rose-500/15 px-4 py-3 text-sm text-rose-100">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <form method="POST" action="{{ route('backoffice.cases.update', $case) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="title" class="mb-2 block text-sm text-slate-200">Case Title</label>
                <input id="title" type="text" name="title" value="{{ old('title', $case->title) }}"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
            </div>

            <div>
                <label for="instruction" class="mb-2 block text-sm text-slate-200">Instruction for AI</label>
                <p class="mb-2 text-xs text-slate-400">Tulis instruksi yang jelas agar AI agent tidak mengulangi kesalahan
                    ini.</p>
                <textarea id="instruction" name="instruction" rows="4"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">{{ old('instruction', $case->instruction) }}</textarea>
            </div>

            <div>
                <label for="level" class="mb-2 block text-sm text-slate-200">Level</label>
                <select id="level" name="level"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">
                    <option value="info" {{ old('level', $case->level) === 'info' ? 'selected' : '' }}>Info</option>
                    <option value="warning" {{ old('level', $case->level) === 'warning' ? 'selected' : '' }}>Warning
                    </option>
                    <option value="danger" {{ old('level', $case->level) === 'danger' ? 'selected' : '' }}>Danger</option>
                </select>
            </div>

            <div class="flex items-center gap-3">
                <label
                    class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-slate-900/50 px-4 py-2 text-sm text-slate-200">
                    <input type="checkbox" name="is_active" value="1" {{ $case->is_active ? 'checked' : '' }}
                        class="rounded border-white/20 bg-slate-800 text-cyan-400 focus:ring-cyan-400" />
                    Active
                </label>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <button type="submit"
                    class="rounded-2xl bg-cyan-400 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    Update Case
                </button>
                <a href="{{ route('backoffice.cases.index') }}"
                    class="rounded-2xl border border-white/10 px-6 py-3 text-sm text-slate-300 transition hover:bg-white/5">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
