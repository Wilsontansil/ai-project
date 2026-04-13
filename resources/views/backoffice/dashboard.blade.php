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
    <style>
        #bo-shell input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]),
        #bo-shell textarea,
        #bo-shell select {
            background-color: rgba(15, 23, 42, 0.7);
            color: #e2e8f0;
        }
    </style>
</head>

<body class="min-h-screen bg-slate-950 text-slate-100">
    @if (!file_exists(public_path('build/manifest.json')) && !file_exists(public_path('hot')))
        <div class="fallback-note">
            Frontend assets belum di-build. Jalankan <strong>npm run build</strong> di server untuk tampilan penuh.
        </div>
    @endif
    <div class="min-h-screen bg-[linear-gradient(180deg,_#020617,_#0f172a_40%,_#111827)] p-3 sm:p-4 md:p-6">
        <div id="bo-shell" class="mx-auto flex max-w-7xl flex-col gap-4 lg:gap-6 xl:flex-row xl:items-start xl:gap-8">
            @include('backoffice.partials.sidebar', ['active' => 'customer', 'currentTool' => null])

            <main class="relative z-0 min-w-0 flex-1 space-y-5 lg:space-y-6 xl:pt-1">
                {{-- Header --}}
                <div
                    class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
                    <div>
                        <h1 class="text-lg font-semibold sm:text-2xl">Customer Dashboard</h1>
                        <p class="text-xs text-slate-400">Monitoring customer dari Telegram & WhatsApp.</p>
                    </div>
                </div>

                {{-- Compact Stat Cards --}}
                <div class="grid grid-cols-1 gap-3 sm:gap-4 md:grid-cols-3">
                    <div class="rounded-xl border border-cyan-400/20 bg-cyan-400/10 px-4 py-3 sm:px-5 sm:py-4"
                        style="background-color:rgba(34,211,238,0.08);border:1px solid rgba(34,211,238,0.25);border-radius:12px">
                        <p class="text-[11px] text-cyan-200/70" style="color:rgba(34,211,238,0.7);font-size:11px">Total
                        </p>
                        <p class="text-lg font-bold text-white" style="color:#fff;font-size:18px;font-weight:700">
                            {{ number_format($stats['total_customers']) }}</p>
                    </div>
                    <div class="rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 sm:px-5 sm:py-4"
                        style="background-color:rgba(52,211,153,0.08);border:1px solid rgba(52,211,153,0.25);border-radius:12px">
                        <p class="text-[11px] text-emerald-200/70" style="color:rgba(52,211,153,0.7);font-size:11px">
                            Telegram</p>
                        <p class="text-lg font-bold text-white" style="color:#fff;font-size:18px;font-weight:700">
                            {{ number_format($stats['telegram_customers']) }}</p>
                    </div>
                    <div class="rounded-xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 sm:px-5 sm:py-4"
                        style="background-color:rgba(251,191,36,0.08);border:1px solid rgba(251,191,36,0.25);border-radius:12px">
                        <p class="text-[11px] text-amber-200/70" style="color:rgba(251,191,36,0.7);font-size:11px">
                            WhatsApp</p>
                        <p class="text-lg font-bold text-white" style="color:#fff;font-size:18px;font-weight:700">
                            {{ number_format($stats['whatsapp_customers']) }}</p>
                    </div>
                </div>

                {{-- Customer Table --}}
                <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5">
                    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <h2 class="text-sm font-semibold">Customers</h2>
                        <form method="GET" action="{{ route('backoffice.dashboard') }}"
                            class="flex w-full max-w-full gap-2 sm:max-w-md">
                            <input type="text" name="search" value="{{ $search }}"
                                placeholder="Cari nama, phone, platform..."
                                style="background-color:rgba(15,23,42,0.7);color:#e2e8f0"
                                class="w-full rounded-lg border border-white/10 bg-slate-900/70 px-3 py-1.5 text-xs text-white outline-none transition focus:border-cyan-400" />
                            <button type="submit"
                                class="rounded-lg bg-cyan-400 px-3 py-1.5 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300">Cari</button>
                        </form>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-white/10">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs">
                                <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                                    <tr>
                                        <th class="px-3 py-2 font-medium">Name</th>
                                        <th class="px-3 py-2 font-medium">Platform</th>
                                        <th class="px-3 py-2 font-medium">Phone</th>
                                        <th class="px-3 py-2 font-medium text-center">Msgs</th>
                                        <th class="px-3 py-2 font-medium">Last Seen</th>
                                        <th class="px-3 py-2 font-medium"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    @forelse ($customers as $customer)
                                        <tr class="hover:bg-white/5">
                                            <td class="px-3 py-2 text-white">
                                                {{ $customer->name ?: '-' }}
                                            </td>
                                            <td class="px-3 py-2 text-slate-300">{{ ucfirst($customer->platform) }}</td>
                                            <td class="px-3 py-2 text-slate-400">{{ $customer->phone_number ?: '-' }}
                                            </td>
                                            <td class="px-3 py-2 text-center text-slate-400">
                                                {{ $customer->total_messages }}</td>
                                            <td class="px-3 py-2 text-slate-400">
                                                {{ $customer->last_seen_at?->diffForHumans() ?: '-' }}</td>
                                            <td class="px-3 py-2">
                                                <a href="{{ route('backoffice.customer.chat', $customer->id) }}"
                                                    class="rounded bg-cyan-400/20 px-2 py-1 text-[10px] font-semibold text-cyan-300 transition hover:bg-cyan-400/30">Chat</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-3 py-6 text-center text-slate-500">Belum ada
                                                data customer.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        {{ $customers->links() }}
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>

</html>
