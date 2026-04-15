@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.data_models.new_data_model'))
@section('page-title', __('backoffice.pages.data_models.page_title'))

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <h1 class="text-3xl font-semibold">{{ __('backoffice.pages.data_models.new_data_model') }}</h1>
        <p class="mt-2 text-sm text-slate-300">Buat model referensi field (JSON) yang terhubung ke tabel DB game.</p>
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
        <form method="POST" action="{{ route('backoffice.data-models.store') }}" class="space-y-5">
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="model_name" class="mb-2 block text-sm text-slate-200">Model Name</label>
                    <p class="mb-2 text-xs text-slate-400">Contoh: Player, Transaction, Wallet</p>
                    <input id="model_name" type="text" name="model_name" value="{{ old('model_name') }}"
                        placeholder="e.g. Player"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
                <div>
                    <label for="description" class="mb-2 block text-sm text-slate-200">Description</label>
                    <p class="mb-2 text-xs text-slate-400">Penjelasan singkat fungsi model.</p>
                    <input id="description" type="text" name="description" value="{{ old('description') }}"
                        placeholder="e.g. Struktur data player dari provider"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="table_name" class="mb-2 block text-sm text-slate-200">Table Name</label>
                    <p class="mb-2 text-xs text-slate-400">Nama tabel pada DB game, contoh: players</p>
                    <input id="table_name" type="text" name="table_name" value="{{ old('table_name') }}"
                        placeholder="e.g. players"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
                <div>
                    <label for="connection_name" class="mb-2 block text-sm text-slate-200">Connection</label>
                    <p class="mb-2 text-xs text-slate-400">Pilih koneksi database untuk model ini.</p>
                    <select id="connection_name" name="connection_name"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">
                        <option value="">-- Pilih Connection --</option>
                        @foreach ($connections as $conn)
                            <option value="{{ $conn->name }}"
                                {{ old('connection_name') === $conn->name ? 'selected' : '' }}>
                                {{ $conn->name }} ({{ $conn->driver }} — {{ $conn->host }}:{{ $conn->port }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <p class="mb-2 block text-sm text-slate-200">Fields</p>
                <p class="mb-2 text-xs text-slate-400">Tambah/edit/hapus field. Disimpan sebagai JSON map. Centang
                    <strong>Required</strong> agar field wajib diisi saat tool menggunakan model ini.
                </p>
                <div id="field-list" class="space-y-3"></div>
                <button type="button" onclick="addFieldRow()"
                    class="mt-3 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs text-slate-300 transition hover:bg-white/10">
                    + Tambah Field
                </button>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-4">
                <p class="text-xs text-slate-400 mb-2">Preview JSON</p>
                <pre id="json-preview" class="max-h-72 overflow-auto text-xs text-cyan-200">{}</pre>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <button type="submit"
                    class="rounded-2xl bg-cyan-400 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    {{ __('backoffice.pages.data_models.add_data_model') }}
                </button>
                <a href="{{ route('backoffice.data-models.index') }}"
                    class="rounded-2xl border border-white/10 px-6 py-3 text-sm text-slate-300 transition hover:bg-white/5">
                    {{ __('backoffice.common.cancel') }}
                </a>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        let fieldIndex = 0;

        function addFieldRow(name = '', type = '', required = false, value = '') {
            const list = document.getElementById('field-list');
            const row = document.createElement('div');
            row.className = 'rounded-2xl border border-white/10 bg-slate-900/50 p-3 space-y-2';
            const checkedAttr = required ? 'checked' : '';
            const valueDisplay = required ? '' : 'display:none;';
            const escapedValue = value.replace(/"/g, '&quot;');
            row.innerHTML = `
                <div class="flex items-center gap-3">
                    <input type="text" name="fields[${fieldIndex}][name]" value="${name}" placeholder="Field name (e.g. username)"
                        class="w-2/5 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                    <input type="text" name="fields[${fieldIndex}][type]" value="${type}" placeholder="Format (e.g. VARCHAR, BIGINT, DECIMAL(14,3))"
                        class="flex-1 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                    <label class="flex shrink-0 items-center gap-1.5 text-xs text-slate-300 cursor-pointer select-none">
                        <input type="checkbox" name="fields[${fieldIndex}][required]" value="1" ${checkedAttr}
                            class="field-required-cb h-4 w-4 rounded border-white/20 bg-slate-900/70 text-cyan-400 focus:ring-cyan-400" />
                        Required
                    </label>
                    <button type="button" onclick="removeFieldRow(this)"
                        class="shrink-0 rounded-lg border border-red-400/20 bg-red-500/10 px-2 py-1.5 text-xs text-red-300 hover:bg-red-500/20">&times;</button>
                </div>
                <div class="field-value-row flex items-center gap-3 pl-0" style="${valueDisplay}">
                    <span class="w-2/5 text-xs text-slate-400 pl-1">↳ Fixed value</span>
                    <input type="text" name="fields[${fieldIndex}][value]" value="${escapedValue}" placeholder="Value (kosongkan jika diisi oleh AI)"
                        class="flex-1 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                </div>
            `;
            list.appendChild(row);
            fieldIndex++;
            bindPreviewListeners(row);
            updateJsonPreview();
        }

        function removeFieldRow(button) {
            button.closest('#field-list > div').remove();
            updateJsonPreview();
        }

        function bindPreviewListeners(row) {
            row.querySelectorAll('input').forEach(input => {
                input.addEventListener(input.type === 'checkbox' ? 'change' : 'input', updateJsonPreview);
            });
            const cb = row.querySelector('.field-required-cb');
            const valueRow = row.querySelector('.field-value-row');
            if (cb && valueRow) {
                cb.addEventListener('change', function() {
                    valueRow.style.display = this.checked ? '' : 'none';
                    if (!this.checked) {
                        valueRow.querySelector('input[type=text]').value = '';
                    }
                    updateJsonPreview();
                });
            }
        }

        function updateJsonPreview() {
            const rows = document.querySelectorAll('#field-list > div');
            const obj = {};
            rows.forEach(row => {
                const nameInput = row.querySelector('input[type=text]');
                const inputs = row.querySelectorAll('input[type=text]');
                const checkbox = row.querySelector('input[type=checkbox]');
                const name = (inputs[0]?.value || '').trim();
                const type = (inputs[1]?.value || '').trim();
                const required = checkbox?.checked || false;
                const value = (inputs[2]?.value || '').trim();
                if (name && type) {
                    const entry = {
                        type,
                        required
                    };
                    if (required && value) entry.value = value;
                    obj[name] = entry;
                }
            });
            document.getElementById('json-preview').textContent = JSON.stringify(obj, null, 2);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const oldRows = @json(old('fields', []));
            if (Array.isArray(oldRows) && oldRows.length > 0) {
                oldRows.forEach(row => addFieldRow(row.name || '', row.type || '', !!row.required, row.value ||
                    ''));
            } else {
                addFieldRow();
            }
        });
    </script>
@endsection
