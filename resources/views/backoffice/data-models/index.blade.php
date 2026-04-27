@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.data_models.title'))
@section('page-title', __('backoffice.pages.data_models.page_title'))

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">{{ __('backoffice.pages.data_models.title') }}</h1>
            <p class="text-xs text-slate-400">{{ __('backoffice.pages.data_models.subtitle') }}</p>
        </div>
        <a href="{{ route('backoffice.data-models.create') }}"
            class="rounded-lg bg-cyan-400 px-4 py-2 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300 sm:text-sm">
            + {{ __('backoffice.pages.data_models.new_data_model') }}
        </a>
    </div>

    @if ($dataModels->isEmpty())
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-8 text-center">
            <p class="text-sm text-slate-400">{{ __('backoffice.pages.data_models.no_models') }} <span
                    class="font-mono text-cyan-300">Player</span>.</p>
        </div>
    @else
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5">
            <h2 class="mb-4 text-sm font-semibold">{{ __('backoffice.pages.data_models.model_list') }}</h2>
            <div class="overflow-hidden rounded-xl border border-white/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.data_models.model_name') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.data_models.table') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.data_models.connection') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.data_models.description') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.data_models.fields') }}</th>
                                <th class="px-3 py-2 font-medium text-right">{{ __('backoffice.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($dataModels as $model)
                                @php
                                    $fieldCount = count($model->fields ?? []);
                                @endphp
                                <tr class="transition hover:bg-white/5">
                                    <td class="px-3 py-2">
                                        <div>
                                            <p class="font-semibold text-white">{{ $model->model_name }}</p>
                                            <p class="font-mono text-[11px] text-slate-400">{{ $model->slug }}</p>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs text-slate-300">{{ $model->table_name ?: '-' }}
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs text-slate-300">
                                        {{ $model->connection_name ?: 'mysqlgame' }}</td>
                                    <td class="px-3 py-2 text-slate-300">{{ $model->description ?: '-' }}</td>
                                    <td class="px-3 py-2">
                                        <span class="font-mono text-xs text-slate-300">{{ $fieldCount }}
                                            {{ trans_choice('backoffice.pages.data_models.field_count', $fieldCount, ['count' => $fieldCount]) }}</span>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('backoffice.data-models.edit', $model) }}"
                                                class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition hover:bg-white/10">
                                                {{ __('backoffice.common.edit') }}
                                            </a>
                                            <form method="POST"
                                                action="{{ route('backoffice.data-models.destroy', $model) }}"
                                                onsubmit="return confirm('{{ __('backoffice.pages.data_models.delete_confirm', ['name' => $model->model_name]) }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="rounded-lg border border-red-400/20 bg-red-500/10 px-3 py-1.5 text-xs text-red-300 transition hover:bg-red-500/20">
                                                    {{ __('backoffice.common.delete') }}
                                                </button>
                                            </form>
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
@endsection
