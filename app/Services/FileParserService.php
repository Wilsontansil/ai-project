<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class FileParserService
{
    /**
     * Parse uploaded file into knowledge base entries.
     * Returns array of ['question' => ..., 'answer' => ...] pairs.
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

    /**
     * Supported file extensions.
     */
    public static function supportedExtensions(): array
    {
        return ['txt', 'csv', 'xlsx', 'xls', 'docx'];
    }

    /**
     * Parse TXT file.
     * Format: each paragraph as one knowledge entry.
     * Or Q: / A: pairs separated by blank lines.
     */
    private function parseTxt(UploadedFile $file): array
    {
        $content = file_get_contents($file->getRealPath());
        if ($content === false || trim($content) === '') {
            return [];
        }

        // Try Q:/A: format first
        $qaEntries = $this->parseQAFormat($content);
        if ($qaEntries !== []) {
            return $qaEntries;
        }

        // Fallback: split by double newline into paragraphs
        return $this->parseParagraphs($content);
    }

    /**
     * Parse CSV file.
     * Expected columns: question, answer (or first 2 columns).
     */
    private function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return [];
        }

        $entries = [];
        $header = fgetcsv($handle);

        // Detect column indices
        $qIdx = 0;
        $aIdx = 1;
        if (is_array($header)) {
            $headerLower = array_map(fn ($h) => strtolower(trim($h ?? '')), $header);
            foreach ($headerLower as $i => $col) {
                if (in_array($col, ['question', 'pertanyaan', 'q', 'topic', 'topik'])) {
                    $qIdx = $i;
                }
                if (in_array($col, ['answer', 'jawaban', 'a', 'response', 'content'])) {
                    $aIdx = $i;
                }
            }
        }

        while (($row = fgetcsv($handle)) !== false) {
            $question = trim((string) ($row[$qIdx] ?? ''));
            $answer = trim((string) ($row[$aIdx] ?? ''));

            if ($question !== '' && $answer !== '') {
                $entries[] = ['question' => $question, 'answer' => $answer];
            }
        }

        fclose($handle);
        return $entries;
    }

    /**
     * Parse Excel file (xlsx/xls) using simple XML parsing for xlsx.
     * Falls back to CSV-style if PhpSpreadsheet is not available.
     */
    private function parseExcel(UploadedFile $file): array
    {
        // Use PhpSpreadsheet if available
        if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return $this->parseExcelWithSpreadsheet($file);
        }

        // Fallback: try to parse xlsx as XML
        return $this->parseXlsxAsXml($file);
    }

    private function parseExcelWithSpreadsheet(UploadedFile $file): array
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $entries = [];
            $firstRow = true;
            $qIdx = 0;
            $aIdx = 1;

            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) {
                    $cells[] = trim((string) $cell->getValue());
                }

                if ($firstRow) {
                    $firstRow = false;
                    $headerLower = array_map('strtolower', $cells);
                    foreach ($headerLower as $i => $col) {
                        if (in_array($col, ['question', 'pertanyaan', 'q', 'topic', 'topik'])) {
                            $qIdx = $i;
                        }
                        if (in_array($col, ['answer', 'jawaban', 'a', 'response', 'content'])) {
                            $aIdx = $i;
                        }
                    }
                    continue;
                }

                $question = $cells[$qIdx] ?? '';
                $answer = $cells[$aIdx] ?? '';

                if ($question !== '' && $answer !== '') {
                    $entries[] = ['question' => $question, 'answer' => $answer];
                }
            }

            return $entries;
        } catch (\Throwable $e) {
            Log::warning('Excel parse failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Basic xlsx parser using ZipArchive + XML (no extra packages needed).
     */
    private function parseXlsxAsXml(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $zip = new \ZipArchive();

        if ($zip->open($path) !== true) {
            return [];
        }

        // Read shared strings
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

        // Read first sheet
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

        // First row = header
        $header = array_map(fn ($h) => strtolower(trim($h)), $rows[0]);
        $qIdx = 0;
        $aIdx = 1;
        foreach ($header as $i => $col) {
            if (in_array($col, ['question', 'pertanyaan', 'q', 'topic', 'topik'])) {
                $qIdx = $i;
            }
            if (in_array($col, ['answer', 'jawaban', 'a', 'response', 'content'])) {
                $aIdx = $i;
            }
        }

        $entries = [];
        for ($i = 1; $i < count($rows); $i++) {
            $question = trim((string) ($rows[$i][$qIdx] ?? ''));
            $answer = trim((string) ($rows[$i][$aIdx] ?? ''));

            if ($question !== '' && $answer !== '') {
                $entries[] = ['question' => $question, 'answer' => $answer];
            }
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

        // Strip XML tags to get plain text, preserve paragraph breaks
        $content = str_replace('</w:p>', "\n", $xml);
        $content = strip_tags($content);
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');

        if (trim($content) === '') {
            return [];
        }

        // Try Q:/A: format
        $qaEntries = $this->parseQAFormat($content);
        if ($qaEntries !== []) {
            return $qaEntries;
        }

        // Fallback: paragraphs
        return $this->parseParagraphs($content);
    }

    /**
     * Parse text in Q:/A: format.
     * Supports: Q: ... A: ... separated by blank lines.
     */
    private function parseQAFormat(string $content): array
    {
        $entries = [];

        // Match Q: ... A: ... blocks
        if (preg_match_all('/(?:Q|Question|Pertanyaan)\s*:\s*(.+?)(?:\n|\r\n?)(?:A|Answer|Jawaban)\s*:\s*(.+?)(?=\n\s*\n|\n(?:Q|Question|Pertanyaan)\s*:|$)/si', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $question = trim($match[1]);
                $answer = trim($match[2]);

                if ($question !== '' && $answer !== '') {
                    $entries[] = ['question' => $question, 'answer' => $answer];
                }
            }
        }

        return $entries;
    }

    /**
     * Split content into paragraphs as knowledge entries.
     * Each non-empty paragraph becomes a Q&A pair where question = first line, answer = rest.
     */
    private function parseParagraphs(string $content): array
    {
        $blocks = preg_split('/\n\s*\n/', $content);
        $entries = [];

        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '' || mb_strlen($block) < 10) {
                continue;
            }

            $lines = preg_split('/\n/', $block, 2);
            $question = trim($lines[0]);
            $answer = trim($lines[1] ?? $lines[0]);

            if ($question !== '') {
                $entries[] = [
                    'question' => mb_substr($question, 0, 500),
                    'answer' => mb_substr($answer, 0, 5000),
                ];
            }
        }

        return $entries;
    }
}
