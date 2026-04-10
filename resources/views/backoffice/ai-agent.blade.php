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
                'currentTool' => $currentTool ?? null,
            ])

            <main class="min-w-0 flex-1 space-y-6">
                <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                    <h1 class="text-3xl font-semibold">AI Agent Settings</h1>
                    <p class="mt-2 text-sm text-slate-300">Informasi dan konfigurasi AI agent.</p>
                </div>

                {{-- AI Info Panel --}}
                <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                    <h2 class="text-xl font-semibold text-white">AI Agent Info</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-2xl border border-white/10 bg-slate-900/50 px-4 py-3">
                            <p class="text-xs text-slate-400">Bot Name</p>
                            <p class="mt-1 text-sm font-semibold text-white">{{ $aiInfo['bot_name'] }}</p>
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
                                    class="text-xs font-normal text-slate-400">(ID: {{ $aiInfo['agent_id'] }})</span>
                            </p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-900/50 px-4 py-3">
                            <p class="text-xs text-slate-400">Active Cases</p>
                            <p class="mt-1 text-sm font-semibold text-white">
                                {{ $aiInfo['active_cases'] }}
                                @if ($aiInfo['active_cases'] > 0)
                                    <a href="{{ route('backoffice.cases.index') }}"
                                        class="ml-1 text-xs font-normal text-cyan-400 hover:underline">View</a>
                                @endif
                            </p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-900/50 px-4 py-3">
                            <p class="text-xs text-slate-400">Language</p>
                            <p class="mt-1 text-sm font-semibold text-white">Bahasa Indonesia</p>
                        </div>
                    </div>
                </div>

                {{-- Tools Section Header --}}
                <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                    <h2 class="text-xl font-semibold text-white">Tools Configuration</h2>
                    <p class="mt-1 text-sm text-slate-300">Atur tool mana yang aktif untuk AI agent saat melayani
                        customer.</p>
                </div>

                @if (session('success'))
                    <div
                        class="mb-4 rounded-2xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-sm text-emerald-100">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div
                        class="mb-4 rounded-2xl border border-rose-300/30 bg-rose-500/15 px-4 py-3 text-sm text-rose-100">
                        {{ session('error') }}
                    </div>
                @endif

                @if (!$hasToolSettingsTable)
                    <div
                        class="mb-4 rounded-2xl border border-amber-300/30 bg-amber-500/15 px-4 py-3 text-sm text-amber-100">
                        Table <strong>tool_settings</strong> belum tersedia. Jalankan migration agar setting bisa
                        disimpan.
                    </div>
                @endif

                <form method="POST" action="{{ route('backoffice.ai-agent.update') }}" class="space-y-4">
                    @csrf

                    @foreach ($tools as $index => $tool)
                        <div id="tool-{{ $tool['tool_name'] }}"
                            class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h2 class="text-xl font-semibold text-white">{{ $tool['display_name'] }}</h2>
                                    <p class="mt-1 text-sm text-slate-300">{{ $tool['description'] }}</p>
                                    <p class="mt-1 text-xs text-slate-400">Tool key: {{ $tool['tool_name'] }}</p>
                                </div>
                                <label
                                    class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-slate-900/50 px-4 py-2 text-sm text-slate-200">
                                    <input type="checkbox" name="enabled[{{ $tool['tool_name'] }}]" value="1"
                                        {{ $tool['is_enabled'] ? 'checked' : '' }}
                                        class="rounded border-white/20 bg-slate-800 text-cyan-400 focus:ring-cyan-400" />
                                    Enable tool
                                </label>
                            </div>

                            <input type="hidden" name="tools[{{ $index }}][tool_name]"
                                value="{{ $tool['tool_name'] }}">

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label for="display_name_{{ $index }}"
                                        class="mb-2 block text-sm text-slate-200">Display Name</label>
                                    <input id="display_name_{{ $index }}" type="text"
                                        name="tools[{{ $index }}][display_name]"
                                        value="{{ $tool['display_name'] }}"
                                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                                </div>
                                <div>
                                    <label for="description_{{ $index }}"
                                        class="mb-2 block text-sm text-slate-200">Description</label>
                                    <input id="description_{{ $index }}" type="text"
                                        name="tools[{{ $index }}][description]"
                                        value="{{ $tool['description'] }}"
                                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="pt-2">
                        <button type="submit"
                            class="rounded-2xl bg-cyan-400 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                            Save AI Tool Settings
                        </button>
                    </div>
                </form>
            </main>
        </div>
    </div>

    @if (!empty($currentTool))
        <script>
            (() => {
                const id = 'tool-{{ $currentTool }}';
                const section = document.getElementById(id);

                if (section) {
                    section.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            })();
        </script>
    @endif
</body>

</html>
