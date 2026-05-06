@extends('backoffice.partials.layout')

@section('title', __('backoffice.common.edit') . ' — ' . $agent->name)
@section('page-title', __('backoffice.pages.chat_agents.page_title'))

@php
    $boActive = 'chat-agents';
@endphp

@section('content')
    <?php $isGeneralTab = ($activeTab ?? 'general') === 'general'; ?>
    <?php $isKnowledgeTab = ($activeTab ?? 'general') === 'knowledge-base'; ?>
    <?php $isRulesTab = ($activeTab ?? 'general') === 'rules'; ?>
    <?php $isToolsTab = ($activeTab ?? 'general') === 'tools'; ?>
    <?php $isSystemConfigTab = ($activeTab ?? 'general') === 'system-config'; ?>

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

    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-2">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('backoffice.chat-agents.edit', ['chatAgent' => $agent, 'tab' => 'general']) }}"
                class="rounded-lg px-4 py-2 text-xs font-semibold transition {{ $isGeneralTab ? 'bg-cyan-400 text-slate-950' : 'bg-white/5 text-slate-200 hover:bg-white/10' }}">
                General
            </a>
            <a href="{{ route('backoffice.chat-agents.edit', ['chatAgent' => $agent, 'tab' => 'rules']) }}"
                class="rounded-lg px-4 py-2 text-xs font-semibold transition {{ $isRulesTab ? 'bg-cyan-400 text-slate-950' : 'bg-white/5 text-slate-200 hover:bg-white/10' }}">
                {{ __('backoffice.pages.chat_agents.agent_rules') }}
            </a>
            <a href="{{ route('backoffice.chat-agents.edit', ['chatAgent' => $agent, 'tab' => 'knowledge-base']) }}"
                class="rounded-lg px-4 py-2 text-xs font-semibold transition {{ $isKnowledgeTab ? 'bg-cyan-400 text-slate-950' : 'bg-white/5 text-slate-200 hover:bg-white/10' }}">
                Knowledge Base
            </a>
            <a href="{{ route('backoffice.chat-agents.edit', ['chatAgent' => $agent, 'tab' => 'tools']) }}"
                class="rounded-lg px-4 py-2 text-xs font-semibold transition {{ $isToolsTab ? 'bg-cyan-400 text-slate-950' : 'bg-white/5 text-slate-200 hover:bg-white/10' }}">
                Tools
            </a>
            @can('manage settings')
                <a href="{{ route('backoffice.chat-agents.edit', ['chatAgent' => $agent, 'tab' => 'system-config']) }}"
                    class="rounded-lg px-4 py-2 text-xs font-semibold transition {{ $isSystemConfigTab ? 'bg-cyan-400 text-slate-950' : 'bg-white/5 text-slate-200 hover:bg-white/10' }}">
                    System Config
                </a>
            @endcan
        </div>
    </div>

    @if ($isGeneralTab)
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5">
            <form method="POST" action="{{ route('backoffice.chat-agents.update', $agent) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem">
                    <div>
                        <label for="name" class="bo-label">{{ __('backoffice.pages.chat_agents.agent_name') }}</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $agent->name) }}" />
                    </div>
                    <div>
                        <label for="model" class="bo-label">{{ __('backoffice.pages.chat_agents.model') }}</label>
                        <select id="model" name="model">
                            <?php $currentModel = old('model', $agent->model); ?>
                            <optgroup label="── GPT-5 Series ──">
                                <option value="gpt-5" {{ $currentModel === 'gpt-5' ? 'selected' : '' }}>gpt-5 — ★★★★★
                                    Latest flagship, most intelligent</option>
                            </optgroup>
                            <optgroup label="── Reasoning Models ──">
                                <option value="o4-mini" {{ $currentModel === 'o4-mini' ? 'selected' : '' }}>o4-mini — ★★★★★
                                    Advanced reasoning, best logic &amp; analysis</option>
                                <option value="o3-mini" {{ $currentModel === 'o3-mini' ? 'selected' : '' }}>o3-mini — ★★★★★
                                    Strong reasoning, cost-efficient</option>
                            </optgroup>
                            <optgroup label="── GPT-4.1 Series ──">
                                <option value="gpt-4.1" {{ $currentModel === 'gpt-4.1' ? 'selected' : '' }}>gpt-4.1 — ★★★★★
                                    Most capable, complex tasks</option>
                                <option value="gpt-4.1-mini" {{ $currentModel === 'gpt-4.1-mini' ? 'selected' : '' }}>
                                    gpt-4.1-mini — ★★★★☆ Recommended · balanced speed &amp; quality</option>
                                <option value="gpt-4.1-nano" {{ $currentModel === 'gpt-4.1-nano' ? 'selected' : '' }}>
                                    gpt-4.1-nano — ★★★☆☆ Fastest &amp; cheapest</option>
                            </optgroup>
                            <optgroup label="── GPT-4o Series ──">
                                <option value="gpt-4o" {{ $currentModel === 'gpt-4o' ? 'selected' : '' }}>gpt-4o — ★★★★☆
                                    Multimodal, high quality</option>
                                <option value="gpt-4o-mini" {{ $currentModel === 'gpt-4o-mini' ? 'selected' : '' }}>
                                    gpt-4o-mini — ★★★☆☆ Fast &amp; affordable</option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="description" class="bo-label">{{ __('backoffice.pages.chat_agents.description') }}</label>
                    <input id="description" type="text" name="description"
                        value="{{ old('description', $agent->description) }}" maxlength="200"
                        oninput="document.getElementById('desc-count').textContent=this.value.length"
                        placeholder="Short description of this agent's purpose" />
                    <p style="margin-top:0.25rem;font-size:0.7rem;color:#64748b"><span
                            id="desc-count">{{ strlen(old('description', $agent->description) ?? '') }}</span>/200</p>
                </div>

                <div>
                    <label for="system_prompt"
                        class="bo-label">{{ __('backoffice.pages.chat_agents.system_prompt') }}</label>
                    <p style="margin-bottom:0.5rem;font-size:0.75rem;color:#94a3b8">
                        {{ __('backoffice.pages.chat_agents.system_prompt_help') }}
                        Variabel: <code style="color:#22d3ee">{bot_name}</code>, <code
                            style="color:#22d3ee">{server_time}</code>, <code style="color:#22d3ee">{server_timezone}</code>
                    </p>
                    <textarea id="system_prompt" name="system_prompt" rows="14" maxlength="2000"
                        oninput="document.getElementById('prompt-count').textContent=this.value.length"
                        style="font-family:ui-monospace,SFMono-Regular,monospace;font-size:12px">{{ old('system_prompt', $agent->system_prompt) }}</textarea>
                    <p style="margin-top:0.25rem;font-size:0.7rem;color:#64748b"><span
                            id="prompt-count">{{ strlen(old('system_prompt', $agent->system_prompt) ?? '') }}</span>/2000
                    </p>
                </div>

                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;align-items:end">
                    <div>
                        <label for="max_tokens"
                            class="bo-label">{{ __('backoffice.pages.chat_agents.max_tokens') }}</label>
                        <input id="max_tokens" type="number" name="max_tokens"
                            value="{{ old('max_tokens', $agent->max_tokens) }}" min="50" max="4096" />
                    </div>
                    <div>
                        <label for="temperature" class="bo-label">{{ __('backoffice.pages.chat_agents.temp') }}</label>
                        <input id="temperature" type="number" name="temperature"
                            value="{{ old('temperature', $agent->temperature) }}" min="0" max="2"
                            step="0.1" />
                        <p style="margin-top:0.25rem;font-size:0.7rem;color:#64748b">
                            {{ __('backoffice.pages.chat_agents.temp_help') }}</p>
                    </div>
                    <div>
                        <label for="max_history_messages"
                            class="bo-label">{{ __('backoffice.pages.chat_agents.max_history_messages') }}</label>
                        <input id="max_history_messages" type="number" name="max_history_messages"
                            value="{{ old('max_history_messages', $agent->max_history_messages ?? 20) }}" min="2"
                            max="100" step="1" />
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
                </div>

                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;align-items:end">
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
                        <label for="timezone" class="bo-label">{{ __('backoffice.pages.chat_agents.timezone') }}</label>
                        <select id="timezone" name="timezone">
                            <?php $selectedTimezone = old('timezone', $agent->timezone ?? config('app.timezone', 'Asia/Jakarta')); ?>
                            @foreach ($timezoneOptions as $timezone)
                                <option value="{{ $timezone }}"
                                    {{ $selectedTimezone === $timezone ? 'selected' : '' }}>
                                    {{ $timezone }}
                                </option>
                            @endforeach
                        </select>
                        <p style="margin-top:0.25rem;font-size:0.7rem;color:#64748b">
                            {{ __('backoffice.pages.chat_agents.timezone_help') }}</p>
                    </div>
                </div>

                {{-- Agent Transfer Conditions --}}
                <div class="rounded-xl border border-slate-700/50 bg-slate-950/40 p-4 space-y-4">
                    <div>
                        <h3 class="text-xs font-semibold text-cyan-400 mb-1">
                            {{ __('backoffice.pages.chat_agents.agent_transfer_title') }}</h3>
                        <p class="text-[11px] text-slate-400">{!! __('backoffice.pages.chat_agents.agent_transfer_subtitle') !!}</p>
                    </div>
                    <div>
                        <textarea name="escalation_condition" rows="3" maxlength="3000"
                            placeholder="{{ __('backoffice.pages.chat_agents.agent_transfer_placeholder') }}"
                            class="block w-full rounded-lg border border-white/10 bg-slate-900 px-3 py-2 text-xs text-white outline-none transition focus:border-cyan-400 placeholder-slate-500 resize-y"
                            style="background-color:rgba(15,23,42,0.7)">{{ old('escalation_condition', $agent->escalation_condition) }}</textarea>
                        <p class="mt-1 text-[11px] text-slate-500">
                            {{ mb_strlen(old('escalation_condition', $agent->escalation_condition ?? '')) }}/3000</p>
                    </div>
                    <div class="flex flex-col gap-3">
                        <div>
                            <label class="bo-checkbox-label">
                                <input type="checkbox" name="stop_ai_after_handoff" value="1"
                                    {{ old('stop_ai_after_handoff', $agent->stop_ai_after_handoff) ? 'checked' : '' }} />
                                <span
                                    class="font-medium">{{ __('backoffice.pages.chat_agents.stop_ai_after_handoff_label') }}</span>
                            </label>
                            <p class="mt-0.5 ml-5 text-[11px] text-slate-400">{!! __('backoffice.pages.chat_agents.stop_ai_after_handoff_help') !!}</p>
                        </div>
                        <div>
                            <label class="bo-checkbox-label">
                                <input type="checkbox" name="silent_handoff" value="1"
                                    {{ old('silent_handoff', $agent->silent_handoff) ? 'checked' : '' }} />
                                <span
                                    class="font-medium">{{ __('backoffice.pages.chat_agents.silent_handoff_label') }}</span>
                            </label>
                            <p class="mt-0.5 ml-5 text-[11px] text-slate-400">{!! __('backoffice.pages.chat_agents.silent_handoff_help') !!}</p>
                        </div>
                    </div>
                </div>

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

                <div
                    style="display:flex;align-items:center;gap:0.75rem;border-top:1px solid rgba(51,65,85,0.5);padding-top:1rem">
                    <button type="submit" class="bo-btn-primary">{{ __('backoffice.common.save_changes') }}</button>
                    <a href="{{ route('backoffice.chat-agents.index') }}"
                        class="bo-btn-secondary">{{ __('backoffice.common.cancel') }}</a>
                </div>
            </form>
        </div>
    @endif

    @if ($isKnowledgeTab)
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5 space-y-5">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
                <div>
                    <h2 class="text-sm font-semibold text-white">Knowledge Base</h2>
                    <p class="text-xs text-slate-400">Assign atau lepas KB entries dari agent ini. Untuk edit konten,
                        gunakan menu Knowledge Base di sidebar.</p>
                </div>
                @can('manage agents')
                    <a href="{{ route('backoffice.knowledge-base.index') }}" class="bo-btn-sm">Manage Library ↗</a>
                @endcan
            </div>

            <div class="overflow-hidden rounded-xl border border-white/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs" style="width:100%">
                        <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-3 py-2 font-medium">Title</th>
                                <th class="px-3 py-2 font-medium">Source</th>
                                <th class="px-3 py-2 font-medium">Status</th>
                                <th class="px-3 py-2 font-medium">Updated</th>
                                <th class="px-3 py-2 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse ($knowledgeEntries as $entry)
                                <tr class="transition hover:bg-white/5">
                                    <td class="px-3 py-2">
                                        <span class="font-medium text-white">{{ $entry->title }}</span>
                                    </td>
                                    <td class="px-3 py-2 text-slate-300">{{ $entry->source }}</td>
                                    <td class="px-3 py-2">
                                        @if ($entry->is_active)
                                            <span
                                                style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(16,185,129,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#6ee7b7">ACTIVE</span>
                                        @else
                                            <span
                                                style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(239,68,68,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#fca5a5">INACTIVE</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-slate-300">{{ $entry->updated_at->format('d M Y H:i') }}
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:0.5rem">
                                            @can('manage agents')
                                                <form method="POST"
                                                    action="{{ route('backoffice.chat-agents.knowledge-base.detach', [$agent, $entry]) }}"
                                                    onsubmit="return confirm('Lepas \'{{ addslashes($entry->title) }}\' dari agent ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="bo-btn-danger"
                                                        style="font-size:0.7rem;padding:0.3rem 0.75rem">Remove</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-slate-400">No knowledge base
                                        entries for this agent yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Add from Library --}}
            @can('manage agents')
                @if (($knowledgeLibrary ?? collect())->isNotEmpty())
                    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5 space-y-4">
                        <div>
                            <h2 class="text-sm font-semibold text-white">Add from Library</h2>
                            <p class="text-xs text-slate-400">Knowledge base entries yang belum di-assign ke agent ini. Klik "+
                                Add" untuk assign.</p>
                        </div>
                        <div class="overflow-hidden rounded-xl border border-white/10">
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-xs" style="width:100%">
                                    <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                                        <tr>
                                            <th class="px-3 py-2 font-medium">Title</th>
                                            <th class="px-3 py-2 font-medium">Source</th>
                                            <th class="px-3 py-2 font-medium">Status</th>
                                            <th class="px-3 py-2 font-medium text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-white/5">
                                        @foreach ($knowledgeLibrary as $libEntry)
                                            <tr
                                                class="transition hover:bg-white/5 {{ $libEntry->is_active ? '' : 'opacity-40' }}">
                                                <td class="px-3 py-2 font-medium text-white">{{ $libEntry->title }}</td>
                                                <td class="px-3 py-2 text-slate-300">{{ $libEntry->source }}</td>
                                                <td class="px-3 py-2">
                                                    @if ($libEntry->is_active)
                                                        <span
                                                            style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(16,185,129,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#6ee7b7">ACTIVE</span>
                                                    @else
                                                        <span
                                                            style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(239,68,68,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#fca5a5">INACTIVE</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-right">
                                                    <form method="POST"
                                                        action="{{ route('backoffice.chat-agents.knowledge-base.attach', [$agent, $libEntry]) }}">
                                                        @csrf
                                                        <button type="submit" class="bo-btn-primary"
                                                            style="font-size:0.7rem;padding:0.3rem 0.75rem">+ Add</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            @endcan
        </div>
    @endif

    @if ($isRulesTab)
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5">
            <div
                style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
                <div>
                    <h2 class="text-sm font-semibold text-white">{{ __('backoffice.pages.chat_agents.agent_rules') }}</h2>
                    <p class="text-xs text-slate-400">Assign atau lepas rules dari agent ini. Untuk edit konten, gunakan
                        menu Agent Rules di sidebar.</p>
                </div>
                @can('manage agent-rules')
                    <a href="{{ route('backoffice.agent-rules.index') }}" class="bo-btn-sm">Manage Rules ↗</a>
                @endcan
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
                                    <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.chat_agents.rule_title') }}
                                    </th>
                                    <th class="px-3 py-2 font-medium">
                                        {{ __('backoffice.pages.chat_agents.rule_instruction') }}</th>
                                    <th class="px-3 py-2 font-medium text-center">
                                        {{ __('backoffice.pages.chat_agents.rule_type') }}</th>
                                    <th class="px-3 py-2 font-medium text-center">
                                        {{ __('backoffice.pages.chat_agents.rule_level') }}</th>
                                    <th class="px-3 py-2 font-medium text-center">{{ __('backoffice.common.status') }}
                                    </th>
                                    <th class="px-3 py-2 font-medium text-right">{{ __('backoffice.common.actions') }}
                                    </th>
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
                                            <div
                                                style="display:flex;align-items:center;justify-content:flex-end;gap:0.5rem">
                                                @can('manage agent-rules')
                                                    <form method="POST"
                                                        action="{{ route('backoffice.chat-agents.rules.detach', [$agent, $rule]) }}"
                                                        onsubmit="return confirm('Lepas rule \'{{ addslashes($rule->title) }}\' dari agent ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="bo-btn-danger"
                                                            style="font-size:0.7rem;padding:0.3rem 0.75rem">Remove</button>
                                                    </form>
                                                @endcan
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

        {{-- Add from Library --}}
        @can('manage agent-rules')
            @if (($rulesLibrary ?? collect())->isNotEmpty())
                <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5 space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold text-white">Add from Library</h2>
                        <p class="text-xs text-slate-400">Rules yang belum di-assign ke agent ini. Klik "+ Add" untuk assign.
                        </p>
                    </div>
                    <div class="overflow-hidden rounded-xl border border-white/10">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs" style="width:100%">
                                <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                                    <tr>
                                        <th class="px-3 py-2 font-medium">Title</th>
                                        <th class="px-3 py-2 font-medium">Type</th>
                                        <th class="px-3 py-2 font-medium">Level</th>
                                        <th class="px-3 py-2 font-medium">Category</th>
                                        <th class="px-3 py-2 font-medium text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    @foreach ($rulesLibrary as $libRule)
                                        <tr class="transition hover:bg-white/5 {{ $libRule->is_active ? '' : 'opacity-40' }}">
                                            <td class="px-3 py-2">
                                                <span class="font-medium text-white">{{ $libRule->title }}</span>
                                                <span
                                                    class="block text-[11px] text-slate-400 line-clamp-1">{{ $libRule->instruction }}</span>
                                            </td>
                                            <td class="px-3 py-2">
                                                @if ($libRule->type === 'forbidden')
                                                    <span
                                                        style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(239,68,68,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#fca5a5">FORBIDDEN</span>
                                                @else
                                                    <span
                                                        style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(59,130,246,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#93c5fd">GUIDELINE</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2">
                                                @if ($libRule->level === 'danger')
                                                    <span
                                                        style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(239,68,68,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#fca5a5">DANGER</span>
                                                @elseif ($libRule->level === 'warning')
                                                    <span
                                                        style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(245,158,11,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#fcd34d">WARNING</span>
                                                @else
                                                    <span
                                                        style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(59,130,246,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#93c5fd">INFO</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-slate-300">{{ $libRule->category }}</td>
                                            <td class="px-3 py-2 text-right">
                                                <form method="POST"
                                                    action="{{ route('backoffice.chat-agents.rules.attach', [$agent, $libRule]) }}">
                                                    @csrf
                                                    <button type="submit" class="bo-btn-primary"
                                                        style="font-size:0.7rem;padding:0.3rem 0.75rem">+ Add</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        @endcan
    @endif

    @if ($isToolsTab)
        {{-- Assigned Tools --}}
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5 space-y-5">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
                <div>
                    <h2 class="text-sm font-semibold text-white">Assigned Tools</h2>
                    <p class="text-xs text-slate-400">Tools yang aktif digunakan oleh agent ini.</p>
                </div>
            </div>

            @if (session('success'))
                <div
                    style="background:rgba(16,185,129,0.15);border:1px solid rgba(52,211,153,0.3);border-radius:0.75rem;padding:0.75rem 1rem;font-size:0.75rem;color:#6ee7b7">
                    {{ session('success') }}
                </div>
            @endif

            @if (($agentTools ?? collect())->isEmpty())
                <p class="text-xs text-slate-400">Belum ada tool yang di-assign ke agent ini.</p>
            @else
                <div class="overflow-hidden rounded-xl border border-white/10">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs" style="width:100%">
                            <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th class="px-3 py-2 font-medium">Name</th>
                                    <th class="px-3 py-2 font-medium">Tool Key</th>
                                    <th class="px-3 py-2 font-medium">Type</th>
                                    <th class="px-3 py-2 font-medium">Category</th>
                                    <th class="px-3 py-2 font-medium">Status</th>
                                    @can('manage agents')
                                        <th class="px-3 py-2 font-medium text-right">Actions</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @foreach ($agentTools as $tool)
                                    <tr class="transition hover:bg-white/5 {{ $tool->is_enabled ? '' : 'opacity-50' }}">
                                        <td class="px-3 py-2">
                                            <span class="font-medium text-white">{{ $tool->display_name }}</span>
                                            @if ($tool->description)
                                                <span
                                                    class="block text-[11px] text-slate-400">{{ Str::limit($tool->description, 60) }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-slate-300"
                                            style="font-family:ui-monospace,monospace;font-size:11px">
                                            {{ $tool->tool_name }}</td>
                                        <td class="px-3 py-2">
                                            @if ($tool->type === 'function')
                                                <span
                                                    style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(139,92,246,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#c4b5fd">FUNCTION</span>
                                            @elseif ($tool->type === 'api')
                                                <span
                                                    style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(59,130,246,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#93c5fd">API</span>
                                            @else
                                                <span
                                                    style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(100,116,139,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#94a3b8">{{ strtoupper((string) ($tool->type ?: 'api')) }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            <span
                                                style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(6,182,212,0.15);padding:2px 10px;font-size:11px;font-weight:600;color:#67e8f9">{{ strtoupper((string) ($tool->category ?: 'general')) }}</span>
                                        </td>
                                        <td class="px-3 py-2">
                                            @if ($tool->is_enabled)
                                                <span
                                                    style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(16,185,129,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#6ee7b7;border:1px solid rgba(52,211,153,0.3)">Active</span>
                                            @else
                                                <span
                                                    style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(239,68,68,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#fca5a5;border:1px solid rgba(248,113,113,0.3)">Inactive</span>
                                            @endif
                                        </td>
                                        @can('manage agents')
                                            <td class="px-3 py-2 text-right">
                                                <form method="POST"
                                                    action="{{ route('backoffice.chat-agents.tools.detach', [$agent, $tool]) }}"
                                                    onsubmit="return confirm('Hapus \'{{ addslashes($tool->display_name) }}\' dari agent ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="bo-btn-danger"
                                                        style="font-size:0.7rem;padding:0.3rem 0.75rem">Remove</button>
                                                </form>
                                            </td>
                                        @endcan
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Available Tools (not yet assigned) --}}
        @can('manage agents')
            @php
                $assignedIds = ($agentTools ?? collect())->pluck('id');
                $unassignedTools = ($availableTools ?? collect())
                    ->filter(fn($t) => !$assignedIds->contains($t->id))
                    ->values();
            @endphp
            @if ($unassignedTools->isNotEmpty())
                <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5 space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold text-white">Add Tools</h2>
                        <p class="text-xs text-slate-400">Tool lain yang tersedia. Klik "+ Add" untuk assign ke agent ini.</p>
                    </div>
                    <div class="overflow-hidden rounded-xl border border-white/10">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs" style="width:100%">
                                <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                                    <tr>
                                        <th class="px-3 py-2 font-medium">Name</th>
                                        <th class="px-3 py-2 font-medium">Tool Key</th>
                                        <th class="px-3 py-2 font-medium">Category</th>
                                        <th class="px-3 py-2 font-medium text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    @foreach ($unassignedTools as $tool)
                                        <tr class="transition hover:bg-white/5 {{ $tool->is_enabled ? '' : 'opacity-40' }}">
                                            <td class="px-3 py-2">
                                                <span class="font-medium text-white">{{ $tool->display_name }}</span>
                                                @if ($tool->description)
                                                    <span
                                                        class="block text-[11px] text-slate-400">{{ Str::limit($tool->description, 60) }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-slate-300"
                                                style="font-family:ui-monospace,monospace;font-size:11px">
                                                {{ $tool->tool_name }}</td>
                                            <td class="px-3 py-2">
                                                <span
                                                    style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(6,182,212,0.15);padding:2px 10px;font-size:11px;font-weight:600;color:#67e8f9">{{ strtoupper((string) ($tool->category ?: 'general')) }}</span>
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <form method="POST"
                                                    action="{{ route('backoffice.chat-agents.tools.attach', [$agent, $tool]) }}">
                                                    @csrf
                                                    <button type="submit" class="bo-btn-primary"
                                                        style="font-size:0.7rem;padding:0.3rem 0.75rem">+ Add</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        @endcan

    @endif

    @if ($isSystemConfigTab)
        @can('manage settings')
            <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5 space-y-5">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem">
                    <div>
                        <h2 class="text-sm font-semibold text-white">System Config</h2>
                        <p class="text-xs text-slate-400">Global key / value configuration entries. Accessible via <code
                                style="color:#22d3ee">SystemConfig::getValue('key')</code>.</p>
                    </div>
                    <form method="POST" action="{{ route('backoffice.system-config.sync-all') }}" style="flex-shrink:0">
                        @csrf
                        <input type="hidden" name="from_agent" value="{{ $agent->id }}">
                        <button type="submit" class="bo-btn-sm"
                            title="Re-resolve all DataModel lookup entries and store the snapshot value">
                            ↻ Sync All
                        </button>
                    </form>
                </div>

                @if (session('success'))
                    <div
                        style="background:rgba(16,185,129,0.15);border:1px solid rgba(52,211,153,0.3);border-radius:0.75rem;padding:0.75rem 1rem;font-size:0.75rem;color:#6ee7b7">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="GET" action="{{ route('backoffice.chat-agents.edit', $agent) }}"
                    class="rounded-xl border border-slate-700/50 bg-slate-950/40 p-3"
                    style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap">
                    <input type="hidden" name="tab" value="system-config">
                    <label for="sc_search" class="bo-label" style="margin:0;min-width:92px">Search</label>
                    <input id="sc_search" type="text" name="sc_search" value="{{ $systemConfigSearch ?? '' }}"
                        placeholder="Search key, value, description, or lookup fields..." style="flex:1;min-width:220px" />
                    <button type="submit" class="bo-btn-sm">Filter</button>
                    @if (!empty($systemConfigSearch))
                        <a class="bo-btn-secondary"
                            href="{{ route('backoffice.chat-agents.edit', ['chatAgent' => $agent, 'tab' => 'system-config']) }}"
                            style="font-size:0.75rem;padding:0.45rem 0.75rem">Reset</a>
                    @endif
                </form>

                {{-- Existing rows --}}
                <div class="overflow-hidden rounded-xl border border-white/10">
                    <table class="min-w-full text-xs" style="width:100%">
                        <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-3 py-2 font-medium" style="width:22%">Key</th>
                                <th class="px-3 py-2 font-medium">Value</th>
                                <th class="px-3 py-2 font-medium">Description</th>
                                <th class="px-3 py-2 font-medium">Source</th>
                                <th class="px-3 py-2 font-medium text-right" style="width:120px">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5" id="sc-table-body">
                            @forelse ($systemConfigs as $sc)
                                <tr class="transition hover:bg-white/5" id="sc-row-{{ $sc->id }}">
                                    <td class="px-3 py-2 font-mono text-slate-200">{{ $sc->key }}</td>
                                    <td class="px-3 py-2 text-slate-300" style="word-break:break-all">
                                        @if (($sc->source_type ?? 'manual') === 'datamodel_lookup')
                                            @if ($sc->value !== null && $sc->value !== '')
                                                <span class="text-emerald-300">{{ $sc->value }}</span>
                                                <br>
                                            @endif
                                            <span class="text-slate-500"
                                                style="font-size:10px">{{ $sc->lookup_field }}={{ $sc->lookup_value }} →
                                                {{ $sc->result_field }}</span>
                                        @else
                                            {{ $sc->value }}
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-slate-400" style="word-break:break-word">
                                        {{ $sc->description ?: '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-slate-400">
                                        @if (($sc->source_type ?? 'manual') === 'datamodel_lookup')
                                            DM #{{ $sc->data_model_id }}
                                        @else
                                            Manual
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <div style="display:inline-flex;align-items:center;gap:0.375rem">
                                            <button type="button"
                                                onclick="scOpenEdit({{ $sc->id }}, {{ json_encode($sc->key) }}, {{ json_encode($sc->value) }}, {{ json_encode($sc->description) }}, {{ json_encode($sc->source_type ?? 'manual') }}, {{ json_encode($sc->data_model_id) }}, {{ json_encode($sc->lookup_field) }}, {{ json_encode($sc->lookup_value) }}, {{ json_encode($sc->result_field) }})"
                                                class="bo-btn-sm" style="white-space:nowrap">
                                                Edit
                                            </button>
                                            <form method="POST"
                                                action="{{ route('backoffice.system-config.destroy', $sc) }}"
                                                onsubmit="return confirm('Delete config \'{{ addslashes($sc->key) }}\'?')"
                                                style="margin:0">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="from_agent" value="{{ $agent->id }}">
                                                <button type="submit" class="bo-btn-danger" style="white-space:nowrap">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-slate-400">No system config entries
                                        yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($systemConfigs, 'links'))
                    <div class="pt-1">
                        {{ $systemConfigs->onEachSide(1)->links() }}
                    </div>
                @endif

                {{-- Edit inline form (hidden until Edit clicked) --}}
                <div id="sc-edit-panel" style="display:none"
                    class="rounded-xl border border-slate-700/50 bg-slate-950/40 p-4 space-y-3">
                    <h3 class="text-sm font-semibold text-white">Edit Entry</h3>
                    <form id="sc-edit-form" method="POST" action="" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="from_agent" value="{{ $agent->id }}">
                        <div>
                            <label class="bo-label" for="sc-edit-source-type">Source Type</label>
                            <select id="sc-edit-source-type" name="source_type" onchange="scSyncEditSourceType()">
                                <option value="manual">Manual</option>
                                <option value="datamodel_lookup">DataModel Lookup</option>
                            </select>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 2fr;gap:0.75rem;align-items:start">
                            <div>
                                <label class="bo-label" for="sc-edit-key">Key</label>
                                <input type="text" name="key" id="sc-edit-key" required maxlength="191" />
                            </div>
                            <div id="sc-edit-manual-wrap">
                                <label class="bo-label" for="sc-edit-value">Value</label>
                                <textarea name="value" id="sc-edit-value" rows="3"></textarea>
                            </div>
                        </div>
                        <div id="sc-edit-dm-wrap" class="rounded-lg border border-white/10 bg-slate-900/50 p-3"
                            style="display:none">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;align-items:start">
                                <div>
                                    <label class="bo-label" for="sc-edit-data-model-id">DataModel</label>
                                    <select id="sc-edit-data-model-id" name="data_model_id"
                                        onchange="scPopulateEditFields()">
                                        <option value="">-- Select DataModel --</option>
                                        @foreach ($systemConfigDataModels as $dm)
                                            <option value="{{ $dm->id }}">{{ $dm->model_name }}
                                                ({{ $dm->table_name }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="bo-label" for="sc-edit-lookup-value">Lookup Value</label>
                                    <input id="sc-edit-lookup-value" type="text" name="lookup_value"
                                        placeholder="e.g. mindeposit" />
                                </div>
                                <div>
                                    <label class="bo-label" for="sc-edit-lookup-field">Lookup Field</label>
                                    <select id="sc-edit-lookup-field" name="lookup_field">
                                        <option value="">-- Select Field --</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="bo-label" for="sc-edit-result-field">Result Field</label>
                                    <select id="sc-edit-result-field" name="result_field">
                                        <option value="">-- Select Field --</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="bo-label" for="sc-edit-description">Description</label>
                            <textarea name="description" id="sc-edit-description" rows="2" maxlength="1000"
                                placeholder="Short explanation of what this key is used for..."></textarea>
                        </div>
                        <div style="display:flex;gap:0.5rem">
                            <button type="submit" class="bo-btn-primary">Save</button>
                            <button type="button" onclick="document.getElementById('sc-edit-panel').style.display='none'"
                                class="bo-btn-secondary">Cancel</button>
                        </div>
                    </form>
                </div>

                {{-- Add new --}}
                <div class="rounded-xl border border-slate-700/50 bg-slate-950/40 p-4 space-y-3">
                    <h3 class="text-sm font-semibold text-white">Add New Entry</h3>
                    <form method="POST" action="{{ route('backoffice.system-config.store') }}" class="space-y-3">
                        @csrf
                        <input type="hidden" name="from_agent" value="{{ $agent->id }}">
                        <div>
                            <label class="bo-label" for="sc-create-source-type">Source Type</label>
                            <select id="sc-create-source-type" name="source_type" onchange="scSyncCreateSourceType()">
                                <option value="manual" {{ old('source_type', 'manual') === 'manual' ? 'selected' : '' }}>
                                    Manual</option>
                                <option value="datamodel_lookup"
                                    {{ old('source_type') === 'datamodel_lookup' ? 'selected' : '' }}>DataModel Lookup
                                </option>
                            </select>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 2fr;gap:0.75rem;align-items:start">
                            <div>
                                <label class="bo-label" for="sc-create-key">Key</label>
                                <input id="sc-create-key" type="text" name="key" required maxlength="191"
                                    placeholder="e.g. welcome_message" />
                                @error('key')
                                    <p style="margin-top:0.25rem;font-size:0.7rem;color:#f87171">{{ $message }}</p>
                                @enderror
                            </div>
                            <div id="sc-create-manual-wrap">
                                <label class="bo-label" for="sc-create-value">Value</label>
                                <textarea id="sc-create-value" name="value" rows="3" placeholder="Config value...">{{ old('value') }}</textarea>
                            </div>
                        </div>
                        <div id="sc-create-dm-wrap" class="rounded-lg border border-white/10 bg-slate-900/50 p-3"
                            style="display:none">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;align-items:start">
                                <div>
                                    <label class="bo-label" for="sc-create-data-model-id">DataModel</label>
                                    <select id="sc-create-data-model-id" name="data_model_id"
                                        onchange="scPopulateCreateFields()">
                                        <option value="">-- Select DataModel --</option>
                                        @foreach ($systemConfigDataModels as $dm)
                                            <option value="{{ $dm->id }}"
                                                {{ (string) old('data_model_id') === (string) $dm->id ? 'selected' : '' }}>
                                                {{ $dm->model_name }} ({{ $dm->table_name }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('data_model_id')
                                        <p style="margin-top:0.25rem;font-size:0.7rem;color:#f87171">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="bo-label" for="sc-create-lookup-value">Lookup Value</label>
                                    <input id="sc-create-lookup-value" type="text" name="lookup_value"
                                        value="{{ old('lookup_value') }}" placeholder="e.g. mindeposit" />
                                    @error('lookup_value')
                                        <p style="margin-top:0.25rem;font-size:0.7rem;color:#f87171">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="bo-label" for="sc-create-lookup-field">Lookup Field</label>
                                    <select id="sc-create-lookup-field" name="lookup_field">
                                        <option value="">-- Select Field --</option>
                                    </select>
                                    @error('lookup_field')
                                        <p style="margin-top:0.25rem;font-size:0.7rem;color:#f87171">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="bo-label" for="sc-create-result-field">Result Field</label>
                                    <select id="sc-create-result-field" name="result_field">
                                        <option value="">-- Select Field --</option>
                                    </select>
                                    @error('result_field')
                                        <p style="margin-top:0.25rem;font-size:0.7rem;color:#f87171">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="bo-label" for="sc-create-description">Description</label>
                            <textarea id="sc-create-description" name="description" rows="2" maxlength="1000"
                                placeholder="Short explanation of what this key is used for...">{{ old('description') }}</textarea>
                            @error('description')
                                <p style="margin-top:0.25rem;font-size:0.7rem;color:#f87171">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="bo-btn-primary">+ Add</button>
                    </form>
                </div>
            </div>
        @endcan
    @endif
@endsection

@if ($isSystemConfigTab)
    <script>
        const scDataModels = @json(
            $systemConfigDataModels->mapWithKeys(function ($dm) {
                return [
                    (string) $dm->id => [
                        'fields' => array_keys((array) ($dm->fields ?? [])),
                    ],
                ];
            }));

        function scSetFieldOptions(selectEl, fields, selectedValue = '') {
            if (!selectEl) return;
            const opts = ['<option value="">-- Select Field --</option>'];
            fields.forEach((f) => {
                const sel = String(f) === String(selectedValue) ? ' selected' : '';
                opts.push(`<option value="${f}"${sel}>${f}</option>`);
            });
            selectEl.innerHTML = opts.join('');
        }

        function scPopulateCreateFields() {
            const dmId = document.getElementById('sc-create-data-model-id')?.value ?? '';
            const fields = scDataModels[String(dmId)]?.fields ?? [];
            scSetFieldOptions(document.getElementById('sc-create-lookup-field'), fields,
                @json(old('lookup_field')));
            scSetFieldOptions(document.getElementById('sc-create-result-field'), fields,
                @json(old('result_field')));
        }

        function scPopulateEditFields(selectedLookup = '', selectedResult = '') {
            const dmId = document.getElementById('sc-edit-data-model-id')?.value ?? '';
            const fields = scDataModels[String(dmId)]?.fields ?? [];
            scSetFieldOptions(document.getElementById('sc-edit-lookup-field'), fields, selectedLookup);
            scSetFieldOptions(document.getElementById('sc-edit-result-field'), fields, selectedResult);
        }

        function scSyncCreateSourceType() {
            const sourceType = document.getElementById('sc-create-source-type')?.value ?? 'manual';
            document.getElementById('sc-create-manual-wrap').style.display = sourceType === 'manual' ? '' : 'none';
            document.getElementById('sc-create-dm-wrap').style.display = sourceType === 'datamodel_lookup' ? '' : 'none';
            if (sourceType === 'datamodel_lookup') scPopulateCreateFields();
        }

        function scSyncEditSourceType() {
            const sourceType = document.getElementById('sc-edit-source-type')?.value ?? 'manual';
            document.getElementById('sc-edit-manual-wrap').style.display = sourceType === 'manual' ? '' : 'none';
            document.getElementById('sc-edit-dm-wrap').style.display = sourceType === 'datamodel_lookup' ? '' : 'none';
            if (sourceType === 'datamodel_lookup') scPopulateEditFields(
                document.getElementById('sc-edit-lookup-field')?.value ?? '',
                document.getElementById('sc-edit-result-field')?.value ?? ''
            );
        }

        function scOpenEdit(id, key, value, description, sourceType, dataModelId, lookupField, lookupValue, resultField) {
            const panel = document.getElementById('sc-edit-panel');
            const form = document.getElementById('sc-edit-form');
            const actionTemplate = @json(route('backoffice.system-config.update', ['systemConfig' => '__ID__']));
            form.action = actionTemplate.replace('__ID__', String(id));
            document.getElementById('sc-edit-key').value = key;
            document.getElementById('sc-edit-value').value = value ?? '';
            document.getElementById('sc-edit-description').value = description ?? '';
            document.getElementById('sc-edit-source-type').value = sourceType ?? 'manual';
            document.getElementById('sc-edit-data-model-id').value = dataModelId ?? '';
            document.getElementById('sc-edit-lookup-value').value = lookupValue ?? '';
            scPopulateEditFields(lookupField ?? '', resultField ?? '');
            scSyncEditSourceType();
            panel.style.display = '';
            panel.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            scSyncCreateSourceType();
        });
    </script>
@endif
