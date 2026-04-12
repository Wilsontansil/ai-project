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
                <label class="mb-2 block text-sm text-slate-200">Parameters</label>
                <p class="mb-2 text-xs text-slate-400">Data yang perlu dikumpulkan dari user. Kosongkan jika tool hanya
                    memberikan informasi.</p>

                <div id="param-list" class="space-y-3">
                    {{-- Rows added by JS --}}
                </div>

                <button type="button" onclick="addParamRow()"
                    class="mt-3 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs text-slate-300 transition hover:bg-white/10">
                    + Tambah Parameter
                </button>
            </div>

            <div>
                <label for="keywords" class="mb-2 block text-sm text-slate-200">Keywords (comma-separated)</label>
                <p class="mb-2 text-xs text-slate-400">Kata kunci untuk intent matching fallback, pisahkan dengan koma.
                </p>
                <input id="keywords" type="text" name="keywords" value="{{ old('keywords') }}"
                    placeholder="e.g. reset password, resetpass, kata sandi"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
            </div>

            {{-- API Endpoints --}}
            <div class="rounded-2xl border border-white/10 bg-slate-900/30 p-4 space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-white">API Endpoints</h3>
                    <p class="text-xs text-slate-400">Route yang dipanggil ke webhook base URL saat tool dieksekusi.</p>
                </div>

                {{-- GET Endpoint --}}
                <div class="rounded-xl border border-white/10 bg-slate-900/40 p-3 space-y-3">
                    <div class="flex items-center gap-2">
                        <span
                            class="rounded bg-emerald-500/20 px-2 py-0.5 text-[10px] font-bold text-emerald-300">GET</span>
                        <label for="endpoint_get_route" class="text-sm text-slate-200">Route</label>
                    </div>
                    <input id="endpoint_get_route" type="text" name="endpoint_get_route"
                        value="{{ old('endpoint_get_route') }}" placeholder="e.g. /getplayer"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none transition focus:border-cyan-400" />
                    <div>
                        <p class="mb-2 text-xs text-slate-400">Body fields (key → value). Kosongkan value jika diisi dari
                            parameter customer.</p>
                        <div id="get-body-list" class="space-y-2"></div>
                        <div class="flex items-center gap-2 mt-2">
                            <button type="button" onclick="addGetBodyField()"
                                class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-white/10">
                                + Tambah Field
                            </button>
                            <button type="button" onclick="testEndpoint('get')"
                                class="rounded-lg border border-emerald-400/30 bg-emerald-500/10 px-3 py-1.5 text-xs text-emerald-300 transition hover:bg-emerald-500/20">
                                ▶ Test Request
                            </button>
                        </div>
                        <div id="get-test-result" class="mt-2 hidden rounded-lg border border-white/10 bg-slate-950/60 p-3">
                            <div class="flex items-center justify-between mb-1">
                                <span id="get-test-status" class="text-xs font-mono"></span>
                                <button type="button"
                                    onclick="document.getElementById('get-test-result').classList.add('hidden')"
                                    class="text-xs text-slate-500 hover:text-slate-300">&times;</button>
                            </div>
                            <pre id="get-test-body" class="text-xs text-slate-300 whitespace-pre-wrap max-h-48 overflow-auto"></pre>
                        </div>
                    </div>
                </div>

                {{-- UPDATE Endpoint --}}
                <div class="rounded-xl border border-white/10 bg-slate-900/40 p-3 space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="rounded bg-amber-500/20 px-2 py-0.5 text-[10px] font-bold text-amber-300">UPDATE</span>
                        <label for="endpoint_update_route" class="text-sm text-slate-200">Route</label>
                    </div>
                    <input id="endpoint_update_route" type="text" name="endpoint_update_route"
                        value="{{ old('endpoint_update_route') }}" placeholder="e.g. /updateplayer"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none transition focus:border-cyan-400" />
                    <div>
                        <p class="mb-2 text-xs text-slate-400">Body fields (key → value). Kosongkan value jika diisi dari
                            parameter customer.</p>
                        <div id="update-body-list" class="space-y-2"></div>
                        <div class="flex items-center gap-2 mt-2">
                            <button type="button" onclick="addUpdateBodyField()"
                                class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-white/10">
                                + Tambah Field
                            </button>
                            <button type="button" onclick="testEndpoint('update')"
                                class="rounded-lg border border-amber-400/30 bg-amber-500/10 px-3 py-1.5 text-xs text-amber-300 transition hover:bg-amber-500/20">
                                ▶ Test Request
                            </button>
                        </div>
                        <div id="update-test-result"
                            class="mt-2 hidden rounded-lg border border-white/10 bg-slate-950/60 p-3">
                            <div class="flex items-center justify-between mb-1">
                                <span id="update-test-status" class="text-xs font-mono"></span>
                                <button type="button"
                                    onclick="document.getElementById('update-test-result').classList.add('hidden')"
                                    class="text-xs text-slate-500 hover:text-slate-300">&times;</button>
                            </div>
                            <pre id="update-test-body" class="text-xs text-slate-300 whitespace-pre-wrap max-h-48 overflow-auto"></pre>
                        </div>
                    </div>
                </div>
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
                <label for="information_text" class="mb-2 block text-sm text-slate-200">Information Text</label>
                <p class="mb-2 text-xs text-slate-400">Teks informasi yang langsung dikirim sebagai jawaban. Cocok untuk
                    tool yang hanya memberikan info tanpa perlu eksekusi.</p>
                <textarea id="information_text" name="information_text" rows="4"
                    placeholder="e.g. Untuk deposit, silakan transfer ke rekening BCA 1234567890 a/n PT XYZ."
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">{{ old('information_text') }}</textarea>
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
                    <input type="checkbox" name="is_enabled" value="1"
                        {{ old('is_enabled', true) ? 'checked' : '' }}
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

