<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\DB;

/**
 * Guards KB datamodel SQL queries before they are saved or executed.
 *
 * Two layers of protection:
 *   1. validateSql()  — static analysis (SELECT-only, no DDL/DML/multi-statement).
 *   2. countRows()    — live row-count check so huge results are rejected before saving.
 *
 * The same MAX_ROWS constant is used by PromptBuilder as a runtime cap.
 */
class KnowledgeBaseQueryGuard
{
    /** Hard limit on how many rows a KB datamodel query may return. */
    public const MAX_ROWS = 500;

    /**
     * Validate that the SQL is a safe, read-only SELECT statement.
     *
     * @throws \InvalidArgumentException with a user-facing Indonesian message.
     */
    public static function validateSql(string $sql): void
    {
        $trimmed = trim($sql);

        if ($trimmed === '') {
            throw new \InvalidArgumentException('Query SQL tidak boleh kosong.');
        }

        // Strip leading block-comments and line-comments to find the first real keyword.
        $normalized = (string) preg_replace(
            '/\A(\s|\/\*.*?\*\/\s*|--[^\n]*\n?\s*)*/s',
            '',
            $trimmed
        );

        $firstWord = strtoupper((string) strtok($normalized, " \t\r\n("));

        if ($firstWord !== 'SELECT') {
            throw new \InvalidArgumentException('Hanya query SELECT yang diizinkan.');
        }

        $upper = strtoupper($trimmed);

        // ── 1. Forbidden DML / DDL keywords ──────────────────────────────────
        $forbiddenWords = [
            'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER',
            'TRUNCATE', 'REPLACE', 'EXEC', 'EXECUTE', 'GRANT', 'REVOKE',
            // File I/O
            'LOAD_FILE',
            // Time-delay / blind injection functions
            'SLEEP', 'BENCHMARK', 'WAIT_FOR_EXECUTED_GTID_SET',
            // UNION-based injection
            'UNION',
        ];

        foreach ($forbiddenWords as $kw) {
            if (preg_match('/\b' . preg_quote($kw, '/') . '\b/', $upper)) {
                throw new \InvalidArgumentException("Query mengandung perintah terlarang: {$kw}");
            }
        }

        // ── 2. Forbidden multi-word phrases ──────────────────────────────────
        $forbiddenPhrases = [
            'INTO OUTFILE',
            'INTO DUMPFILE',
        ];

        foreach ($forbiddenPhrases as $phrase) {
            if (str_contains($upper, $phrase)) {
                throw new \InvalidArgumentException("Query mengandung perintah terlarang: {$phrase}");
            }
        }

        // ── 3. Block access to internal/system databases ─────────────────────
        // Matches: information_schema.x, mysql.x, sys.x, performance_schema.x
        $forbiddenSchemas = [
            'INFORMATION_SCHEMA',
            'PERFORMANCE_SCHEMA',
        ];

        foreach ($forbiddenSchemas as $schema) {
            if (str_contains($upper, $schema)) {
                throw new \InvalidArgumentException("Akses ke database sistem tidak diizinkan: {$schema}");
            }
        }

        // Word-boundary check for short schema names to avoid false positives
        // e.g. "mysql.user" or "`sys`.`user_summary`"
        $forbiddenSchemaWords = ['MYSQL', 'SYS'];
        foreach ($forbiddenSchemaWords as $schema) {
            // Only flag if followed by a dot (i.e. used as a database prefix)
            if (preg_match('/\b' . preg_quote($schema, '/') . '\s*\./', $upper)) {
                throw new \InvalidArgumentException("Akses ke database sistem tidak diizinkan: {$schema}");
            }
        }

        // ── 4. Block MySQL system variable access (@@var) ────────────────────
        if (str_contains($trimmed, '@@')) {
            throw new \InvalidArgumentException('Akses ke variabel sistem (@@) tidak diizinkan.');
        }

        // ── 5. Block information-disclosure functions ─────────────────────────
        $forbiddenFunctions = [
            'USER()', 'CURRENT_USER()', 'SESSION_USER()', 'SYSTEM_USER()',
            'DATABASE()', 'SCHEMA()', 'VERSION()',
        ];

        foreach ($forbiddenFunctions as $fn) {
            if (str_contains($upper, strtoupper($fn))) {
                throw new \InvalidArgumentException("Fungsi sistem tidak diizinkan: {$fn}");
            }
        }

        // ── 6. Reject multi-statement SQL (semicolons not at the very end) ────
        // Strip string literals first to avoid matching semicolons inside values.
        $stripped = (string) preg_replace("/'(?:[^'\\\\]|\\\\.)*'/", "''", $trimmed);
        if (preg_match('/;(?!\s*$)/', $stripped)) {
            throw new \InvalidArgumentException('Multi-statement SQL tidak diizinkan.');
        }
    }

    /**
     * Wrap the SQL in COUNT(*) and execute it to get the number of result rows.
     *
     * This is cheaper than fetching all rows just to count them.
     *
     * @throws \RuntimeException if the query cannot be executed.
     */
    public static function countRows(string $connectionName, string $sql): int
    {
        $countSql = 'SELECT COUNT(*) AS _cnt FROM (' . $sql . ') AS _kb_count_check';
        $result   = DB::connection($connectionName)->selectOne($countSql);

        return (int) ($result->_cnt ?? 0);
    }
}
