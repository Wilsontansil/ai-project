@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.db_connections.edit_title'))
@section('page-title', __('backoffice.pages.db_connections.page_title'))

@php($boActive = 'database-connections')

@section('content')
    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <h1 class="text-lg font-semibold sm:text-2xl">{{ __('backoffice.pages.db_connections.edit_title') }}</h1>
        <p class="text-xs text-slate-400">{{ $connection->name }}</p>
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

    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-6">
        <form method="POST" action="{{ route('backoffice.database-connections.update', $connection) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem">
                <div>
                    <label for="name"
                        class="bo-label">{{ __('backoffice.pages.db_connections.connection_name') }}</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $connection->name) }}"
                        placeholder="e.g. mysqlgame" />
                </div>
                <div>
                    <label for="driver" class="bo-label">{{ __('backoffice.pages.db_connections.driver') }}</label>
                    <select id="driver" name="driver">
                        <option value="mysql" {{ old('driver', $connection->driver) === 'mysql' ? 'selected' : '' }}>MySQL
                        </option>
                        <option value="pgsql" {{ old('driver', $connection->driver) === 'pgsql' ? 'selected' : '' }}>
                            PostgreSQL</option>
                        <option value="sqlite" {{ old('driver', $connection->driver) === 'sqlite' ? 'selected' : '' }}>
                            SQLite</option>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:1rem">
                <div>
                    <label for="host" class="bo-label">{{ __('backoffice.pages.db_connections.host') }}</label>
                    <input id="host" type="text" name="host" value="{{ old('host', $connection->host) }}"
                        placeholder="127.0.0.1" />
                </div>
                <div>
                    <label for="port" class="bo-label">Port</label>
                    <input id="port" type="number" name="port" value="{{ old('port', $connection->port) }}"
                        placeholder="3306" />
                </div>
            </div>

            <div>
                <label for="database" class="bo-label">{{ __('backoffice.pages.db_connections.database_name') }}</label>
                <input id="database" type="text" name="database" value="{{ old('database', $connection->database) }}"
                    placeholder="e.g. game_db" />
            </div>

            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem">
                <div>
                    <label for="username" class="bo-label">{{ __('backoffice.pages.db_connections.username') }}</label>
                    <input id="username" type="text" name="username"
                        value="{{ old('username', $connection->username) }}" placeholder="e.g. root" />
                </div>
                <div>
                    <label for="password" class="bo-label">{{ __('backoffice.pages.db_connections.password') }}</label>
                    <input id="password" type="password" name="password" autocomplete="new-password"
                        placeholder="••••••••" />
                    <div style="display:flex;align-items:center;gap:0.35rem;margin-top:0.35rem">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;flex-shrink:0" fill="none"
                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="text-slate-400">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        <span
                            class="text-xs text-slate-400">{{ __('backoffice.pages.db_connections.password_keep_hint') }}</span>
                    </div>
                </div>
            </div>

            <div>
                <label class="bo-checkbox-label">
                    <input type="checkbox" name="is_active" value="1"
                        {{ old('is_active', $connection->is_active) ? 'checked' : '' }} />
                    {{ __('backoffice.common.active') }}
                </label>
            </div>

            <div style="display:flex;align-items:center;gap:0.75rem;padding-top:0.5rem">
                <button type="submit"
                    class="bo-btn-primary">{{ __('backoffice.pages.db_connections.update_connection') }}</button>
                <a href="{{ route('backoffice.database-connections.index') }}"
                    class="bo-btn-secondary">{{ __('backoffice.common.cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
