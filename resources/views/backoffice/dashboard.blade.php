<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Backoffice Dashboard</title>
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
                max-width: 1100px;
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
    <div class="min-h-screen bg-[linear-gradient(180deg,_#020617,_#0f172a_40%,_#111827)] p-4 md:p-6">
        <div id="bo-shell" class="mx-auto flex max-w-7xl gap-6">
            @include('backoffice.partials.sidebar', ['active' => 'customer', 'currentTool' => null])

            <main class="min-w-0 flex-1 space-y-6">
                <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                    <h1 class="text-3xl font-semibold">Customer Dashboard</h1>
                    <p class="mt-2 text-sm text-slate-300">Monitoring data customer yang masuk dari Telegram dan
                        WhatsApp.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-4">
                    <div class="rounded-3xl border border-cyan-400/20 bg-cyan-400/10 p-5">
                        <p class="text-sm text-cyan-100/80">Total Customer</p>
                        <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($stats['total_customers']) }}
                        </p>
                    </div>
                    <div class="rounded-3xl border border-emerald-400/20 bg-emerald-400/10 p-5">
                        <p class="text-sm text-emerald-100/80">Telegram</p>
                        <p class="mt-2 text-3xl font-semibold text-white">
                            {{ number_format($stats['telegram_customers']) }}</p>
                    </div>
                    <div class="rounded-3xl border border-amber-400/20 bg-amber-400/10 p-5">
                        <p class="text-sm text-amber-100/80">WhatsApp</p>
                        <p class="mt-2 text-3xl font-semibold text-white">
                            {{ number_format($stats['whatsapp_customers']) }}</p>
                    </div>
                    <a href="{{ route('backoffice.escalations.index') }}"
                        class="rounded-3xl border border-red-400/20 bg-red-400/10 p-5 transition hover:bg-red-400/15">
                        <p class="text-sm text-red-100/80">Need Human</p>
                        <p class="mt-2 text-3xl font-semibold text-white">
                            {{ number_format($stats['needs_human']) }}</p>
                    </a>
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                    <div class="mb-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-xl font-semibold">Customer Table</h2>
                            <p class="mt-1 text-sm text-slate-300">List customer terbaru dan status aktivitas terakhir.
                            </p>
                        </div>
                        <form method="GET" action="{{ route('backoffice.dashboard') }}"
                            class="flex w-full max-w-md gap-3">
                            <input type="text" name="search" value="{{ $search }}"
                                placeholder="Cari nama, phone, platform, user id"
                                style="background-color:rgba(15,23,42,0.7);color:#e2e8f0" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                            <button type="submit"
                                class="rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">Cari</button>
                        </form>
                    </div>

                    <div class="overflow-hidden rounded-3xl border border-white/10">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-white/10 text-sm">
                                <thead class="bg-white/5 text-left text-slate-300">
                                    <tr>
                                        <th class="px-4 py-3 font-medium">Name</th>
                                        <th class="px-4 py-3 font-medium">Platform</th>
                                        <th class="px-4 py-3 font-medium">Platform User ID</th>
                                        <th class="px-4 py-3 font-medium">Phone</th>
                                        <th class="px-4 py-3 font-medium">Messages</th>
                                        <th class="px-4 py-3 font-medium">Last Seen</th>
                                        <th class="px-4 py-3 font-medium">Chat</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5 bg-slate-950/40">
                                    @forelse ($customers as $customer)
                                        <tr class="hover:bg-white/5">
                                            <td class="px-4 py-3 text-white">
                                                {{ $customer->name ?: '-' }}
                                                @if ($customer->needs_human)
                                                    <span
                                                        class="ml-1.5 inline-flex items-center rounded-full bg-red-500/20 px-2 py-0.5 text-[10px] font-bold text-red-300">NEED
                                                        HUMAN</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-slate-200">{{ ucfirst($customer->platform) }}
                                            </td>
                                            <td class="px-4 py-3 text-slate-300">{{ $customer->platform_user_id }}</td>
                                            <td class="px-4 py-3 text-slate-300">{{ $customer->phone_number ?: '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-slate-300">
                                                {{ number_format($customer->total_messages) }}</td>
                                            <td class="px-4 py-3 text-slate-300">
                                                {{ $customer->last_seen_at?->diffForHumans() ?: '-' }}</td>
                                            <td class="px-4 py-3">
                                                <a href="{{ route('backoffice.customer.chat', $customer->id) }}"
                                                    class="rounded-xl bg-cyan-400/20 px-3 py-1.5 text-xs font-semibold text-cyan-300 transition hover:bg-cyan-400/30">Open
                                                    Chat</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-8 text-center text-slate-400">Belum ada
                                                data customer.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-5">
                        {{ $customers->links() }}
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>
