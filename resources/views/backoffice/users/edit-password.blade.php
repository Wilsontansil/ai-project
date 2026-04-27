@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.users.edit_password'))
@section('page-title', __('backoffice.pages.users.page_title'))

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <h1 class="text-3xl font-semibold">{{ __('backoffice.pages.users.edit_password') }}</h1>
        <p class="mt-2 text-sm text-slate-300">{{ __('backoffice.pages.users.edit_password_subtitle') }}</p>
    </div>

    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        {{-- User info badge --}}
        <div class="mb-5 flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3">
            <div>
                <p class="text-sm font-semibold text-white">{{ $user->name }}</p>
                <p class="text-xs text-slate-400">{{ $user->username }} &middot; {{ $user->email }}</p>
            </div>
            @if ($user->id === auth()->id())
                <span class="ml-auto rounded bg-cyan-400/20 px-2 py-0.5 text-[10px] font-bold text-cyan-300">YOU</span>
            @endif
        </div>

        <form method="POST" action="{{ route('backoffice.users.password.update', $user) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="password"
                        class="mb-2 block text-sm text-slate-200">{{ __('backoffice.pages.users.password') }}</label>
                    <input id="password" type="password" name="password" placeholder="••••••••"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400"
                        autofocus />
                </div>
                <div>
                    <label for="password_confirmation"
                        class="mb-2 block text-sm text-slate-200">{{ __('backoffice.pages.users.password_confirmation') }}</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" placeholder="••••••••"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bo-btn-primary">
                    {{ __('backoffice.pages.users.update_password') }}
                </button>
                <a href="{{ route('backoffice.users.index') }}" class="bo-btn-secondary">
                    {{ __('backoffice.common.cancel') }}
                </a>
            </div>
        </form>
    </div>
@endsection
