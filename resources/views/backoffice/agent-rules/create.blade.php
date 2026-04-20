@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.agent_rules.new_title') . ' — ' . $chatAgent->name)
@section('page-title', __('backoffice.pages.agent_rules.page_title'))

@section('content')
    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between"
        class="rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">{{ __('backoffice.pages.agent_rules.new_title') }}</h1>
            <p class="text-xs text-slate-400">
                {{ __('backoffice.pages.agent_rules.new_subtitle', ['agent' => $chatAgent->name]) }}</p>
        </div>
        <a href="{{ route('backoffice.chat-agents.edit', $chatAgent) }}" class="bo-btn-secondary"
            style="font-size:0.75rem;padding:0.5rem 1rem">
            &larr; {{ __('backoffice.pages.agent_rules.back_to_agent') }}
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
        <form method="POST" action="{{ route('backoffice.agent-rules.store', $chatAgent) }}" class="space-y-4">
            @csrf

            <div>
                <label for="title" class="bo-label">{{ __('backoffice.pages.agent_rules.rule_title') }}</label>
                <input id="title" type="text" name="title" value="{{ old('title') }}" maxlength="100"
                    oninput="document.getElementById('title-count').textContent=this.value.length"
                    placeholder="e.g. Must use tools for account actions" />
                <p style="margin-top:0.25rem;font-size:0.7rem;color:#64748b"><span
                        id="title-count">{{ strlen(old('title', '')) }}</span>/100</p>
            </div>

            <div>
                <label for="instruction"
                    class="bo-label">{{ __('backoffice.pages.agent_rules.instruction_for_ai') }}</label>
                <p style="margin-bottom:0.375rem;font-size:0.75rem;color:#94a3b8">
                    {{ __('backoffice.pages.agent_rules.instruction_help') }}</p>
                <textarea id="instruction" name="instruction" rows="4" maxlength="500"
                    oninput="document.getElementById('instr-count').textContent=this.value.length"
                    placeholder="e.g. AI harus selalu menggunakan tool yang tersedia untuk cek status akun">{{ old('instruction') }}</textarea>
                <p style="margin-top:0.25rem;font-size:0.7rem;color:#64748b"><span
                        id="instr-count">{{ strlen(old('instruction', '')) }}</span>/500</p>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div>
                    <label for="type" class="bo-label">{{ __('backoffice.pages.agent_rules.type') }}</label>
                    <select id="type" name="type">
                        <option value="guideline" {{ old('type', 'guideline') === 'guideline' ? 'selected' : '' }}>
                            Guideline</option>
                        <option value="forbidden" {{ old('type') === 'forbidden' ? 'selected' : '' }}>Forbidden</option>
                    </select>
                </div>

                <div>
                    <label for="category" class="bo-label">{{ __('backoffice.pages.agent_rules.category') }}</label>
                    <select id="category" name="category">
                        <option value="behavior" {{ old('category', 'behavior') === 'behavior' ? 'selected' : '' }}>
                            Behavior</option>
                        <option value="security" {{ old('category') === 'security' ? 'selected' : '' }}>Security</option>
                        <option value="tool_usage" {{ old('category') === 'tool_usage' ? 'selected' : '' }}>Tool Usage
                        </option>
                        <option value="language" {{ old('category') === 'language' ? 'selected' : '' }}>Language</option>
                        <option value="formatting" {{ old('category') === 'formatting' ? 'selected' : '' }}>Formatting
                        </option>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div>
                    <label for="level" class="bo-label">{{ __('backoffice.pages.agent_rules.level') }}</label>
                    <select id="level" name="level">
                        <option value="info" {{ old('level') === 'info' ? 'selected' : '' }}>Info</option>
                        <option value="warning" {{ old('level', 'warning') === 'warning' ? 'selected' : '' }}>Warning
                        </option>
                        <option value="danger" {{ old('level') === 'danger' ? 'selected' : '' }}>Danger</option>
                    </select>
                </div>

                <div>
                    <label for="priority" class="bo-label">{{ __('backoffice.pages.agent_rules.priority') }}</label>
                    <input id="priority" type="number" name="priority" value="{{ old('priority', 100) }}" min="1"
                        max="9999" />
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:0.75rem;padding-top:0.5rem">
                <button type="submit" class="bo-btn-primary">{{ __('backoffice.pages.agent_rules.submit_rule') }}</button>
                <a href="{{ route('backoffice.chat-agents.edit', $chatAgent) }}"
                    class="bo-btn-secondary">{{ __('backoffice.common.cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
