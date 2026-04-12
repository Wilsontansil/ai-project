<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Global Settings</title>
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
                'active' => 'settings',
            ])

            <main class="min-w-0 flex-1 space-y-6">
                {{-- Header --}}
                <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                    <h1 class="text-3xl font-semibold">Global Settings</h1>
                    <p class="mt-2 text-sm text-slate-300">Konfigurasi environment & endpoint untuk project ini.</p>
                </div>

                @if (session('success'))
                    <div
                        class="rounded-2xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-sm text-emerald-100">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('backoffice.settings.update') }}" autocomplete="off">
                    @csrf

                    @forelse ($grouped as $group => $settings)
                        <div class="mb-6 rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                            <h2 class="mb-4 text-lg font-semibold capitalize text-white">
                                @switch($group)
                                    @case('webhook')
                                        🔗 Webhook Endpoint
                                    @break

                                    @case('openai')
                                        🤖 OpenAI
                                    @break

                                    @case('telegram')
                                        📨 Telegram
                                    @break

                                    @case('whatsapp')
                                        💬 WhatsApp (WAHA)
                                    @break

                                    @case('agent')
                                        🎮 Agent
                                    @break

                                    @case('support')
                                        📞 Support
                                    @break

                                    @default
                                        {{ ucfirst($group) }}
                                @endswitch
                            </h2>

                            <div class="grid gap-4 sm:grid-cols-2">
                                @foreach ($settings as $setting)
                                    <div class="rounded-2xl border border-white/10 bg-slate-900/50 px-4 py-3">
                                        <label for="setting_{{ $setting->id }}"
                                            class="text-xs font-medium text-slate-400">
                                            {{ $setting->label }}
                                        </label>

                                        @if ($setting->type === 'secret')
                                            <input id="setting_{{ $setting->id }}" type="password"
                                                name="setting_{{ $setting->id }}"
                                                placeholder="{{ $setting->value ? '••••••••' : 'Not set' }}"
                                                autocomplete="new-password"
                                                style="color:#000" class="mt-1 block w-full rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm text-black outline-none transition placeholder:text-slate-500 focus:border-cyan-400" />
                                            <p class="mt-1 text-[10px] text-slate-500">Kosongkan jika tidak ingin
                                                mengubah.</p>
                                        @elseif ($setting->type === 'url')
                                            <input id="setting_{{ $setting->id }}" type="url"
                                                name="setting_{{ $setting->id }}" value="{{ $setting->value }}"
                                                placeholder="https://..."
                                                style="color:#000" class="mt-1 block w-full rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm text-black outline-none transition placeholder:text-slate-500 focus:border-cyan-400" />
                                        @elseif ($setting->type === 'number')
                                            <input id="setting_{{ $setting->id }}" type="number"
                                                name="setting_{{ $setting->id }}" value="{{ $setting->value }}"
                                                style="color:#000" class="mt-1 block w-full rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm text-black outline-none transition placeholder:text-slate-500 focus:border-cyan-400" />
                                        @else
                                            <input id="setting_{{ $setting->id }}" type="text"
                                                name="setting_{{ $setting->id }}" value="{{ $setting->value }}"
                                                style="color:#000" class="mt-1 block w-full rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm text-black outline-none transition placeholder:text-slate-500 focus:border-cyan-400" />
                                        @endif

                                        <p class="mt-1 text-[10px] text-slate-500 font-mono">{{ $setting->key }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @empty
                            <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur text-center">
                                <p class="text-slate-400">Belum ada settings. Jalankan seeder terlebih dahulu.</p>
                            </div>
                        @endforelse

                        @if ($grouped->isNotEmpty())
                            <div class="flex justify-end">
                                <button type="submit"
                                    class="rounded-2xl bg-cyan-400 px-8 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                                    Save All Settings
                                </button>
                            </div>
                        @endif
                    </form>

                </main>
            </div>
        </div>
    </body>

    </html>
