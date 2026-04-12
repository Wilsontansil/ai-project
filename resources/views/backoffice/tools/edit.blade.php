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
                    <p class="mb-2 block text-sm text-slate-200">Tool Name (key)</p>
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
                <label for="data_model_id" class="mb-2 block text-sm text-slate-200">Data Model Connection</label>
                <p class="mb-2 text-xs text-slate-400">Pilih Data Model untuk tool action. Boleh kosong untuk
                    information-only tool.</p>
                <select id="data_model_id" name="data_model_id"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">
                    <option value="">-- No Data Model (Information-only) --</option>
                    @foreach ($dataModels as $dm)
                        <option value="{{ $dm->id }}"
                            {{ (string) old('data_model_id', $tool->data_model_id) === (string) $dm->id ? 'selected' : '' }}>
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
                        value="{{ old('endpoint_get_route', $tool->endpoints['get']['route'] ?? '') }}"
                        placeholder="e.g. /getplayer"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none transition focus:border-cyan-400" />
                    <div>
                        <p class="mb-2 text-xs text-slate-300">Expected Response</p>
                        <div class="grid gap-2 md:grid-cols-2">
                            <input type="number" name="endpoint_get_expected_status"
                                value="{{ old('endpoint_get_expected_status', data_get($tool->endpoints, 'get.expected_response.status', 200)) }}"
                                placeholder="Status (e.g. 200)"
                                class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-xs text-white outline-none focus:border-cyan-400" />
                            <input type="text" name="endpoint_get_expected_message"
                                value="{{ old('endpoint_get_expected_message', data_get($tool->endpoints, 'get.expected_response.message', 'Success')) }}"
                                placeholder="Message (e.g. Success)"
                                class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-xs text-white outline-none focus:border-cyan-400" />
                        </div>
                        <p class="mt-2 mb-2 text-xs text-slate-400">Data fields (key → value). Hanya key+value non-empty
                            yang disimpan.</p>
                        <div id="get-expected-data-list" class="space-y-2"></div>
                        <button type="button" onclick="addGetExpectedDataField()"
                            class="mt-2 rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-white/10">
                            + Tambah Expected Data
                        </button>
                    </div>
                    <div>
                        <p class="mb-2 text-xs text-slate-400">Body fields (key → value). Kosongkan value jika diisi dari
                            parameter customer.</p>
                        <div id="get-body-list" class="space-y-2"></div>
                        <div class="flex items-center gap-2 mt-2">
                            <button type="button" onclick="addGetBodyField()"
                                class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-white/10">
                                + Tambah Field
                            </button>
                            <button type="button" onclick="copyParamsToEndpointBody('get')"
                                class="rounded-lg border border-cyan-400/30 bg-cyan-500/10 px-3 py-1.5 text-xs text-cyan-300 transition hover:bg-cyan-500/20">
                                Copy from Parameters
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
                        <span
                            class="rounded bg-amber-500/20 px-2 py-0.5 text-[10px] font-bold text-amber-300">UPDATE</span>
                        <label for="endpoint_update_route" class="text-sm text-slate-200">Route</label>
                    </div>
                    <input id="endpoint_update_route" type="text" name="endpoint_update_route"
                        value="{{ old('endpoint_update_route', $tool->endpoints['update']['route'] ?? '') }}"
                        placeholder="e.g. /updateplayer"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none transition focus:border-cyan-400" />
                    <div>
                        <p class="mb-2 text-xs text-slate-300">Expected Response</p>
                        <div class="grid gap-2 md:grid-cols-2">
                            <input type="number" name="endpoint_update_expected_status"
                                value="{{ old('endpoint_update_expected_status', data_get($tool->endpoints, 'update.expected_response.status', 200)) }}"
                                placeholder="Status (e.g. 200)"
                                class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-xs text-white outline-none focus:border-cyan-400" />
                            <input type="text" name="endpoint_update_expected_message"
                                value="{{ old('endpoint_update_expected_message', data_get($tool->endpoints, 'update.expected_response.message', 'Success')) }}"
                                placeholder="Message (e.g. Success)"
                                class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-xs text-white outline-none focus:border-cyan-400" />
                        </div>
                        <p class="mt-2 mb-2 text-xs text-slate-400">Data fields (key → value). Hanya key+value non-empty
                            yang disimpan.</p>
                        <div id="update-expected-data-list" class="space-y-2"></div>
                        <button type="button" onclick="addUpdateExpectedDataField()"
                            class="mt-2 rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-white/10">
                            + Tambah Expected Data
                        </button>
                    </div>
                    <div>
                        <p class="mb-2 text-xs text-slate-400">Body fields (key → value). Kosongkan value jika diisi dari
                            parameter customer.</p>
                        <div id="update-body-list" class="space-y-2"></div>
                        <div class="flex items-center gap-2 mt-2">
                            <button type="button" onclick="addUpdateBodyField()"
                                class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-white/10">
                                + Tambah Field
                            </button>
                            <button type="button" onclick="copyParamsToEndpointBody('update')"
                                class="rounded-lg border border-cyan-400/30 bg-cyan-500/10 px-3 py-1.5 text-xs text-cyan-300 transition hover:bg-cyan-500/20">
                                Copy from Parameters
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
                <p class="mb-2 text-xs text-slate-400">Pesan yang ditampilkan jika data belum lengkap.</p>
                <textarea id="missing_message" name="missing_message" rows="3"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">{{ old('missing_message', $tool->missing_message) }}</textarea>
            </div>

            <div>
                <label for="information_text" class="mb-2 block text-sm text-slate-200">Information Text</label>
                <p class="mb-2 text-xs text-slate-400">Teks informasi yang langsung dikirim sebagai jawaban. Cocok untuk
                    tool yang hanya memberikan info.</p>
                <textarea id="information_text" name="information_text" rows="4"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">{{ old('information_text', $tool->information_text) }}</textarea>
            </div>

            <div>
                <label for="icon" class="mb-2 block text-sm text-slate-200">SVG Icon Path</label>
                <input id="icon" type="text" name="icon"
                    value="{{ old('icon', $tool->meta['icon'] ?? '') }}"
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

        function copyParamsToEndpointBody(type) {
            const names = getCurrentParameterNames();
            const list = document.getElementById(`${type}-body-list`);
            if (!list) {
                return;
            }

            list.innerHTML = '';
            if (type === 'get') {
                getBodyIdx = 0;
                names.forEach(name => addGetBodyField(name, ''));
            } else {
                updateBodyIdx = 0;
                names.forEach(name => addUpdateBodyField(name, ''));
            }
        }

        // Pre-populate existing parameters
        document.addEventListener('DOMContentLoaded', function() {
            const existing = @json($tool->parameters ?? []);
            const properties = existing.properties || {};
            const required = existing.required || [];

            for (const [name, prop] of Object.entries(properties)) {
                addParamRow(name, prop.description || '', required.includes(name));
            }

            // Pre-populate endpoint body fields (key-value)
            const endpoints = @json($tool->endpoints ?? []);
            if (endpoints.get && endpoints.get.body) {
                for (const [k, v] of Object.entries(endpoints.get.body)) {
                    addGetBodyField(k, v);
                }
            }
            if (endpoints.update && endpoints.update.body) {
                for (const [k, v] of Object.entries(endpoints.update.body)) {
                    addUpdateBodyField(k, v);
                }
            }

            const getExpectedData = (endpoints.get && endpoints.get.expected_response && endpoints.get
                .expected_response.data) ? endpoints.get.expected_response.data : {};
            for (const [k, v] of Object.entries(getExpectedData)) {
                addGetExpectedDataField(k, v);
            }

            const updateExpectedData = (endpoints.update && endpoints.update.expected_response && endpoints.update
                .expected_response.data) ? endpoints.update.expected_response.data : {};
            for (const [k, v] of Object.entries(updateExpectedData)) {
                addUpdateExpectedDataField(k, v);
            }

            refreshParameterFieldOptions();
        });

        document.getElementById('data_model_id')?.addEventListener('change', refreshParameterFieldOptions);

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

        let getExpectedDataIdx = 0;

        function addGetExpectedDataField(key = '', val = '') {
            const list = document.getElementById('get-expected-data-list');
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2';
            row.innerHTML = `
                <input type="text" name="endpoint_get_expected_data[${getExpectedDataIdx}][key]" value="${key}" placeholder="Key (e.g. username)"
                    class="w-2/5 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                <input type="text" name="endpoint_get_expected_data[${getExpectedDataIdx}][value]" value="${val}" placeholder="Value (e.g. john_doe)"
                    class="flex-1 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                <button type="button" onclick="this.parentElement.remove()"
                    class="shrink-0 rounded-lg border border-red-400/20 bg-red-500/10 px-2 py-1.5 text-xs text-red-300 hover:bg-red-500/20">&times;</button>
            `;
            list.appendChild(row);
            getExpectedDataIdx++;
        }

        let updateExpectedDataIdx = 0;

        function addUpdateExpectedDataField(key = '', val = '') {
            const list = document.getElementById('update-expected-data-list');
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2';
            row.innerHTML = `
                <input type="text" name="endpoint_update_expected_data[${updateExpectedDataIdx}][key]" value="${key}" placeholder="Key (e.g. username)"
                    class="w-2/5 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                <input type="text" name="endpoint_update_expected_data[${updateExpectedDataIdx}][value]" value="${val}" placeholder="Value (e.g. john_doe)"
                    class="flex-1 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                <button type="button" onclick="this.parentElement.remove()"
                    class="shrink-0 rounded-lg border border-red-400/20 bg-red-500/10 px-2 py-1.5 text-xs text-red-300 hover:bg-red-500/20">&times;</button>
            `;
            list.appendChild(row);
            updateExpectedDataIdx++;
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
