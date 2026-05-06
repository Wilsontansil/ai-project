@extends('backoffice.partials.layout')

@section('title', 'Edit System Config — ' . $config->key)
@section('page-title', 'System Config')

@php($boActive = 'system-config')

@section('content')
    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between"
        class="rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">Edit System Config</h1>
            <p class="text-xs text-slate-400 font-mono">{{ $config->key }}</p>
        </div>
        <a href="{{ route('backoffice.system-config.index') }}" class="bo-btn-secondary"
            style="font-size:0.75rem;padding:0.5rem 1rem">
            &larr; Back
        </a>
    </div>

    @if ($errors->any())
        <div
            style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:0.75rem;padding:0.75rem 1rem;font-size:0.75rem;color:#fca5a5">
            <ul style="margin:0;padding-left:1.25rem">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5">
        <form method="POST" action="{{ route('backoffice.system-config.update', $config) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="bo-label" for="source_type">Source Type</label>
                <select id="source_type" name="source_type" onchange="scSyncSourceType()">
                    <option value="manual" {{ old('source_type', $config->source_type) === 'manual' ? 'selected' : '' }}>
                        Manual</option>
                    <option value="datamodel_lookup"
                        {{ old('source_type', $config->source_type) === 'datamodel_lookup' ? 'selected' : '' }}>DataModel
                        Lookup</option>
                </select>
            </div>

            <div style="display:grid;grid-template-columns:1fr 2fr;gap:0.75rem;align-items:start">
                <div>
                    <label class="bo-label" for="key">Key</label>
                    <input id="key" type="text" name="key" value="{{ old('key', $config->key) }}" required
                        maxlength="191" />
                    @error('key')
                        <p style="margin-top:0.25rem;font-size:0.7rem;color:#f87171">{{ $message }}</p>
                    @enderror
                </div>
                <div id="manual-wrap">
                    <label class="bo-label" for="value">Value</label>
                    <textarea id="value" name="value" rows="3">{{ old('value', $config->value) }}</textarea>
                </div>
            </div>

            <div id="dm-wrap" class="rounded-lg border border-white/10 bg-slate-900/50 p-3" style="display:none">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;align-items:start">
                    <div>
                        <label class="bo-label" for="data_model_id">DataModel</label>
                        <select id="data_model_id" name="data_model_id" onchange="scPopulateFields()">
                            <option value="">-- Select DataModel --</option>
                            @foreach ($dataModels as $dm)
                                <option value="{{ $dm->id }}"
                                    {{ (string) old('data_model_id', $config->data_model_id) === (string) $dm->id ? 'selected' : '' }}>
                                    {{ $dm->model_name }} ({{ $dm->table_name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="bo-label" for="lookup_value">Lookup Value</label>
                        <input id="lookup_value" type="text" name="lookup_value"
                            value="{{ old('lookup_value', $config->lookup_value) }}" placeholder="e.g. mindeposit" />
                    </div>
                    <div>
                        <label class="bo-label" for="lookup_field">Lookup Field</label>
                        <select id="lookup_field" name="lookup_field">
                            <option value="">-- Select Field --</option>
                        </select>
                    </div>
                    <div>
                        <label class="bo-label" for="result_field">Result Field</label>
                        <select id="result_field" name="result_field">
                            <option value="">-- Select Field --</option>
                        </select>
                    </div>
                </div>
                @error('source_type')
                    <p style="margin-top:0.5rem;font-size:0.7rem;color:#f87171">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="bo-label" for="description">Description</label>
                <textarea id="description" name="description" rows="2" maxlength="1000"
                    placeholder="Short explanation of what this key is used for...">{{ old('description', $config->description) }}</textarea>
                @error('description')
                    <p style="margin-top:0.25rem;font-size:0.7rem;color:#f87171">{{ $message }}</p>
                @enderror
            </div>

            <div style="display:flex;gap:0.5rem">
                <button type="submit" class="bo-btn-primary">Save Changes</button>
                <a href="{{ route('backoffice.system-config.index') }}" class="bo-btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        const scDataModels = @json(
            $dataModels->mapWithKeys(fn($dm) => [
                    (string) $dm->id => ['fields' => array_keys((array) ($dm->fields ?? []))],
                ]));
        const scInitLookupField = @json(old('lookup_field', $config->lookup_field));
        const scInitResultField = @json(old('result_field', $config->result_field));

        function scSetFieldOptions(selectEl, fields, selected = '') {
            if (!selectEl) return;
            const opts = ['<option value="">-- Select Field --</option>'];
            fields.forEach(f => {
                opts.push(`<option value="${f}"${String(f) === String(selected) ? ' selected' : ''}>${f}</option>`);
            });
            selectEl.innerHTML = opts.join('');
        }

        function scPopulateFields(selLookup = '', selResult = '') {
            const dmId = document.getElementById('data_model_id')?.value ?? '';
            const fields = scDataModels[String(dmId)]?.fields ?? [];
            scSetFieldOptions(document.getElementById('lookup_field'), fields, selLookup || document.getElementById(
                'lookup_field')?.value);
            scSetFieldOptions(document.getElementById('result_field'), fields, selResult || document.getElementById(
                'result_field')?.value);
        }

        function scSyncSourceType() {
            const t = document.getElementById('source_type')?.value ?? 'manual';
            document.getElementById('manual-wrap').style.display = t === 'manual' ? '' : 'none';
            document.getElementById('dm-wrap').style.display = t === 'datamodel_lookup' ? '' : 'none';
            if (t === 'datamodel_lookup') scPopulateFields(scInitLookupField, scInitResultField);
        }

        document.addEventListener('DOMContentLoaded', () => {
            scSyncSourceType();
        });
    </script>
@endsection
