@extends('backoffice.partials.layout')

@section('title', 'Edit Tool — ' . $tool->display_name)

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <h1 class="text-3xl font-semibold">Edit Tool</h1>
        <p class="mt-2 text-sm text-slate-300">{{ $tool->display_name }} — <span
                class="font-mono text-cyan-300">{{ $tool->tool_name }}</span></p>
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
        <form method="POST" action="{{ route('backoffice.tools.update', $tool) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm text-slate-200">Tool Name (key)</label>
                    <p
                        class="rounded-2xl border border-white/10 bg-slate-900/30 px-4 py-3 text-sm font-mono text-slate-400">
                        {{ $tool->tool_name }}</p>
                </div>
                <div>
                    <label for="display_name" class="mb-2 block text-sm text-slate-200">Display Name</label>
                    <input id="display_name" type="text" name="display_name"
                        value="{{ old('display_name', $tool->display_name) }}"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
            </div>

            <div>
                <label for="description" class="mb-2 block text-sm text-slate-200">Description</label>
                <p class="mb-2 text-xs text-slate-400">Deskripsi fungsi tool ini — dikirim ke OpenAI.</p>
                <input id="description" type="text" name="description"
                    value="{{ old('description', $tool->description) }}"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
            </div>

            <div>
                <label for="parameters" class="mb-2 block text-sm text-slate-200">Parameters (JSON)</label>
                <p class="mb-2 text-xs text-slate-400">Schema parameter untuk OpenAI function calling.</p>
                <textarea id="parameters" name="parameters" rows="6"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm font-mono text-white outline-none transition focus:border-cyan-400">{{ old('parameters', $tool->parameters ? json_encode($tool->parameters, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
            </div>

            <div>
                <label for="keywords" class="mb-2 block text-sm text-slate-200">Keywords (comma-separated)</label>
                <p class="mb-2 text-xs text-slate-400">Kata kunci untuk intent matching fallback.</p>
                <input id="keywords" type="text" name="keywords"
                    value="{{ old('keywords', $tool->keywords ? implode(', ', $tool->keywords) : '') }}"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
            </div>

            <div>
                <label for="missing_message" class="mb-2 block text-sm text-slate-200">Missing Data Message</label>
                <p class="mb-2 text-xs text-slate-400">Pesan yang ditampilkan jika data belum lengkap.</p>
                <textarea id="missing_message" name="missing_message" rows="3"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">{{ old('missing_message', $tool->missing_message) }}</textarea>
            </div>

            <div>
                <label for="class_name" class="mb-2 block text-sm text-slate-200">Class Name (optional)</label>
                <p class="mb-2 text-xs text-slate-400">PHP class untuk execution logic. Kosongkan jika tidak ada.</p>
                <input id="class_name" type="text" name="class_name" value="{{ old('class_name', $tool->class_name) }}"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm font-mono text-white outline-none transition focus:border-cyan-400" />
            </div>

            <div>
                <label for="icon" class="mb-2 block text-sm text-slate-200">SVG Icon Path</label>
                <input id="icon" type="text" name="icon" value="{{ old('icon', $tool->meta['icon'] ?? '') }}"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm font-mono text-white outline-none transition focus:border-cyan-400" />
            </div>

            <div>
                <label
                    class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-slate-900/50 px-4 py-2 text-sm text-slate-200">
                    <input type="checkbox" name="is_enabled" value="1"
                        {{ old('is_enabled', $tool->is_enabled) ? 'checked' : '' }}
                        class="rounded border-white/20 bg-slate-800 text-cyan-400 focus:ring-cyan-400" />
                    Enable tool
                </label>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <button type="submit"
                    class="rounded-2xl bg-cyan-400 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    Save Changes
                </button>
                <a href="{{ route('backoffice.tools.index') }}"
                    class="rounded-2xl border border-white/10 px-6 py-3 text-sm text-slate-300 transition hover:bg-white/5">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
