@extends('backoffice.partials.layout')

@section('title', 'System Config')
@section('page-title', 'System Config')

@section('content')
    {{-- Header --}}
    <div
        class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">System Config</h1>
            <p class="text-xs text-slate-400">Global key / value config entries. Accessible via <code
                    style="color:#22d3ee">SystemConfig::getValue('key')</code>.</p>
        </div>
        <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap">
            @can('manage settings')
                <form method="POST" action="{{ route('backoffice.system-config.sync-all') }}">
                    @csrf
                    <button type="submit" class="bo-btn-sm" title="Re-resolve all DataModel lookup entries">
                        ↻ Sync All
                    </button>
                </form>
                <a href="{{ route('backoffice.system-config.create') }}"
                    class="rounded-lg bg-cyan-400 px-4 py-2 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300 sm:text-sm">
                    + New Entry
                </a>
            @endcan
        </div>
    </div>

    @if (session('success'))
        <div
            style="background:rgba(16,185,129,0.15);border:1px solid rgba(52,211,153,0.3);border-radius:0.75rem;padding:0.75rem 1rem;font-size:0.75rem;color:#6ee7b7">
            {{ session('success') }}
        </div>
    @endif

    {{-- Search --}}
    <form method="GET" action="{{ route('backoffice.system-config.index') }}"
        class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-3"
        style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search key, value, description..."
            style="flex:1;min-width:220px" />
        <button type="submit" class="bo-btn-sm">Filter</button>
        @if ($search !== '')
            <a class="bo-btn-secondary" href="{{ route('backoffice.system-config.index') }}"
                style="font-size:0.75rem;padding:0.45rem 0.75rem">Reset</a>
        @endif
    </form>

    @if ($configs->isEmpty())
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-8 text-center">
            <p class="text-sm text-slate-400">No system config entries yet.</p>
        </div>
    @else
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5">
            <div class="overflow-hidden rounded-xl border border-white/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-4 py-3" style="width:22%">Key</th>
                                <th class="px-4 py-3">Value</th>
                                <th class="px-4 py-3">Source</th>
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3 text-right" style="width:130px">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($configs as $sc)
                                <tr class="transition hover:bg-white/5">
                                    <td class="px-4 py-3 font-mono text-slate-200">{{ $sc->key }}</td>
                                    <td class="px-4 py-3 text-slate-300" style="word-break:break-all;max-width:260px">
                                        @if (($sc->source_type ?? 'manual') === 'datamodel_lookup')
                                            @if ($sc->value !== null && $sc->value !== '')
                                                <span class="text-emerald-300">{{ $sc->value }}</span><br>
                                            @endif
                                            <span class="text-slate-500" style="font-size:10px">
                                                {{ $sc->lookup_field }}={{ $sc->lookup_value }} → {{ $sc->result_field }}
                                            </span>
                                        @else
                                            {{ Str::limit($sc->value, 80) }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if (($sc->source_type ?? 'manual') === 'datamodel_lookup')
                                            <span
                                                class="rounded-full bg-violet-500/20 px-2 py-0.5 text-[10px] font-semibold text-violet-300 ring-1 ring-violet-400/30">DM
                                                Lookup</span>
                                        @else
                                            <span
                                                class="rounded-full bg-slate-500/20 px-2 py-0.5 text-[10px] font-semibold text-slate-300 ring-1 ring-slate-400/30">Manual</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-400" style="word-break:break-word;max-width:220px">
                                        {{ $sc->description ?: '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @can('manage settings')
                                            <div style="display:inline-flex;align-items:center;gap:0.375rem">
                                                <a href="{{ route('backoffice.system-config.edit', $sc) }}" class="bo-btn-sm"
                                                    style="white-space:nowrap">Edit</a>
                                                <form method="POST"
                                                    action="{{ route('backoffice.system-config.destroy', $sc) }}"
                                                    onsubmit="return confirm('Delete config \'{{ addslashes($sc->key) }}\'?')"
                                                    style="margin:0">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="bo-btn-danger"
                                                        style="white-space:nowrap">Delete</button>
                                                </form>
                                            </div>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($configs->hasPages())
                <div class="pt-3">
                    {{ $configs->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    @endif
@endsection
