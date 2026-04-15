@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.chat_agents.title'))
@section('page-title', __('backoffice.pages.chat_agents.page_title'))

@php($boActive = 'chat-agents')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">{{ __('backoffice.pages.chat_agents.title') }}</h1>
            <p class="text-xs text-slate-400">{{ __('backoffice.pages.chat_agents.subtitle') }}</p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-xs text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    {{-- Agent Card Grid --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem">
        {{-- Create New Card --}}
        <a href="{{ route('backoffice.chat-agents.create') }}"
            class="group flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-600/60 bg-slate-900/50 p-6 transition hover:border-cyan-400/50 hover:bg-slate-900/70"
            style="min-height:240px;display:flex;flex-direction:column;align-items:center;justify-content:center">
            <div
                style="display:flex;width:56px;height:56px;align-items:center;justify-content:center;border-radius:16px;background:rgba(6,182,212,0.1);color:#22d3ee;margin-bottom:0.75rem">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" width="28"
                    height="28">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </div>
            <p class="text-sm font-semibold text-slate-300 group-hover:text-white">
                {{ __('backoffice.pages.chat_agents.create_new') }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ __('backoffice.pages.chat_agents.create_hint') }}</p>
        </a>

        {{-- Agent Cards --}}
        @foreach ($agents as $agent)
            <div class="relative flex flex-col rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5 transition hover:border-slate-600"
                style="min-height:240px;display:flex;flex-direction:column;position:relative">
                {{-- Status badge --}}
                <div style="position:absolute;right:1rem;top:1rem;display:flex;align-items:center;gap:0.5rem">
                    @if ($agent->is_default)
                        <span
                            class="inline-flex items-center rounded-full bg-cyan-500/20 px-2.5 py-0.5 text-[11px] font-semibold text-cyan-300 ring-1 ring-cyan-400/30"
                            style="display:inline-flex;align-items:center;font-size:11px">{{ __('backoffice.pages.chat_agents.default') }}</span>
                    @endif
                    @if ($agent->is_enabled)
                        <span style="display:inline-flex;width:10px;height:10px;border-radius:50%;background:#34d399"
                            title="{{ __('backoffice.pages.chat_agents.enabled') }}"></span>
                    @else
                        <span style="display:inline-flex;width:10px;height:10px;border-radius:50%;background:#f87171"
                            title="{{ __('backoffice.pages.chat_agents.disabled') }}"></span>
                    @endif
                </div>

                {{-- Avatar + Name --}}
                <div style="display:flex;align-items:center;gap:0.75rem">
                    <div
                        style="display:flex;width:48px;height:48px;min-width:48px;align-items:center;justify-content:center;border-radius:12px;background:linear-gradient(135deg,rgba(6,182,212,0.3),rgba(37,99,235,0.3));font-size:18px;font-weight:700;color:#fff">
                        {{ strtoupper(mb_substr($agent->name, 0, 2)) }}
                    </div>
                    <div style="min-width:0">
                        <h3
                            style="font-size:14px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ $agent->name }}</h3>
                        <p style="font-size:11px;color:#94a3b8">{{ $agent->model }}</p>
                    </div>
                </div>

                {{-- Description --}}
                <p
                    style="margin-top:0.75rem;flex:1;font-size:12px;color:#94a3b8;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">
                    {{ $agent->description ?: __('backoffice.pages.chat_agents.no_description') }}
                </p>

                {{-- Meta info --}}
                <div style="margin-top:0.75rem;display:flex;flex-wrap:wrap;gap:1rem;font-size:11px;color:#64748b">
                    <span>{{ __('backoffice.pages.chat_agents.max_tokens') }}: {{ $agent->max_tokens }}</span>
                    <span>{{ __('backoffice.pages.chat_agents.temp') }}: {{ $agent->temperature }}</span>
                    <span>{{ __('backoffice.pages.chat_agents.forbidden') }}:
                        {{ $agent->forbiddenBehaviours()->where('is_active', true)->count() }}</span>
                </div>

                {{-- Actions --}}
                <div
                    style="margin-top:1rem;display:flex;align-items:center;gap:0.5rem;border-top:1px solid rgba(51,65,85,0.5);padding-top:0.75rem">
                    <a href="{{ route('backoffice.chat-agents.edit', $agent) }}"
                        class="rounded-lg bg-white/5 px-3 py-1.5 text-xs text-slate-400 transition hover:bg-white/10 hover:text-slate-200"
                        title="{{ __('backoffice.pages.chat_agents.settings') }}">
                        <svg class="inline h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="1.5" width="14" height="14">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.212-1.281c-.063-.374-.313-.686-.645-.87a6.47 6.47 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0Z" />
                        </svg>
                        {{ __('backoffice.pages.chat_agents.settings') }}
                    </a>
                    <form method="POST" action="{{ route('backoffice.chat-agents.duplicate', $agent) }}">
                        @csrf
                        <button type="submit"
                            class="rounded-lg bg-white/5 px-3 py-1.5 text-xs text-slate-400 transition hover:bg-white/10 hover:text-slate-200"
                            title="{{ __('backoffice.pages.chat_agents.clone') }}">
                            <svg class="inline h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="1.5" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                            </svg>
                            {{ __('backoffice.pages.chat_agents.clone') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('backoffice.chat-agents.destroy', $agent) }}"
                        onsubmit="return confirm('{{ __('backoffice.pages.chat_agents.delete_confirm_agent', ['name' => $agent->name]) }}')"
                        style="margin-left:auto">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="rounded-lg bg-red-500/10 px-3 py-1.5 text-xs text-red-400 transition hover:bg-red-500/20"
                            title="Delete">
                            <svg class="inline h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="1.5" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endsection
