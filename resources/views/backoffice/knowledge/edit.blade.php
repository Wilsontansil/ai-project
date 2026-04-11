@extends('backoffice.partials.layout')

@section('title', 'Edit Knowledge')

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <h1 class="text-3xl font-semibold">Edit Knowledge</h1>
        <p class="mt-2 text-sm text-slate-300">Update training data untuk AI agent.</p>
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
        <form method="POST" action="{{ route('backoffice.knowledge.update', $entry) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="category" class="mb-2 block text-sm text-slate-200">Category</label>
                    <input id="category" type="text" name="category" value="{{ old('category', $entry->category) }}"
                        placeholder="e.g. FAQ, Game, Transaksi"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
                <div>
                    <label for="confidence_score" class="mb-2 block text-sm text-slate-200">Confidence Score</label>
                    <input id="confidence_score" type="number" name="confidence_score"
                        value="{{ old('confidence_score', $entry->confidence_score) }}" min="0" max="1"
                        step="0.1"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
            </div>

            <div>
                <label for="title" class="mb-2 block text-sm text-slate-200">Title <span
                        class="text-slate-500">(opsional)</span></label>
                <input id="title" type="text" name="title" value="{{ old('title', $entry->title) }}"
                    placeholder="e.g. Cara Deposit, Minimum Withdraw"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
            </div>

            <div>
                <label for="content" class="mb-2 block text-sm text-slate-200">Content</label>
                <textarea id="content" name="content" rows="5"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">{{ old('content', $entry->content) }}</textarea>
            </div>

            <div>
                <label for="tags" class="mb-2 block text-sm text-slate-200">Tags <span class="text-slate-500">(pisahkan
                        dengan koma)</span></label>
                <input id="tags" type="text" name="tags"
                    value="{{ old('tags', is_array($entry->tags) ? implode(', ', $entry->tags) : '') }}"
                    placeholder="e.g. deposit, withdraw, game"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
            </div>

            <div class="flex items-center gap-4">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1"
                        {{ old('is_active', $entry->is_active) ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-white/20 bg-slate-900/70 text-cyan-400 focus:ring-cyan-400">
                    <span class="text-sm text-slate-200">Active</span>
                </label>
            </div>

            {{-- Source info --}}
            @if ($entry->source === 'file')
                <div class="rounded-2xl border border-blue-400/20 bg-blue-500/10 px-4 py-3 text-sm text-blue-200">
                    📁 Imported from file: <strong>{{ $entry->source_file }}</strong>
                </div>
            @endif

            <div class="flex items-center gap-4">
                <button type="submit"
                    class="rounded-2xl bg-cyan-400 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    Update Knowledge
                </button>
                <a href="{{ route('backoffice.knowledge.index') }}"
                    class="rounded-2xl bg-white/10 px-6 py-3 text-sm text-slate-300 transition hover:bg-white/15">Cancel</a>
            </div>
        </form>
    </div>
@endsection
