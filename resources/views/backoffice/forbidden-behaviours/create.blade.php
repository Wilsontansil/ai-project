@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.forbidden.new_title') . ' — ' . $chatAgent->name)
@section('page-title', __('backoffice.pages.forbidden.page_title'))

@section('content')
    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between"
        class="rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">{{ __('backoffice.pages.forbidden.new_title') }}</h1>
            <p class="text-xs text-slate-400">
                {{ __('backoffice.pages.forbidden.new_subtitle', ['agent' => $chatAgent->name]) }}</p>
        </div>
        <a href="{{ route('backoffice.chat-agents.edit', $chatAgent) }}" class="bo-btn-secondary"
            style="font-size:0.75rem;padding:0.5rem 1rem">
            &larr; {{ __('backoffice.pages.forbidden.back_to_agent') }}
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
        <form method="POST" action="{{ route('backoffice.forbidden.store', $chatAgent) }}" class="space-y-4">
            @csrf

            <div>
                <label for="title" class="bo-label">{{ __('backoffice.pages.forbidden.rule_title') }}</label>
                <input id="title" type="text" name="title" value="{{ old('title') }}"
                    placeholder="e.g. Forbidden to create player without confirmation" />
            </div>

            <div>
                <label for="instruction" class="bo-label">{{ __('backoffice.pages.forbidden.instruction_for_ai') }}</label>
                <p style="margin-bottom:0.375rem;font-size:0.75rem;color:#94a3b8">
                    {{ __('backoffice.pages.forbidden.instruction_help') }}</p>
                <textarea id="instruction" name="instruction" rows="4"
                    placeholder="e.g. AI dilarang membuat data player tanpa konfirmasi dari player">{{ old('instruction') }}</textarea>
            </div>

            <div>
                <label for="level" class="bo-label">{{ __('backoffice.pages.forbidden.level') }}</label>
                <select id="level" name="level">
                    <option value="info" {{ old('level') === 'info' ? 'selected' : '' }}>Info</option>
                    <option value="warning" {{ old('level', 'warning') === 'warning' ? 'selected' : '' }}>Warning</option>
                    <option value="danger" {{ old('level') === 'danger' ? 'selected' : '' }}>Danger</option>
                </select>
            </div>

            <div style="display:flex;align-items:center;gap:0.75rem;padding-top:0.5rem">
                <button type="submit" class="bo-btn-primary">{{ __('backoffice.pages.forbidden.submit_rule') }}</button>
                <a href="{{ route('backoffice.chat-agents.edit', $chatAgent) }}"
                    class="bo-btn-secondary">{{ __('backoffice.common.cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
