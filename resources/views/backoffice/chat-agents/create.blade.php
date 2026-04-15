@extends('backoffice.partials.layout')

@section('title', 'New Agent')

@php($boActive = 'chat-agents')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">Create New Agent</h1>
            <p class="text-xs text-slate-400">Buat AI agent baru dengan konfigurasi kustom.</p>
        </div>
        <a href="{{ route('backoffice.chat-agents.index') }}"
            class="rounded-lg border border-white/10 bg-white/5 px-4 py-2 text-xs text-slate-300 transition hover:bg-white/10">
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
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="name" class="mb-1.5 block text-sm text-slate-200">Agent Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}"
                        placeholder="e.g. Customer Support Bot"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2.5 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
                <div>
                    <label for="model" class="mb-1.5 block text-sm text-slate-200">Model</label>
                    <select id="model" name="model"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2.5 text-sm text-white outline-none transition focus:border-cyan-400">
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
                <label for="description" class="mb-1.5 block text-sm text-slate-200">Description</label>
                <input id="description" type="text" name="description" value="{{ old('description') }}"
                    placeholder="Short description of this agent's purpose"
                    class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2.5 text-sm text-white outline-none transition focus:border-cyan-400" />
            </div>

            {{-- System Prompt --}}
            <div>
                <label for="system_prompt" class="mb-1.5 block text-sm text-slate-200">System Prompt</label>
                <p class="mb-2 text-xs text-slate-400">Prompt utama yang dikirim ke AI model. Variabel: <code
                        class="text-cyan-400">{bot_name}</code>, <code class="text-cyan-400">{server_time}</code>, <code
                        class="text-cyan-400">{server_timezone}</code></p>
                <textarea id="system_prompt" name="system_prompt" rows="14"
                    placeholder="You are {bot_name}, a friendly customer support assistant..."
                    class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2.5 text-sm leading-relaxed text-white outline-none transition focus:border-cyan-400"
                    style="font-family:ui-monospace,SFMono-Regular,monospace;font-size:12px">{{ old('system_prompt') }}</textarea>
            </div>

            {{-- Max Tokens, Temperature, Toggles --}}
            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <label for="max_tokens" class="mb-1.5 block text-sm text-slate-200">Max Tokens</label>
                    <input id="max_tokens" type="number" name="max_tokens" value="{{ old('max_tokens', 420) }}"
                        min="50" max="4096"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2.5 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
                <div>
                    <label for="temperature" class="mb-1.5 block text-sm text-slate-200">Temperature</label>
                    <input id="temperature" type="number" name="temperature" value="{{ old('temperature', '0.7') }}"
                        min="0" max="2" step="0.1"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2.5 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
                <div class="flex items-end">
                    <label
                        class="flex items-center gap-2.5 rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2.5 cursor-pointer">
                        <input type="checkbox" name="is_enabled" value="1"
                            {{ old('is_enabled', true) ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-white/20 bg-slate-800 text-cyan-400 focus:ring-cyan-400" />
                        <span class="text-sm text-slate-200">Enabled</span>
                    </label>
                </div>
                <div class="flex items-end">
                    <label
                        class="flex items-center gap-2.5 rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2.5 cursor-pointer">
                        <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-white/20 bg-slate-800 text-cyan-400 focus:ring-cyan-400" />
                        <span class="text-sm text-slate-200">Default Agent</span>
                    </label>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex items-center gap-3 border-t border-slate-700/50 pt-4">
                <button type="submit"
                    class="rounded-lg bg-cyan-400 px-6 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    Create Agent
                </button>
                <a href="{{ route('backoffice.chat-agents.index') }}"
                    class="rounded-lg border border-white/10 px-5 py-2.5 text-sm text-slate-400 transition hover:bg-white/5 hover:text-slate-200">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
