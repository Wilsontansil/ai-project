@extends('backoffice.partials.layout')

@section('title', 'New Agent')

@php($boActive = 'chat-agents')

@section('content')
    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between"
        class="rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">Create New Agent</h1>
            <p class="text-xs text-slate-400">Buat AI agent baru dengan konfigurasi kustom.</p>
        </div>
        <a href="{{ route('backoffice.chat-agents.index') }}" class="bo-btn-secondary"
            style="font-size:0.75rem;padding:0.5rem 1rem">
            &larr; Back
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-rose-300/30 bg-rose-500/15 px-4 py-3 text-sm text-rose-100">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5">
        <form method="POST" action="{{ route('backoffice.chat-agents.store') }}" class="space-y-5">
            @csrf

            {{-- Name & Model row --}}
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem">
                <div>
                    <label for="name" class="bo-label">Agent Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}"
                        placeholder="e.g. Customer Support Bot" />
                </div>
                <div>
                    <label for="model" class="bo-label">Model</label>
                    <select id="model" name="model">
                        <option value="gpt-4.1-mini"
                            {{ old('model', 'gpt-4.1-mini') === 'gpt-4.1-mini' ? 'selected' : '' }}>gpt-4.1-mini</option>
                        <option value="gpt-4.1" {{ old('model') === 'gpt-4.1' ? 'selected' : '' }}>gpt-4.1</option>
                        <option value="gpt-4.1-nano" {{ old('model') === 'gpt-4.1-nano' ? 'selected' : '' }}>gpt-4.1-nano
                        </option>
                        <option value="gpt-4o" {{ old('model') === 'gpt-4o' ? 'selected' : '' }}>gpt-4o</option>
                        <option value="gpt-4o-mini" {{ old('model') === 'gpt-4o-mini' ? 'selected' : '' }}>gpt-4o-mini
                        </option>
                    </select>
                </div>
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="bo-label">Description</label>
                <input id="description" type="text" name="description" value="{{ old('description') }}"
                    placeholder="Short description of this agent's purpose" />
            </div>

            {{-- System Prompt --}}
            <div>
                <label for="system_prompt" class="bo-label">System Prompt</label>
                <p style="margin-bottom:0.5rem;font-size:0.75rem;color:#94a3b8">Prompt utama yang dikirim ke AI model.
                    Variabel: <code style="color:#22d3ee">{bot_name}</code>, <code
                        style="color:#22d3ee">{server_time}</code>, <code style="color:#22d3ee">{server_timezone}</code></p>
                <textarea id="system_prompt" name="system_prompt" rows="14"
                    placeholder="You are {bot_name}, a friendly customer support assistant..."
                    style="font-family:ui-monospace,SFMono-Regular,monospace;font-size:12px">{{ old('system_prompt') }}</textarea>
            </div>

            {{-- Max Tokens, Temperature, Toggles --}}
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;align-items:end">
                <div>
                    <label for="max_tokens" class="bo-label">Max Tokens</label>
                    <input id="max_tokens" type="number" name="max_tokens" value="{{ old('max_tokens', 420) }}"
                        min="50" max="4096" />
                </div>
                <div>
                    <label for="temperature" class="bo-label">Temperature</label>
                    <input id="temperature" type="number" name="temperature" value="{{ old('temperature', '0.7') }}"
                        min="0" max="2" step="0.1" />
                </div>
                <div>
                    <label class="bo-checkbox-label">
                        <input type="checkbox" name="is_enabled" value="1"
                            {{ old('is_enabled', true) ? 'checked' : '' }} />
                        <span>Enabled</span>
                    </label>
                </div>
                <div>
                    <label class="bo-checkbox-label">
                        <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }} />
                        <span>Default Agent</span>
                    </label>
                </div>
            </div>

            {{-- Submit --}}
            <div
                style="display:flex;align-items:center;gap:0.75rem;border-top:1px solid rgba(51,65,85,0.5);padding-top:1rem">
                <button type="submit" class="bo-btn-primary">Create Agent</button>
                <a href="{{ route('backoffice.chat-agents.index') }}" class="bo-btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection
