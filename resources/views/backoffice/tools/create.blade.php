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
                <label for="data_model_id" class="mb-2 block text-sm text-slate-200">Data Model Connection</label>
                <p class="mb-2 text-xs text-slate-400">Pilih Data Model untuk tool action (register/suspend/change
                    password). Boleh kosong untuk tool information-only seperti game_gacor/pola_gacor.</p>
                <select id="data_model_id" name="data_model_id"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">
                    <option value="">-- No Data Model (Information-only) --</option>
                    @foreach ($dataModels as $dm)
                        <option value="{{ $dm->id }}"
                            {{ (string) old('data_model_id') === (string) $dm->id ? 'selected' : '' }}>
                            {{ $dm->model_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <p class="mb-2 block text-sm text-slate-200">Parameters</p>
                <p class="mb-2 text-xs text-slate-400">Parameter hanya boleh menggunakan field dari Data Model yang dipilih.
                </p>

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

            {{-- API Endpoint --}}
            <div class="rounded-2xl border border-white/10 bg-slate-900/30 p-4 space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-white">API Endpoint</h3>
                    <p class="text-xs text-slate-400">Route yang dipanggil ke webhook base URL saat tool dieksekusi.</p>
                </div>

                <div class="rounded-xl border border-white/10 bg-slate-900/40 p-3 space-y-3">
                    {{-- Route --}}
                    <div>
                        <label for="endpoint_route" class="mb-1 block text-xs text-slate-300">Route</label>
                        <input id="endpoint_route" type="text" name="endpoint_route" value="{{ old('endpoint_route') }}"
                            placeholder="e.g. /getplayer"
                            class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none transition focus:border-cyan-400" />
                    </div>

                    {{-- Body --}}
                    <div>
                        <p class="mb-1 text-xs text-slate-300">Body</p>
                        <p class="mb-2 text-xs text-slate-400">Key → value pairs. Value bisa custom text, kosong (ambil dari
                            parameter dengan key yang sama), atau token field DataModel (contoh: $player->id).</p>
                        <div id="body-list" class="space-y-2"></div>
                        <div class="flex items-center gap-2 mt-2">
                            <button type="button" onclick="addBodyField()"
                                class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-white/10">
                                + Tambah Field
                            </button>
                            <button type="button" onclick="copyParamsToBody()"
                                class="rounded-lg border border-cyan-400/30 bg-cyan-500/10 px-3 py-1.5 text-xs text-cyan-300 transition hover:bg-cyan-500/20">
                                Copy from Parameters
                            </button>
                            <button type="button" onclick="testEndpoint()"
                                class="rounded-lg border border-emerald-400/30 bg-emerald-500/10 px-3 py-1.5 text-xs text-emerald-300 transition hover:bg-emerald-500/20">
                                ▶ Test Request
                            </button>
                        </div>
                        <div id="endpoint-test-result"
                            class="mt-2 hidden rounded-lg border border-white/10 bg-slate-950/60 p-3">
                            <div class="flex items-center justify-between mb-1">
                                <span id="endpoint-test-status" class="text-xs font-mono"></span>
                                <button type="button"
                                    onclick="document.getElementById('endpoint-test-result').classList.add('hidden')"
                                    class="text-xs text-slate-500 hover:text-slate-300">&times;</button>
                            </div>
                            <pre id="endpoint-test-body" class="text-xs text-slate-300 whitespace-pre-wrap max-h-48 overflow-auto"></pre>
                        </div>
                    </div>

                    {{-- Expected Response --}}
                    <div>
                        <p class="mb-2 text-xs text-slate-300">Expected Response</p>
                        <div class="rounded-lg border border-white/10 bg-slate-950/60 p-3 mb-3">
                            <pre id="expected-response-preview"
                                class="text-xs text-slate-300 whitespace-pre-wrap font-mono overflow-auto max-h-64">{
  "status": 200,
  "message": "Success",
  "data": {}
}</pre>
                        </div>

                        <p class="mb-2 text-xs text-slate-400">Add expected data fields (key → value):</p>
                        <div id="expected-data-list" class="space-y-2 mb-2"></div>
                        <button type="button" onclick="addExpectedDataField()"
                            class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-white/10">
                            + Tambah Expected Data
                        </button>

                        <div class="hidden">
                            <input type="hidden" name="endpoint_expected_status"
                                value="{{ old('endpoint_expected_status', 200) }}" />
                            <input type="hidden" name="endpoint_expected_message"
                                value="{{ old('endpoint_expected_message', 'Success') }}" />
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
                <label class="mb-2 block text-sm text-slate-200">Information Texts</label>
                <p class="mb-2 text-xs text-slate-400">Teks informasi yang langsung dikirim sebagai jawaban. Tambahkan
                    beberapa variasi agar bot tidak monoton. Bot akan memilih salah satu secara acak.</p>
                <div id="info-texts-wrapper" class="space-y-2">
                    @if (old('information_texts'))
                        @foreach (old('information_texts') as $i => $text)
                            <div class="info-text-row flex gap-2">
                                <textarea name="information_texts[]" rows="3"
                                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400"
                                    placeholder="Teks informasi...">{{ $text }}</textarea>
                                <button type="button" onclick="this.closest('.info-text-row').remove()"
                                    class="shrink-0 rounded-xl border border-red-400/20 bg-red-500/10 px-3 py-1 text-xs text-red-300 hover:bg-red-500/20">✕</button>
                            </div>
                        @endforeach
                    @else
                        <div class="info-text-row flex gap-2">
                            <textarea name="information_texts[]" rows="3"
                                class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400"
                                placeholder="Teks informasi..."></textarea>
                            <button type="button" onclick="this.closest('.info-text-row').remove()"
                                class="shrink-0 rounded-xl border border-red-400/20 bg-red-500/10 px-3 py-1 text-xs text-red-300 hover:bg-red-500/20">✕</button>
                        </div>
                    @endif
                </div>
                <button type="button" onclick="addInfoText()"
                    class="mt-2 rounded-xl border border-cyan-400/30 bg-cyan-400/10 px-3 py-1.5 text-xs text-cyan-300 transition hover:bg-cyan-400/20">
                    + Add Text
                </button>
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
                <button type="submit" onclick="return validateForm()"
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
        const dataModels = @json($dataModels->map(fn($dm) => ['id' => $dm->id, 'fields' => array_keys($dm->fields ?? [])])->values());
        let paramIndex = 0;

        function getSelectedDataModelFields() {
            const selectedId = document.getElementById('data_model_id')?.value || '';
            const model = dataModels.find(m => String(m.id) === String(selectedId));
            return model ? model.fields : [];
        }

        function buildFieldOptions(selected = '') {
            const fields = getSelectedDataModelFields();
            let html = '<option value="">-- pilih field --</option>';
            fields.forEach(field => {
                const isSelected = String(field) === String(selected) ? 'selected' : '';
                html += `<option value="${field}" ${isSelected}>${field}</option>`;
            });
            return html;
        }

        function refreshParameterFieldOptions() {
            const selects = document.querySelectorAll('.param-name-select');
            selects.forEach(select => {
                const current = select.value;
                select.innerHTML = buildFieldOptions(current);
                const stillExists = Array.from(select.options).some(opt => opt.value === current);
                if (!stillExists) {
                    select.value = '';
                }
            });

            refreshBodyFieldOptions();
        }

        function buildBodyModelFieldOptions(selected = '') {
            const fields = getSelectedDataModelFields();
            let html = '<option value="">-- pilih field DataModel --</option>';
            fields.forEach(field => {
                const isSelected = String(field) === String(selected) ? 'selected' : '';
                html += `<option value="${field}" ${isSelected}>${field}</option>`;
            });
            return html;
        }

        function refreshBodyFieldOptions() {
            const selects = document.querySelectorAll('.body-model-field-select');
            selects.forEach(select => {
                const current = select.value;
                select.innerHTML = buildBodyModelFieldOptions(current);
                const stillExists = Array.from(select.options).some(opt => opt.value === current);
                if (!stillExists) {
                    select.value = '';
                }
            });
        }

        function applyModelFieldToBodyValue(button) {
            const row = button.closest('.endpoint-body-row');
            if (!row) {
                return;
            }

            const fieldSelect = row.querySelector('.body-model-field-select');
            const valueInput = row.querySelector('.body-value-input');
            const selectedField = fieldSelect?.value || '';

            if (!selectedField || !valueInput) {
                return;
            }

            valueInput.value = `$player->${selectedField}`;
        }

        function addParamRow(name = '', desc = '', required = false) {
            const list = document.getElementById('param-list');
            const row = document.createElement('div');
            row.className = 'flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-900/50 p-3';
            row.innerHTML = `
                    <select name="params[${paramIndex}][name]"
                        class="param-name-select w-1/3 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400">
                        ${buildFieldOptions(name)}
                    </select>
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

        function getCurrentParameterNames() {
            const names = [];
            document.querySelectorAll('.param-name-select').forEach(select => {
                const value = (select.value || '').trim();
                if (value !== '' && !names.includes(value)) {
                    names.push(value);
                }
            });
            return names;
        }

        function copyParamsToBody() {
            const names = getCurrentParameterNames();
            const list = document.getElementById('body-list');
            if (!list) return;
            list.innerHTML = '';
            bodyIdx = 0;
            names.forEach(name => addBodyField(name, ''));
        }

        document.getElementById('data_model_id')?.addEventListener('change', refreshParameterFieldOptions);

        document.addEventListener('DOMContentLoaded', function() {
            const oldParams = @json(old('params', []));
            if (Array.isArray(oldParams) && oldParams.length > 0) {
                oldParams.forEach(p => addParamRow(p.name || '', p.description || '', !!p.required));
            }

            const oldBody = @json(old('endpoint_body', []));
            if (Array.isArray(oldBody)) {
                oldBody.forEach(row => addBodyField(row.key || '', row.value || ''));
            }

            const oldExpected = @json(old('endpoint_expected_data', []));
            if (Array.isArray(oldExpected)) {
                oldExpected.forEach(row => addExpectedDataField(row.key || '', row.value || ''));
            }

            refreshParameterFieldOptions();
        });

        let bodyIdx = 0;

        function addBodyField(key = '', val = '') {
            const list = document.getElementById('body-list');
            const row = document.createElement('div');
            row.className = 'endpoint-body-row flex items-center gap-2';
            row.innerHTML = `
                <input type="text" name="endpoint_body[${bodyIdx}][key]" value="${key}" placeholder="Key (e.g. username)"
                    class="w-2/5 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                <input type="text" name="endpoint_body[${bodyIdx}][value]" value="${val}" placeholder="Value custom / kosong / $player->field"
                    class="body-value-input flex-1 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                <select class="body-model-field-select w-48 rounded-xl border border-white/10 bg-slate-900/70 px-2 py-2 text-xs text-white outline-none focus:border-cyan-400">
                    ${buildBodyModelFieldOptions()}
                </select>
                <button type="button" onclick="applyModelFieldToBodyValue(this)"
                    class="shrink-0 rounded-lg border border-cyan-400/20 bg-cyan-500/10 px-2 py-1.5 text-xs text-cyan-300 hover:bg-cyan-500/20">Use Field</button>
                <button type="button" onclick="this.parentElement.remove()"
                    class="shrink-0 rounded-lg border border-red-400/20 bg-red-500/10 px-2 py-1.5 text-xs text-red-300 hover:bg-red-500/20">&times;</button>
            `;
            list.appendChild(row);
            bodyIdx++;
        }

        let expectedDataIdx = 0;

        function updateExpectedResponsePreview() {
            const list = document.getElementById('expected-data-list');
            const rows = list.querySelectorAll(':scope > div');
            const data = {};

            rows.forEach(row => {
                const inputs = row.querySelectorAll('input[type=text]');
                const key = inputs[0]?.value.trim();
                const value = inputs[1]?.value.trim();
                if (key && value) {
                    data[key] = value;
                }
            });

            const preview = {
                status: 200,
                message: "Success",
                data: data
            };

            document.getElementById('expected-response-preview').textContent = JSON.stringify(preview, null, 2);
        }

        function addExpectedDataField(key = '', val = '') {
            const list = document.getElementById('expected-data-list');
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2';
            row.innerHTML = `
                <input type="text" name="endpoint_expected_data[${expectedDataIdx}][key]" value="${key}" placeholder="Key (e.g. username)"
                    class="w-2/5 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400 expected-data-input" />
                <input type="text" name="endpoint_expected_data[${expectedDataIdx}][value]" value="${val}" placeholder="Value (e.g. john_doe)"
                    class="flex-1 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400 expected-data-input" />
                <button type="button" class="shrink-0 rounded-lg border border-red-400/20 bg-red-500/10 px-2 py-1.5 text-xs text-red-300 hover:bg-red-500/20 remove-expected">&times;</button>
            `;

            // Add event listeners for dynamic preview update
            const inputs = row.querySelectorAll('.expected-data-input');
            inputs.forEach(input => {
                input.addEventListener('input', updateExpectedResponsePreview);
            });

            // Add event listener for remove button
            row.querySelector('.remove-expected').addEventListener('click', function() {
                row.remove();
                updateExpectedResponsePreview();
            });

            list.appendChild(row);
            expectedDataIdx++;
            updateExpectedResponsePreview();
        }

        function validateForm() {
            const expectedDataList = document.getElementById('expected-data-list');
            const rows = expectedDataList.querySelectorAll(':scope > div');

            for (let row of rows) {
                const inputs = row.querySelectorAll('input[type=text]');
                const key = inputs[0]?.value.trim();
                const value = inputs[1]?.value.trim();

                if ((key && !value) || (!key && value)) {
                    alert(
                        'Expected data fields must have both KEY and VALUE filled. Please complete all fields or remove empty rows.'
                    );
                    return false;
                }
            }
            return true;
        }

        function addInfoText() {
            const wrapper = document.getElementById('info-texts-wrapper');
            const row = document.createElement('div');
            row.className = 'info-text-row flex gap-2';
            row.innerHTML =
                `<textarea name="information_texts[]" rows="3"
                class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400"
                style="background-color:rgba(15,23,42,0.7);color:#e2e8f0"
                placeholder="Teks informasi..."></textarea>
                <button type="button" onclick="this.closest('.info-text-row').remove()"
                    class="shrink-0 rounded-xl border border-red-400/20 bg-red-500/10 px-3 py-1 text-xs text-red-300 hover:bg-red-500/20">✕</button>`;
            wrapper.appendChild(row);
        }

        async function testEndpoint() {
            const routeInput = document.getElementById('endpoint_route');
            const route = routeInput ? routeInput.value.trim() : '';
            if (!route) {
                alert('Route belum diisi.');
                return;
            }

            const bodyList = document.getElementById('body-list');
            const rows = bodyList.querySelectorAll(':scope > div');
            const body = {};
            rows.forEach(row => {
                const inputs = row.querySelectorAll('input[type=text]');
                const k = inputs[0]?.value.trim();
                const v = inputs[1]?.value.trim();
                if (k) body[k] = v;
            });

            const resultEl = document.getElementById('endpoint-test-result');
            const statusEl = document.getElementById('endpoint-test-status');
            const bodyEl = document.getElementById('endpoint-test-body');

            resultEl.classList.remove('hidden');
            statusEl.textContent = 'Loading...';
            statusEl.className = 'text-xs font-mono text-slate-400';
            bodyEl.textContent = '';

            try {
                const basePath = window.location.pathname.substring(0, window.location.pathname.indexOf(
                    '/backoffice/'));
                const res = await fetch(`${basePath}/backoffice/tools/test-endpoint`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
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
