@extends('backoffice.partials.layout')

@section('title', 'Global Settings')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">Global Settings</h1>
            <p class="text-xs text-slate-400">Konfigurasi environment dan endpoint untuk project ini.</p>
        </div>
    </div>

    @if (session('success'))
        <div
            class="flex items-center gap-2 rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-200">
            <svg class="h-4 w-4 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('backoffice.settings.update') }}" autocomplete="off" class="space-y-5">
        @csrf

        @php
            $groupIcons = [
                'webhook' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.54a4.5 4.5 0 00-1.242-7.244l4.5-4.5a4.5 4.5 0 016.364 6.364l-1.757 1.757"/>',
                'openai' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z"/>',
                'telegram' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>',
                'whatsapp' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/>',
                'agent' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>',
                'support' =>
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M16.712 4.33a9.027 9.027 0 011.652 1.306c.51.51.944 1.064 1.306 1.652M16.712 4.33l-3.448 4.138m3.448-4.138a9.014 9.014 0 00-9.424 0M19.67 7.288l-4.138 3.448m4.138-3.448a9.014 9.014 0 010 9.424m-4.138-5.976a3.736 3.736 0 00-.88-1.388 3.737 3.737 0 00-1.388-.88m2.268 2.268a3.765 3.765 0 010 3.44m-2.268-5.708a3.736 3.736 0 00-3.44 0m0 0a3.736 3.736 0 00-1.388.88 3.737 3.737 0 00-.88 1.388m0 0a3.765 3.765 0 000 3.44m2.268-5.708l-4.138-3.448m4.138 3.448l-3.448 4.138m3.448-4.138a9.027 9.027 0 00-1.306-1.652m1.306 1.652a9.014 9.014 0 000 9.424m0 0a9.027 9.027 0 001.306-1.652M4.33 16.712l4.138-3.448m-4.138 3.448a9.014 9.014 0 010-9.424m4.138 5.976l-3.448-4.138m3.448 4.138a3.765 3.765 0 010-3.44m0 3.44l-4.138 3.448M7.288 4.33l3.448 4.138M7.288 4.33a9.014 9.014 0 019.424 0"/>',
            ];
            $groupColors = [
                'webhook' => 'cyan',
                'openai' => 'violet',
                'telegram' => 'sky',
                'whatsapp' => 'emerald',
                'agent' => 'amber',
                'support' => 'rose',
            ];
            $groupDescriptions = [
                'webhook' => 'Base URL untuk koneksi ke API eksternal.',
                'openai' => 'Konfigurasi API key untuk OpenAI.',
                'telegram' => 'Token dan konfigurasi bot Telegram.',
                'whatsapp' => 'Konfigurasi WAHA untuk WhatsApp gateway.',
                'agent' => 'Pengaturan agent default dan identitas.',
                'support' => 'Kontak dan link support untuk customer.',
            ];
            $groupLabels = [
                'webhook' => 'Webhook Endpoint',
                'openai' => 'OpenAI',
                'telegram' => 'Telegram',
                'whatsapp' => 'WhatsApp (WAHA)',
                'agent' => 'Agent',
                'support' => 'Support',
            ];
        @endphp

        @forelse ($grouped as $group => $settings)
            @php
                $color = $groupColors[$group] ?? 'slate';
                $icon =
                    $groupIcons[$group] ??
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>';
                $label = $groupLabels[$group] ?? ucfirst($group);
                $desc = $groupDescriptions[$group] ?? '';
            @endphp

            <div
                class="group rounded-2xl border border-slate-700/70 bg-slate-900/85 overflow-hidden transition hover:border-{{ $color }}-500/30">
                {{-- Group header --}}
                <div class="flex items-center gap-3 border-b border-slate-700/50 px-5 py-3.5">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-{{ $color }}-500/10 text-{{ $color }}-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.5">{!! $icon !!}</svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-white">{{ $label }}</h2>
                        @if ($desc)
                            <p class="text-[11px] text-slate-500">{{ $desc }}</p>
                        @endif
                    </div>
                </div>

                {{-- Fields --}}
                <div class="grid gap-px bg-slate-700/30 sm:grid-cols-2">
                    @foreach ($settings as $setting)
                        <div class="bg-slate-900/85 px-5 py-4 transition hover:bg-slate-800/50">
                            <label for="setting_{{ $setting->id }}" class="mb-1.5 flex items-center justify-between">
                                <span class="text-xs font-medium text-slate-300">{{ $setting->label }}</span>
                                <span
                                    class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-[10px] text-slate-500">{{ $setting->key }}</span>
                            </label>

                            @if ($setting->type === 'secret')
                                <div class="relative">
                                    <input id="setting_{{ $setting->id }}" type="password"
                                        name="setting_{{ $setting->id }}"
                                        placeholder="{{ $setting->value ? '••••••••' : 'Not set' }}"
                                        autocomplete="new-password"
                                        class="block w-full rounded-lg border border-white/10 bg-slate-950/60 px-3 py-2 pr-9 text-xs text-white outline-none transition placeholder:text-slate-600 focus:border-{{ $color }}-400/60 focus:ring-1 focus:ring-{{ $color }}-400/20" />
                                    <button type="button" onclick="toggleSecret(this)"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition"
                                        title="Show/hide">
                                        <svg class="h-3.5 w-3.5 eye-closed" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                        </svg>
                                        <svg class="h-3.5 w-3.5 eye-open hidden" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-[10px] text-slate-600">Kosongkan jika tidak ingin mengubah.</p>
                            @elseif ($setting->type === 'url')
                                <div class="relative">
                                    <span
                                        class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.54a4.5 4.5 0 00-1.242-7.244l4.5-4.5a4.5 4.5 0 016.364 6.364l-1.757 1.757" />
                                        </svg>
                                    </span>
                                    <input id="setting_{{ $setting->id }}" type="url"
                                        name="setting_{{ $setting->id }}" value="{{ $setting->value }}"
                                        placeholder="https://..."
                                        class="block w-full rounded-lg border border-white/10 bg-slate-950/60 py-2 pl-8 pr-3 text-xs text-white outline-none transition placeholder:text-slate-600 focus:border-{{ $color }}-400/60 focus:ring-1 focus:ring-{{ $color }}-400/20" />
                                </div>
                            @elseif ($setting->type === 'number')
                                <input id="setting_{{ $setting->id }}" type="number" name="setting_{{ $setting->id }}"
                                    value="{{ $setting->value }}"
                                    class="block w-full rounded-lg border border-white/10 bg-slate-950/60 px-3 py-2 text-xs text-white outline-none transition placeholder:text-slate-600 focus:border-{{ $color }}-400/60 focus:ring-1 focus:ring-{{ $color }}-400/20" />
                            @else
                                <input id="setting_{{ $setting->id }}" type="text" name="setting_{{ $setting->id }}"
                                    value="{{ $setting->value }}"
                                    class="block w-full rounded-lg border border-white/10 bg-slate-950/60 px-3 py-2 text-xs text-white outline-none transition placeholder:text-slate-600 focus:border-{{ $color }}-400/60 focus:ring-1 focus:ring-{{ $color }}-400/20" />
                            @endif
                        </div>
                    @endforeach

                    {{-- Fill empty cell when odd number of settings --}}
                    @if ($settings->count() % 2 !== 0)
                        <div class="hidden bg-slate-900/85 sm:block"></div>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-600 bg-slate-900/50 p-10 text-center">
                <svg class="mx-auto mb-3 h-10 w-10 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <p class="text-sm text-slate-400">Belum ada settings.</p>
                <p class="mt-1 text-xs text-slate-500">Jalankan <code
                        class="rounded bg-slate-800 px-1.5 py-0.5 text-[11px] text-cyan-400">php artisan db:seed
                        --class=ProjectSettingSeeder</code></p>
            </div>
        @endforelse

        @if ($grouped->isNotEmpty())
            <div
                class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-5 py-3.5">
                <p class="text-[11px] text-slate-500">{{ $grouped->flatten()->count() }} settings across
                    {{ $grouped->count() }} groups</p>
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-cyan-400 px-5 py-2 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300 active:scale-[0.98]">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    Save All Settings
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
