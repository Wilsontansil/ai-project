@extends('backoffice.partials.layout')

@section('title', 'New Tool')

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <h1 class="text-3xl font-semibold">Add New Tool</h1>
        <p class="mt-2 text-sm text-slate-300">Tambahkan tool baru untuk AI agent.</p>
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
        <form method="POST" action="{{ route('backoffice.tools.store') }}" class="space-y-5">
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="tool_name" class="mb-2 block text-sm text-slate-200">Tool Name (key)</label>
                    <p class="mb-2 text-xs text-slate-400">Identifier unik, contoh: resetPassword, checkBalance</p>
                    <input id="tool_name" type="text" name="tool_name" value="{{ old('tool_name') }}"
                        placeholder="e.g. resetPassword"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
                <div>
                    <label for="display_name" class="mb-2 block text-sm text-slate-200">Display Name</label>
                    <p class="mb-2 text-xs text-slate-400">Nama yang ditampilkan di sidebar.</p>
                    <input id="display_name" type="text" name="display_name" value="{{ old('display_name') }}"
                        placeholder="e.g. Reset Password"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
            </div>

            <div>
                <label for="class_name" class="mb-2 block text-sm text-slate-200">Class Name (full namespace)</label>
                <p class="mb-2 text-xs text-slate-400">Contoh: App\Services\Tools\ResetPasswordTool</p>
                <input id="class_name" type="text" name="class_name" value="{{ old('class_name') }}"
                    placeholder="App\Services\Tools\YourTool"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm font-mono text-white outline-none transition focus:border-cyan-400" />
            </div>

            <div>
                <label for="description" class="mb-2 block text-sm text-slate-200">Description</label>
                <input id="description" type="text" name="description" value="{{ old('description') }}"
                    placeholder="Deskripsi singkat fungsi tool ini"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
            </div>

            <div>
                <label for="icon" class="mb-2 block text-sm text-slate-200">SVG Icon Path (optional)</label>
                <p class="mb-2 text-xs text-slate-400">SVG path data untuk icon di sidebar. Kosongkan untuk icon default.
                </p>
                <input id="icon" type="text" name="icon" value="{{ old('icon') }}"
                    placeholder="M13 10V3L4 14h7v7l9-11h-7z"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm font-mono text-white outline-none transition focus:border-cyan-400" />
            </div>

            <div>
                <label
                    class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-slate-900/50 px-4 py-2 text-sm text-slate-200">
                    <input type="checkbox" name="is_enabled" value="1" {{ old('is_enabled', true) ? 'checked' : '' }}
                        class="rounded border-white/20 bg-slate-800 text-cyan-400 focus:ring-cyan-400" />
                    Enable tool
                </label>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <button type="submit"
                    class="rounded-2xl bg-cyan-400 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    Add Tool
                </button>
                <a href="{{ route('backoffice.tools.index') }}"
                    class="rounded-2xl border border-white/10 px-6 py-3 text-sm text-slate-300 transition hover:bg-white/5">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
