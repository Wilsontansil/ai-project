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
                        style="background-color:rgba(15,23,42,0.7);color:#e2e8f0" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
            </div>

            <div>
                <label for="description" class="mb-2 block text-sm text-slate-200">Description</label>
                <p class="mb-2 text-xs text-slate-400">Deskripsi fungsi tool ini — dikirim ke OpenAI.</p>
                <input id="description" type="text" name="description"
                    value="{{ old('description', $tool->description) }}"
                    style="background-color:rgba(15,23,42,0.7);color:#e2e8f0" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
            </div>

            <div>
                <label class="mb-2 block text-sm text-slate-200">Parameters</label>
                <p class="mb-2 text-xs text-slate-400">Data yang perlu dikumpulkan dari user. Kosongkan jika tool hanya
                    memberikan informasi.</p>

                <div id="param-list" class="space-y-3">
                    {{-- Rows populated by JS --}}
                </div>

                <button type="button" onclick="addParamRow()"
                    class="mt-3 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs text-slate-300 transition hover:bg-white/10">
                    + Tambah Parameter
                </button>
            </div>

            <div>
                <label for="keywords" class="mb-2 block text-sm text-slate-200">Keywords (comma-separated)</label>
                <p class="mb-2 text-xs text-slate-400">Kata kunci untuk intent matching fallback.</p>
                <input id="keywords" type="text" name="keywords"
                    value="{{ old('keywords', $tool->keywords ? implode(', ', $tool->keywords) : '') }}"
                    style="background-color:rgba(15,23,42,0.7);color:#e2e8f0" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
            </div>

            <div>
                <label for="missing_message" class="mb-2 block text-sm text-slate-200">Missing Data Message</label>
                <p class="mb-2 text-xs text-slate-400">Pesan yang ditampilkan jika data belum lengkap.</p>
                <textarea id="missing_message" name="missing_message" rows="3"
                    style="background-color:rgba(15,23,42,0.7);color:#e2e8f0" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">{{ old('missing_message', $tool->missing_message) }}</textarea>
            </div>

            <div>
                <label for="information_text" class="mb-2 block text-sm text-slate-200">Information Text</label>
                <p class="mb-2 text-xs text-slate-400">Teks informasi yang langsung dikirim sebagai jawaban. Cocok untuk
                    tool yang hanya memberikan info.</p>
                <textarea id="information_text" name="information_text" rows="4"
                    style="background-color:rgba(15,23,42,0.7);color:#e2e8f0" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">{{ old('information_text', $tool->information_text) }}</textarea>
            </div>

            <div>
                <label for="icon" class="mb-2 block text-sm text-slate-200">SVG Icon Path</label>
                <input id="icon" type="text" name="icon" value="{{ old('icon', $tool->meta['icon'] ?? '') }}"
                    style="background-color:rgba(15,23,42,0.7);color:#e2e8f0" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm font-mono text-white outline-none transition focus:border-cyan-400" />
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

@section('scripts')
    <script>
        let paramIndex = 0;

        function addParamRow(name = '', desc = '', required = false) {
            const list = document.getElementById('param-list');
            const row = document.createElement('div');
            row.className = 'flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-900/50 p-3';
            row.innerHTML = `
                    <input type="text" name="params[${paramIndex}][name]" value="${name}" placeholder="Nama field (e.g. username)"
                        style="background-color:rgba(15,23,42,0.7);color:#e2e8f0" class="w-1/3 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                    <input type="text" name="params[${paramIndex}][description]" value="${desc}" placeholder="Deskripsi (e.g. Username akun)"
                        style="background-color:rgba(15,23,42,0.7);color:#e2e8f0" class="flex-1 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                    <label class="flex items-center gap-1.5 text-xs text-slate-300 whitespace-nowrap">
                        <input type="checkbox" name="params[${paramIndex}][required]" value="1" ${required ? 'checked' : ''}
                            class="rounded border-white/20 bg-slate-800 text-cyan-400 focus:ring-cyan-400" />
                        Wajib
                    </label>
                    <button type="button" onclick="this.parentElement.remove()"
                        class="shrink-0 rounded-lg border border-red-400/20 bg-red-500/10 px-2 py-1.5 text-xs text-red-300 hover:bg-red-500/20">&times;</button>
                `;
            list.appendChild(row);
            paramIndex++;
        }

        // Pre-populate existing parameters
        document.addEventListener('DOMContentLoaded', function() {
            const existing = @json($tool->parameters ?? []);
            const properties = existing.properties || {};
            const required = existing.required || [];

            for (const [name, prop] of Object.entries(properties)) {
                addParamRow(name, prop.description || '', required.includes(name));
            }
        });
    </script>
@endsection
@endsection
