@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.roles.new_title'))
@section('page-title', __('backoffice.pages.roles.new_title'))

@section('content')
    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-6">
        <h1 class="mb-1 text-lg font-semibold sm:text-2xl">{{ __('backoffice.pages.roles.new_title') }}</h1>
        <p class="mb-5 text-xs text-slate-400">{{ __('backoffice.pages.roles.new_subtitle') }}</p>

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-rose-300/30 bg-rose-500/15 px-4 py-3 text-xs text-rose-100">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('backoffice.roles.store') }}" class="space-y-5 max-w-lg">
            @csrf

            <div>
                <label for="name" class="mb-1 block text-xs font-medium text-slate-300">
                    {{ __('backoffice.pages.roles.name') }} <span class="text-rose-400">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                    class="block w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-cyan-400 focus:outline-none focus:ring-1 focus:ring-cyan-400">
                <p class="mt-1 text-[11px] text-slate-500">{{ __('backoffice.pages.roles.name_help') }}</p>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                    class="rounded-lg bg-cyan-400 px-5 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    {{ __('backoffice.pages.roles.create_role') }}
                </button>
                <a href="{{ route('backoffice.roles.index') }}"
                    class="rounded-lg border border-white/10 px-5 py-2 text-sm text-slate-300 transition hover:bg-white/5">
                    {{ __('backoffice.common.cancel') }}
                </a>
            </div>
        </form>
    </div>
@endsection
