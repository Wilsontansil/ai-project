@extends('backoffice.partials.layout')

@section('title', 'Upload Training File')

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <h1 class="text-3xl font-semibold">Upload Training File</h1>
        <p class="mt-2 text-sm text-slate-300">Upload file untuk mengisi knowledge base secara batch.</p>
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
        <form method="POST" action="{{ route('backoffice.knowledge.upload.submit') }}" enctype="multipart/form-data"
            class="space-y-5">
            @csrf

            {{-- File upload --}}
            <div>
                <label for="file" class="mb-2 block text-sm text-slate-200">File</label>
                <div id="drop-zone"
                    class="relative flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-white/20 bg-slate-900/50 p-8 transition hover:border-cyan-400/50 hover:bg-slate-900/70 cursor-pointer">
                    <svg class="mb-3 h-10 w-10 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <p class="text-sm text-slate-400">Drag & drop file atau <span class="text-cyan-400 font-medium">klik
                            untuk pilih</span></p>
                    <p class="mt-1 text-xs text-slate-500">Format: {{ implode(', ', $supportedExtensions) }} — Max 5MB</p>
                    <p class="mt-1 text-xs text-slate-500" id="file-name"></p>
                    <input id="file" type="file" name="file" accept=".{{ implode(',.', $supportedExtensions) }}"
                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0" />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="category" class="mb-2 block text-sm text-slate-200">Category <span
                            class="text-slate-500">(opsional)</span></label>
                    <input id="category" type="text" name="category" value="{{ old('category') }}"
                        placeholder="e.g. FAQ, Game Info"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
                <div>
                    <label for="confidence_score" class="mb-2 block text-sm text-slate-200">Confidence Score</label>
                    <input id="confidence_score" type="number" name="confidence_score"
                        value="{{ old('confidence_score', '0.6') }}" min="0" max="1" step="0.1"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
            </div>

            {{-- Format guide --}}
            <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                <h3 class="mb-3 text-sm font-semibold text-white">📋 Format Guide</h3>
                <div class="space-y-3 text-xs text-slate-400">
                    <div>
                        <p class="font-medium text-slate-300">TXT File:</p>
                        <pre class="mt-1 rounded-xl bg-slate-900/70 p-3 text-slate-400">## Cara Deposit
Deposit dapat dilakukan melalui menu deposit. Transfer ke rekening yang tertera.

## Minimum Withdraw
Minimum withdraw adalah Rp 50.000 untuk semua metode pembayaran.</pre>
                    </div>
                    <div>
                        <p class="font-medium text-slate-300">CSV / Excel File:</p>
                        <p class="mt-1">Gunakan kolom header: <code
                                class="rounded bg-slate-800 px-1.5 py-0.5">content</code> (wajib), <code
                                class="rounded bg-slate-800 px-1.5 py-0.5">title</code> dan <code
                                class="rounded bg-slate-800 px-1.5 py-0.5">category</code> (opsional)</p>
                    </div>
                    <div>
                        <p class="font-medium text-slate-300">DOCX File:</p>
                        <p class="mt-1">Gunakan heading ## untuk judul section, atau teks biasa yang akan dipecah per
                            paragraf.</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit"
                    class="rounded-2xl bg-cyan-400 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    Upload & Import
                </button>
                <a href="{{ route('backoffice.knowledge.index') }}"
                    class="rounded-2xl bg-white/10 px-6 py-3 text-sm text-slate-300 transition hover:bg-white/15">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        (() => {
            const input = document.getElementById('file');
            const label = document.getElementById('file-name');
            const zone = document.getElementById('drop-zone');

            if (!input || !label || !zone) return;

            input.addEventListener('change', () => {
                label.textContent = input.files.length ? input.files[0].name : '';
            });

            ['dragenter', 'dragover'].forEach(evt => {
                zone.addEventListener(evt, e => {
                    e.preventDefault();
                    zone.classList.add('border-cyan-400/50');
                });
            });
            ['dragleave', 'drop'].forEach(evt => {
                zone.addEventListener(evt, e => {
                    e.preventDefault();
                    zone.classList.remove('border-cyan-400/50');
                });
            });
            zone.addEventListener('drop', e => {
                if (e.dataTransfer.files.length) {
                    input.files = e.dataTransfer.files;
                    label.textContent = e.dataTransfer.files[0].name;
                }
            });
        })();
    </script>
@endsection
