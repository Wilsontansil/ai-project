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

        // Forbidden DML / DDL keywords.
        $upper = strtoupper($trimmed);
        $forbidden = [
            'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER',
            'TRUNCATE', 'REPLACE', 'EXEC', 'EXECUTE', 'GRANT', 'REVOKE',
            'LOAD_FILE', 'INTO OUTFILE', 'INTO DUMPFILE',
        ];

        foreach ($forbidden as $kw) {
            if (str_contains($kw, ' ')) {
                // Multi-word phrases — simple substring match on uppercased SQL.
                if (str_contains($upper, $kw)) {
                    throw new \InvalidArgumentException("Query mengandung perintah terlarang: {$kw}");
                }
            } else {
                // Single-word keywords — use word-boundary to avoid false positives.
                if (preg_match('/\b' . preg_quote($kw, '/') . '\b/', $upper)) {
                    throw new \InvalidArgumentException("Query mengandung perintah terlarang: {$kw}");
                }
            }
        }

        // Reject multi-statement SQL (semicolons not at the very end).
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
