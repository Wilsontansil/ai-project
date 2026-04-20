<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\WebsitePage;
use App\Services\AI\ToolEngines\WebScraperToolEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebsitePageController extends Controller
{
    public function index(): View
    {
        $pages = WebsitePage::orderByDesc('updated_at')->get();

        return view('backoffice.website-pages.index', [
            'pages' => $pages,
            'boActive' => 'website-pages',
            'active' => 'website-pages',
        ]);
    }

    public function create(): View
    {
        return view('backoffice.website-pages.create', [
            'boActive' => 'website-pages',
            'active' => 'website-pages',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048', 'unique:website_pages,url'],
        ]);

        $page = WebsitePage::create([
            'url' => $validated['url'],
            'status' => 'pending',
        ]);

        // Scrape immediately
        $result = WebScraperToolEngine::scrapeUrl($page->url);

        if ($result['error'] === null) {
            $page->update([
                'title' => $result['title'],
                'content' => $result['content'],
                'meta' => $result['meta'],
                'status' => 'scraped',
                'error_message' => null,
                'last_scraped_at' => now(),
            ]);

            return redirect()
                ->route('backoffice.website-pages.show', $page)
                ->with('success', __('backoffice.pages.website_pages.scrape_success'));
        }

        $page->update([
            'status' => 'failed',
            'error_message' => $result['error'],
        ]);

        return redirect()
            ->route('backoffice.website-pages.index')
            ->with('error', __('backoffice.pages.website_pages.scrape_failed') . ' ' . $result['error']);
    }

    public function show(WebsitePage $websitePage): View
    {
        return view('backoffice.website-pages.show', [
            'page' => $websitePage,
            'boActive' => 'website-pages',
            'active' => 'website-pages',
        ]);
    }

    public function rescrape(WebsitePage $websitePage): RedirectResponse
    {
        $result = WebScraperToolEngine::scrapeUrl($websitePage->url);

        if ($result['error'] === null) {
            $websitePage->update([
                'title' => $result['title'],
                'content' => $result['content'],
                'meta' => $result['meta'],
                'status' => 'scraped',
                'error_message' => null,
                'last_scraped_at' => now(),
            ]);

            return redirect()
                ->back()
                ->with('success', __('backoffice.pages.website_pages.rescrape_success'));
        }

        $websitePage->update([
            'status' => 'failed',
            'error_message' => $result['error'],
        ]);

        return redirect()
            ->back()
            ->with('error', __('backoffice.pages.website_pages.scrape_failed') . ' ' . $result['error']);
    }

    public function destroy(WebsitePage $websitePage): RedirectResponse
    {
        $websitePage->delete();

        return redirect()
            ->route('backoffice.website-pages.index')
            ->with('success', __('backoffice.pages.website_pages.deleted'));
    }
}
