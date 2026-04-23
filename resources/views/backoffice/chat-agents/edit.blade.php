@extends('backoffice.partials.layout')

@section('title', __('backoffice.common.edit') . ' — ' . $agent->name)
@section('page-title', __('backoffice.pages.chat_agents.page_title'))

@php($boActive = 'chat-agents')

@section('content')
    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between"
        class="rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">{{ $agent->name }}</h1>
            <p class="text-xs text-slate-400">{{ __('backoffice.pages.chat_agents.edit_subtitle') }}</p>
        </div>
        <a href="{{ route('backoffice.chat-agents.index') }}" class="bo-btn-secondary"
            style="font-size:0.75rem;padding:0.5rem 1rem">
            &larr; {{ __('backoffice.common.back') }}
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
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem">
                <div>
                    <label for="name" class="bo-label">{{ __('backoffice.pages.chat_agents.agent_name') }}</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $agent->name) }}" />
                </div>
                <div>
                    <label for="model" class="bo-label">{{ __('backoffice.pages.chat_agents.model') }}</label>
                    <select id="model" name="model">
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
                <label for="description" class="bo-label">{{ __('backoffice.pages.chat_agents.description') }}</label>
                <input id="description" type="text" name="description"
                    value="{{ old('description', $agent->description) }}" maxlength="200"
                    oninput="document.getElementById('desc-count').textContent=this.value.length"
                    placeholder="Short description of this agent's purpose" />
                <p style="margin-top:0.25rem;font-size:0.7rem;color:#64748b"><span
                        id="desc-count">{{ strlen(old('description', $agent->description) ?? '') }}</span>/200</p>
            </div>

            {{-- System Prompt --}}
            <div>
                <label for="system_prompt" class="bo-label">{{ __('backoffice.pages.chat_agents.system_prompt') }}</label>
                <p style="margin-bottom:0.5rem;font-size:0.75rem;color:#94a3b8">
                    {{ __('backoffice.pages.chat_agents.system_prompt_help') }}
                    Variabel: <code style="color:#22d3ee">{bot_name}</code>, <code
                        style="color:#22d3ee">{server_time}</code>, <code style="color:#22d3ee">{server_timezone}</code></p>
                <textarea id="system_prompt" name="system_prompt" rows="14" maxlength="2000"
                    oninput="document.getElementById('prompt-count').textContent=this.value.length"
                    style="font-family:ui-monospace,SFMono-Regular,monospace;font-size:12px">{{ old('system_prompt', $agent->system_prompt) }}</textarea>
                <p style="margin-top:0.25rem;font-size:0.7rem;color:#64748b"><span
                        id="prompt-count">{{ strlen(old('system_prompt', $agent->system_prompt) ?? '') }}</span>/2000</p>
            </div>

            {{-- Max Tokens, Temperature, Await Delay, Toggles --}}
            <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:1rem;align-items:end">
                <div>
                    <label for="max_tokens" class="bo-label">{{ __('backoffice.pages.chat_agents.max_tokens') }}</label>
                    <input id="max_tokens" type="number" name="max_tokens"
                        value="{{ old('max_tokens', $agent->max_tokens) }}" min="50" max="4096" />
                </div>
                <div>
                    <label for="temperature" class="bo-label">{{ __('backoffice.pages.chat_agents.temp') }}</label>
                    <input id="temperature" type="number" name="temperature"
                        value="{{ old('temperature', $agent->temperature) }}" min="0" max="2"
                        step="0.1" />
                </div>
                <div>
                    <label for="message_await_seconds"
                        class="bo-label">{{ __('backoffice.pages.chat_agents.message_await_seconds') }}</label>
                    <input id="message_await_seconds" type="number" name="message_await_seconds"
                        value="{{ old('message_await_seconds', $agent->message_await_seconds ?? 2) }}" min="0"
                        max="15" step="1" />
                    <p style="margin-top:0.25rem;font-size:0.7rem;color:#64748b">
                        {{ __('backoffice.pages.chat_agents.message_await_help') }}</p>
                </div>
                <div>
                    <label class="bo-checkbox-label">
                        <input type="checkbox" name="is_enabled" value="1"
                            {{ old('is_enabled', $agent->is_enabled) ? 'checked' : '' }} />
                        <span>{{ __('backoffice.pages.chat_agents.enabled_label') }}</span>
                    </label>
                </div>
                <div>
                    <label class="bo-checkbox-label">
                        <input type="checkbox" name="is_default" value="1"
                            {{ old('is_default', $agent->is_default) ? 'checked' : '' }} />
                        <span>{{ __('backoffice.pages.chat_agents.default_agent') }}</span>
                    </label>
                </div>
                <div>
                    <label class="bo-checkbox-label">
                        <input type="checkbox" name="escalation_enabled" value="1"
                            {{ old('escalation_enabled', $agent->escalation_enabled) ? 'checked' : '' }} />
                        <span>{{ __('backoffice.pages.chat_agents.escalation_enabled_label') }}</span>
                    </label>
                </div>
            </div>

            {{-- Agent Info --}}
            <div class="rounded-xl border border-slate-700/50 bg-slate-950/40 p-4">
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    {{ __('backoffice.pages.chat_agents.agent_info') }}</h3>
                <div class="grid gap-x-6 gap-y-1 text-xs sm:grid-cols-3">
                    <div><span class="text-slate-500">{{ __('backoffice.pages.chat_agents.slug') }}:</span> <span
                            class="text-slate-300">{{ $agent->slug }}</span>
                    </div>
                    <div><span class="text-slate-500">{{ __('backoffice.pages.chat_agents.created') }}:</span> <span
                            class="text-slate-300">{{ $agent->created_at->format('d M Y H:i') }}</span></div>
                    <div><span class="text-slate-500">{{ __('backoffice.pages.chat_agents.updated') }}:</span> <span
                            class="text-slate-300">{{ $agent->updated_at->format('d M Y H:i') }}</span></div>
                </div>
            </div>

            {{-- Submit --}}
            <div
                style="display:flex;align-items:center;gap:0.75rem;border-top:1px solid rgba(51,65,85,0.5);padding-top:1rem">
                <button type="submit" class="bo-btn-primary">{{ __('backoffice.common.save_changes') }}</button>
                <a href="{{ route('backoffice.chat-agents.index') }}"
                    class="bo-btn-secondary">{{ __('backoffice.common.cancel') }}</a>
            </div>
        </form>
    </div>

    {{-- Agent Rules Section --}}
    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
            <div>
                <h2 class="text-sm font-semibold text-white">{{ __('backoffice.pages.chat_agents.agent_rules') }}</h2>
                <p class="text-xs text-slate-400">{{ __('backoffice.pages.chat_agents.agent_rules_subtitle') }}</p>
            </div>
            <a href="{{ route('backoffice.agent-rules.create', $agent) }}" class="bo-btn-primary"
                style="font-size:0.75rem;padding:0.5rem 1rem">
                + {{ __('backoffice.pages.chat_agents.new_rule') }}
            </a>
        </div>

        @if ($agentRules->isEmpty())
            <div class="rounded-xl border border-slate-700/50 bg-slate-950/40 p-6 text-center">
                <p class="text-sm text-slate-400">{{ __('backoffice.pages.chat_agents.no_rules') }}</p>
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-white/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs" style="width:100%">
                        <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.chat_agents.rule_title') }}</th>
                                <th class="px-3 py-2 font-medium">
                                    {{ __('backoffice.pages.chat_agents.rule_instruction') }}</th>
                                <th class="px-3 py-2 font-medium text-center">
                                    {{ __('backoffice.pages.chat_agents.rule_type') }}</th>
                                <th class="px-3 py-2 font-medium text-center">
                                    {{ __('backoffice.pages.chat_agents.rule_level') }}</th>
                                <th class="px-3 py-2 font-medium text-center">{{ __('backoffice.common.status') }}</th>
                                <th class="px-3 py-2 font-medium text-right">{{ __('backoffice.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($agentRules as $rule)
                                <tr class="transition hover:bg-white/5">
                                    <td class="px-3 py-2">
                                        <p class="font-medium text-white">{{ $rule->title }}</p>
                                        <p class="text-[10px] text-slate-500">{{ $rule->category }} ·
                                            #{{ $rule->priority }}</p>
                                    </td>
                                    <td class="max-w-xs px-3 py-2">
                                        <p class="text-xs text-slate-300 line-clamp-2">{{ $rule->instruction }}</p>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if ($rule->type === 'forbidden')
                                            <span
                                                style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(239,68,68,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#fca5a5">FORBIDDEN</span>
                                        @else
                                            <span
                                                style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(59,130,246,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#93c5fd">GUIDELINE</span>
                                        @endif
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
                                            <a href="{{ route('backoffice.agent-rules.edit', [$agent, $rule]) }}"
                                                class="bo-btn-sm">
                                                {{ __('backoffice.common.edit') }}
                                            </a>
                                            <form method="POST"
                                                action="{{ route('backoffice.agent-rules.destroy', [$agent, $rule]) }}"
                                                onsubmit="return confirm('{{ __('backoffice.pages.chat_agents.delete_confirm_rule', ['title' => $rule->title]) }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="bo-btn-danger">{{ __('backoffice.common.delete') }}</button>
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
