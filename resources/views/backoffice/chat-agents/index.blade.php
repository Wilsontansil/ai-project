@extends('backoffice.partials.layout')

@section('title', 'AI Agents')

@php($boActive = 'chat-agents')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">AI Agents</h1>
            <p class="text-xs text-slate-400">Kelola AI agent untuk chatbot Anda.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-xs text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    {{-- Agent Card Grid --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {{-- Create New Card --}}
        <a href="{{ route('backoffice.chat-agents.create') }}"
            class="group flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-600/60 bg-slate-900/50 p-6 transition hover:border-cyan-400/50 hover:bg-slate-900/70"
            style="min-height:220px">
            <div
                class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-cyan-400/10 text-cyan-400 transition group-hover:bg-cyan-400/20">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"
                    width="28" height="28">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </div>
            <p class="text-sm font-semibold text-slate-300 group-hover:text-white">Create New Agent</p>
            <p class="mt-1 text-xs text-slate-500">Buat agent baru dengan prompt kustom</p>
        </a>

        {{-- Agent Cards --}}
        @foreach ($agents as $agent)
            <div class="relative flex flex-col rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5 transition hover:border-slate-600"
                style="min-height:220px">
                {{-- Status badge --}}
                <div class="absolute right-4 top-4 flex items-center gap-2">
                    @if ($agent->is_default)
                        <span
                            class="inline-flex items-center rounded-full bg-cyan-500/20 px-2.5 py-0.5 text-[11px] font-semibold text-cyan-300 ring-1 ring-cyan-400/30">DEFAULT</span>
                    @endif
                    @if ($agent->is_enabled)
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400" title="Enabled"></span>
                    @else
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-red-400" title="Disabled"></span>
                    @endif
                </div>

                {{-- Avatar + Name --}}
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-500/30 to-blue-600/30 text-lg font-bold text-white">
                        {{ strtoupper(mb_substr($agent->name, 0, 2)) }}
                    </div>
                    <div class="min-w-0">
                        <h3 class="truncate text-sm font-semibold text-white">{{ $agent->name }}</h3>
                        <p class="text-[11px] text-slate-400">{{ $agent->model }}</p>
                    </div>
                </div>

                {{-- Description --}}
                <p class="mt-3 flex-1 text-xs text-slate-400 line-clamp-2">
                    {{ $agent->description ?: 'No description' }}
                </p>

                {{-- Meta info --}}
                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-slate-500">
                    <span>Max tokens: {{ $agent->max_tokens }}</span>
                    <span>Temp: {{ $agent->temperature }}</span>
                </div>

                {{-- Actions --}}
                <div class="mt-4 flex items-center gap-2 border-t border-slate-700/50 pt-3">
                    <a href="{{ route('backoffice.chat-agents.edit', $agent) }}"
                        class="rounded-lg bg-white/10 px-3.5 py-1.5 text-xs font-medium text-slate-200 transition hover:bg-white/20">
                        Settings
                    </a>
                    <form method="POST" action="{{ route('backoffice.chat-agents.duplicate', $agent) }}">
                        @csrf
                        <button type="submit"
                            class="rounded-lg bg-white/5 px-3 py-1.5 text-xs text-slate-400 transition hover:bg-white/10 hover:text-slate-200"
                            title="Duplicate">
                            <svg class="inline h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="1.5" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                            </svg>
                            Clone
                        </button>
                    </form>
                    <form method="POST" action="{{ route('backoffice.chat-agents.destroy', $agent) }}"
                        onsubmit="return confirm('Hapus agent {{ $agent->name }}?')" class="ml-auto">
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
