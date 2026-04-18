@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.users.title'))
@section('page-title', __('backoffice.pages.users.page_title'))

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">{{ __('backoffice.pages.users.title') }}</h1>
            <p class="text-xs text-slate-400">{{ __('backoffice.pages.users.subtitle') }}</p>
        </div>
        <a href="{{ route('backoffice.users.create') }}"
            class="rounded-lg bg-cyan-400 px-4 py-2 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300 sm:text-sm">
            + {{ __('backoffice.pages.users.new_user') }}
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-xs text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-xl border border-rose-300/30 bg-rose-500/15 px-4 py-3 text-xs text-rose-100">
            {{ session('error') }}
        </div>
    @endif

    @if ($users->isEmpty())
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-8 text-center">
            <p class="text-sm text-slate-400">{{ __('backoffice.pages.users.no_users') }}</p>
        </div>
    @else
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5">
            <h2 class="mb-4 text-sm font-semibold">{{ __('backoffice.pages.users.user_list') }}
                <span class="ml-1 text-slate-400">({{ $users->count() }})</span>
            </h2>
            <div class="rounded-xl border border-white/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="py-2 pl-4 pr-3 font-medium">{{ __('backoffice.pages.users.name') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.users.email') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.users.role') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.users.created_at') }}</th>
                                <th class="px-3 py-2 font-medium text-right">{{ __('backoffice.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($users as $user)
                                <tr class="transition hover:bg-white/5">
                                    <td class="py-2 pl-4 pr-3">
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-white">{{ $user->name }}</span>
                                            @if ($user->id === auth()->id())
                                                <span
                                                    class="rounded bg-cyan-400/20 px-1.5 py-0.5 text-[10px] font-bold text-cyan-300">YOU</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs text-slate-300">{{ $user->email }}</td>
                                    <td class="px-3 py-2">
                                        @foreach ($user->roles as $role)
                                            @php
                                                $roleColor = match ($role->name) {
                                                    'admin' => 'bg-amber-400/20 text-amber-300 border-amber-400/30',
                                                    'operator' => 'bg-sky-400/20 text-sky-300 border-sky-400/30',
                                                    default => 'bg-slate-400/20 text-slate-300 border-slate-400/30',
                                                };
                                            @endphp
                                            <span
                                                class="inline-block rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase {{ $roleColor }}">
                                                {{ $role->name }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="px-3 py-2 text-slate-400">{{ $user->created_at?->format('d M Y') }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('backoffice.users.edit', $user) }}"
                                                class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-white/10">
                                                {{ __('backoffice.common.edit') }}
                                            </a>
                                            @if ($user->id !== auth()->id())
                                                <form method="POST"
                                                    action="{{ route('backoffice.users.destroy', $user) }}"
                                                    onsubmit="return confirm('{{ __('backoffice.pages.users.delete_confirm', ['name' => $user->name]) }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="rounded-lg border border-red-400/20 bg-red-500/10 px-3 py-1.5 text-xs text-red-300 transition hover:bg-red-500/20">
                                                        {{ __('backoffice.common.delete') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
