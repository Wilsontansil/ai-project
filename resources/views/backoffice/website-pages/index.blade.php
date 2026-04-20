@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.website_pages.title'))
@section('page-title', __('backoffice.pages.website_pages.title'))

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between rounded-2xl border border-slate-700/70 bg-slate-900/85 px-4 py-4 sm:px-5">
        <div>
            <h1 class="text-lg font-semibold sm:text-2xl">{{ __('backoffice.pages.website_pages.title') }}</h1>
            <p class="text-xs text-slate-400">{{ __('backoffice.pages.website_pages.subtitle') }}</p>
        </div>
        <a href="{{ route('backoffice.website-pages.create') }}"
            class="rounded-lg bg-cyan-400 px-4 py-2 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300 sm:text-sm">
            + {{ __('backoffice.pages.website_pages.add_url') }}
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-xs text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-xl border border-red-300/30 bg-red-500/15 px-4 py-3 text-xs text-red-100">
            {{ session('error') }}
        </div>
    @endif

    @if ($pages->isEmpty())
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-8 text-center">
            <svg class="mx-auto mb-3 h-12 w-12 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
            </svg>
            <p class="text-sm text-slate-400">{{ __('backoffice.pages.website_pages.no_pages') }}</p>
        </div>
    @else
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-4 sm:p-5">
            <h2 class="mb-4 text-sm font-semibold">{{ __('backoffice.pages.website_pages.page_list') }}</h2>
            <div class="overflow-hidden rounded-xl border border-white/10">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-white/5 text-left text-[11px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-3 py-2 font-medium">URL</th>
                                <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.website_pages.page_title') }}</th>
                                <th class="px-3 py-2 font-medium text-center">{{ __('backoffice.common.status') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('backoffice.pages.website_pages.last_scraped') }}
                                </th>
                                <th class="px-3 py-2 font-medium text-right">{{ __('backoffice.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($pages as $page)
                                <tr class="transition hover:bg-white/5">
                                    <td class="px-3 py-2 max-w-xs">
                                        <a href="{{ $page->url }}" target="_blank"
                                            class="font-mono text-xs text-cyan-300 hover:underline truncate block">
                                            {{ Str::limit($page->url, 50) }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="text-white">{{ $page->title ?: '-' }}</span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if ($page->status === 'scraped')
                                            <span
                                                class="inline-flex items-center rounded-full bg-emerald-500/20 px-2.5 py-0.5 text-xs font-semibold text-emerald-300 ring-1 ring-emerald-400/30">
                                                {{ __('backoffice.pages.website_pages.status_scraped') }}
                                            </span>
                                        @elseif ($page->status === 'pending')
                                            <span
                                                class="inline-flex items-center rounded-full bg-amber-500/20 px-2.5 py-0.5 text-xs font-semibold text-amber-300 ring-1 ring-amber-400/30">
                                                {{ __('backoffice.pages.website_pages.status_pending') }}
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center rounded-full bg-red-500/20 px-2.5 py-0.5 text-xs font-semibold text-red-300 ring-1 ring-red-400/30">
                                                {{ __('backoffice.pages.website_pages.status_failed') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-slate-400">
                                        {{ $page->last_scraped_at?->diffForHumans() ?? '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('backoffice.website-pages.show', $page) }}"
                                                class="rounded-lg bg-cyan-500/20 px-3 py-1.5 text-[11px] font-semibold text-cyan-300 ring-1 ring-cyan-400/30 transition hover:bg-cyan-500/30">
                                                {{ __('backoffice.pages.website_pages.view') }}
                                            </a>
                                            <form action="{{ route('backoffice.website-pages.rescrape', $page) }}"
                                                method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="rounded-lg bg-amber-500/20 px-3 py-1.5 text-[11px] font-semibold text-amber-300 ring-1 ring-amber-400/30 transition hover:bg-amber-500/30">
                                                    {{ __('backoffice.pages.website_pages.rescrape') }}
                                                </button>
                                            </form>
                                            <form action="{{ route('backoffice.website-pages.destroy', $page) }}"
                                                method="POST" class="inline"
                                                onsubmit="return confirm('{{ __('backoffice.pages.website_pages.confirm_delete') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="rounded-lg bg-red-500/20 px-3 py-1.5 text-[11px] font-semibold text-red-300 ring-1 ring-red-400/30 transition hover:bg-red-500/30">
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
