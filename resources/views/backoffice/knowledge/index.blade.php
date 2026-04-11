@extends('backoffice.partials.layout')

@section('title', 'Knowledge Base')

@section('content')
    {{-- Header --}}
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold">Knowledge Base</h1>
                <p class="mt-2 text-sm text-slate-300">Kelola training data &amp; AI learned memory.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('backoffice.knowledge.upload') }}"
                    class="rounded-2xl border border-cyan-400/40 px-5 py-2.5 text-sm font-semibold text-cyan-300 transition hover:bg-cyan-400/10">
                    📁 Upload File
                </a>
                <a href="{{ route('backoffice.knowledge.create') }}"
                    class="rounded-2xl bg-cyan-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    + Add Manual
                </a>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
            <p class="text-xs text-slate-400">Total Knowledge</p>
            <p class="mt-1 text-2xl font-bold text-white">{{ $stats['total_knowledge'] }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
            <p class="text-xs text-slate-400">Active Knowledge</p>
            <p class="mt-1 text-2xl font-bold text-emerald-300">{{ $stats['active_knowledge'] }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
            <p class="text-xs text-slate-400">Total Memory</p>
            <p class="mt-1 text-2xl font-bold text-white">{{ $stats['total_memories'] }}</p>
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
            <p class="text-xs text-slate-400">Approved Memory</p>
            <p class="mt-1 text-2xl font-bold text-cyan-300">{{ $stats['approved_memories'] }}</p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-sm text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    {{-- Tabs --}}
    <div class="flex gap-1 rounded-2xl border border-white/10 bg-white/5 p-1 backdrop-blur">
        <a href="{{ route('backoffice.knowledge.index', ['tab' => 'knowledge', 'search' => $search]) }}"
            class="rounded-xl px-5 py-2.5 text-sm font-medium transition {{ $tab === 'knowledge' ? 'bg-cyan-400 text-slate-950' : 'text-slate-300 hover:bg-white/10' }}">
            📚 Training Data
        </a>
        <a href="{{ route('backoffice.knowledge.index', ['tab' => 'memories', 'search' => $search]) }}"
            class="rounded-xl px-5 py-2.5 text-sm font-medium transition {{ $tab === 'memories' ? 'bg-cyan-400 text-slate-950' : 'text-slate-300 hover:bg-white/10' }}">
            🧠 AI Memory
        </a>
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('backoffice.knowledge.index') }}" class="flex gap-3">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="text" name="search" value="{{ $search }}" placeholder="Cari knowledge atau memory..."
            class="flex-1 rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">
        <button type="submit"
            class="rounded-2xl bg-white/10 px-5 py-3 text-sm font-medium text-white transition hover:bg-white/15">
            Search
        </button>
        @if ($search !== '')
            <a href="{{ route('backoffice.knowledge.index', ['tab' => $tab]) }}"
                class="rounded-2xl bg-white/5 px-5 py-3 text-sm text-slate-400 transition hover:bg-white/10">
                Clear
            </a>
        @endif
    </form>

    {{-- Knowledge Tab --}}
    @if ($tab === 'knowledge')
        @if ($entries->isEmpty())
            <div class="rounded-3xl border border-white/10 bg-white/5 p-8 text-center backdrop-blur">
                <p class="text-slate-400">Belum ada training data. Tambahkan secara manual atau upload file.</p>
            </div>
        @else
            <div class="rounded-3xl border border-white/10 bg-white/5 backdrop-blur overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10 text-sm">
                        <thead class="bg-white/5 text-left text-slate-300">
                            <tr>
                                <th class="px-5 py-3.5 font-medium">Question</th>
                                <th class="px-5 py-3.5 font-medium">Answer</th>
                                <th class="px-5 py-3.5 font-medium">Category</th>
                                <th class="px-5 py-3.5 font-medium">Source</th>
                                <th class="px-5 py-3.5 font-medium text-center">Status</th>
                                <th class="px-5 py-3.5 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 bg-slate-950/40">
                            @foreach ($entries as $entry)
                                <tr class="transition hover:bg-white/[0.03]">
                                    <td class="px-5 py-3.5 max-w-[200px]">
                                        <p class="truncate text-white" title="{{ $entry->question }}">
                                            {{ Str::limit($entry->question, 60) }}</p>
                                    </td>
                                    <td class="px-5 py-3.5 max-w-[250px]">
                                        <p class="truncate text-slate-300" title="{{ $entry->answer }}">
                                            {{ Str::limit($entry->answer, 80) }}</p>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        @if ($entry->category)
                                            <span
                                                class="rounded-full bg-purple-500/20 px-2.5 py-1 text-xs font-medium text-purple-300">{{ $entry->category }}</span>
                                        @else
                                            <span class="text-slate-500">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5">
                                        @if ($entry->source === 'file')
                                            <span
                                                class="rounded-full bg-blue-500/20 px-2.5 py-1 text-xs font-medium text-blue-300"
                                                title="{{ $entry->source_file }}">📁 File</span>
                                        @else
                                            <span
                                                class="rounded-full bg-slate-500/20 px-2.5 py-1 text-xs font-medium text-slate-300">✏️
                                                Manual</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if ($entry->is_active)
                                            <span
                                                class="rounded-full bg-emerald-500/20 px-2.5 py-1 text-xs font-medium text-emerald-300">Active</span>
                                        @else
                                            <span
                                                class="rounded-full bg-rose-500/20 px-2.5 py-1 text-xs font-medium text-rose-300">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('backoffice.knowledge.edit', $entry) }}"
                                                class="rounded-xl bg-white/10 px-3 py-1.5 text-xs text-cyan-300 transition hover:bg-white/15">Edit</a>
                                            <form method="POST"
                                                action="{{ route('backoffice.knowledge.destroy', $entry) }}"
                                                onsubmit="return confirm('Hapus knowledge ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="rounded-xl bg-rose-500/20 px-3 py-1.5 text-xs text-rose-300 transition hover:bg-rose-500/30">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="flex justify-center">
                {{ $entries->links() }}
            </div>
        @endif

        {{-- Memories Tab --}}
    @else
        @if ($memories->isEmpty())
            <div class="rounded-3xl border border-white/10 bg-white/5 p-8 text-center backdrop-blur">
                <p class="text-slate-400">Belum ada AI learned memory. AI akan belajar secara otomatis dari percakapan.</p>
            </div>
        @else
            <div class="rounded-3xl border border-white/10 bg-white/5 backdrop-blur overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-white/10 text-sm">
                        <thead class="bg-white/5 text-left text-slate-300">
                            <tr>
                                <th class="px-5 py-3.5 font-medium">Pattern</th>
                                <th class="px-5 py-3.5 font-medium">Response</th>
                                <th class="px-5 py-3.5 font-medium">Category</th>
                                <th class="px-5 py-3.5 font-medium text-center">Hits</th>
                                <th class="px-5 py-3.5 font-medium text-center">Status</th>
                                <th class="px-5 py-3.5 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 bg-slate-950/40">
                            @foreach ($memories as $memory)
                                <tr class="transition hover:bg-white/[0.03]">
                                    <td class="px-5 py-3.5 max-w-[200px]">
                                        <p class="truncate text-white" title="{{ $memory->pattern }}">
                                            {{ Str::limit($memory->pattern, 60) }}</p>
                                    </td>
                                    <td class="px-5 py-3.5 max-w-[250px]">
                                        <p class="truncate text-slate-300" title="{{ $memory->learned_response }}">
                                            {{ Str::limit($memory->learned_response, 80) }}</p>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        @if ($memory->category)
                                            <span
                                                class="rounded-full bg-purple-500/20 px-2.5 py-1 text-xs font-medium text-purple-300">{{ $memory->category }}</span>
                                        @else
                                            <span class="text-slate-500">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="font-mono text-xs text-slate-300">{{ $memory->hit_count }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if ($memory->is_approved)
                                            <span
                                                class="rounded-full bg-emerald-500/20 px-2.5 py-1 text-xs font-medium text-emerald-300">Approved</span>
                                        @else
                                            <span
                                                class="rounded-full bg-amber-500/20 px-2.5 py-1 text-xs font-medium text-amber-300">Pending</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            @if (!$memory->is_approved)
                                                <form method="POST"
                                                    action="{{ route('backoffice.knowledge.memory.approve', $memory) }}">
                                                    @csrf
                                                    <button type="submit"
                                                        class="rounded-xl bg-emerald-500/20 px-3 py-1.5 text-xs text-emerald-300 transition hover:bg-emerald-500/30">Approve</button>
                                                </form>
                                            @else
                                                <form method="POST"
                                                    action="{{ route('backoffice.knowledge.memory.reject', $memory) }}">
                                                    @csrf
                                                    <button type="submit"
                                                        class="rounded-xl bg-amber-500/20 px-3 py-1.5 text-xs text-amber-300 transition hover:bg-amber-500/30">Reject</button>
                                                </form>
                                            @endif
                                            <form method="POST"
                                                action="{{ route('backoffice.knowledge.memory.destroy', $memory) }}"
                                                onsubmit="return confirm('Hapus memory ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="rounded-xl bg-rose-500/20 px-3 py-1.5 text-xs text-rose-300 transition hover:bg-rose-500/30">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="flex justify-center">
                {{ $memories->links() }}
            </div>
        @endif
    @endif
@endsection
