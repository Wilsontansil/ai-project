@extends('backoffice.partials.layout')

@section('title', 'Edit Rule — ' . $rule->title)

@php($boActive = 'chat-agents')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">Edit Rule</h1>
            <p class="text-xs text-slate-400">Agent: {{ $chatAgent->name }} — {{ $rule->title }}</p>
        </div>
        <a href="{{ route('backoffice.chat-agents.edit', $chatAgent) }}"
            class="rounded-lg border border-white/10 bg-white/5 px-4 py-2 text-xs text-slate-300 transition hover:bg-white/10">
            &larr; Back to Agent
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-rose-300/30 bg-rose-500/15 px-4 py-3 text-xs text-rose-100">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5">
        <form method="POST" action="{{ route('backoffice.forbidden.update', [$chatAgent, $rule]) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="title" class="mb-1.5 block text-sm text-slate-200">Rule Title</label>
                <input id="title" type="text" name="title" value="{{ old('title', $rule->title) }}"
                    class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2.5 text-sm text-white outline-none transition focus:border-cyan-400" />
            </div>

            <div>
                <label for="instruction" class="mb-1.5 block text-sm text-slate-200">Instruction for AI</label>
                <p class="mb-1.5 text-xs text-slate-400">Tulis instruksi yang jelas tentang apa yang AI agent dilarang
                    lakukan.</p>
                <textarea id="instruction" name="instruction" rows="4"
                    class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2.5 text-sm text-white outline-none transition focus:border-cyan-400">{{ old('instruction', $rule->instruction) }}</textarea>
            </div>

            <div>
                <label for="level" class="mb-1.5 block text-sm text-slate-200">Level</label>
                <select id="level" name="level"
                    class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2.5 text-sm text-white outline-none transition focus:border-cyan-400">
                    <option value="info" {{ old('level', $rule->level) === 'info' ? 'selected' : '' }}>Info</option>
                    <option value="warning" {{ old('level', $rule->level) === 'warning' ? 'selected' : '' }}>Warning
                    </option>
                    <option value="danger" {{ old('level', $rule->level) === 'danger' ? 'selected' : '' }}>Danger</option>
                </select>
            </div>

            <div class="flex items-center gap-3">
                <label
                    class="inline-flex items-center gap-2 rounded-lg border border-white/15 bg-slate-900/50 px-3 py-2 text-sm text-slate-200">
                    <input type="checkbox" name="is_active" value="1" {{ $rule->is_active ? 'checked' : '' }}
                        class="rounded border-white/20 bg-slate-800 text-cyan-400 focus:ring-cyan-400" />
                    Active
                </label>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                    class="rounded-lg bg-cyan-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    Update Rule
                </button>
                <a href="{{ route('backoffice.chat-agents.edit', $chatAgent) }}"
                    class="rounded-lg border border-white/10 px-5 py-2.5 text-sm text-slate-300 transition hover:bg-white/5">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
