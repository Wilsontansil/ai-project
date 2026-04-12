<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Agent Settings</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            body {
                margin: 0;
                font-family: Arial, sans-serif;
                background: #0f172a;
                color: #f8fafc;
            }
        </style>
    @endif
</head>

<body class="min-h-screen bg-slate-950 text-slate-100">
    <div class="min-h-screen bg-[linear-gradient(180deg,_#020617,_#0f172a_40%,_#111827)] p-4 md:p-6">
        <div id="bo-shell" class="mx-auto flex max-w-7xl gap-6">
            @include('backoffice.partials.sidebar', [
                'active' => 'ai-agent',
            ])

            <main class="min-w-0 flex-1 space-y-6">
                <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                    <h1 class="text-3xl font-semibold">AI Agent Settings</h1>
                    <p class="mt-2 text-sm text-slate-300">Informasi dan konfigurasi AI agent.</p>
                </div>

                {{-- AI Info Panel --}}
                <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                    <h2 class="text-xl font-semibold text-white">AI Agent Info</h2>

                    @if (session('success'))
                        <div
                            class="mt-4 rounded-2xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-sm text-emerald-100">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('backoffice.ai-agent.update') }}" class="mt-4">
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="rounded-2xl border border-white/10 bg-slate-900/50 px-4 py-3">
                                <label for="bot_name" class="text-xs text-slate-400">Bot Name</label>
                                <input id="bot_name" type="text" name="bot_name" value="{{ $aiInfo['bot_name'] }}"
                                    style="background-color:rgba(15,23,42,0.7);color:#e2e8f0" class="mt-1 block w-full rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm font-semibold text-white outline-none transition focus:border-cyan-400" />
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-slate-900/50 px-4 py-3">
                                <p class="text-xs text-slate-400">Model</p>
                                <p class="mt-1 text-sm font-semibold text-white">{{ $aiInfo['model'] }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-slate-900/50 px-4 py-3">
                                <p class="text-xs text-slate-400">Max Tokens</p>
                                <p class="mt-1 text-sm font-semibold text-white">{{ $aiInfo['max_tokens'] }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-slate-900/50 px-4 py-3">
                                <p class="text-xs text-slate-400">Agent</p>
                                <p class="mt-1 text-sm font-semibold text-white">{{ $aiInfo['agent_kode'] }} <span
                                        class="text-xs font-normal text-slate-400">(ID:
                                        {{ $aiInfo['agent_id'] }})</span>
                                </p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-slate-900/50 px-4 py-3">
                                <p class="text-xs text-slate-400">Forbidden Rules</p>
                                <p class="mt-1 text-sm font-semibold text-white">
                                    {{ $aiInfo['active_forbidden'] }}
                                    @if ($aiInfo['active_forbidden'] > 0)
                                        <a href="{{ route('backoffice.forbidden.index') }}"
                                            class="ml-1 text-xs font-normal text-cyan-400 hover:underline">View</a>
                                    @endif
                                </p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-slate-900/50 px-4 py-3">
                                <p class="text-xs text-slate-400">Language</p>
                                <p class="mt-1 text-sm font-semibold text-white">Bahasa Indonesia</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit"
                                class="rounded-2xl bg-cyan-400 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                                Save
                            </button>
                        </div>
                    </form>
                </div>

            </main>
        </div>
    </div>
</body>

</html>
