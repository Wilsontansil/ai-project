@extends('backoffice.partials.layout')

@section('title', ($page->title ?: 'Website Page') . ' — ' . __('backoffice.pages.website_pages.title'))
@section('page-title', __('backoffice.pages.website_pages.title'))

@section('content')
    {{-- Header --}}
    <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('backoffice.website-pages.index') }}"
                class="rounded-lg bg-slate-700/60 px-3 py-1.5 text-xs font-semibold text-slate-300 transition hover:bg-slate-700">
                &larr; {{ __('backoffice.common.back') }}
            </a>
            <h1 class="text-lg font-semibold">{{ $page->title ?: __('backoffice.pages.website_pages.title') }}</h1>
        </div>
        <div class="flex gap-2">
            <form action="{{ route('backoffice.website-pages.rescrape', $page) }}" method="POST">
                @csrf
                <button type="submit"
                    class="rounded-lg bg-amber-500/20 px-3 py-1.5 text-xs font-semibold text-amber-300 ring-1 ring-amber-400/30 transition hover:bg-amber-500/30">
                    {{ __('backoffice.pages.website_pages.rescrape') }}
                </button>
            </form>
            <form action="{{ route('backoffice.website-pages.destroy', $page) }}" method="POST"
                onsubmit="return confirm('{{ __('backoffice.pages.website_pages.confirm_delete') }}')">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="rounded-lg bg-red-500/20 px-3 py-1.5 text-xs font-semibold text-red-300 ring-1 ring-red-400/30 transition hover:bg-red-500/30">
                    {{ __('backoffice.common.delete') }}
                </button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-emerald-300/30 bg-emerald-500/15 px-4 py-3 text-xs text-emerald-100">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-xl border border-red-300/30 bg-red-500/15 px-4 py-3 text-xs text-red-100">
            {{ session('error') }}
        </div>
    @endif

    {{-- Page Info Card --}}
    <div class="mb-4 rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5">
        <h2 class="mb-3 text-sm font-semibold text-cyan-300">{{ __('backoffice.pages.website_pages.page_info') }}</h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <span class="text-[11px] font-semibold uppercase text-slate-400">URL</span>
                <p class="mt-0.5">
                    <a href="{{ $page->url }}" target="_blank" class="text-sm text-cyan-300 hover:underline break-all">
                        {{ $page->url }}
                    </a>
                </p>
            </div>
            <div>
                <span
                    class="text-[11px] font-semibold uppercase text-slate-400">{{ __('backoffice.pages.website_pages.page_title') }}</span>
                <p class="mt-0.5 text-sm text-white">{{ $page->title ?: '-' }}</p>
            </div>
            <div>
                <span
                    class="text-[11px] font-semibold uppercase text-slate-400">{{ __('backoffice.common.status') }}</span>
                <p class="mt-0.5">
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
                </p>
            </div>
            <div>
                <span
                    class="text-[11px] font-semibold uppercase text-slate-400">{{ __('backoffice.pages.website_pages.last_scraped') }}</span>
                <p class="mt-0.5 text-sm text-white">{{ $page->last_scraped_at?->format('Y-m-d H:i:s') ?? '-' }}</p>
            </div>
        </div>

        @if ($page->error_message)
            <div class="mt-3 rounded-lg border border-red-400/30 bg-red-500/10 p-3">
                <span
                    class="text-[11px] font-semibold uppercase text-red-300">{{ __('backoffice.pages.website_pages.error') }}</span>
                <p class="mt-0.5 text-xs text-red-200">{{ $page->error_message }}</p>
            </div>
        @endif
    </div>

    {{-- Meta Information --}}
    @if (!empty($page->meta))
        <div class="mb-4 rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5">
            <h2 class="mb-3 text-sm font-semibold text-cyan-300">{{ __('backoffice.pages.website_pages.meta_info') }}</h2>

            @if (!empty($page->meta['description']))
                <div class="mb-2">
                    <span class="text-[11px] font-semibold uppercase text-slate-400">Meta Description</span>
                    <p class="mt-0.5 text-sm text-slate-200">{{ $page->meta['description'] }}</p>
                </div>
            @endif

            @if (!empty($page->meta['keywords']))
                <div class="mb-2">
                    <span class="text-[11px] font-semibold uppercase text-slate-400">Meta Keywords</span>
                    <p class="mt-0.5 text-sm text-slate-200">{{ $page->meta['keywords'] }}</p>
                </div>
            @endif

            @if (!empty($page->meta['content_length']))
                <div class="mb-2">
                    <span
                        class="text-[11px] font-semibold uppercase text-slate-400">{{ __('backoffice.pages.website_pages.content_length') }}</span>
                    <p class="mt-0.5 text-sm text-slate-200">{{ number_format($page->meta['content_length']) }} characters
                    </p>
                </div>
            @endif

            @if (!empty($page->meta['links']))
                <div>
                    <span
                        class="text-[11px] font-semibold uppercase text-slate-400">{{ __('backoffice.pages.website_pages.internal_links') }}
                        ({{ count($page->meta['links']) }})</span>
                    <div class="mt-1 max-h-40 overflow-y-auto rounded-lg bg-slate-800/60 p-2">
                        @foreach ($page->meta['links'] as $link)
                            <div class="flex items-center gap-2 py-0.5 text-xs">
                                <span class="text-cyan-400">→</span>
                                <span class="text-slate-300">{{ $link['text'] }}</span>
                                <span class="text-slate-500 truncate">{{ $link['href'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Extracted Content --}}
    @if ($page->content)
        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5">
            <h2 class="mb-3 text-sm font-semibold text-cyan-300">
                {{ __('backoffice.pages.website_pages.extracted_content') }}</h2>
            <div class="max-h-[600px] overflow-y-auto rounded-lg bg-slate-800/60 p-4">
                <pre class="whitespace-pre-wrap text-xs leading-relaxed text-slate-200">{{ $page->content }}</pre>
            </div>
        </div>
    @endif
@endsection
