<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class KnowledgeBaseWebsiteScraper
{
    /**
     * Scrape RTP game/pola data from a CMBET-style RTP website.
     *
     * @return array{content:string, item_count:int, base_url:string}
     */
    public function scrapeRtpWebsite(string $sourceUrl, int $limit = 15): array
    {
        $limit = max(1, min(50, $limit));
        $baseUrl = rtrim(trim($sourceUrl), '/');

        if ($baseUrl === '') {
            throw new \RuntimeException('Source URL is empty.');
        }

        $homeResponse = Http::timeout(20)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])
            ->get($baseUrl . '/');

        if (! $homeResponse->successful()) {
            throw new \RuntimeException('Failed to fetch website homepage. HTTP ' . $homeResponse->status());
        }

        $homeHtml = (string) $homeResponse->body();
        $ajaxUrl = $this->extractAjaxUrl($homeHtml) ?? ($baseUrl . '/ajax/pola_gacor');

        $slotIds = $this->extractSlotIds($homeHtml);
        if ($slotIds === []) {
            throw new \RuntimeException('No game slot id found on website.');
        }

        $items = [];
        foreach (array_slice($slotIds, 0, $limit) as $slotId) {
            $detailResponse = Http::asForm()
                ->timeout(20)
                ->withHeaders([
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Referer' => $baseUrl . '/',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
                ])
                ->post($ajaxUrl, ['id_slot' => $slotId]);

            if (! $detailResponse->successful()) {
                continue;
            }

            $parsed = $this->parsePolaHtml((string) $detailResponse->body());
            if ($parsed === null) {
                continue;
            }

            $items[] = $parsed;
        }

        if ($items === []) {
            throw new \RuntimeException('No pola data could be parsed from website responses.');
        }

        $lines = [
            'RTP / Pola Gacor Snapshot',
            'Source: ' . $baseUrl,
            'Synced at: ' . now()->format('Y-m-d H:i:s'),
            '',
        ];

        foreach ($items as $index => $item) {
            $no = $index + 1;
            $lines[] = "{$no}. {$item['game_name']} ({$item['provider']})";
            $lines[] = 'RTP: ' . $item['rtp'];
            $lines[] = 'Jam Gacor: ' . $item['jam_gacor'];
            $lines[] = 'Pola Gacor:';
            foreach ($item['pola_lines'] as $polaLine) {
                $lines[] = '- ' . $polaLine;
            }
            if ($item['bet'] !== '') {
                $lines[] = 'Bet: ' . $item['bet'];
            }
            $lines[] = str_repeat('-', 48);
        }

        return [
            'content' => implode("\n", $lines),
            'item_count' => count($items),
            'base_url' => $baseUrl,
        ];
    }

    private function extractAjaxUrl(string $html): ?string
    {
        if (preg_match("/url\s*:\s*'([^']+\/ajax\/pola_gacor)'/i", $html, $m) === 1) {
            return trim((string) $m[1]);
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function extractSlotIds(string $html): array
    {
        $ids = [];

        if (preg_match_all('/<button[^>]*class="[^"]*pola_gacor[^"]*"[^>]*data-id="(\d+)"[^>]*>/i', $html, $m1) === 1) {
            $ids = array_merge($ids, $m1[1]);
        }

        if (preg_match_all('/<button[^>]*data-id="(\d+)"[^>]*class="[^"]*pola_gacor[^"]*"[^>]*>/i', $html, $m2) === 1) {
            $ids = array_merge($ids, $m2[1]);
        }

        return array_values(array_unique(array_map('strval', $ids)));
    }

    /**
     * @return array{game_name:string,provider:string,rtp:string,jam_gacor:string,pola_lines:array<int,string>,bet:string}|null
     */
    private function parsePolaHtml(string $html): ?array
    {
        $gameName = $this->match('/<h5>\s*([^<]+?)\s*<\/h5>/i', $html);
        $provider = $this->match('/text-secondary">\s*([^<]+?)\s*<\/span>/i', $html);
        $rtp = $this->match('/<div class="col-6 text-end">\s*([0-9]+%?)\s*<\/div>/i', $html);
        $jamGacor = $this->match('/<strong>\s*Jam Gacor\s*<\/strong>\s*:\s*([^<\r\n]+)/i', $html);

        if ($gameName === '' || $provider === '') {
            return null;
        }

        $polaBlock = '';
        if (preg_match('/<strong>\s*Pola Gacor\s*<\/strong>(.*?)<hr/iis', $html, $m) === 1) {
            $polaBlock = (string) $m[1];
        }

        $cleanBlock = trim((string) preg_replace('/<br\s*\/?\s*>/i', "\n", $polaBlock));
        $cleanBlock = html_entity_decode(strip_tags($cleanBlock), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $rawLines = preg_split('/\r\n|\r|\n/', $cleanBlock) ?: [];

        $polaLines = [];
        $bet = '';
        foreach ($rawLines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            if (stripos($line, 'Bet:') === 0) {
                $bet = trim(substr($line, 4));
                continue;
            }

            $polaLines[] = $line;
        }

        return [
            'game_name' => $gameName,
            'provider' => $provider,
            'rtp' => $rtp !== '' ? $rtp : '-',
            'jam_gacor' => $jamGacor !== '' ? $jamGacor : '-',
            'pola_lines' => $polaLines,
            'bet' => $bet,
        ];
    }

    private function match(string $pattern, string $subject): string
    {
        return preg_match($pattern, $subject, $m) === 1 ? trim((string) $m[1]) : '';
    }
}
