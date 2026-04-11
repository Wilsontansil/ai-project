<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class FileParserService
{
    /**
     * Parse uploaded file into knowledge documents.
     * Returns array of ['title' => ..., 'content' => ..., 'category' => ...] entries.
     */
    public function parse(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'txt' => $this->parseTxt($file),
            'csv' => $this->parseCsv($file),
            'xlsx', 'xls' => $this->parseExcel($file),
            'docx' => $this->parseDocx($file),
            default => [],
        };
    }

    public static function supportedExtensions(): array
    {
        return ['txt', 'csv', 'xlsx', 'xls', 'docx'];
    }

    /**
     * Parse TXT file.
     * Supports section-based format (## Title / content blocks separated by blank lines).
     */
    private function parseTxt(UploadedFile $file): array
    {
        $content = file_get_contents($file->getRealPath());
        if ($content === false || trim($content) === '') {
            return [];
        }

        // Try section-based format: ## Title\nContent...
        $sections = $this->parseSections($content);
        if ($sections !== []) {
            return $sections;
        }

        // Fallback: split by double newline into separate documents
        return $this->splitIntoParagraphs($content);
    }

    /**
     * Parse CSV file.
     * Expected columns: category, title, content (or content-only).
     */
    private function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return [];
        }

        $entries = [];
        $header = fgetcsv($handle);

        $catIdx = null;
        $titleIdx = null;
        $contentIdx = null;

        if (is_array($header)) {
            $headerLower = array_map(fn ($h) => strtolower(trim($h ?? '')), $header);
            foreach ($headerLower as $i => $col) {
                if (in_array($col, ['category', 'kategori', 'cat'])) {
                    $catIdx = $i;
                }
                if (in_array($col, ['title', 'judul', 'topic', 'topik', 'nama'])) {
                    $titleIdx = $i;
                }
                if (in_array($col, ['content', 'konten', 'isi', 'text', 'jawaban', 'answer', 'description'])) {
                    $contentIdx = $i;
                }
            }

            // If no content column found, use second column
            if ($contentIdx === null) {
                $contentIdx = min(1, count($header) - 1);
            }
        }

        while (($row = fgetcsv($handle)) !== false) {
            $content = trim((string) ($row[$contentIdx] ?? ''));
            if ($content === '') {
                continue;
            }

            $entries[] = [
                'category' => $catIdx !== null ? trim((string) ($row[$catIdx] ?? '')) : null,
                'title' => $titleIdx !== null ? trim((string) ($row[$titleIdx] ?? '')) : null,
                'content' => $content,
            ];
        }

        fclose($handle);
        return $entries;
    }

    /**
     * Parse Excel file (xlsx/xls).
     */
    private function parseExcel(UploadedFile $file): array
    {
        if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return $this->parseExcelWithSpreadsheet($file);
        }

        return $this->parseXlsxAsXml($file);
    }

    private function parseExcelWithSpreadsheet(UploadedFile $file): array
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $entries = [];
            $firstRow = true;
            $catIdx = null;
            $titleIdx = null;
            $contentIdx = null;

            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) {
                    $cells[] = trim((string) $cell->getValue());
                }

                if ($firstRow) {
                    $firstRow = false;
                    $headerLower = array_map('strtolower', $cells);
                    foreach ($headerLower as $i => $col) {
                        if (in_array($col, ['category', 'kategori', 'cat'])) $catIdx = $i;
                        if (in_array($col, ['title', 'judul', 'topic', 'topik', 'nama'])) $titleIdx = $i;
                        if (in_array($col, ['content', 'konten', 'isi', 'text', 'jawaban', 'answer', 'description'])) $contentIdx = $i;
                    }
                    if ($contentIdx === null) $contentIdx = min(1, count($cells) - 1);
                    continue;
                }

                $content = $cells[$contentIdx] ?? '';
                if ($content === '') continue;

                $entries[] = [
                    'category' => $catIdx !== null ? ($cells[$catIdx] ?? '') : null,
                    'title' => $titleIdx !== null ? ($cells[$titleIdx] ?? '') : null,
                    'content' => $content,
                ];
            }

            return $entries;
        } catch (\Throwable $e) {
            Log::warning('Excel parse failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function parseXlsxAsXml(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $zip = new \ZipArchive();

        if ($zip->open($path) !== true) {
            return [];
        }

        $strings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = simplexml_load_string($sharedStringsXml);
            if ($xml !== false) {
                foreach ($xml->si as $si) {
                    $strings[] = (string) $si->t;
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $xml = simplexml_load_string($sheetXml);
        if ($xml === false) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $cell) {
                $type = (string) ($cell['t'] ?? '');
                $value = (string) $cell->v;

                if ($type === 's' && isset($strings[(int) $value])) {
                    $cells[] = $strings[(int) $value];
                } else {
                    $cells[] = $value;
                }
            }
            $rows[] = $cells;
        }

        if (count($rows) < 2) {
            return [];
        }

        $header = array_map(fn ($h) => strtolower(trim($h)), $rows[0]);
        $catIdx = null;
        $titleIdx = null;
        $contentIdx = null;

        foreach ($header as $i => $col) {
            if (in_array($col, ['category', 'kategori', 'cat'])) $catIdx = $i;
            if (in_array($col, ['title', 'judul', 'topic', 'topik', 'nama'])) $titleIdx = $i;
            if (in_array($col, ['content', 'konten', 'isi', 'text', 'jawaban', 'answer', 'description'])) $contentIdx = $i;
        }
        if ($contentIdx === null) $contentIdx = min(1, count($header) - 1);

        $entries = [];
        for ($i = 1; $i < count($rows); $i++) {
            $content = trim((string) ($rows[$i][$contentIdx] ?? ''));
            if ($content === '') continue;

            $entries[] = [
                'category' => $catIdx !== null ? trim((string) ($rows[$i][$catIdx] ?? '')) : null,
                'title' => $titleIdx !== null ? trim((string) ($rows[$i][$titleIdx] ?? '')) : null,
                'content' => $content,
            ];
        }

        return $entries;
    }

    /**
     * Parse DOCX file by extracting text from document.xml.
     */
    private function parseDocx(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $zip = new \ZipArchive();

        if ($zip->open($path) !== true) {
            return [];
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            return [];
        }

        $content = str_replace('</w:p>', "\n", $xml);
        $content = strip_tags($content);
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');

        if (trim($content) === '') {
            return [];
        }

        $sections = $this->parseSections($content);
        if ($sections !== []) {
            return $sections;
        }

        return $this->splitIntoParagraphs($content);
    }

    /**
     * Parse text with section headers (## Title or [Title] or Title:).
     * Each section becomes a knowledge document.
     */
    private function parseSections(string $content): array
    {
        $entries = [];

        // Match: ## Title or [Title] headers followed by content
        if (preg_match_all('/(?:^|\n)(?:##\s*(.+)|(?:\[(.+?)\]))\s*\n([\s\S]*?)(?=\n(?:##\s|\[)|\z)/m', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $title = trim($match[1] ?: $match[2]);
                $body = trim($match[3]);

                if ($title !== '' && $body !== '') {
                    $entries[] = [
                        'title' => $title,
                        'content' => $body,
                        'category' => null,
                    ];
                }
            }
        }

        return $entries;
    }

    /**
     * Split plain text by double-newline into separate knowledge documents.
     */
    private function splitIntoParagraphs(string $content): array
    {
        $blocks = preg_split('/\n\s*\n/', $content, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $entries = [];

        foreach ($blocks as $block) {
            $text = trim($block);
            if (mb_strlen($text) < 10) {
                continue;
            }

            $entries[] = [
                'title' => null,
                'content' => $text,
                'category' => null,
            ];
        }

        return $entries;
    }
}
