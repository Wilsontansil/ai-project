@extends('backoffice.partials.layout')

@section('title', 'New Database Connection')

@php($boActive = 'database-connections')

@section('content')
    <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <h1 class="text-lg font-semibold sm:text-2xl">Add Database Connection</h1>
        <p class="text-xs text-slate-400">Tambahkan koneksi database baru.</p>
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
        <form method="POST" action="{{ route('backoffice.database-connections.store') }}" class="space-y-5">
            @csrf

            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem">
                <div>
                    <label for="name" class="bo-label">Connection Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}"
                        placeholder="e.g. mysqlgame" />
                </div>
                <div>
                    <label for="driver" class="bo-label">Driver</label>
                    <select id="driver" name="driver">
                        <option value="mysql" {{ old('driver') === 'mysql' ? 'selected' : '' }}>MySQL</option>
                        <option value="pgsql" {{ old('driver') === 'pgsql' ? 'selected' : '' }}>PostgreSQL</option>
                        <option value="sqlite" {{ old('driver') === 'sqlite' ? 'selected' : '' }}>SQLite</option>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:1rem">
                <div>
                    <label for="host" class="bo-label">Host</label>
                    <input id="host" type="text" name="host" value="{{ old('host', '127.0.0.1') }}"
                        placeholder="127.0.0.1" />
                </div>
                <div>
                    <label for="port" class="bo-label">Port</label>
                    <input id="port" type="number" name="port" value="{{ old('port', 3306) }}"
                        placeholder="3306" />
                </div>
            </div>

            <div>
                <label for="database" class="bo-label">Database Name</label>
                <input id="database" type="text" name="database" value="{{ old('database') }}"
                    placeholder="e.g. game_db" />
            </div>

            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem">
                <div>
                    <label for="username" class="bo-label">Username</label>
                    <input id="username" type="text" name="username" value="{{ old('username') }}"
                        placeholder="e.g. root" />
                </div>
                <div>
                    <label for="password" class="bo-label">Password</label>
                    <input id="password" type="password" name="password" autocomplete="new-password"
                        placeholder="Database password" />
                </div>
            </div>

            <div>
                <label class="bo-checkbox-label">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }} />
                    Active
                </label>
            </div>

            <div style="display:flex;align-items:center;gap:0.75rem;padding-top:0.5rem">
                <button type="submit" class="bo-btn-primary">Save Connection</button>
                <a href="{{ route('backoffice.database-connections.index') }}" class="bo-btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection
