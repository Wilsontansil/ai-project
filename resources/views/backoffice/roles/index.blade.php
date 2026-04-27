@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.roles.title'))
@section('page-title', __('backoffice.pages.roles.page_title'))

@section('content')
    <div class="space-y-5">
        {{-- Header --}}
        <div
            class="relative overflow-hidden rounded-2xl border border-slate-700/70 bg-gradient-to-br from-slate-900/95 via-slate-900/85 to-slate-950/90 px-4 py-4 sm:px-5">
            <div class="absolute -right-16 -top-16 h-44 w-44 rounded-full bg-cyan-400/10 blur-3xl"></div>
            <div class="relative flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-lg font-semibold sm:text-2xl">{{ __('backoffice.pages.roles.title') }}</h1>
                    <p class="text-xs text-slate-400">{{ __('backoffice.pages.roles.subtitle') }}</p>
                </div>
                <a href="{{ route('backoffice.roles.create') }}"
                    class="rounded-lg bg-cyan-400 px-4 py-2 text-xs font-semibold text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:-translate-y-0.5 hover:bg-cyan-300 sm:text-sm">
                    + {{ __('backoffice.pages.roles.new_role') }}
                </a>
            </div>
        </div>

        @if ($roles->isEmpty())
            <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-8 text-center">
                <p class="text-sm text-slate-400">{{ __('backoffice.pages.roles.no_roles') }}</p>
            </div>
        @else
            <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold">{{ __('backoffice.pages.roles.role_list') }}</h2>
                    <span
                        class="rounded-full border border-cyan-400/30 bg-cyan-500/10 px-2.5 py-1 text-[11px] text-cyan-300">
                        {{ $roles->count() }} {{ __('backoffice.pages.roles.role_list') }}
                    </span>
                </div>
                <div class="overflow-hidden rounded-xl border border-white/10 bg-slate-950/20">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th class="py-2.5 pl-4 pr-3 font-medium">{{ __('backoffice.pages.roles.name') }}</th>
                                    <th class="px-3 py-2.5 font-medium">{{ __('backoffice.pages.roles.users') }}</th>
                                    <th class="px-3 py-2.5 font-medium text-right">{{ __('backoffice.common.actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @foreach ($roles as $role)
                                    <tr class="transition hover:bg-white/5">
                                        <td class="py-3 pl-4 pr-3">
                                            <span
                                                class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-2.5 py-1 font-semibold text-white">
                                                {{ $role->name }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-slate-300">
                                            <span
                                                class="inline-flex items-center rounded-md bg-slate-800/60 px-2 py-1 font-mono text-[11px] text-slate-200">
                                                {{ $role->users_count }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('backoffice.roles.edit', $role) }}"
                                                    class="rounded-lg border border-cyan-400/20 bg-cyan-500/10 px-3 py-1.5 text-[11px] font-medium text-cyan-300 transition hover:bg-cyan-500/20">
                                                    {{ __('backoffice.common.edit') }}
                                                </a>
                                                @if ($role->name !== 'admin')
                                                    <form method="POST"
                                                        action="{{ route('backoffice.roles.destroy', $role) }}"
                                                        onsubmit="return confirm('{{ __('backoffice.pages.roles.delete_confirm', ['name' => $role->name]) }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="rounded-lg border border-rose-400/20 bg-rose-500/10 px-3 py-1.5 text-[11px] font-medium text-rose-300 transition hover:bg-rose-500/20">
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
    </div>
@endsection
