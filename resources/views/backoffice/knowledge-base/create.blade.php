@extends('backoffice.partials.layout')

@section('title', 'New Knowledge Base Entry')
@section('page-title', 'Knowledge Base')

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <h1 class="text-3xl font-semibold">New Knowledge Base Entry</h1>
        <p class="mt-2 text-sm text-slate-300">Add reference text the AI will use as background knowledge.</p>
    </div>

    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <form method="POST" action="{{ route('backoffice.knowledge-base.store') }}" enctype="multipart/form-data"
            class="space-y-5">
            @csrf

            <div>
                <label for="title" class="mb-2 block text-sm text-slate-200">Title</label>
                <input id="title" type="text" name="title" value="{{ old('title') }}"
                    placeholder="e.g. Daftar Provider Aktif"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
            </div>

            {{-- Source type selector --}}
            <div>
                <label class="mb-2 block text-sm text-slate-200">Source Type</label>
                <div class="flex flex-wrap gap-3">
                    @foreach (['manual' => 'Manual Text', 'file' => 'Upload .txt File', 'datamodel' => 'DataModel Query'] as $val => $label)
                        <label
                            class="flex cursor-pointer items-center gap-2 rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2.5 text-sm text-slate-200 transition has-[:checked]:border-cyan-400 has-[:checked]:text-cyan-300">
                            <input type="radio" name="source_type" value="{{ $val }}"
                                {{ old('source_type', 'manual') === $val ? 'checked' : '' }}
                                class="source-type-radio accent-cyan-400" />
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Manual text / file panels --}}
            <div id="panel-manual" class="space-y-4">
                <div>
                    <label for="content" class="mb-2 block text-sm text-slate-200">Content</label>
                    <p class="mb-2 text-xs text-slate-400">Paste or type the reference text. If you upload a .txt file
                        below,
                        this field will be replaced by the file content.</p>
                    <textarea id="content" name="content" rows="10" placeholder="Enter knowledge text here..."
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">{{ old('content') }}</textarea>
                </div>
            </div>

            <div id="panel-file" class="hidden space-y-4">
                <div>
                    <label for="file" class="mb-2 block text-sm text-slate-200">Upload .txt file</label>
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
                            <option value="{{ $dm->id }}" {{ old('data_model_id') == $dm->id ? 'selected' : '' }}>
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
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 font-mono text-sm text-white outline-none transition focus:border-cyan-400">{{ old('query_sql') }}</textarea>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <input id="is_active" type="checkbox" name="is_active" value="1" checked
                    class="h-4 w-4 rounded border-white/20 bg-slate-800 text-cyan-400" />
                <label for="is_active" class="text-sm text-slate-200">Active (inject into AI context)</label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="rounded-xl bg-cyan-400 px-6 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    Save Entry
                </button>
                <a href="{{ route('backoffice.knowledge-base.index') }}"
                    class="rounded-xl border border-white/10 bg-white/5 px-6 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-white/10">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
        (function() {
            const radios = document.querySelectorAll('.source-type-radio');
            const panels = {
                manual: document.getElementById('panel-manual'),
                file: document.getElementById('panel-file'),
                datamodel: document.getElementById('panel-datamodel'),
            };

            function showPanel(val) {
                Object.entries(panels).forEach(([key, el]) => {
                    el.classList.toggle('hidden', key !== val);
                });
            }

            radios.forEach(r => r.addEventListener('change', () => showPanel(r.value)));
            const checked = document.querySelector('.source-type-radio:checked');
            if (checked) showPanel(checked.value);
        })();
    </script>
@endsection