@section('scripts')
    <script>
        let paramIndex = 0;

        function addParamRow(name = '', desc = '', required = false) {
            const list = document.getElementById('param-list');
            const row = document.createElement('div');
            row.className = 'flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-900/50 p-3';
            row.innerHTML = `
                    <input type="text" name="params[${paramIndex}][name]" value="${name}" placeholder="Nama field (e.g. username)"
                        class="w-1/3 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                    <input type="text" name="params[${paramIndex}][description]" value="${desc}" placeholder="Deskripsi (e.g. Username akun)"
                        class="flex-1 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
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

        let getBodyIdx = 0;

        function addGetBodyField(key = '', val = '') {
            const list = document.getElementById('get-body-list');
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2';
            row.innerHTML = `
                <input type="text" name="endpoint_get_body[${getBodyIdx}][key]" value="${key}" placeholder="Key (e.g. username)"
                    class="w-2/5 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                <input type="text" name="endpoint_get_body[${getBodyIdx}][value]" value="${val}" placeholder="Value (kosong = dari parameter)"
                    class="flex-1 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                <button type="button" onclick="this.parentElement.remove()"
                    class="shrink-0 rounded-lg border border-red-400/20 bg-red-500/10 px-2 py-1.5 text-xs text-red-300 hover:bg-red-500/20">&times;</button>
            `;
            list.appendChild(row);
            getBodyIdx++;
        }

        let updateBodyIdx = 0;

        function addUpdateBodyField(key = '', val = '') {
            const list = document.getElementById('update-body-list');
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2';
            row.innerHTML = `
                <input type="text" name="endpoint_update_body[${updateBodyIdx}][key]" value="${key}" placeholder="Key (e.g. username)"
                    class="w-2/5 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                <input type="text" name="endpoint_update_body[${updateBodyIdx}][value]" value="${val}" placeholder="Value (kosong = dari parameter)"
                    class="flex-1 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                <button type="button" onclick="this.parentElement.remove()"
                    class="shrink-0 rounded-lg border border-red-400/20 bg-red-500/10 px-2 py-1.5 text-xs text-red-300 hover:bg-red-500/20">&times;</button>
            `;
            list.appendChild(row);
            updateBodyIdx++;
        }

        async function testEndpoint(type) {
            const routeInput = document.getElementById(`endpoint_${type}_route`);
            const route = routeInput ? routeInput.value.trim() : '';
            if (!route) {
                alert('Route belum diisi.');
                return;
            }

            const bodyList = document.getElementById(`${type}-body-list`);
            const rows = bodyList.querySelectorAll(':scope > div');
            const body = {};
            rows.forEach(row => {
                const inputs = row.querySelectorAll('input[type=text]');
                const k = inputs[0]?.value.trim();
                const v = inputs[1]?.value.trim();
                if (k) body[k] = v;
            });

            const resultEl = document.getElementById(`${type}-test-result`);
            const statusEl = document.getElementById(`${type}-test-status`);
            const bodyEl = document.getElementById(`${type}-test-body`);

            resultEl.classList.remove('hidden');
            statusEl.textContent = 'Loading...';
            statusEl.className = 'text-xs font-mono text-slate-400';
            bodyEl.textContent = '';

            try {
                const res = await fetch('{{ route('backoffice.tools.testEndpoint') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        route,
                        body
                    }),
                });
                const data = await res.json();
                statusEl.textContent = data.success ? `✓ HTTP ${data.status}` :
                    `✗ ${data.error || 'HTTP ' + data.status}`;
                statusEl.className = `text-xs font-mono ${data.success ? 'text-emerald-400' : 'text-red-400'}`;
                bodyEl.textContent = typeof data.response === 'object' ? JSON.stringify(data.response, null, 2) : (data
                    .response || data.error || '');
            } catch (e) {
                statusEl.textContent = '✗ Network error';
                statusEl.className = 'text-xs font-mono text-red-400';
                bodyEl.textContent = e.message;
            }
        }
    </script>
@endsection
@endsection
