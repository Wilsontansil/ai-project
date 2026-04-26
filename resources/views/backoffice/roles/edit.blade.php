@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.roles.edit_title'))
@section('page-title', __('backoffice.pages.roles.edit_title'))

@section('content')
    @php
        $selectedPermissionsCount = count($rolePerms);
        $totalPermissionsCount = $allPermissions->count();
    @endphp

    <div class="space-y-5">

        <div
            class="relative overflow-hidden rounded-2xl border border-slate-700/70 bg-gradient-to-br from-slate-900/95 via-slate-900/85 to-slate-950/90 p-4 sm:p-6">
            <div class="absolute -right-16 -top-16 h-44 w-44 rounded-full bg-cyan-400/10 blur-3xl"></div>
            <div class="relative">
                <h1 class="mb-1 text-lg font-semibold sm:text-2xl">{{ __('backoffice.pages.roles.edit_title') }}</h1>
                <p class="mb-4 text-xs text-slate-400">
                    {{ __('backoffice.pages.roles.edit_subtitle', ['role' => $role->name]) }}
                </p>
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-[11px] font-semibold text-slate-200">
                        {{ __('backoffice.pages.roles.name') }}: {{ $role->name }}
                    </span>
                    <span
                        class="rounded-full border border-cyan-400/30 bg-cyan-500/10 px-2.5 py-1 text-[11px] text-cyan-300">
                        {{ $selectedPermissionsCount }}/{{ $totalPermissionsCount }}
                        {{ __('backoffice.pages.roles.assign_permissions') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-6">
            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-rose-300/30 bg-rose-500/15 px-4 py-3 text-xs text-rose-100">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div
                    class="mb-4 rounded-xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-xs text-emerald-100">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('backoffice.roles.update', $role) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="rounded-xl border border-white/10 bg-slate-950/40 p-4 sm:p-5">
                    <p class="mb-3 text-[11px] uppercase tracking-wide text-slate-400">
                        {{ __('backoffice.pages.roles.name') }}</p>
                    <label for="name" class="mb-1 block text-xs font-medium text-slate-300">
                        {{ __('backoffice.pages.roles.name') }} <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name', $role->name) }}" required
                        @if ($role->name === 'admin') readonly @endif
                        class="block w-full max-w-2xl rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-cyan-400 focus:outline-none focus:ring-1 focus:ring-cyan-400 disabled:opacity-60 read-only:opacity-60">
                    @if ($role->name === 'admin')
                        <p class="mt-1 text-[11px] text-amber-400">{{ __('backoffice.pages.roles.admin_name_locked') }}</p>
                    @endif
                </div>

                <div class="rounded-xl border border-white/10 bg-slate-950/40 p-4 sm:p-5">
                    <h2 class="mb-1 text-sm font-semibold text-slate-200">
                        {{ __('backoffice.pages.roles.assign_permissions') }}</h2>
                    <p class="mb-3 text-xs text-slate-400">{{ __('backoffice.pages.roles.assign_permissions_help') }}</p>

                    @if ($allPermissions->isEmpty())
                        <p class="text-xs text-slate-500">{{ __('backoffice.pages.roles.no_permissions') }}</p>
                    @else
                        <div class="max-h-[50vh] overflow-y-auto pr-1">
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($allPermissions as $permission)
                                    <label
                                        class="group flex cursor-pointer items-center gap-2.5 rounded-lg border border-white/10 bg-slate-900/40 px-3 py-2.5 transition hover:border-cyan-400/30 hover:bg-cyan-500/5
                                        {{ in_array($permission->name, $rolePerms) ? 'border-cyan-500/40 bg-cyan-500/10' : '' }}">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                            {{ in_array($permission->name, $rolePerms) ? 'checked' : '' }}
                                            class="h-4 w-4 rounded border-white/20 bg-white/5 text-cyan-400 focus:ring-cyan-400">
                                        <span
                                            class="text-xs text-slate-200 transition group-hover:text-cyan-200">{{ $permission->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-1">
                    <button type="submit"
                        class="rounded-lg bg-cyan-400 px-5 py-2 text-sm font-semibold text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:-translate-y-0.5 hover:bg-cyan-300">
                        {{ __('backoffice.pages.roles.save_changes') }}
                    </button>
                    <a href="{{ route('backoffice.roles.index') }}"
                        class="rounded-lg border border-white/10 px-5 py-2 text-sm text-slate-300 transition hover:bg-white/5">
                        {{ __('backoffice.common.cancel') }}
                    </a>
                </div>
            </form>
        </div>

    </div>
@endsection
