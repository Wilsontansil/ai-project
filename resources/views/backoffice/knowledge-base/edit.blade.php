@extends('backoffice.partials.layout')

@section('title', 'Edit Knowledge Base Entry')
@section('page-title', 'Knowledge Base')

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <h1 class="text-3xl font-semibold">Edit Knowledge Base Entry</h1>
        <p class="mt-2 text-sm text-slate-300">Update the reference text, file, or DataModel query.</p>
    </div>

    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <form method="POST" action="{{ route('backoffice.knowledge-base.update', $entry) }}" enctype="multipart/form-data"
            class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="title" class="mb-2 block text-sm text-slate-200">Title</label>
                <input id="title" type="text" name="title" value="{{ old('title', $entry->title) }}"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
            </div>

            {{-- Source type selector --}}
            <div>
                <label class="mb-2 block text-sm text-slate-200">Source Type</label>
                <div class="flex flex-wrap gap-3">
                    @php $currentSource = old('source_type', $entry->source); @endphp
                    @foreach (['manual' => 'Manual Text', 'file' => 'Upload .txt File', 'datamodel' => 'DataModel Query', 'website' => 'Website Scrape (RTP)'] as $val => $label)
                        <label
                            class="flex cursor-pointer items-center gap-2 rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2.5 text-sm text-slate-200 transition has-[:checked]:border-cyan-400 has-[:checked]:text-cyan-300">
                            <input type="radio" name="source_type" value="{{ $val }}"
                                {{ $currentSource === $val ? 'checked' : '' }} class="source-type-radio accent-cyan-400" />
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Manual text panel --}}
            <div id="panel-manual" class="space-y-4">
                <div>
                    <label for="content" class="mb-2 block text-sm text-slate-200">Content</label>
                    @if ($entry->source === 'file')
                        <p class="mb-2 text-xs text-amber-400">This entry was imported from
                            <strong>{{ $entry->file_name }}</strong>. You can edit the content directly or re-upload a file.
                        </p>
                    @endif
                    @if ($systemConfigs->isNotEmpty())
                        <div
                            style="margin-bottom:0.6rem;padding:0.6rem 0.75rem;border-radius:0.75rem;border:1px solid rgba(34,211,238,0.2);background:rgba(8,145,178,0.08);font-size:0.7rem;color:#94a3b8">
                            <span style="color:#22d3ee;font-weight:600">Tip:</span> Use <code
                                style="color:#22d3ee">{key}</code> placeholders to inject SystemConfig values dynamically.
                            <span style="margin-left:0.5rem;color:#64748b">Available keys:</span>
                            @foreach ($systemConfigs as $sc)
                                <code onclick="scInsertPlaceholder('{{ $sc->key }}')"
                                    title="Current: {{ $sc->value }}"
                                    style="cursor:pointer;margin-left:0.3rem;padding:0.1rem 0.35rem;border-radius:0.35rem;background:rgba(15,23,42,0.6);border:1px solid rgba(255,255,255,0.1);color:#7dd3fc">{{ '{' }}{{ $sc->key }}{{ '}' }}</code>
                            @endforeach
                        </div>
                    @endif
                    <textarea id="content" name="content" rows="10"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">{{ old('content', $entry->source !== 'datamodel' ? $entry->content : '') }}</textarea>
                </div>
            </div>

            {{-- File upload panel --}}
            <div id="panel-file" class="hidden space-y-4">
                <div>
                    <label for="file" class="mb-2 block text-sm text-slate-200">Re-upload .txt file <span
                            class="text-slate-400">(optional — replaces content above)</span></label>
                    <input id="file" type="file" name="file" accept=".txt"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
            </div>

            {{-- DataModel query panel --}}
            <div id="panel-datamodel" class="hidden space-y-4">
                <div>
                    <label for="data_model_id" class="mb-2 block text-sm text-slate-200">DataModel</label>
                    <select id="data_model_id" name="data_model_id"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">
                        <option value="">— select a data model —</option>
                        @foreach ($dataModels as $dm)
                            <option value="{{ $dm->id }}"
                                {{ old('data_model_id', $entry->data_model_id) == $dm->id ? 'selected' : '' }}>
                                {{ $dm->model_name }} ({{ $dm->slug }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">The DB connection is resolved automatically from the selected
                        DataModel.</p>
                </div>
                <div>
                    <label for="query_sql" class="mb-2 block text-sm text-slate-200">SQL Query</label>
                    <p class="mb-2 text-xs text-slate-400">Write a SELECT query. Result is injected as live KB content
                        (cached 5 min).</p>
                    <textarea id="query_sql" name="query_sql" rows="6"
                        placeholder="SELECT name, alias, category FROM providers WHERE active = 1 ORDER BY urutan ASC"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 font-mono text-sm text-white outline-none transition focus:border-cyan-400">{{ old('query_sql', $entry->query_sql) }}</textarea>
                    @error('query_sql')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Website scrape panel --}}
            <div id="panel-website" class="hidden space-y-4">
                <div>
                    <label for="source_url" class="mb-2 block text-sm text-slate-200">Website URL</label>
                    <input id="source_url" type="url" name="source_url"
                        value="{{ old('source_url', $entry->source_url) }}" placeholder="https://rtpcmbet95.xyz/"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                    @error('source_url')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="source_limit" class="mb-2 block text-sm text-slate-200">Max Games to Sync</label>
                    <input id="source_limit" type="number" name="source_limit"
                        value="{{ old('source_limit', (int) ($entry->source_options['limit'] ?? 15)) }}" min="1"
                        max="50"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
                <div class="rounded-xl border border-white/10 bg-slate-900/40 p-3 text-xs text-slate-300">
                    <p>Status sync: <span class="font-semibold text-cyan-300">{{ $entry->last_sync_status ?? '-' }}</span>
                    </p>
                    <p>Last synced: <span
                            class="font-semibold text-slate-100">{{ $entry->last_synced_at?->format('d M Y H:i:s') ?? '-' }}</span>
                    </p>
                    @if (!empty($entry->last_sync_error))
                        <p class="mt-1 text-rose-300">Error: {{ $entry->last_sync_error }}</p>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3">
                <input id="is_active" type="checkbox" name="is_active" value="1"
                    {{ old('is_active', $entry->is_active) ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-white/20 bg-slate-800 text-cyan-400" />
                <label for="is_active" class="text-sm text-slate-200">Active (inject into AI context)</label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="rounded-xl bg-cyan-400 px-6 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    Update Entry
                </button>
                <a href="{{ route('backoffice.knowledge-base.index') }}"
                    class="rounded-xl border border-white/10 bg-white/5 px-6 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-white/10">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
        function scInsertPlaceholder(key) {
            const ta = document.getElementById('content');
            if (!ta) return;
            const placeholder = '{' + key + '}';
            const start = ta.selectionStart;
            const end = ta.selectionEnd;
            ta.value = ta.value.slice(0, start) + placeholder + ta.value.slice(end);
            ta.selectionStart = ta.selectionEnd = start + placeholder.length;
            ta.focus();
        }
        (function() {
            const radios = document.querySelectorAll('.source-type-radio');
            const panels = {
                manual: document.getElementById('panel-manual'),
                file: document.getElementById('panel-file'),
                datamodel: document.getElementById('panel-datamodel'),
                website: document.getElementById('panel-website'),
            };

            function showPanel(val) {
                Object.entries(panels).forEach(([key, el]) => {
                    if (val === 'file') {
                        el.classList.toggle('hidden', key !== 'manual' && key !== 'file');
                    } else {
                        el.classList.toggle('hidden', key !== val);
                    }
                });
            }

            radios.forEach(r => r.addEventListener('change', () => showPanel(r.value)));
            const checked = document.querySelector('.source-type-radio:checked');
            if (checked) showPanel(checked.value);
        })();
    </script>
@endsection
