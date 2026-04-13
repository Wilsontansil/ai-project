@extends('backoffice.partials.layout')

@section('title', 'AI Agent Settings')

@php($boActive = 'ai-agent')

@section('content')
    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5 sm:p-6">
        <h1 class="text-2xl font-semibold sm:text-3xl">AI Agent Settings</h1>
        <p class="mt-2 text-sm text-slate-300">Informasi dan konfigurasi AI agent.</p>
    </div>

    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5 sm:p-6">
        <h2 class="text-xl font-semibold text-white">AI Agent Info</h2>

        @if (session('success'))
            <div class="mt-4 rounded-2xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-sm text-emerald-100">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('backoffice.ai-agent.update') }}" class="mt-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-2xl border border-slate-700/70 bg-slate-950/60 px-4 py-3">
                    <label for="bot_name" class="text-xs text-slate-400">Bot Name</label>
                    <input id="bot_name" type="text" name="bot_name" value="{{ $aiInfo['bot_name'] }}"
                        class="mt-1 block w-full rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm font-semibold text-white outline-none transition focus:border-cyan-400" />
                </div>
                <div class="rounded-2xl border border-slate-700/70 bg-slate-950/60 px-4 py-3">
                    <p class="text-xs text-slate-400">Model</p>
                    <p class="mt-1 text-sm font-semibold text-white">{{ $aiInfo['model'] }}</p>
                </div>
                <div class="rounded-2xl border border-slate-700/70 bg-slate-950/60 px-4 py-3">
                    <p class="text-xs text-slate-400">Max Tokens</p>
                    <p class="mt-1 text-sm font-semibold text-white">{{ $aiInfo['max_tokens'] }}</p>
                </div>
                <div class="rounded-2xl border border-slate-700/70 bg-slate-950/60 px-4 py-3">
                    <p class="text-xs text-slate-400">Agent</p>
                    <p class="mt-1 text-sm font-semibold text-white">{{ $aiInfo['agent_kode'] }} <span
                            class="text-xs font-normal text-slate-400">(ID: {{ $aiInfo['agent_id'] }})</span>
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-700/70 bg-slate-950/60 px-4 py-3">
                    <p class="text-xs text-slate-400">Forbidden Rules</p>
                    <p class="mt-1 text-sm font-semibold text-white">
                        {{ $aiInfo['active_forbidden'] }}
                        @if ($aiInfo['active_forbidden'] > 0)
                            <a href="{{ route('backoffice.forbidden.index') }}"
                                class="ml-1 text-xs font-normal text-cyan-400 hover:underline">View</a>
                        @endif
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-700/70 bg-slate-950/60 px-4 py-3">
                    <p class="text-xs text-slate-400">Language</p>
                    <p class="mt-1 text-sm font-semibold text-white">Bahasa Indonesia</p>
                </div>
            </div>

            <div class="mt-5">
                <button type="submit"
                    class="rounded-2xl bg-cyan-400 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    Save
                </button>
            </div>
        </form>
    </div>
@endsection
