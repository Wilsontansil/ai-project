@extends('backoffice.partials.layout')

@section('title', 'Global Settings')

@php($boActive = 'settings')

@section('content')
    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5 sm:p-6">
        <h1 class="text-2xl font-semibold sm:text-3xl">Global Settings</h1>
        <p class="mt-2 text-sm text-slate-300">Konfigurasi environment dan endpoint untuk project ini.</p>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-sm text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('backoffice.settings.update') }}" autocomplete="off" class="space-y-6">
        @csrf

        @forelse ($grouped as $group => $settings)
            <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5 sm:p-6">
                <h2 class="mb-4 text-lg font-semibold capitalize text-white">
                    @switch($group)
                        @case('webhook')
                            Webhook Endpoint
                        @break

                        @case('openai')
                            OpenAI
                        @break

                        @case('telegram')
                            Telegram
                        @break

                        @case('whatsapp')
                            WhatsApp (WAHA)
                        @break

                        @case('agent')
                            Agent
                        @break

                        @case('support')
                            Support
                        @break

                        @default
                            {{ ucfirst($group) }}
                    @endswitch
                </h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($settings as $setting)
                        <div class="rounded-2xl border border-slate-700/70 bg-slate-950/60 px-4 py-3">
                            <label for="setting_{{ $setting->id }}" class="text-xs font-medium text-slate-400">
                                {{ $setting->label }}
                            </label>

                            @if ($setting->type === 'secret')
                                <input id="setting_{{ $setting->id }}" type="password" name="setting_{{ $setting->id }}"
                                    placeholder="{{ $setting->value ? '••••••••' : 'Not set' }}" autocomplete="new-password"
                                    class="mt-1 block w-full rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-cyan-400" />
                                <p class="mt-1 text-[10px] text-slate-500">Kosongkan jika tidak ingin mengubah.</p>
                            @elseif ($setting->type === 'url')
                                <input id="setting_{{ $setting->id }}" type="url" name="setting_{{ $setting->id }}"
                                    value="{{ $setting->value }}" placeholder="https://..."
                                    class="mt-1 block w-full rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-cyan-400" />
                            @elseif ($setting->type === 'number')
                                <input id="setting_{{ $setting->id }}" type="number" name="setting_{{ $setting->id }}"
                                    value="{{ $setting->value }}"
                                    class="mt-1 block w-full rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-cyan-400" />
                            @else
                                <input id="setting_{{ $setting->id }}" type="text" name="setting_{{ $setting->id }}"
                                    value="{{ $setting->value }}"
                                    class="mt-1 block w-full rounded-xl border border-white/10 bg-slate-900 px-3 py-2 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-cyan-400" />
                            @endif

                            <p class="mt-1 font-mono text-[10px] text-slate-500">{{ $setting->key }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            @empty
                <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-6 text-center">
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
    @endsection
