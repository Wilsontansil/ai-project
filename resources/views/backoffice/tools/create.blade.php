@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.tools.new_tool'))
@section('page-title', __('backoffice.pages.tools.page_title'))

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <h1 class="text-3xl font-semibold">{{ __('backoffice.pages.tools.new_tool') }}</h1>
        <p class="mt-2 text-sm text-slate-300">{{ __('backoffice.pages.tools.add_new_tool') }}</p>
    </div>

    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <form method="POST" action="{{ route('backoffice.tools.store') }}" class="space-y-5">
            @csrf

            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <label for="tool_name"
                        class="mb-2 block text-sm text-slate-200">{{ __('backoffice.pages.tools.tool_name_key') }}</label>
                    <p class="mb-2 text-xs text-slate-400">{{ __('backoffice.pages.tools.tool_name_help') }}</p>
                    <input id="tool_name" type="text" name="tool_name" value="{{ old('tool_name') }}"
                        placeholder="e.g. resetPassword"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
                <div>
                    <label for="display_name"
                        class="mb-2 block text-sm text-slate-200">{{ __('backoffice.pages.tools.display_name') }}</label>
                    <p class="mb-2 text-xs text-slate-400">{{ __('backoffice.pages.tools.display_name_help') }}</p>
                    <input id="display_name" type="text" name="display_name" value="{{ old('display_name') }}"
                        placeholder="e.g. Reset Password"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
                <div>
                    <label for="type"
                        class="mb-2 block text-sm text-slate-200">{{ __('backoffice.pages.tools.type') }}</label>
                    <p class="mb-2 text-xs text-slate-400">{{ __('backoffice.pages.tools.type_help') }}</p>
                    <select id="type" name="type"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">
                        <option value="info" {{ old('type', 'info') === 'info' ? 'selected' : '' }}>
                            {{ __('backoffice.pages.tools.type_info') }}</option>
                        <option value="get" {{ old('type') === 'get' ? 'selected' : '' }}>
                            {{ __('backoffice.pages.tools.type_get') }}</option>
                        <option value="get_multiple" {{ old('type') === 'get_multiple' ? 'selected' : '' }}>
                            {{ __('backoffice.pages.tools.type_get_multiple') }}</option>
                        <option value="update" {{ old('type') === 'update' ? 'selected' : '' }}>
                            {{ __('backoffice.pages.tools.type_update') }}</option>
                    </select>
                </div>
                <div>
                    <label for="category" class="mb-2 block text-sm text-slate-200">Category</label>
                    <p class="mb-2 text-xs text-slate-400">Group this tool for backoffice filtering.</p>
                    <select id="category" name="category"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">
                        <option value="">— None —</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>
                                {{ ucfirst($cat) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="keywords"
                    class="mb-2 block text-sm text-slate-200">{{ __('backoffice.pages.tools.keywords') }}</label>
                <p class="mb-2 text-xs text-slate-400">{{ __('backoffice.pages.tools.keywords_help') }}</p>
                <input id="keywords" type="text" name="keywords" value="{{ old('keywords') }}"
                    placeholder="e.g. reset password, resetpass, kata sandi" maxlength="500"
                    oninput="document.getElementById('kw-count').textContent=this.value.length"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                <p class="mt-1 text-xs text-slate-500"><span id="kw-count">{{ strlen(old('keywords', '')) }}</span>/500
                </p>
            </div>

            <div>
                <label for="description"
                    class="mb-2 block text-sm text-slate-200">{{ __('backoffice.pages.tools.description') }}</label>
                <p class="mb-2 text-xs text-slate-400">{{ __('backoffice.pages.tools.description_help') }}</p>
                <input id="description" type="text" name="description" value="{{ old('description') }}"
                    placeholder="e.g. Reset user password after account data verification" maxlength="100"
                    oninput="document.getElementById('desc-count').textContent=this.value.length"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                <p class="mt-1 text-xs text-slate-500"><span
                        id="desc-count">{{ strlen(old('description', '')) }}</span>/100
                </p>
            </div>

            {{-- ─── GET type: Data Model + Parameters ─── --}}
            <div id="section-get" class="space-y-4" style="display:none">
                <div>
                    <label for="data_model_id"
                        class="mb-2 block text-sm text-slate-200">{{ __('backoffice.pages.tools.data_model_connection') }}</label>
                    <p class="mb-2 text-xs text-slate-400">{{ __('backoffice.pages.tools.choose_data_model') }}</p>
                    <select id="data_model_id" name="data_model_id"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">
                        <option value="">{{ __('backoffice.pages.tools.select_data_model') }}</option>
                        @foreach ($dataModels as $dm)
                            <option value="{{ $dm->id }}"
                                {{ (string) old('data_model_id') === (string) $dm->id ? 'selected' : '' }}>
                                {{ $dm->model_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <p class="mb-2 block text-sm text-slate-200">{{ __('backoffice.pages.tools.parameters') }}</p>
                    <p class="mb-2 text-xs text-slate-400">{{ __('backoffice.pages.tools.parameters_help') }}</p>
                    <div id="param-list" class="space-y-3"></div>
                    <button type="button" onclick="addParamRow()"
                        class="mt-3 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs text-slate-300 transition hover:bg-white/10">
                        {{ __('backoffice.pages.tools.add_parameter') }}
                    </button>
                </div>
            </div>

            {{-- ─── GET MULTIPLE type: Multiple Data Models + Custom Parameters ─── --}}
            <div id="section-get-multiple" class="space-y-4" style="display:none">
                <div>
                    <p class="mb-2 block text-sm text-slate-200">{{ __('backoffice.pages.tools.data_models_label') }}</p>
                    <p class="mb-2 text-xs text-slate-400">{{ __('backoffice.pages.tools.data_models_help') }}</p>
                    <div class="space-y-2">
                        @foreach ($dataModels as $dm)
                            <label
                                class="flex items-center gap-2 rounded-xl border border-white/10 bg-slate-900/50 px-4 py-2 text-sm text-slate-200 cursor-pointer hover:bg-slate-900/70 transition">
                                <input type="checkbox" name="data_model_ids[]" value="{{ $dm->id }}"
                                    {{ in_array((string) $dm->id, old('data_model_ids', []) ?: []) ? 'checked' : '' }}
                                    class="rounded border-white/20 bg-slate-800 text-cyan-400 focus:ring-cyan-400" />
                                {{ $dm->model_name }} <span class="text-xs text-slate-400">({{ $dm->table_name }})</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <p class="mb-2 block text-sm text-slate-200">{{ __('backoffice.pages.tools.parameters') }}</p>
                    <p class="mb-2 text-xs text-slate-400">{{ __('backoffice.pages.tools.free_parameters') }}</p>
                    <div id="getmulti-param-list" class="space-y-3"></div>
                    <button type="button" onclick="addGetMultiParamRow()"
                        class="mt-3 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs text-slate-300 transition hover:bg-white/10">
                        {{ __('backoffice.pages.tools.add_parameter') }}
                    </button>
                </div>
            </div>

            {{-- ─── UPDATE type: Parameters + API Endpoint ─── --}}
            <div id="section-update" class="space-y-4" style="display:none">
                <div>
                    <p class="mb-2 block text-sm text-slate-200">{{ __('backoffice.pages.tools.parameters') }}</p>
                    <p class="mb-2 text-xs text-slate-400">{{ __('backoffice.pages.tools.parameters_help') }}</p>
                    <div id="update-param-list" class="space-y-3"></div>
                    <button type="button" onclick="addUpdateParamRow()"
                        class="mt-3 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs text-slate-300 transition hover:bg-white/10">
                        {{ __('backoffice.pages.tools.add_parameter') }}
                    </button>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-900/30 p-4 space-y-4">
                    <div>
                        <h3 class="text-sm font-semibold text-white">{{ __('backoffice.pages.tools.api_endpoint') }}</h3>
                        <p class="text-xs text-slate-400">{{ __('backoffice.pages.tools.endpoint_help') }}</p>
                    </div>

                    <div class="rounded-xl border border-white/10 bg-slate-900/40 p-3 space-y-3">
                        <div>
                            <label for="endpoint_route"
                                class="mb-1 block text-xs text-slate-300">{{ __('backoffice.pages.tools.route') }}</label>
                            <input id="endpoint_route" type="text" name="endpoint_route"
                                value="{{ old('endpoint_route') }}"
                                placeholder="{{ __('backoffice.pages.tools.route_example') }}"
                                class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none transition focus:border-cyan-400" />
                        </div>

                        <div>
                            <p class="mb-1 text-xs text-slate-300">{{ __('backoffice.pages.tools.body') }}</p>
                            <p class="mb-2 text-xs text-slate-400">{{ __('backoffice.pages.tools.body_help') }}</p>
                            <div id="body-list" class="space-y-2"></div>
                            <div class="flex items-center gap-2 mt-2">
                                <button type="button" onclick="addBodyField()"
                                    class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-white/10">
                                    {{ __('backoffice.pages.tools.add_field') }}
                                </button>
                                <button type="button" onclick="copyParamsToBody()"
                                    class="rounded-lg border border-cyan-400/30 bg-cyan-500/10 px-3 py-1.5 text-xs text-cyan-300 transition hover:bg-cyan-500/20">
                                    {{ __('backoffice.pages.tools.copy_from_parameters') }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <p class="mb-2 text-xs text-slate-300">{{ __('backoffice.pages.tools.expected_response') }}
                            </p>
                            <div class="rounded-lg border border-white/10 bg-slate-950/60 p-3 mb-3">
                                <pre id="expected-response-preview"
                                    class="text-xs text-slate-300 whitespace-pre-wrap font-mono overflow-auto max-h-64">{
  "status": 200,
  "message": "Success",
  "data": {}
}</pre>
                            </div>
                            <p class="mb-2 text-xs text-slate-300">{{ __('backoffice.pages.tools.expected_data_help') }}
                            </p>
                            <div id="expected-data-list" class="space-y-2 mb-2"></div>
                            <button type="button" onclick="addExpectedDataField()"
                                class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-white/10">
                                {{ __('backoffice.pages.tools.add_expected_data') }}
                            </button>
                            <div class="hidden">
                                <input type="hidden" name="endpoint_expected_status"
                                    value="{{ old('endpoint_expected_status', 200) }}" />
                                <input type="hidden" name="endpoint_expected_message"
                                    value="{{ old('endpoint_expected_message', 'Success') }}" />
                            </div>
                        </div>

                        {{-- Expected Error Responses --}}
                        <div>
                            <p class="mb-2 text-xs text-slate-300">{{ __('backoffice.pages.tools.error_responses') }}</p>
                            <p class="mb-2 text-xs text-slate-400">{{ __('backoffice.pages.tools.error_responses_help') }}
                            </p>
                            <div class="rounded-lg border border-white/10 bg-slate-950/60 p-3 mb-3">
                                <pre id="error-response-preview" class="text-xs text-slate-300 whitespace-pre-wrap font-mono overflow-auto max-h-64">[
  {
    "status": 500,
    "message": "error message",
    "data": {}
  }
]</pre>
                            </div>
                            <div id="error-response-list" class="space-y-2 mb-2"></div>
                            <button type="button" onclick="addErrorResponse()"
                                class="rounded-lg border border-amber-400/30 bg-amber-500/10 px-3 py-1.5 text-xs text-amber-300 transition hover:bg-amber-500/20">
                                {{ __('backoffice.pages.tools.add_error_response') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label for="tool_rules"
                    class="mb-2 block text-sm text-slate-200">{{ __('backoffice.pages.tools.tool_rules') }}</label>
                <p class="mb-2 text-xs text-slate-400">{{ __('backoffice.pages.tools.tool_rules_help') }}</p>
                <textarea id="tool_rules" name="tool_rules" rows="4" maxlength="500"
                    placeholder="{{ __('backoffice.pages.tools.tool_rules_placeholder') }}"
                    oninput="document.getElementById('rules-count').textContent=this.value.length"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">{{ old('tool_rules') }}</textarea>
                <p class="mt-1 text-xs text-slate-500"><span
                        id="rules-count">{{ strlen(old('tool_rules', '')) }}</span>/500</p>
            </div>

            {{-- ─── INFO type: Information Texts ─── --}}
            <div id="section-info" class="space-y-4" style="display:none">
                <div>
                    <p class="mb-2 block text-sm text-slate-200">{{ __('backoffice.pages.tools.information_texts') }}</p>
                    <p class="mb-2 text-xs text-slate-400">{{ __('backoffice.pages.tools.information_texts_help') }}</p>
                    <div id="info-texts-wrapper" class="space-y-2">
                        @if (old('information_texts'))
                            @foreach (old('information_texts') as $i => $text)
                                <div class="info-text-row flex gap-2">
                                    <textarea name="information_texts[]" rows="3" maxlength="500"
                                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400"
                                        placeholder="{{ __('backoffice.pages.tools.information_text_placeholder') }}">{{ $text }}</textarea>
                                    <button type="button" onclick="this.closest('.info-text-row').remove()"
                                        class="shrink-0 rounded-xl border border-red-400/20 bg-red-500/10 px-3 py-1 text-xs text-red-300 hover:bg-red-500/20">✕</button>
                                </div>
                            @endforeach
                        @else
                            <div class="info-text-row flex gap-2">
                                <textarea name="information_texts[]" rows="3" maxlength="500"
                                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400"
                                    placeholder="{{ __('backoffice.pages.tools.information_text_placeholder') }}"></textarea>
                                <button type="button" onclick="this.closest('.info-text-row').remove()"
                                    class="shrink-0 rounded-xl border border-red-400/20 bg-red-500/10 px-3 py-1 text-xs text-red-300 hover:bg-red-500/20">✕</button>
                            </div>
                        @endif
                    </div>
                    <button type="button" onclick="addInfoText()"
                        class="mt-2 rounded-xl border border-cyan-400/30 bg-cyan-400/10 px-3 py-1.5 text-xs text-cyan-300 transition hover:bg-cyan-400/20">
                        {{ __('backoffice.pages.tools.add_text') }}
                    </button>
                </div>
            </div>{{-- end #section-info --}}

            <div class="flex items-center gap-4 pt-2">
                <button type="submit" onclick="return validateForm()"
                    class="rounded-2xl bg-cyan-400 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    {{ __('backoffice.pages.tools.add_tool') }}
                </button>
                <a href="{{ route('backoffice.tools.index') }}"
                    class="rounded-2xl border border-white/10 px-6 py-3 text-sm text-slate-300 transition hover:bg-white/5">
                    {{ __('backoffice.common.cancel') }}
                </a>
            </div>
        </form>
    </div>

@section('scripts')
    <script>
        const dataModels = @json($dataModels->map(fn($dm) => ['id' => $dm->id, 'fields' => array_keys($dm->fields ?? [])])->values());
        let paramIndex = 0;
        let updateParamIndex = 0;

        /* ── Type section toggling ── */
        function toggleTypeSections() {
            const type = document.getElementById('type').value;
            document.getElementById('section-info').style.display = type === 'info' ? '' : 'none';
            document.getElementById('section-get').style.display = type === 'get' ? '' : 'none';
            document.getElementById('section-get-multiple').style.display = type === 'get_multiple' ? '' : 'none';
            document.getElementById('section-update').style.display = type === 'update' ? '' : 'none';
        }

        document.getElementById('type').addEventListener('change', toggleTypeSections);

        /* ── Data Model field helpers (GET type) ── */
        function getSelectedDataModelFields() {
            const selectedId = document.getElementById('data_model_id')?.value || '';
            const model = dataModels.find(m => String(m.id) === String(selectedId));
            return model ? model.fields : [];
        }

        function buildFieldOptions(selected = '') {
            const fields = getSelectedDataModelFields();
            let html = '<option value="">{{ __('backoffice.pages.tools.select_field') }}</option>';
            fields.forEach(field => {
                const isSelected = String(field) === String(selected) ? 'selected' : '';
                html += `<option value="${field}" ${isSelected}>${field}</option>`;
            });
            return html;
        }

        function refreshParameterFieldOptions() {
            document.querySelectorAll('#param-list .param-name-select').forEach(select => {
                const current = select.value;
                select.innerHTML = buildFieldOptions(current);
                if (!Array.from(select.options).some(opt => opt.value === current)) select.value = '';
            });
        }

        document.getElementById('data_model_id')?.addEventListener('change', refreshParameterFieldOptions);

        /* ── GET type: parameter rows ── */
        function addParamRow(name = '', desc = '', required = false) {
            const list = document.getElementById('param-list');
            const row = document.createElement('div');
            row.className = 'flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-900/50 p-3';
            row.innerHTML = `
                <div class="w-2/5">
                    <select name="params[${paramIndex}][name]"
                        class="param-name-select w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400">
                        ${buildFieldOptions(name)}
                    </select>
                </div>
                <div class="flex-1">
                    <input type="text" name="params[${paramIndex}][description]" value="${desc}" placeholder="{{ __('backoffice.pages.tools.parameter_description') }}"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                </div>
                <label class="inline-flex items-center gap-1.5 text-xs text-slate-300 whitespace-nowrap cursor-pointer">
                    <input type="checkbox" name="params[${paramIndex}][required]" value="1" ${required ? 'checked' : ''}
                        class="rounded border-white/20 bg-slate-800 text-cyan-400 focus:ring-cyan-400" />
                    {{ __('backoffice.pages.tools.required') }}
                </label>
                <button type="button" onclick="this.closest('div').remove()"
                    class="flex-none rounded-lg border border-red-400/20 bg-red-500/10 px-3 py-2 text-sm text-red-300 hover:bg-red-500/20">&times;</button>
            `;
            list.appendChild(row);
            paramIndex++;
        }

        /* ── UPDATE type: parameter rows (free-text name) ── */
        let getMultiParamIndex = 0;

        function addGetMultiParamRow(name = '', desc = '', required = false) {
            const list = document.getElementById('getmulti-param-list');
            const row = document.createElement('div');
            row.className = 'flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-900/50 p-3';
            row.innerHTML = `
                <div class="w-2/5">
                    <input type="text" name="params[${getMultiParamIndex}][name]" value="${name}" placeholder="{{ __('backoffice.pages.tools.parameter_name') }}"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                </div>
                <div class="flex-1">
                    <input type="text" name="params[${getMultiParamIndex}][description]" value="${desc}" placeholder="{{ __('backoffice.pages.tools.parameter_description') }}"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                </div>
                <label class="inline-flex items-center gap-1.5 text-xs text-slate-300 whitespace-nowrap cursor-pointer">
                    <input type="checkbox" name="params[${getMultiParamIndex}][required]" value="1" ${required ? 'checked' : ''}
                        class="rounded border-white/20 bg-slate-800 text-cyan-400 focus:ring-cyan-400" />
                    {{ __('backoffice.pages.tools.required') }}
                </label>
                <button type="button" onclick="this.closest('div').remove()"
                    class="flex-none rounded-lg border border-red-400/20 bg-red-500/10 px-3 py-2 text-sm text-red-300 hover:bg-red-500/20">&times;</button>
            `;
            list.appendChild(row);
            getMultiParamIndex++;
        }

        function addUpdateParamRow(name = '', desc = '', required = false) {
            const list = document.getElementById('update-param-list');
            const row = document.createElement('div');
            row.className = 'flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-900/50 p-3';
            row.innerHTML = `
                <div class="w-2/5">
                    <input type="text" name="params[${updateParamIndex}][name]" value="${name}" placeholder="{{ __('backoffice.pages.tools.parameter_name') }}"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                </div>
                <div class="flex-1">
                    <input type="text" name="params[${updateParamIndex}][description]" value="${desc}" placeholder="{{ __('backoffice.pages.tools.parameter_description') }}"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                </div>
                <label class="inline-flex items-center gap-1.5 text-xs text-slate-300 whitespace-nowrap cursor-pointer">
                    <input type="checkbox" name="params[${updateParamIndex}][required]" value="1" ${required ? 'checked' : ''}
                        class="rounded border-white/20 bg-slate-800 text-cyan-400 focus:ring-cyan-400" />
                    {{ __('backoffice.pages.tools.required') }}
                </label>
                <button type="button" onclick="this.closest('div').remove()"
                    class="flex-none rounded-lg border border-red-400/20 bg-red-500/10 px-3 py-2 text-sm text-red-300 hover:bg-red-500/20">&times;</button>
            `;
            list.appendChild(row);
            updateParamIndex++;
        }

        /* ── Endpoint body helpers (UPDATE type) ── */
        function getCurrentParameterNames() {
            const names = [];
            document.querySelectorAll('#update-param-list input[name$="[name]"]').forEach(input => {
                const value = (input.value || '').trim();
                if (value && !names.includes(value)) names.push(value);
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

        let bodyIdx = 0;

        function addBodyField(key = '', val = '') {
            const list = document.getElementById('body-list');
            const row = document.createElement('div');
            row.className = 'endpoint-body-row flex items-center gap-2';
            row.innerHTML = `
                <div class="w-2/5">
                    <input type="text" name="endpoint_body[${bodyIdx}][key]" value="${key}" placeholder="{{ __('backoffice.pages.tools.key_placeholder') }}"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                </div>
                <div class="flex-1">
                    <input type="text" name="endpoint_body[${bodyIdx}][value]" value="${val}" placeholder="{{ __('backoffice.pages.tools.value_placeholder') }}"
                        class="body-value-input w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                </div>
                <button type="button" onclick="this.closest('.endpoint-body-row').remove()"
                    class="flex-none rounded-lg border border-red-400/20 bg-red-500/10 px-3 py-2 text-sm text-red-300 hover:bg-red-500/20">&times;</button>
            `;
            list.appendChild(row);
            bodyIdx++;
        }

        /* ── Expected response helpers ── */
        let expectedDataIdx = 0;

        function updateExpectedResponsePreview() {
            const list = document.getElementById('expected-data-list');
            const rows = list.querySelectorAll(':scope > div');
            const data = {};
            rows.forEach(row => {
                const inputs = row.querySelectorAll('input[type=text]');
                const key = inputs[0]?.value.trim();
                const value = inputs[1]?.value.trim();
                if (key && value) data[key] = value;
            });
            document.getElementById('expected-response-preview').textContent = JSON.stringify({
                status: 200,
                message: "Success",
                data
            }, null, 2);
        }

        function addExpectedDataField(key = '', val = '') {
            const list = document.getElementById('expected-data-list');
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2';
            row.innerHTML = `
                <div class="w-2/5">
                    <input type="text" name="endpoint_expected_data[${expectedDataIdx}][key]" value="${key}" placeholder="{{ __('backoffice.pages.tools.key_placeholder') }}"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400 expected-data-input" />
                </div>
                <div class="flex-1">
                    <input type="text" name="endpoint_expected_data[${expectedDataIdx}][value]" value="${val}" placeholder="{{ __('backoffice.pages.tools.value_data_placeholder') }}"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400 expected-data-input" />
                </div>
                <button type="button" class="flex-none rounded-lg border border-red-400/20 bg-red-500/10 px-3 py-2 text-sm text-red-300 hover:bg-red-500/20 remove-expected">&times;</button>
            `;
            row.querySelectorAll('.expected-data-input').forEach(input => input.addEventListener('input',
                updateExpectedResponsePreview));
            row.querySelector('.remove-expected').addEventListener('click', function() {
                row.remove();
                updateExpectedResponsePreview();
            });
            list.appendChild(row);
            expectedDataIdx++;
            updateExpectedResponsePreview();
        }

        /* ── Error response helpers ── */
        let errorResponseIdx = 0;

        function updateErrorResponsePreview() {
            const list = document.getElementById('error-response-list');
            const rows = list.querySelectorAll(':scope > .error-response-row');
            const errors = [];
            rows.forEach(row => {
                const status = row.querySelector('.err-status')?.value.trim();
                const message = row.querySelector('.err-message')?.value.trim();
                if (status && message) {
                    errors.push({
                        status: parseInt(status) || 0,
                        message,
                        data: {}
                    });
                }
            });
            document.getElementById('error-response-preview').textContent =
                errors.length > 0 ? JSON.stringify(errors, null, 2) : '[]';
        }

        function addErrorResponse(status = '', message = '') {
            const list = document.getElementById('error-response-list');
            const row = document.createElement('div');
            row.className = 'error-response-row flex items-center gap-2';
            row.innerHTML = `
                <div class="w-24">
                    <input type="number" name="error_responses[${errorResponseIdx}][status]" value="${status}" placeholder="{{ __('backoffice.pages.tools.status_placeholder') }}"
                        class="err-status w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-amber-400" />
                </div>
                <div class="flex-1">
                    <input type="text" name="error_responses[${errorResponseIdx}][message]" value="${message}" placeholder="{{ __('backoffice.pages.tools.message_placeholder') }}"
                        class="err-message w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-amber-400" />
                </div>
                <button type="button" class="flex-none rounded-lg border border-red-400/20 bg-red-500/10 px-3 py-2 text-sm text-red-300 hover:bg-red-500/20 remove-error">&times;</button>
            `;
            row.querySelectorAll('input').forEach(input => input.addEventListener('input', updateErrorResponsePreview));
            row.querySelector('.remove-error').addEventListener('click', function() {
                row.remove();
                updateErrorResponsePreview();
            });
            list.appendChild(row);
            errorResponseIdx++;
            updateErrorResponsePreview();
        }

        /* ── Form init ── */
        document.addEventListener('DOMContentLoaded', function() {
            toggleTypeSections();

            const oldParams = @json(old('params', []));
            const type = document.getElementById('type').value;
            if (Array.isArray(oldParams) && oldParams.length > 0) {
                oldParams.forEach(p => {
                    if (type === 'update') {
                        addUpdateParamRow(p.name || '', p.description || '', !!p.required);
                    } else if (type === 'get_multiple') {
                        addGetMultiParamRow(p.name || '', p.description || '', !!p.required);
                    } else {
                        addParamRow(p.name || '', p.description || '', !!p.required);
                    }
                });
            }

            const oldBody = @json(old('endpoint_body', []));
            if (Array.isArray(oldBody)) oldBody.forEach(row => addBodyField(row.key || '', row.value || ''));

            const oldExpected = @json(old('endpoint_expected_data', []));
            if (Array.isArray(oldExpected)) oldExpected.forEach(row => addExpectedDataField(row.key || '', row
                .value || ''));

            const oldErrors = @json(old('error_responses', []));
            if (Array.isArray(oldErrors) && oldErrors.length > 0) {
                oldErrors.forEach(row => addErrorResponse(row.status || '', row.message || ''));
            } else {
                addErrorResponse(500, 'error message');
            }

            refreshParameterFieldOptions();
        });

        /* ── Validation ── */
        function validateForm() {
            const type = document.getElementById('type').value;
            if (type === 'update') {
                const rows = document.getElementById('expected-data-list').querySelectorAll(':scope > div');
                for (let row of rows) {
                    const inputs = row.querySelectorAll('input[type=text]');
                    const key = inputs[0]?.value.trim();
                    const value = inputs[1]?.value.trim();
                    if ((key && !value) || (!key && value)) {
                        alert('{{ __('backoffice.pages.tools.form_validation_error') }}');
                        return false;
                    }
                }
            }
            return true;
        }

        function addInfoText() {
            const wrapper = document.getElementById('info-texts-wrapper');
            const row = document.createElement('div');
            row.className = 'info-text-row flex gap-2';
            row.innerHTML =
                `<textarea name="information_texts[]" rows="3" maxlength="500"
                class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400"
                style="background-color:rgba(15,23,42,0.7);color:#e2e8f0" placeholder="{{ __('backoffice.pages.tools.information_text_placeholder') }}"></textarea>
                <button type="button" onclick="this.closest('.info-text-row').remove()"
                    class="shrink-0 rounded-xl border border-red-400/20 bg-red-500/10 px-3 py-1 text-xs text-red-300 hover:bg-red-500/20">✕</button>
            `;
            wrapper.appendChild(row);
        }
        placeholder = "Teks informasi..." > < /textarea> <
        button type = "button"
        onclick = "this.closest('.info-text-row').remove()"
        class =
        "shrink-0 rounded-xl border border-red-400/20 bg-red-500/10 px-3 py-1 text-xs text-red-300 hover:bg-red-500/20" > ✕
        <
        /button>`;
        wrapper.appendChild(row);
        }
    </script>
@endsection
@endsection
