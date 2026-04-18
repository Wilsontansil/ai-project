@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.users.new_user'))
@section('page-title', __('backoffice.pages.users.page_title'))

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <h1 class="text-3xl font-semibold">{{ __('backoffice.pages.users.new_user') }}</h1>
        <p class="mt-2 text-sm text-slate-300">{{ __('backoffice.pages.users.new_subtitle') }}</p>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-300/30 bg-rose-500/15 px-4 py-3 text-sm text-rose-100">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <form method="POST" action="{{ route('backoffice.users.store') }}" class="space-y-5">
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="name"
                        class="mb-2 block text-sm text-slate-200">{{ __('backoffice.pages.users.name') }}</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="John Doe"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
                <div>
                    <label for="email"
                        class="mb-2 block text-sm text-slate-200">{{ __('backoffice.pages.users.email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                        placeholder="user@example.com"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="password"
                        class="mb-2 block text-sm text-slate-200">{{ __('backoffice.pages.users.password') }}</label>
                    <input id="password" type="password" name="password" placeholder="••••••••"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                    <p class="mt-1 text-xs text-slate-400">{{ __('backoffice.pages.users.password_min') }}</p>
                </div>
                <div>
                    <label for="password_confirmation"
                        class="mb-2 block text-sm text-slate-200">{{ __('backoffice.pages.users.password_confirmation') }}</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" placeholder="••••••••"
                        class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400" />
                </div>
            </div>

            <div>
                <label for="role"
                    class="mb-2 block text-sm text-slate-200">{{ __('backoffice.pages.users.role') }}</label>
                <select id="role" name="role"
                    class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-white outline-none transition focus:border-cyan-400">
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>
                            {{ ucfirst($role->name) }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">{{ __('backoffice.pages.users.role_help') }}</p>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bo-btn-primary">
                    {{ __('backoffice.pages.users.create_user') }}
                </button>
                <a href="{{ route('backoffice.users.index') }}" class="bo-btn-secondary">
                    {{ __('backoffice.common.cancel') }}
                </a>
            </div>
        </form>
    </div>
@endsection
