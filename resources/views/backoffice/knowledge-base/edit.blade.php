@extends('backoffice.partials.layout')

@section('title', 'Edit Knowledge Base Entry')
@section('page-title', 'Knowledge Base')

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <h1 class="text-3xl font-semibold">Edit Knowledge Base Entry</h1>
        <p class="mt-2 text-sm text-slate-300">Update the reference text or re-upload a file.</p>
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

            <div>
                <label for="content" class="mb-2 block text-sm text-slate-200">Content</label>
                @if ($entry->source === 'file')
                    <p class="mb-2 text-xs text-amber-400">This entry was imported from
                        <strong>{{ $entry->file_name }}</strong>. You can edit the content directly or re-upload a file.
                    </p>
                @endif
                <textarea id="content" name="content" rows="10"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">{{ old('content', $entry->content) }}</textarea>
            </div>

            <div>
                <label for="file" class="mb-2 block text-sm text-slate-200">Re-upload .txt file <span
                        class="text-slate-400">(optional — replaces content above)</span></label>
                <input id="file" type="file" name="file" accept=".txt"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
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
@endsection
