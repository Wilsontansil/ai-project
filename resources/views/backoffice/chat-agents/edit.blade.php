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
                            <option value="gpt-4.1-mini" {{ $currentModel === 'gpt-4.1-mini' ? 'selected' : '' }}>
                                gpt-4.1-mini
                            </option>
                            <option value="gpt-4.1" {{ $currentModel === 'gpt-4.1' ? 'selected' : '' }}>gpt-4.1</option>
                            <option value="gpt-4.1-nano" {{ $currentModel === 'gpt-4.1-nano' ? 'selected' : '' }}>
                                gpt-4.1-nano
                            </option>
                            <option value="gpt-4o" {{ $currentModel === 'gpt-4o' ? 'selected' : '' }}>gpt-4o</option>
                            <option value="gpt-4o-mini" {{ $currentModel === 'gpt-4o-mini' ? 'selected' : '' }}>gpt-4o-mini
                            </option>
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
                    <p class="text-xs text-slate-400">Klik View/Edit pada tabel untuk melihat atau mengubah detail entry.
                    </p>
                </div>
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
                                            <a class="bo-btn-sm"
                                                href="{{ route('backoffice.chat-agents.edit', ['chatAgent' => $agent, 'tab' => 'knowledge-base', 'mode' => 'view', 'kb' => $entry->id]) }}">View</a>
                                            <a class="bo-btn-sm"
                                                href="{{ route('backoffice.chat-agents.edit', ['chatAgent' => $agent, 'tab' => 'knowledge-base', 'mode' => 'edit', 'kb' => $entry->id]) }}">Edit</a>
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

            @if (($knowledgeMode ?? 'view') === 'edit' && ($selectedKnowledge ?? null) !== null)
                <div class="rounded-xl border border-slate-700/50 bg-slate-950/40 p-4">
                    <h3 class="mb-3 text-sm font-semibold text-white">Edit Knowledge Entry</h3>
                    <form method="POST"
                        action="{{ route('backoffice.chat-agents.knowledge-base.update', [$agent, $selectedKnowledge]) }}"
                        enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <div style="display:grid;grid-template-columns:2fr 1fr;gap:1rem">
                            <div>
                                <label for="kb_edit_title" class="bo-label">Title</label>
                                <input id="kb_edit_title" type="text" name="title"
                                    value="{{ $selectedKnowledge->title }}" />
                            </div>
                            <div>
                                <label for="kb_edit_file" class="bo-label">Re-upload .txt</label>
                                <input id="kb_edit_file" type="file" name="file" accept=".txt" />
                            </div>
                        </div>
                        <div>
                            <label for="kb_edit_content" class="bo-label">Content</label>
                            <textarea id="kb_edit_content" name="content" rows="8">{{ $selectedKnowledge->content }}</textarea>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400">
                            <span>Source: {{ $selectedKnowledge->source }}</span>
                            @if ($selectedKnowledge->file_name)
                                <span>File: {{ $selectedKnowledge->file_name }}</span>
                            @endif
                            <span>Updated: {{ $selectedKnowledge->updated_at->format('d M Y H:i') }}</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <label class="bo-checkbox-label" style="max-width:max-content">
                                <input type="checkbox" name="is_active" value="1"
                                    {{ $selectedKnowledge->is_active ? 'checked' : '' }} />
                                <span>{{ __('backoffice.common.active') }}</span>
                            </label>
                            <button type="submit"
                                class="bo-btn-primary">{{ __('backoffice.common.save_changes') }}</button>
                        </div>
                    </form>

                    <form method="POST"
                        action="{{ route('backoffice.chat-agents.knowledge-base.destroy', [$agent, $selectedKnowledge]) }}"
                        class="mt-3" onsubmit="return confirm('Delete this knowledge entry?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bo-btn-danger">{{ __('backoffice.common.delete') }}</button>
                    </form>
                </div>
            @elseif (($knowledgeMode ?? 'view') === 'view' && ($selectedKnowledge ?? null) !== null)
                <div class="rounded-xl border border-slate-700/50 bg-slate-950/40 p-4">
                    <h3 class="mb-3 text-sm font-semibold text-white">View Knowledge Entry</h3>
                    <div class="space-y-3 text-sm text-slate-200">
                        <div>
                            <p class="text-xs text-slate-400">Title</p>
                            <p>{{ $selectedKnowledge->title }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Content</p>
                            <div class="whitespace-pre-wrap rounded-lg border border-white/10 bg-slate-900/70 p-3">
                                {{ $selectedKnowledge->content }}</div>
                        </div>
                        <div class="flex flex-wrap gap-4 text-xs text-slate-400">
                            <span>Source: {{ $selectedKnowledge->source }}</span>
                            @if ($selectedKnowledge->file_name)
                                <span>File: {{ $selectedKnowledge->file_name }}</span>
                            @endif
                            <span>Status: {{ $selectedKnowledge->is_active ? 'ACTIVE' : 'INACTIVE' }}</span>
                            <span>Updated: {{ $selectedKnowledge->updated_at->format('d M Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <div class="rounded-xl border border-slate-700/50 bg-slate-950/40 p-4">
                <h3 class="mb-3 text-sm font-semibold text-white">Add New Knowledge Entry</h3>
                <form method="POST" action="{{ route('backoffice.chat-agents.knowledge-base.store', $agent) }}"
                    enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div style="display:grid;grid-template-columns:2fr 1fr;gap:1rem">
                        <div>
                            <label for="kb_title" class="bo-label">Title</label>
                            <input id="kb_title" type="text" name="title" value="{{ old('title') }}"
                                placeholder="e.g. Cara klaim bonus" />
                        </div>
                        <div>
                            <label for="kb_file" class="bo-label">Upload .txt (optional)</label>
                            <input id="kb_file" type="file" name="file" accept=".txt" />
                        </div>
                    </div>
                    <div>
                        <label for="kb_content" class="bo-label">Content</label>
                        <textarea id="kb_content" name="content" rows="6" placeholder="Knowledge text...">{{ old('content') }}</textarea>
                    </div>
                    <label class="bo-checkbox-label" style="max-width:max-content">
                        <input type="checkbox" name="is_active" value="1"
                            {{ old('is_active', true) ? 'checked' : '' }} />
                        <span>{{ __('backoffice.common.active') }}</span>
                    </label>
                    <button type="submit" class="bo-btn-primary">+ Add Knowledge</button>
                </form>
            </div>
        </div>
    @endif

    @if ($isRulesTab)
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
                <div>
                    <h2 class="text-sm font-semibold text-white">{{ __('backoffice.pages.chat_agents.agent_rules') }}</h2>
                    <p class="text-xs text-slate-400">{{ __('backoffice.pages.chat_agents.agent_rules_subtitle') }}</p>
                </div>
                @can('manage agent-rules')
                    <a href="{{ route('backoffice.agent-rules.create', $agent) }}" class="bo-btn-primary"
                        style="font-size:0.75rem;padding:0.5rem 1rem">
                        + {{ __('backoffice.pages.chat_agents.new_rule') }}
                    </a>
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
    @endif

    @if ($isToolsTab)
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5 space-y-5">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
                <div>
                    <h2 class="text-sm font-semibold text-white">Tools</h2>
                    <p class="text-xs text-slate-400">All tools are available to this agent by default.</p>
                </div>
                @can('manage tools')
                    <a href="{{ route('backoffice.tools.create') }}" class="bo-btn-primary"
                        style="font-size:0.75rem;padding:0.5rem 1rem">+ New Tool</a>
                @endcan
            </div>

            @if (($availableTools ?? collect())->isEmpty())
                <p class="text-xs text-slate-400">No tools available.
                    @can('manage tools')
                        <a href="{{ route('backoffice.tools.create') }}" class="text-cyan-400 hover:underline">Create
                            the first tool</a>.
                    @endcan
                </p>
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
                                    @can('manage tools')
                                        <th class="px-3 py-2 font-medium text-right">Actions</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @foreach ($availableTools as $tool)
                                    <tr class="transition hover:bg-white/5">
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
                                            <?php $toolType = strtoupper((string) ($tool->type ?: 'api')); ?>
                                            @if ($tool->type === 'function')
                                                <span
                                                    style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(139,92,246,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#c4b5fd">FUNCTION</span>
                                            @elseif ($tool->type === 'api')
                                                <span
                                                    style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(59,130,246,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#93c5fd">API</span>
                                            @else
                                                <span
                                                    style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(100,116,139,0.2);padding:2px 10px;font-size:11px;font-weight:600;color:#94a3b8">{{ $toolType }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            <span
                                                style="display:inline-flex;align-items:center;border-radius:9999px;background:rgba(6,182,212,0.15);padding:2px 10px;font-size:11px;font-weight:600;color:#67e8f9">{{ strtoupper((string) ($tool->category ?: 'general')) }}</span>
                                        </td>
                                        @can('manage tools')
                                            <td class="px-3 py-2 text-right">
                                                <div
                                                    style="display:flex;align-items:center;justify-content:flex-end;gap:0.5rem">
                                                    <a href="{{ route('backoffice.tools.edit', $tool) }}"
                                                        class="bo-btn-sm">Edit</a>
                                                    <form method="POST"
                                                        action="{{ route('backoffice.tools.destroy', $tool) }}"
                                                        onsubmit="return confirm('Delete tool \'{{ addslashes($tool->display_name) }}\'?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="bo-btn-danger">Delete</button>
                                                    </form>
                                                </div>
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
    @endif
@endsection
