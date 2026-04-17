@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.settings.title'))
@section('page-title', __('backoffice.pages.settings.page_title'))

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">{{ __('backoffice.pages.settings.title') }}</h1>
            <p class="text-xs text-slate-400">{{ __('backoffice.pages.settings.subtitle') }}</p>
        </div>
    </div>

    @if (session('success'))
        <div
            class="flex items-center gap-2 rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-200">
            <svg class="h-4 w-4 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2" width="16" height="16" style="min-width:16px;max-width:16px;max-height:16px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('backoffice.settings.update') }}" autocomplete="off" class="space-y-5">
        @csrf

        @php
            $groupMeta = [
                'webhook' => [
                    'label' => 'Webhook Endpoint',
                    'desc' => 'Base URL untuk koneksi ke API eksternal.',
                    'color' => '#22d3ee',
                    'bg' => 'rgba(34,211,238,0.1)',
                    'icon' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>',
                ],
                'openai' => [
                    'label' => 'OpenAI',
                    'desc' => 'Konfigurasi API key untuk OpenAI.',
                    'color' => '#a78bfa',
                    'bg' => 'rgba(167,139,250,0.1)',
                    'icon' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>',
                ],
                'telegram' => [
                    'label' => 'Telegram',
                    'desc' => 'Token dan konfigurasi bot Telegram.',
                    'color' => '#38bdf8',
                    'bg' => 'rgba(56,189,248,0.1)',
                    'icon' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>',
                ],
                'livechat' => [
                    'label' => 'LiveChat',
                    'desc' => 'Token verifikasi untuk webhook LiveChat.',
                    'color' => '#f472b6',
                    'bg' => 'rgba(244,114,182,0.1)',
                    'icon' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>',
                ],
                'whatsapp' => [
                    'label' => 'WhatsApp (WAHA)',
                    'desc' => 'Konfigurasi WAHA untuk WhatsApp gateway.',
                    'color' => '#34d399',
                    'bg' => 'rgba(52,211,153,0.1)',
                    'icon' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>',
                ],
                'agent' => [
                    'label' => 'Agent',
                    'desc' => 'Pengaturan agent default dan identitas.',
                    'color' => '#fbbf24',
                    'bg' => 'rgba(251,191,36,0.1)',
                    'icon' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
                ],
                'support' => [
                    'label' => 'Support',
                    'desc' => 'Kontak dan link support untuk customer.',
                    'color' => '#fb7185',
                    'bg' => 'rgba(251,113,133,0.1)',
                    'icon' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>',
                ],
            ];
            $defaultMeta = [
                'label' => '',
                'desc' => '',
                'color' => '#94a3b8',
                'bg' => 'rgba(148,163,184,0.1)',
                'icon' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
            ];
        @endphp

        @forelse ($grouped as $group => $settings)
            @php
                $meta = $groupMeta[$group] ?? array_merge($defaultMeta, ['label' => ucfirst($group)]);
            @endphp

            <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 overflow-hidden transition"
                style="--gc: {{ $meta['color'] }}">
                {{-- Group header --}}
                <div class="flex items-center gap-3 border-b border-slate-700/50" style="padding:1rem 1.25rem">
                    <div class="flex shrink-0 items-center justify-center rounded-xl"
                        style="background: {{ $meta['bg'] }}; color: {{ $meta['color'] }}; width: 36px; height: 36px;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="18"
                            height="18">{!! $meta['icon'] !!}</svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-white">{{ $meta['label'] }}</h2>
                        @if ($meta['desc'])
                            <p class="text-[11px] text-slate-500">{{ $meta['desc'] }}</p>
                        @endif
                    </div>
                </div>

                {{-- Fields --}}
                <div class="grid gap-px bg-slate-700/30 sm:grid-cols-2">
                    @foreach ($settings as $setting)
                        <div class="bg-slate-900/85 transition hover:bg-slate-800/50" style="padding:1.25rem 1.5rem">
                            <label for="setting_{{ $setting->id }}" class="mb-1.5 flex items-center justify-between">
                                <span class="text-xs font-medium text-slate-300">{{ $setting->label }}</span>
                                <span
                                    class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-[10px] text-slate-500">{{ $setting->key }}</span>
                            </label>

                            @if ($setting->type === 'secret')
                                <div style="position:relative">
                                    <input id="setting_{{ $setting->id }}" type="password"
                                        name="setting_{{ $setting->id }}"
                                        placeholder="{{ $setting->value ? '••••••••' : 'Not set' }}"
                                        autocomplete="new-password"
                                        class="block w-full rounded-lg border border-white/10 bg-slate-950/60 text-xs text-white outline-none transition placeholder:text-slate-600 focus:border-white/30 focus:ring-1 focus:ring-white/10"
                                        style="padding:0.5rem 2.25rem 0.5rem 0.75rem" />
                                    <button type="button" onclick="toggleSecret(this)"
                                        style="position:absolute;right:0.625rem;top:50%;transform:translateY(-50%);display:flex;align-items:center;justify-content:center;color:#64748b;cursor:pointer;background:none;border:none;padding:0"
                                        title="{{ __('backoffice.pages.settings.show_hide_secret') }}">
                                        <svg class="eye-closed" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="1.5" width="14" height="14">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                        </svg>
                                        <svg class="eye-open hidden" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.5" width="14" height="14">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </button>
                                </div>
                                <p
                                    style="margin-top:0.5rem;padding-left:0.25rem;font-size:10px;color:#475569;display:flex;align-items:center;gap:0.375rem">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                                        width="12" height="12" style="min-width:12px;color:#64748b">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ __('backoffice.pages.settings.secret_keep_hint') }}
                                </p>
                            @elseif ($setting->type === 'url')
                                <div style="position:relative">
                                    <span
                                        style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);display:flex;align-items:center;justify-content:center;pointer-events:none;color:#475569">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                                            width="14" height="14">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                    </span>
                                    <input id="setting_{{ $setting->id }}" type="url"
                                        name="setting_{{ $setting->id }}" value="{{ $setting->value }}"
                                        placeholder="https://..."
                                        class="block w-full rounded-lg border border-white/10 bg-slate-950/60 text-xs text-white outline-none transition placeholder:text-slate-600 focus:border-white/30 focus:ring-1 focus:ring-white/10"
                                        style="padding:0.5rem 0.75rem 0.5rem 2.25rem" />
                                </div>
                            @elseif ($setting->type === 'number')
                                <input id="setting_{{ $setting->id }}" type="number" name="setting_{{ $setting->id }}"
                                    value="{{ $setting->value }}"
                                    class="block w-full rounded-lg border border-white/10 bg-slate-950/60 text-xs text-white outline-none transition placeholder:text-slate-600 focus:border-white/30 focus:ring-1 focus:ring-white/10"
                                    style="padding:0.5rem 0.75rem" />
                            @else
                                <input id="setting_{{ $setting->id }}" type="text"
                                    name="setting_{{ $setting->id }}" value="{{ $setting->value }}"
                                    class="block w-full rounded-lg border border-white/10 bg-slate-950/60 text-xs text-white outline-none transition placeholder:text-slate-600 focus:border-white/30 focus:ring-1 focus:ring-white/10"
                                    style="padding:0.5rem 0.75rem" />
                            @endif
                        </div>
                    @endforeach

                    @if ($settings->count() % 2 !== 0)
                        <div class="hidden bg-slate-900/85 sm:block"></div>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-600 bg-slate-900/50 p-10 text-center">
                <svg class="mx-auto mb-3 h-10 w-10 text-slate-600" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <p class="text-sm text-slate-400">{{ __('backoffice.pages.settings.empty') }}</p>
                <p class="mt-1 text-xs text-slate-500">Jalankan <code
                        class="rounded bg-slate-800 px-1.5 py-0.5 text-[11px] text-cyan-400">php artisan db:seed
                        --class=ProjectSettingSeeder</code> ({{ __('backoffice.pages.settings.seed_hint') }})</p>
            </div>
        @endforelse

        @if ($grouped->isNotEmpty())
            <div
                class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-5 py-3.5">
                <p class="text-[11px] text-slate-500">
                    {{ __('backoffice.pages.settings.settings_summary', ['settings' => $grouped->flatten()->count(), 'groups' => $grouped->count()]) }}
                </p>
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-cyan-400 px-5 py-2 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300 active:scale-[0.98]">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    {{ __('backoffice.pages.settings.save_all') }}
                </button>
            </div>
        @endif
    </form>

    <script>
        function toggleSecret(btn) {
            const input = btn.closest('.relative').querySelector('input');
            const eyeClosed = btn.querySelector('.eye-closed');
            const eyeOpen = btn.querySelector('.eye-open');
            if (input.type === 'password') {
                input.type = 'text';
                eyeClosed.classList.add('hidden');
                eyeOpen.classList.remove('hidden');
            } else {
                input.type = 'password';
                eyeClosed.classList.remove('hidden');
                eyeOpen.classList.add('hidden');
            }
        }
    </script>
@endsection
