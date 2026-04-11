@extends('backoffice.partials.layout')

@section('title', 'Add Knowledge')

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <h1 class="text-3xl font-semibold">Add Knowledge</h1>
        <p class="mt-2 text-sm text-slate-300">Tambahkan knowledge baru secara manual.</p>
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
        <form method="POST" action="{{ route('backoffice.knowledge.store') }}" class="space-y-5">
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="category" class="mb-2 block text-sm text-slate-200">Category</label>
                    <input id="category" type="text" name="category" value="{{ old('category') }}"
                        placeholder="e.g. FAQ, Game, Transaksi"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
                <div>
                    <label for="confidence_score" class="mb-2 block text-sm text-slate-200">Confidence Score</label>
                    <input id="confidence_score" type="number" name="confidence_score"
                        value="{{ old('confidence_score', '0.7') }}" min="0" max="1" step="0.1"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
            </div>

            <div>
                <label for="question" class="mb-2 block text-sm text-slate-200">Question / Pertanyaan</label>
                <textarea id="question" name="question" rows="3" placeholder="Tulis pertanyaan atau topik..."
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">{{ old('question') }}</textarea>
            </div>

            <div>
                <label for="answer" class="mb-2 block text-sm text-slate-200">Answer / Jawaban</label>
                <textarea id="answer" name="answer" rows="5" placeholder="Tulis jawaban yang diharapkan..."
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">{{ old('answer') }}</textarea>
            </div>

            <div>
                <label for="tags" class="mb-2 block text-sm text-slate-200">Tags <span class="text-slate-500">(pisahkan
                        dengan koma)</span></label>
                <input id="tags" type="text" name="tags" value="{{ old('tags') }}"
                    placeholder="e.g. deposit, withdraw, game"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
            </div>

            <div class="flex items-center gap-4">
                <button type="submit"
                    class="rounded-2xl bg-cyan-400 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    Save Knowledge
                </button>
                <a href="{{ route('backoffice.knowledge.index') }}"
                    class="rounded-2xl bg-white/10 px-6 py-3 text-sm text-slate-300 transition hover:bg-white/15">Cancel</a>
            </div>
        </form>
    </div>
@endsection
