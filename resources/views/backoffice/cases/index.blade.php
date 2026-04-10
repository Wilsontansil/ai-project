@extends('backoffice.partials.layout')

@section('title', 'Agent Cases')

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold">Agent Cases</h1>
                <p class="mt-2 text-sm text-slate-300">Report dan kelola case untuk memperbaiki behaviour AI agent.</p>
            </div>
            <a href="{{ route('backoffice.cases.create') }}"
                class="rounded-2xl bg-cyan-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                + New Case
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-sm text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    @if ($cases->isEmpty())
        <div class="rounded-3xl border border-white/10 bg-white/5 p-8 text-center backdrop-blur">
            <p class="text-slate-400">Belum ada case. Tambahkan case pertama untuk melatih AI agent.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($cases as $case)
                <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-3">
                                @if ($case->level === 'danger')
                                    <span
                                        class="inline-flex items-center rounded-full bg-red-500/20 px-2.5 py-0.5 text-xs font-semibold text-red-300 ring-1 ring-red-400/30">
                                        DANGER
                                    </span>
                                @elseif ($case->level === 'warning')
                                    <span
                                        class="inline-flex items-center rounded-full bg-amber-500/20 px-2.5 py-0.5 text-xs font-semibold text-amber-300 ring-1 ring-amber-400/30">
                                        WARNING
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-blue-500/20 px-2.5 py-0.5 text-xs font-semibold text-blue-300 ring-1 ring-blue-400/30">
                                        INFO
                                    </span>
                                @endif

                                <h3 class="text-base font-semibold text-white">{{ $case->title }}</h3>

                                @if (!$case->is_active)
                                    <span class="text-xs text-slate-500">(inactive)</span>
                                @endif
                            </div>
                            <p class="mt-2 text-sm text-slate-300">{{ $case->instruction }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $case->created_at->format('d M Y H:i') }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('backoffice.cases.edit', $case) }}"
                                class="rounded-xl border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-white/10">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('backoffice.cases.destroy', $case) }}"
                                onsubmit="return confirm('Hapus case ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="rounded-xl border border-red-400/20 bg-red-500/10 px-3 py-1.5 text-xs text-red-300 transition hover:bg-red-500/20">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
