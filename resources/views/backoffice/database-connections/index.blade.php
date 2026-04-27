@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.db_connections.title'))
@section('page-title', __('backoffice.pages.db_connections.page_title'))

@php($boActive = 'database-connections')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">{{ __('backoffice.pages.db_connections.title') }}</h1>
            <p class="text-xs text-slate-400">{{ __('backoffice.pages.db_connections.subtitle') }}</p>
        </div>
        <a href="{{ route('backoffice.database-connections.create') }}" class="bo-btn-primary"
            style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.5rem 1rem;font-size:13px">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            {{ __('backoffice.pages.db_connections.add_connection') }}
        </a>
    </div>

    @if ($connections->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-600 bg-slate-900/50 p-10 text-center">
            <svg class="mx-auto mb-3 h-10 w-10 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 3.75c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
            </svg>
            <p class="text-sm text-slate-400">{{ __('backoffice.pages.db_connections.no_connections') }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ __('backoffice.pages.db_connections.click_add_connection') }}</p>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-700/70 bg-slate-900/85">
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="border-bottom:1px solid rgba(51,65,85,0.5)">
                        <th
                            style="padding:0.75rem 1rem;text-align:left;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em">
                            {{ __('backoffice.pages.db_connections.name') }}</th>
                        <th
                            style="padding:0.75rem 1rem;text-align:left;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em">
                            {{ __('backoffice.pages.db_connections.driver') }}</th>
                        <th
                            style="padding:0.75rem 1rem;text-align:left;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em">
                            {{ __('backoffice.pages.db_connections.host') }}</th>
                        <th
                            style="padding:0.75rem 1rem;text-align:left;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em">
                            {{ __('backoffice.pages.db_connections.database') }}</th>
                        <th
                            style="padding:0.75rem 1rem;text-align:center;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em">
                            {{ __('backoffice.common.status') }}</th>
                        <th
                            style="padding:0.75rem 1rem;text-align:right;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em">
                            {{ __('backoffice.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($connections as $conn)
                        <tr style="border-bottom:1px solid rgba(51,65,85,0.3);transition:background 0.15s"
                            onmouseover="this.style.background='rgba(51,65,85,0.2)'"
                            onmouseout="this.style.background='transparent'"
                            onfocus="this.style.background='rgba(51,65,85,0.2)'"
                            onblur="this.style.background='transparent'">
                            <td style="padding:0.75rem 1rem">
                                <span style="font-size:13px;font-weight:600;color:#fff">{{ $conn->name }}</span>
                            </td>
                            <td style="padding:0.75rem 1rem">
                                <span
                                    style="display:inline-block;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:500;background:rgba(34,211,238,0.1);color:#22d3ee">{{ $conn->driver }}</span>
                            </td>
                            <td style="padding:0.75rem 1rem;font-size:12px;color:#94a3b8">
                                {{ $conn->host }}:{{ $conn->port }}
                            </td>
                            <td style="padding:0.75rem 1rem">
                                <span
                                    style="font-size:12px;color:#cbd5e1;font-family:monospace">{{ $conn->database }}</span>
                            </td>
                            <td style="padding:0.75rem 1rem;text-align:center">
                                @if ($conn->is_active)
                                    <span
                                        style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#34d399"
                                        title="{{ __('backoffice.common.active') }}"></span>
                                @else
                                    <span
                                        style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#f87171"
                                        title="{{ __('backoffice.common.inactive') }}"></span>
                                @endif
                            </td>
                            <td style="padding:0.75rem 1rem;text-align:right">
                                <div style="display:flex;align-items:center;justify-content:flex-end;gap:0.375rem">
                                    <form method="POST"
                                        action="{{ route('backoffice.database-connections.test', $conn) }}">
                                        @csrf
                                        <button type="submit"
                                            class="rounded-lg bg-emerald-500/10 px-3 py-1.5 text-xs text-emerald-400 transition hover:bg-emerald-500/20"
                                            title="Test Connection">
                                            <svg class="inline h-3.5 w-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" stroke-width="1.5" width="14" height="14">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
                                            </svg>
                                            {{ __('backoffice.pages.db_connections.test') }}
                                        </button>
                                    </form>
                                    <a href="{{ route('backoffice.database-connections.edit', $conn) }}"
                                        class="rounded-lg bg-white/5 px-3 py-1.5 text-xs text-slate-400 transition hover:bg-white/10 hover:text-slate-200"
                                        title="Edit">
                                        <svg class="inline h-3.5 w-3.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" stroke-width="1.5" width="14" height="14">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                                        </svg>
                                        {{ __('backoffice.common.edit') }}
                                    </a>
                                    <form method="POST"
                                        action="{{ route('backoffice.database-connections.destroy', $conn) }}"
                                        onsubmit="return confirm('{{ __('backoffice.pages.db_connections.delete_confirm', ['name' => $conn->name]) }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="rounded-lg bg-red-500/10 px-3 py-1.5 text-xs text-red-400 transition hover:bg-red-500/20"
                                            title="{{ __('backoffice.common.delete') }}">
                                            <svg class="inline h-3.5 w-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" stroke-width="1.5" width="14" height="14">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
