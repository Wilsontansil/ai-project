@extends('backoffice.partials.layout')

@section('title', 'Edit Agent — ' . $agent->name)

@php($boActive = 'chat-agents')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">{{ $agent->name }}</h1>
            <p class="text-xs text-slate-400">Agent settings & system prompt.</p>
        </div>
        <a href="{{ route('backoffice.chat-agents.index') }}"
            class="rounded-lg border border-white/10 bg-white/5 px-4 py-2 text-xs text-slate-300 transition hover:bg-white/10">
            &larr; Back
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-xs text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

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
        <form method="POST" action="{{ route('backoffice.chat-agents.update', $agent) }}" class="space-y-5">
            @csrf
            @method('PUT')

            {{-- Name & Model row --}}
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="name" class="mb-1.5 block text-sm text-slate-200">Agent Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $agent->name) }}"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2.5 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
                <div>
                    <label for="model" class="mb-1.5 block text-sm text-slate-200">Model</label>
                    <select id="model" name="model"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2.5 text-sm text-white outline-none transition focus:border-cyan-400">
                        @php($currentModel = old('model', $agent->model))
                        <option value="gpt-4.1-mini" {{ $currentModel === 'gpt-4.1-mini' ? 'selected' : '' }}>gpt-4.1-mini
                        </option>
                        <option value="gpt-4.1" {{ $currentModel === 'gpt-4.1' ? 'selected' : '' }}>gpt-4.1</option>
                        <option value="gpt-4.1-nano" {{ $currentModel === 'gpt-4.1-nano' ? 'selected' : '' }}>gpt-4.1-nano
                        </option>
                        <option value="gpt-4o" {{ $currentModel === 'gpt-4o' ? 'selected' : '' }}>gpt-4o</option>
                        <option value="gpt-4o-mini" {{ $currentModel === 'gpt-4o-mini' ? 'selected' : '' }}>gpt-4o-mini
                        </option>
                    </select>
                </div>
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="mb-1.5 block text-sm text-slate-200">Description</label>
                <input id="description" type="text" name="description"
                    value="{{ old('description', $agent->description) }}"
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
                    class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2.5 text-sm leading-relaxed text-white outline-none transition focus:border-cyan-400"
                    style="font-family:ui-monospace,SFMono-Regular,monospace;font-size:12px">{{ old('system_prompt', $agent->system_prompt) }}</textarea>
            </div>

            {{-- Max Tokens, Temperature, Toggles --}}
            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <label for="max_tokens" class="mb-1.5 block text-sm text-slate-200">Max Tokens</label>
                    <input id="max_tokens" type="number" name="max_tokens"
                        value="{{ old('max_tokens', $agent->max_tokens) }}" min="50" max="4096"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2.5 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
                <div>
                    <label for="temperature" class="mb-1.5 block text-sm text-slate-200">Temperature</label>
                    <input id="temperature" type="number" name="temperature"
                        value="{{ old('temperature', $agent->temperature) }}" min="0" max="2" step="0.1"
                        class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2.5 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
                <div class="flex items-end">
                    <label
                        class="flex items-center gap-2.5 rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2.5 cursor-pointer">
                        <input type="checkbox" name="is_enabled" value="1"
                            {{ old('is_enabled', $agent->is_enabled) ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-white/20 bg-slate-800 text-cyan-400 focus:ring-cyan-400" />
                        <span class="text-sm text-slate-200">Enabled</span>
                    </label>
                </div>
                <div class="flex items-end">
                    <label
                        class="flex items-center gap-2.5 rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2.5 cursor-pointer">
                        <input type="checkbox" name="is_default" value="1"
                            {{ old('is_default', $agent->is_default) ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-white/20 bg-slate-800 text-cyan-400 focus:ring-cyan-400" />
                        <span class="text-sm text-slate-200">Default Agent</span>
                    </label>
                </div>
            </div>

            {{-- Agent Info --}}
            <div class="rounded-xl border border-slate-700/50 bg-slate-950/40 p-4">
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Agent Info</h3>
                <div class="grid gap-x-6 gap-y-1 text-xs sm:grid-cols-3">
                    <div><span class="text-slate-500">Slug:</span> <span class="text-slate-300">{{ $agent->slug }}</span>
                    </div>
                    <div><span class="text-slate-500">Created:</span> <span
                            class="text-slate-300">{{ $agent->created_at->format('d M Y H:i') }}</span></div>
                    <div><span class="text-slate-500">Updated:</span> <span
                            class="text-slate-300">{{ $agent->updated_at->format('d M Y H:i') }}</span></div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex items-center gap-3 border-t border-slate-700/50 pt-4">
                <button type="submit"
                    class="rounded-lg bg-cyan-400 px-6 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    Save Changes
                </button>
                <a href="{{ route('backoffice.chat-agents.index') }}"
                    class="rounded-lg border border-white/10 px-5 py-2.5 text-sm text-slate-400 transition hover:bg-white/5 hover:text-slate-200">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    {{-- Forbidden Rules Section --}}
    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
            <div>
                <h2 class="text-sm font-semibold text-white">Forbidden Rules</h2>
                <p class="text-xs text-slate-400">Aturan perilaku yang dilarang untuk agent ini.</p>
            </div>
            <a href="{{ route('backoffice.forbidden.create', $agent) }}"
                class="rounded-lg bg-cyan-400 px-4 py-2 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300">
                + New Rule
            </a>
        </div>

        @if ($forbiddenRules->isEmpty())
            <div class="rounded-xl border border-slate-700/50 bg-slate-950/40 p-6 text-center">
                <p class="text-sm text-slate-400">Belum ada rule. Tambahkan rule pertama untuk membatasi perilaku agent
                    ini.</p>
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-white/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs" style="width:100%">
                        <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-3 py-2 font-medium">Title</th>
                                <th class="px-3 py-2 font-medium">Instruction</th>
                                <th class="px-3 py-2 font-medium text-center">Level</th>
                                <th class="px-3 py-2 font-medium text-center">Status</th>
                                <th class="px-3 py-2 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($forbiddenRules as $rule)
                                <tr class="transition hover:bg-white/5">
                                    <td class="px-3 py-2">
                                        <p class="font-medium text-white">{{ $rule->title }}</p>
                                    </td>
                                    <td class="max-w-xs px-3 py-2">
                                        <p class="text-xs text-slate-300 line-clamp-2">{{ $rule->instruction }}</p>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if ($rule->level === 'danger')
                                            <span
                                                style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(239,68,68,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#fca5a5">DANGER</span>
                                        @elseif ($rule->level === 'warning')
                                            <span
                                                style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(245,158,11,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#fcd34d">WARNING</span>
                                        @else
                                            <span
                                                style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(59,130,246,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#93c5fd">INFO</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if ($rule->is_active)
                                            <span
                                                style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(16,185,129,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#6ee7b7">ON</span>
                                        @else
                                            <span
                                                style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(239,68,68,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#fca5a5">OFF</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:0.5rem">
                                            <a href="{{ route('backoffice.forbidden.edit', [$agent, $rule]) }}"
                                                class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-white/10">
                                                Edit
                                            </a>
                                            <form method="POST"
                                                action="{{ route('backoffice.forbidden.destroy', [$agent, $rule]) }}"
                                                onsubmit="return confirm('Hapus rule {{ $rule->title }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="rounded-lg border border-red-400/20 bg-red-500/10 px-3 py-1.5 text-xs text-red-300 transition hover:bg-red-500/20">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
