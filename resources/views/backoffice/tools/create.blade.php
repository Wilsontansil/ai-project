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
                <label for="description" class="mb-2 block text-sm text-slate-200">Description</label>
                <p class="mb-2 text-xs text-slate-400">Deskripsi fungsi tool ini — dikirim ke OpenAI.</p>
                <input id="description" type="text" name="description" value="{{ old('description') }}"
                    placeholder="e.g. Reset user password after account data verification"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
            </div>

            <div>
                <label for="parameters" class="mb-2 block text-sm text-slate-200">Parameters (JSON)</label>
                <p class="mb-2 text-xs text-slate-400">Schema parameter untuk OpenAI function calling. Format JSON
                    object.</p>
                <textarea id="parameters" name="parameters" rows="6"
                    placeholder='{
  "type": "object",
  "properties": {
    "username": { "type": "string", "description": "Username akun" }
  },
  "required": ["username"]
}'
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm font-mono text-white outline-none transition focus:border-cyan-400">{{ old('parameters') }}</textarea>
            </div>

            <div>
                <label for="keywords" class="mb-2 block text-sm text-slate-200">Keywords (comma-separated)</label>
                <p class="mb-2 text-xs text-slate-400">Kata kunci untuk intent matching fallback, pisahkan dengan koma.
                </p>
                <input id="keywords" type="text" name="keywords" value="{{ old('keywords') }}"
                    placeholder="e.g. reset password, resetpass, kata sandi"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
            </div>

            <div>
                <label for="missing_message" class="mb-2 block text-sm text-slate-200">Missing Data Message</label>
                <p class="mb-2 text-xs text-slate-400">Pesan yang ditampilkan jika data yang diperlukan belum lengkap.
                </p>
                <textarea id="missing_message" name="missing_message" rows="3"
                    placeholder="Untuk reset password, mohon kirim data berikut:&#10;Username(username) :&#10;Nama rekening(namarek) :"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">{{ old('missing_message') }}</textarea>
            </div>

            <div>
                <label for="class_name" class="mb-2 block text-sm text-slate-200">Class Name (optional)</label>
                <p class="mb-2 text-xs text-slate-400">PHP class untuk execution logic. Kosongkan jika tidak ada.</p>
                <input id="class_name" type="text" name="class_name" value="{{ old('class_name') }}"
                    placeholder="App\Services\Tools\YourTool"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm font-mono text-white outline-none transition focus:border-cyan-400" />
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
