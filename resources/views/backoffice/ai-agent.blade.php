@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.chat_agents.title'))
@section('page-title', __('backoffice.pages.chat_agents.page_title'))

@php($boActive = 'ai-agent')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">{{ __('backoffice.pages.chat_agents.title') }}</h1>
            <p class="text-xs text-slate-400">Informasi dan konfigurasi AI agent.</p>
        </div>
    </div>

    {{-- AI Agent Info --}}
    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5">
        <h2 class="mb-4 text-sm font-semibold">AI Agent Info</h2>

        @if (session('success'))
            <div class="mb-4 rounded-xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-xs text-emerald-100">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('backoffice.ai-agent.update') }}">
            @csrf
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
                style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0.75rem">
                <div class="rounded-xl border border-slate-700/70 bg-slate-950/60 px-3 py-2.5">
                    <label for="bot_name" class="text-[11px] text-slate-400">Bot Name</label>
                    <input id="bot_name" type="text" name="bot_name" value="{{ $aiInfo['bot_name'] }}"
                        class="mt-1 block w-full rounded-lg border border-white/10 bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-white outline-none transition focus:border-cyan-400"
                        style="background-color:rgba(15,23,42,0.7);color:#e2e8f0;font-size:12px" />
                </div>
                <div class="rounded-xl border border-slate-700/70 bg-slate-950/60 px-3 py-2.5">
                    <p class="text-[11px] text-slate-400">Model</p>
                    <p class="mt-1 text-xs font-semibold text-white">{{ $aiInfo['model'] }}</p>
                </div>
                <div class="rounded-xl border border-slate-700/70 bg-slate-950/60 px-3 py-2.5">
                    <p class="text-[11px] text-slate-400">Max Tokens</p>
                    <p class="mt-1 text-xs font-semibold text-white">{{ $aiInfo['max_tokens'] }}</p>
                </div>
                <div class="rounded-xl border border-slate-700/70 bg-slate-950/60 px-3 py-2.5">
                    <p class="text-[11px] text-slate-400">Agent</p>
                    <p class="mt-1 text-xs font-semibold text-white">{{ $aiInfo['agent_kode'] }} <span
                            class="text-[11px] font-normal text-slate-400">(ID: {{ $aiInfo['agent_id'] }})</span></p>
                </div>
                <div class="rounded-xl border border-slate-700/70 bg-slate-950/60 px-3 py-2.5">
                    <p class="text-[11px] text-slate-400">Agent Rules</p>
                    <p class="mt-1 text-xs font-semibold text-white">
                        {{ $aiInfo['active_rules'] }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-700/70 bg-slate-950/60 px-3 py-2.5">
                    <p class="text-[11px] text-slate-400">Language</p>
                    <p class="mt-1 text-xs font-semibold text-white">Bahasa Indonesia</p>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit"
                    class="rounded-lg bg-cyan-400 px-5 py-2 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300">
                    Save
                </button>
            </div>
        </form>
    </div>
@endsection
