@extends('backoffice.partials.layout')

@section('title', 'Edit Rule — ' . $rule->title)

@php($boActive = 'chat-agents')

@section('content')
    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between"
        class="rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">Edit Rule</h1>
            <p class="text-xs text-slate-400">Agent: {{ $chatAgent->name }} — {{ $rule->title }}</p>
        </div>
        <a href="{{ route('backoffice.chat-agents.edit', $chatAgent) }}" class="bo-btn-secondary"
            style="font-size:0.75rem;padding:0.5rem 1rem">
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
                <label for="title" class="bo-label">Rule Title</label>
                <input id="title" type="text" name="title" value="{{ old('title', $rule->title) }}" />
            </div>

            <div>
                <label for="instruction" class="bo-label">Instruction for AI</label>
                <p style="margin-bottom:0.375rem;font-size:0.75rem;color:#94a3b8">Tulis instruksi yang jelas tentang apa
                    yang AI agent dilarang
                    lakukan.</p>
                <textarea id="instruction" name="instruction" rows="4">{{ old('instruction', $rule->instruction) }}</textarea>
            </div>

            <div>
                <label for="level" class="bo-label">Level</label>
                <select id="level" name="level">
                    <option value="info" {{ old('level', $rule->level) === 'info' ? 'selected' : '' }}>Info</option>
                    <option value="warning" {{ old('level', $rule->level) === 'warning' ? 'selected' : '' }}>Warning
                    </option>
                    <option value="danger" {{ old('level', $rule->level) === 'danger' ? 'selected' : '' }}>Danger</option>
                </select>
            </div>

            <div>
                <label class="bo-checkbox-label" style="display:inline-flex">
                    <input type="checkbox" name="is_active" value="1" {{ $rule->is_active ? 'checked' : '' }} />
                    <span>Active</span>
                </label>
            </div>

            <div style="display:flex;align-items:center;gap:0.75rem;padding-top:0.5rem">
                <button type="submit" class="bo-btn-primary">Update Rule</button>
                <a href="{{ route('backoffice.chat-agents.edit', $chatAgent) }}" class="bo-btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection
