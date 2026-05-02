<?php

namespace App\Services\AI\ToolEngines;

use App\Models\Tool;
use App\Models\WebsitePage;
use Illuminate\Support\Facades\Log;
use OpenAI;

class WebScraperToolEngine
{
    /**
     * Maximum characters of website content to send to OpenAI.
     */
    private const MAX_CONTENT_LENGTH = 8000;

    /**
     * Execute a web_scraper tool: find matching website pages from the DB
     * cache and return their content for the AI to answer from.
     */
    public function execute(Tool $tool, array $arguments): array
    {
        $pages = WebsitePage::where('status', 'scraped')
            ->whereNotNull('content')
            ->get();

        if ($pages->isEmpty()) {
            // No scraped pages yet — skip this tool and let the AI answer normally
            // from Knowledge Base or its own knowledge.
            return ['mode' => 'skip'];
        }

        $combinedContent = $pages->map(function (WebsitePage $page) {
            $title = $page->title ?: parse_url($page->url, PHP_URL_HOST);
            $content = mb_substr((string) $page->content, 0, self::MAX_CONTENT_LENGTH);

            return "=== {$title} ({$page->url}) ===\n{$content}";
        })->implode("\n\n");

        // Truncate total to stay within token budget
        if (mb_strlen($combinedContent) > self::MAX_CONTENT_LENGTH * 3) {
            $combinedContent = mb_substr($combinedContent, 0, self::MAX_CONTENT_LENGTH * 3);
        }

        return [
            'mode' => 'model',
            'tool_context' => [
                'tool_name' => $tool->tool_name,
                'tool_display_name' => $tool->display_name,
                'tool_description' => $tool->description,
                'website_content' => $combinedContent,
                'page_count' => $pages->count(),
            ],
        ];
    }

    /**
     * Use OpenAI to generate a 2-3 sentence summary of scraped webpage content.
     * Returns null if the API key is missing, content is empty, or the call fails.
     */
    public static function summarizeContent(string $content, string $apiKey): ?string
    {
        if (trim($content) === '' || $apiKey === '') {
            return null;
        }

        try {
            $client = OpenAI::client($apiKey);
            // Keep it cheap: first 3000 chars is enough to identify brand + purpose
            $snippet = mb_substr($content, 0, 3000);

            $response = $client->chat()->create([
                'model' => 'gpt-4.1-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Summarize the following webpage content in 2-3 concise sentences. Focus on what the website or business is about, its name/brand, and its main products or services. Be specific.',
                    ],
                    ['role' => 'user', 'content' => $snippet],
                ],
                'max_completion_tokens' => 150,
            ]);

            $summary = trim((string) ($response->choices[0]->message->content ?? ''));

            return $summary !== '' ? $summary : null;
        } catch (\Throwable $e) {
            Log::warning('Website summary generation failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Reject URLs that resolve to private/reserved IP ranges to prevent SSRF.
     * Returns true if the URL is unsafe (should be blocked).
     */
    private static function isPrivateUrl(string $url): bool
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return true;
        }

        $host = (string) parse_url($url, PHP_URL_HOST);
        if ($host === '') {
            return true;
        }

        // Resolve hostname to IP
        $ip = gethostbyname($host);

        // gethostbyname returns the original string if resolution fails — block it
        if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            return true;
        }

        // Block private, loopback, link-local, and reserved ranges
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    /**
     * Scrape a URL and extract text content from the HTML.
     *
     * @return array{title: string|null, content: string|null, meta: array<string, mixed>, error: string|null}
     */
    public static function scrapeUrl(string $url): array
    {
        if (self::isPrivateUrl($url)) {
            return ['title' => null, 'content' => null, 'meta' => [], 'error' => 'URL not allowed: private or reserved addresses are blocked.'];
        }

        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 15,
                    'user_agent' => 'Mozilla/5.0 (compatible; AIBot/1.0)',
                    'follow_location' => true,
                    'max_redirects' => 3,
                ],
                'ssl' => [
                    'verify_peer' => true,
                ],
            ]);

            error_clear_last();
            $html = file_get_contents($url, false, $context);

            if ($html === false) {
                $lastError = error_get_last();
                $reason = $lastError['message'] ?? 'Failed to fetch URL.';
                return ['title' => null, 'content' => null, 'meta' => [], 'error' => $reason];
            }

            // Extract title
            $title = null;
            if (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $m)) {
                $title = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
            }

            // Extract meta description
            $metaDesc = null;
            if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/si', $html, $m)) {
                $metaDesc = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
            }

            // Extract meta keywords
            $metaKeywords = null;
            if (preg_match('/<meta\s+name=["\']keywords["\']\s+content=["\'](.*?)["\']/si', $html, $m)) {
                $metaKeywords = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
            }

            // Remove scripts, styles, nav, footer, header
            $cleaned = preg_replace('/<(script|style|nav|footer|header|noscript)[^>]*>.*?<\/\1>/si', '', $html);

            // Remove HTML tags
            $text = strip_tags($cleaned);

            // Normalize whitespace
            $text = preg_replace('/[ \t]+/', ' ', $text);
            $text = preg_replace('/\n{3,}/', "\n\n", $text);
            $text = trim($text);

            // Extract links for reference
            $links = [];
            if (preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/si', $html, $matches, PREG_SET_ORDER)) {
                $baseHost = parse_url($url, PHP_URL_HOST);
                foreach ($matches as $match) {
                    $href = $match[1];
                    $linkText = trim(strip_tags($match[2]));
                    if ($linkText === '' || strlen($linkText) > 100) {
                        continue;
                    }
                    // Only keep internal links
                    $linkHost = parse_url($href, PHP_URL_HOST);
                    if ($linkHost !== null && $linkHost !== $baseHost) {
                        continue;
                    }
                    $links[] = ['text' => $linkText, 'href' => $href];
                }
                $links = array_slice(array_unique($links, SORT_REGULAR), 0, 50);
            }

            return [
                'title' => $title,
                'content' => $text,
                'meta' => array_filter([
                    'description' => $metaDesc,
                    'keywords' => $metaKeywords,
                    'links' => $links ?: null,
                    'content_length' => strlen($text),
                ]),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Log::error('Web scraping failed', ['url' => $url, 'error' => $e->getMessage()]);

            return ['title' => null, 'content' => null, 'meta' => [], 'error' => $e->getMessage()];
        }
    }
}
