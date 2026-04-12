<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Backoffice Login</title>
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

            .fallback-note {
                max-width: 720px;
                margin: 16px auto 0;
                padding: 12px 16px;
                border: 1px solid #334155;
                background: #111827;
                border-radius: 10px;
                color: #cbd5e1;
                font-size: 13px;
            }
        </style>
    @endif
</head>

<body class="min-h-screen bg-slate-950 text-slate-100">
    @if (!file_exists(public_path('build/manifest.json')) && !file_exists(public_path('hot')))
        <div class="fallback-note">
            Frontend assets belum di-build. Jalankan <strong>npm run build</strong> di server untuk tampilan penuh.
        </div>
    @endif
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-6 py-12">
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.18),_transparent_32%),radial-gradient(circle_at_bottom_right,_rgba(249,115,22,0.18),_transparent_28%),linear-gradient(135deg,_#020617,_#111827_55%,_#1e293b)]">
        </div>
        <div
            class="relative w-full max-w-md rounded-3xl border border-white/10 bg-white/8 p-8 shadow-2xl backdrop-blur-xl">
            <div class="mb-8">
                <p class="text-sm uppercase tracking-[0.3em] text-cyan-300/80">Backoffice</p>
                <h1 class="mt-3 text-3xl font-semibold text-white">Login Admin</h1>
                <p class="mt-2 text-sm text-slate-300">Masuk untuk melihat customer chat dan aktivitas bot.</p>
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-400/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('backoffice.login.submit') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="mb-2 block text-sm text-slate-200">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-slate-900 outline-none transition focus:border-cyan-400" />
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm text-slate-200">Password</label>
                    <input id="password" name="password" type="password" required
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-slate-900 outline-none transition focus:border-cyan-400" />
                </div>

                <label class="flex items-center gap-3 text-sm text-slate-300">
                    <input type="checkbox" name="remember" value="1"
                        class="rounded border-white/20 bg-slate-900/70 text-cyan-400 focus:ring-cyan-400" />
                    Remember login
                </label>

                <button type="submit"
                    class="w-full rounded-2xl bg-cyan-400 px-4 py-3 font-semibold text-slate-950 transition hover:bg-cyan-300">
                    Masuk
                </button>
            </form>
        </div>
    </div>
</body>

</html>
