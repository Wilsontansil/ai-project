@extends('backoffice.partials.layout')

@section('title', __('backoffice.pages.website_pages.add_url'))
@section('page-title', __('backoffice.pages.website_pages.add_url'))

@section('content')
    <div class="mx-auto max-w-2xl">
        {{-- Header --}}
        <div class="mb-4 flex items-center gap-3">
            <a href="{{ route('backoffice.website-pages.index') }}"
                class="rounded-lg bg-slate-700/60 px-3 py-1.5 text-xs font-semibold text-slate-300 transition hover:bg-slate-700">
                &larr; {{ __('backoffice.common.back') }}
            </a>
            <h1 class="text-lg font-semibold">{{ __('backoffice.pages.website_pages.add_url') }}</h1>
        </div>

        <div class="rounded-2xl border border-slate-700/70 bg-slate-900/85 p-5">
            <form action="{{ route('backoffice.website-pages.store') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label class="mb-1 block text-xs font-semibold text-slate-300">
                        {{ __('backoffice.pages.website_pages.url_label') }}
                    </label>
                    <input type="url" name="url" value="{{ old('url') }}" required
                        placeholder="https://example.com"
                        class="w-full rounded-lg border border-slate-600/70 bg-slate-800/60 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-cyan-400 focus:outline-none focus:ring-1 focus:ring-cyan-400/50">
                    @error('url')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <p class="mb-4 text-xs text-slate-400">
                    {{ __('backoffice.pages.website_pages.add_hint') }}
                </p>

                <div class="flex justify-end">
                    <button type="submit"
                        class="rounded-lg bg-cyan-400 px-5 py-2 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300 sm:text-sm">
                        {{ __('backoffice.pages.website_pages.scrape_now') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
