@extends('backoffice.partials.layout')

@section('title', 'Global Settings')

@php($boActive = 'settings')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">Global Settings</h1>
            <p class="text-xs text-slate-400">Konfigurasi environment dan endpoint untuk project ini.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-xs text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('backoffice.settings.update') }}" autocomplete="off" class="space-y-5" style="display:flex;flex-direction:column;gap:1.25rem">
        @csrf

        @forelse ($grouped as $group => $settings)
            <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5">
                <h2 class="mb-3 text-sm font-semibold capitalize text-white">
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

                <div class="grid gap-3 sm:grid-cols-2" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0.75rem">
                    @foreach ($settings as $setting)
                        <div class="rounded-xl border border-slate-700/70 bg-slate-950/60 px-3 py-2.5">
                            <label for="setting_{{ $setting->id }}" class="text-[11px] font-medium text-slate-400">
                                {{ $setting->label }}
                            </label>

                            @if ($setting->type === 'secret')
                                <input id="setting_{{ $setting->id }}" type="password" name="setting_{{ $setting->id }}"
                                    placeholder="{{ $setting->value ? '••••••••' : 'Not set' }}" autocomplete="new-password"
                                    class="mt-1 block w-full rounded-lg border border-white/10 bg-slate-900 px-2.5 py-1.5 text-xs text-white outline-none transition placeholder:text-slate-500 focus:border-cyan-400"
                                    style="background-color:rgba(15,23,42,0.7);color:#e2e8f0;font-size:12px" />
                                <p class="mt-0.5 text-[10px] text-slate-500">Kosongkan jika tidak ingin mengubah.</p>
                            @elseif ($setting->type === 'url')
                                <input id="setting_{{ $setting->id }}" type="url" name="setting_{{ $setting->id }}"
                                    value="{{ $setting->value }}" placeholder="https://..."
                                    class="mt-1 block w-full rounded-lg border border-white/10 bg-slate-900 px-2.5 py-1.5 text-xs text-white outline-none transition placeholder:text-slate-500 focus:border-cyan-400"
                                    style="background-color:rgba(15,23,42,0.7);color:#e2e8f0;font-size:12px" />
                            @elseif ($setting->type === 'number')
                                <input id="setting_{{ $setting->id }}" type="number" name="setting_{{ $setting->id }}"
                                    value="{{ $setting->value }}"
                                    class="mt-1 block w-full rounded-lg border border-white/10 bg-slate-900 px-2.5 py-1.5 text-xs text-white outline-none transition placeholder:text-slate-500 focus:border-cyan-400"
                                    style="background-color:rgba(15,23,42,0.7);color:#e2e8f0;font-size:12px" />
                            @else
                                <input id="setting_{{ $setting->id }}" type="text" name="setting_{{ $setting->id }}"
                                    value="{{ $setting->value }}"
                                    class="mt-1 block w-full rounded-lg border border-white/10 bg-slate-900 px-2.5 py-1.5 text-xs text-white outline-none transition placeholder:text-slate-500 focus:border-cyan-400"
                                    style="background-color:rgba(15,23,42,0.7);color:#e2e8f0;font-size:12px" />
                            @endif

                            <p class="mt-0.5 font-mono text-[10px] text-slate-500">{{ $setting->key }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            @empty
                <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-6 text-center">
                    <p class="text-sm text-slate-400">Belum ada settings. Jalankan seeder terlebih dahulu.</p>
                </div>
            @endforelse

            @if ($grouped->isNotEmpty())
                <div class="flex justify-end">
                    <button type="submit"
                        class="rounded-lg bg-cyan-400 px-6 py-2 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300">
                        Save All Settings
                    </button>
                </div>
            @endif
        </form>
    @endsection
