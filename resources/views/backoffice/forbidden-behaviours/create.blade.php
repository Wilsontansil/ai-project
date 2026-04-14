@extends('backoffice.partials.layout')

@section('title', 'New Forbidden Rule')

@section('content')
    {{-- Header --}}
    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <h1 class="text-lg font-semibold sm:text-2xl">New Forbidden Rule</h1>
        <p class="text-xs text-slate-400">Tambahkan aturan baru yang AI agent dilarang lakukan.</p>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-rose-300/30 bg-rose-500/15 px-4 py-3 text-xs text-rose-100">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5">
        <form method="POST" action="{{ route('backoffice.forbidden.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="title" class="mb-1.5 block text-sm text-slate-200">Rule Title</label>
                <input id="title" type="text" name="title" value="{{ old('title') }}"
                    placeholder="e.g. Forbidden to create player without confirmation"
                    class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2.5 text-sm text-white outline-none transition focus:border-cyan-400" />
            </div>

            <div>
                <label for="instruction" class="mb-1.5 block text-sm text-slate-200">Instruction for AI</label>
                <p class="mb-1.5 text-xs text-slate-400">Tulis instruksi yang jelas tentang apa yang AI agent dilarang
                    lakukan.</p>
                <textarea id="instruction" name="instruction" rows="4"
                    placeholder="e.g. AI dilarang membuat data player tanpa konfirmasi dari player"
                    class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2.5 text-sm text-white outline-none transition focus:border-cyan-400">{{ old('instruction') }}</textarea>
            </div>

            <div>
                <label for="level" class="mb-1.5 block text-sm text-slate-200">Level</label>
                <select id="level" name="level"
                    class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2.5 text-sm text-white outline-none transition focus:border-cyan-400">
                    <option value="info" {{ old('level') === 'info' ? 'selected' : '' }}>Info</option>
                    <option value="warning" {{ old('level', 'warning') === 'warning' ? 'selected' : '' }}>Warning</option>
                    <option value="danger" {{ old('level') === 'danger' ? 'selected' : '' }}>Danger</option>
                </select>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                    class="rounded-lg bg-cyan-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    Submit Rule
                </button>
                <a href="{{ route('backoffice.forbidden.index') }}"
                    class="rounded-lg border border-white/10 px-5 py-2.5 text-sm text-slate-300 transition hover:bg-white/5">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
