@extends('backoffice.partials.layout')

@section('title', 'Edit Data Model — ' . $dataModel->model_name)

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <h1 class="text-3xl font-semibold">Edit Data Model</h1>
        <p class="mt-2 text-sm text-slate-300">{{ $dataModel->model_name }} — <span
                class="font-mono text-cyan-300">{{ $dataModel->slug }}</span></p>
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
        <form method="POST" action="{{ route('backoffice.data-models.update', $dataModel) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="model_name" class="mb-2 block text-sm text-slate-200">Model Name</label>
                    <input id="model_name" type="text" name="model_name"
                        value="{{ old('model_name', $dataModel->model_name) }}"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
                <div>
                    <label for="description" class="mb-2 block text-sm text-slate-200">Description</label>
                    <input id="description" type="text" name="description"
                        value="{{ old('description', $dataModel->description) }}"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="table_name" class="mb-2 block text-sm text-slate-200">Table Name</label>
                    <input id="table_name" type="text" name="table_name"
                        value="{{ old('table_name', $dataModel->table_name) }}"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
                <div>
                    <label for="connection_name" class="mb-2 block text-sm text-slate-200">Connection</label>
                    <input id="connection_name" type="text"
                        value="{{ old('connection_name', $dataModel->connection_name ?: 'mysqlgame') }}" readonly
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/40 px-4 py-3 text-sm text-slate-300 outline-none" />
                </div>
            </div>

            <div>
                <p class="mb-2 block text-sm text-slate-200">Fields</p>
                <p class="mb-2 text-xs text-slate-400">Edit struktur field model. Disimpan sebagai JSON map.</p>
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
                    Save Changes
                </button>
                <a href="{{ route('backoffice.data-models.index') }}"
                    class="rounded-2xl border border-white/10 px-6 py-3 text-sm text-slate-300 transition hover:bg-white/5">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        let fieldIndex = 0;

        function addFieldRow(name = '', type = '') {
            const list = document.getElementById('field-list');
            const row = document.createElement('div');
            row.className = 'flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-900/50 p-3';
            row.innerHTML = `
                <input type="text" name="fields[${fieldIndex}][name]" value="${name}" placeholder="Field name"
                    class="w-2/5 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                <input type="text" name="fields[${fieldIndex}][type]" value="${type}" placeholder="Format"
                    class="flex-1 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400" />
                <button type="button" onclick="removeFieldRow(this)"
                    class="shrink-0 rounded-lg border border-red-400/20 bg-red-500/10 px-2 py-1.5 text-xs text-red-300 hover:bg-red-500/20">&times;</button>
            `;
            list.appendChild(row);
            fieldIndex++;
            bindPreviewListeners(row);
            updateJsonPreview();
        }

        function removeFieldRow(button) {
            button.parentElement.remove();
            updateJsonPreview();
        }

        function bindPreviewListeners(row) {
            row.querySelectorAll('input').forEach(input => {
                input.addEventListener('input', updateJsonPreview);
            });
        }

        function updateJsonPreview() {
            const rows = document.querySelectorAll('#field-list > div');
            const obj = {};
            rows.forEach(row => {
                const inputs = row.querySelectorAll('input[type=text]');
                const name = (inputs[0]?.value || '').trim();
                const type = (inputs[1]?.value || '').trim();
                if (name && type) obj[name] = type;
            });
            document.getElementById('json-preview').textContent = JSON.stringify(obj, null, 2);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const oldRows = @json(old('fields', []));
            const existingMap = @json($dataModel->fields ?? []);

            if (Array.isArray(oldRows) && oldRows.length > 0) {
                oldRows.forEach(row => addFieldRow(row.name || '', row.type || ''));
            } else {
                for (const [name, type] of Object.entries(existingMap)) {
                    addFieldRow(name, type || '');
                }
            }

            if (document.querySelectorAll('#field-list > div').length === 0) {
                addFieldRow();
            }
        });
    </script>
@endsection
