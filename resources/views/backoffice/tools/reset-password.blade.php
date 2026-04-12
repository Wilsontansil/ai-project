@extends('backoffice.partials.layout')

@section('title', 'Reset Password Tool')

@section('content')
    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <h1 class="text-3xl font-semibold">Reset Password Tool</h1>
        <p class="mt-2 text-sm text-slate-300">Tool untuk verifikasi data rekening dan reset password player.</p>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-sm text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-2xl border border-rose-300/30 bg-rose-500/15 px-4 py-3 text-sm text-rose-100">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <h2 class="text-xl font-semibold text-white">Tool Configuration</h2>
        <p class="mt-1 text-xs text-slate-400">Tool key: resetPassword</p>

        <form method="POST" action="{{ route('backoffice.tools.reset-password.update') }}" class="mt-6 space-y-4">
            @csrf

            <div class="flex items-center gap-3">
                <label
                    class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-slate-900/50 px-4 py-2 text-sm text-slate-200">
                    <input type="checkbox" name="is_enabled" value="1" {{ $tool['is_enabled'] ? 'checked' : '' }}
                        class="rounded border-white/20 bg-slate-800 text-cyan-400 focus:ring-cyan-400" />
                    Enable tool
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="display_name" class="mb-2 block text-sm text-slate-200">Display Name</label>
                    <input id="display_name" type="text" name="display_name" value="{{ $tool['display_name'] }}"
                        style="color:#000" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-black outline-none transition focus:border-cyan-400" />
                </div>
                <div>
                    <label for="description" class="mb-2 block text-sm text-slate-200">Description</label>
                    <input id="description" type="text" name="description" value="{{ $tool['description'] }}"
                        style="color:#000" class="w-full rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-black outline-none transition focus:border-cyan-400" />
                </div>
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="rounded-2xl bg-cyan-400 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    Save Settings
                </button>
            </div>
        </form>
    </div>

    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
        <h2 class="text-xl font-semibold text-white">Tool Logs</h2>
        <p class="mt-1 text-sm text-slate-300">Recent reset password requests processed by AI agent.</p>

        <div class="mt-4 overflow-hidden rounded-2xl border border-white/10">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead class="bg-white/5 text-left text-slate-300">
                    <tr>
                        <th class="px-4 py-3 font-medium">Customer</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 bg-slate-950/40">
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-slate-400">Belum ada log data.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
