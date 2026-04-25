@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.roles.title'))
@section('page-title', __('backoffice.pages.roles.page_title'))

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">{{ __('backoffice.pages.roles.title') }}</h1>
            <p class="text-xs text-slate-400">{{ __('backoffice.pages.roles.subtitle') }}</p>
        </div>
        <a href="{{ route('backoffice.roles.create') }}"
            class="rounded-lg bg-cyan-400 px-4 py-2 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300 sm:text-sm">
            + {{ __('backoffice.pages.roles.new_role') }}
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

    @if ($roles->isEmpty())
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-8 text-center">
            <p class="text-sm text-slate-400">{{ __('backoffice.pages.roles.no_roles') }}</p>
        </div>
    @else
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5">
            <h2 class="mb-4 text-sm font-semibold">{{ __('backoffice.pages.roles.role_list') }}
                <span class="ml-1 text-slate-400">({{ $roles->count() }})</span>
            </h2>
            <div class="rounded-xl border border-white/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="py-2 pl-4 pr-3 font-medium">{{ __('backoffice.pages.roles.name') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.roles.permissions') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.roles.users') }}</th>
                                <th class="px-3 py-2 font-medium text-right">{{ __('backoffice.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($roles as $role)
                                <tr class="transition hover:bg-white/5">
                                    <td class="py-2 pl-4 pr-3">
                                        <span class="font-semibold text-white">{{ $role->name }}</span>
                                    </td>
                                    <td class="px-3 py-2">
                                        @if ($role->permissions->isEmpty())
                                            <span class="text-slate-500">—</span>
                                        @else
                                            <span
                                                class="text-slate-300">{{ $role->permissions->pluck('name')->implode(', ') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-slate-300">{{ $role->users_count }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('backoffice.roles.edit', $role) }}"
                                                class="rounded-md bg-cyan-500/20 px-3 py-1.5 text-[11px] font-medium text-cyan-300 transition hover:bg-cyan-500/30">
                                                {{ __('backoffice.common.edit') }}
                                            </a>
                                            @if ($role->name !== 'admin')
                                                <form method="POST"
                                                    action="{{ route('backoffice.roles.destroy', $role) }}"
                                                    onsubmit="return confirm('{{ __('backoffice.pages.roles.delete_confirm', ['name' => $role->name]) }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="rounded-md bg-rose-500/20 px-3 py-1.5 text-[11px] font-medium text-rose-300 transition hover:bg-rose-500/30">
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
