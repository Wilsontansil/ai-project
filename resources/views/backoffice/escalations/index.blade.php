@extends('backoffice.partials.layout')

@section('title', 'Escalations')

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold">Escalations</h1>
                <p class="mt-2 text-sm text-slate-300">Customer yang butuh bantuan human agent.</p>
            </div>
            <div class="flex items-center gap-3">
                @if ($counts['unread'] > 0)
                    <form method="POST" action="{{ route('backoffice.escalations.markAllRead') }}">
                        @csrf
                        <button type="submit"
                            class="rounded-2xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm font-medium text-slate-200 transition hover:bg-white/10">
                            Tandai Semua Dibaca
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Filter tabs --}}
    <div class="flex gap-2">
        <a href="{{ route('backoffice.escalations.index', ['filter' => 'open']) }}"
            class="rounded-xl px-4 py-2 text-sm font-medium transition {{ $filter === 'open' ? 'bg-red-500/20 text-red-300 border border-red-400/30' : 'bg-white/5 text-slate-300 border border-white/10 hover:bg-white/10' }}">
            Open
            @if ($counts['open'] > 0)
                <span
                    class="ml-1.5 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-xs font-bold text-white">{{ $counts['open'] }}</span>
            @endif
        </a>
        <a href="{{ route('backoffice.escalations.index', ['filter' => 'resolved']) }}"
            class="rounded-xl px-4 py-2 text-sm font-medium transition {{ $filter === 'resolved' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-400/30' : 'bg-white/5 text-slate-300 border border-white/10 hover:bg-white/10' }}">
            Resolved
        </a>
        <a href="{{ route('backoffice.escalations.index', ['filter' => 'all']) }}"
            class="rounded-xl px-4 py-2 text-sm font-medium transition {{ $filter === 'all' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-400/30' : 'bg-white/5 text-slate-300 border border-white/10 hover:bg-white/10' }}">
            All
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-sm text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    @if ($notifications->isEmpty())
        <div class="rounded-3xl border border-white/10 bg-white/5 p-8 text-center backdrop-blur">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500/10">
                <svg class="h-6 w-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <p class="text-slate-400">
                {{ $filter === 'open' ? 'Tidak ada eskalasi aktif. Semua customer sudah ditangani AI.' : 'Tidak ada eskalasi ditemukan.' }}
            </p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($notifications as $notif)
                <div
                    class="rounded-2xl border {{ $notif->is_read ? 'border-white/10 bg-white/[0.03]' : 'border-amber-400/30 bg-amber-500/5' }} p-5 transition hover:bg-white/[0.06]">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4 min-w-0">
                            {{-- Status indicator --}}
                            <div
                                class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $notif->resolved_at ? 'bg-emerald-500/15' : 'bg-red-500/15' }}">
                                @if ($notif->resolved_at)
                                    <svg class="h-5 w-5 text-emerald-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                @else
                                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                @endif
                            </div>

                            <div class="min-w-0">
                                {{-- Customer info --}}
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold text-white">
                                        {{ $notif->customer?->name ?? ($notif->customer?->platform_user_id ?? 'Unknown') }}
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium {{ $notif->channel === 'whatsapp' ? 'bg-green-500/15 text-green-300' : 'bg-blue-500/15 text-blue-300' }}">
                                        {{ ucfirst($notif->channel) }}
                                    </span>
                                    @if (!$notif->is_read)
                                        <span
                                            class="inline-flex items-center rounded-full bg-amber-500/20 px-2 py-0.5 text-xs font-bold text-amber-300">NEW</span>
                                    @endif
                                </div>

                                {{-- Last user message --}}
                                @if ($notif->last_message)
                                    <p class="mt-1.5 text-sm text-slate-300">
                                        <span class="text-slate-500">Pesan:</span>
                                        {{ Str::limit($notif->last_message, 200) }}
                                    </p>
                                @endif

                                {{-- AI reason --}}
                                <p class="mt-1 text-sm text-slate-400">
                                    <span class="text-slate-500">AI:</span> {{ Str::limit($notif->reason, 200) }}
                                </p>

                                {{-- Timestamp --}}
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ $notif->created_at->diffForHumans() }}
                                    @if ($notif->resolved_at)
                                        &middot; <span class="text-emerald-400">Diselesaikan
                                            {{ $notif->resolved_at->diffForHumans() }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex shrink-0 items-center gap-2">
                            @if ($notif->customer)
                                <a href="{{ route('backoffice.customer.chat', $notif->customer) }}"
                                    class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-medium text-slate-200 transition hover:bg-white/10"
                                    title="Lihat chat">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                </a>
                            @endif

                            @if (!$notif->resolved_at)
                                @if (!$notif->is_read)
                                    <form method="POST" action="{{ route('backoffice.escalations.markRead', $notif) }}">
                                        @csrf
                                        <button type="submit"
                                            class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-medium text-slate-200 transition hover:bg-white/10"
                                            title="Tandai sudah dibaca">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('backoffice.escalations.resolve', $notif) }}">
                                    @csrf
                                    <button type="submit"
                                        class="rounded-xl bg-emerald-500/15 border border-emerald-400/30 px-3 py-2 text-xs font-medium text-emerald-300 transition hover:bg-emerald-500/25"
                                        title="Tandai selesai">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    @endif
@endsection
