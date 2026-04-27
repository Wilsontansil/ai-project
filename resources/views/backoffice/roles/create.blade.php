@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.roles.new_title'))
@section('page-title', __('backoffice.pages.roles.new_title'))

@section('content')
    <div class="mx-auto w-full max-w-5xl space-y-5">
        <div
            class="relative overflow-hidden rounded-2xl border border-slate-700/70 bg-gradient-to-br from-slate-900/95 via-slate-900/85 to-slate-950/90 p-4 sm:p-6">
            <div class="absolute -right-16 -top-16 h-44 w-44 rounded-full bg-cyan-400/10 blur-3xl"></div>
            <div class="relative">
                <h1 class="mb-1 text-lg font-semibold sm:text-2xl">{{ __('backoffice.pages.roles.new_title') }}</h1>
                <p class="text-xs text-slate-400">{{ __('backoffice.pages.roles.new_subtitle') }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-6">
            <form method="POST" action="{{ route('backoffice.roles.store') }}" class="space-y-6 max-w-2xl">
                @csrf

                <div class="rounded-xl border border-white/10 bg-slate-950/40 p-4 sm:p-5">
                    <p class="mb-3 text-[11px] uppercase tracking-wide text-slate-400">
                        {{ __('backoffice.pages.roles.name') }}</p>
                    <label for="name" class="mb-1 block text-xs font-medium text-slate-300">
                        {{ __('backoffice.pages.roles.name') }} <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                        class="block w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-cyan-400 focus:outline-none focus:ring-1 focus:ring-cyan-400">
                    <p class="mt-1 text-[11px] text-slate-500">{{ __('backoffice.pages.roles.name_help') }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-1">
                    <button type="submit"
                        class="rounded-lg bg-cyan-400 px-5 py-2 text-sm font-semibold text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:-translate-y-0.5 hover:bg-cyan-300">
                        {{ __('backoffice.pages.roles.create_role') }}
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
