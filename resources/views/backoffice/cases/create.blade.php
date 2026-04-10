@extends('backoffice.partials.layout')

@section('title', 'New Case')

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <h1 class="text-3xl font-semibold">Report New Case</h1>
        <p class="mt-2 text-sm text-slate-300">Tambahkan case baru untuk memperbaiki perilaku AI agent.</p>
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
        <form method="POST" action="{{ route('backoffice.cases.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="title" class="mb-2 block text-sm text-slate-200">Case Title</label>
                <input id="title" type="text" name="title" value="{{ old('title') }}"
                    placeholder="e.g. Agent asking customer for password"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
            </div>

            <div>
                <label for="instruction" class="mb-2 block text-sm text-slate-200">Instruction for AI</label>
                <p class="mb-2 text-xs text-slate-400">Tulis instruksi yang jelas agar AI agent tidak mengulangi kesalahan
                    ini.</p>
                <textarea id="instruction" name="instruction" rows="4"
                    placeholder="e.g. Dont ask customer for password when reset password, just always give 1234567 as default"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">{{ old('instruction') }}</textarea>
            </div>

            <div>
                <label for="level" class="mb-2 block text-sm text-slate-200">Level</label>
                <select id="level" name="level"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">
                    <option value="info" {{ old('level') === 'info' ? 'selected' : '' }}>Info</option>
                    <option value="warning" {{ old('level', 'warning') === 'warning' ? 'selected' : '' }}>Warning</option>
                    <option value="danger" {{ old('level') === 'danger' ? 'selected' : '' }}>Danger</option>
                </select>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <button type="submit"
                    class="rounded-2xl bg-cyan-400 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    Submit Case
                </button>
                <a href="{{ route('backoffice.cases.index') }}"
                    class="rounded-2xl border border-white/10 px-6 py-3 text-sm text-slate-300 transition hover:bg-white/5">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
