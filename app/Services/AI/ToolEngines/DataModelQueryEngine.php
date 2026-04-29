<?php

namespace App\Services\AI\ToolEngines;

use App\Models\DatabaseConnection;
use App\Models\DataModel;
use App\Models\Tool;
use App\Support\LogSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Executes "get" and "get_multiple" type tools against game DataModel tables.
 *
 * ALL query behaviour is driven by tool.meta['query'] — nothing is hard-coded:
 *
 *   meta.query.select     — string[] fields to return (empty = all DataModel fields)
 *   meta.query.filters    — [{field, operator, value}] static filters applied to every query
 *                           operators: =, !=, <>, >, <, >=, <=, like, not like, in, not in
 *   meta.query.date_range — {field, range} or {mode, start_field, end_field}
 *                           named ranges: today, yesterday, this_week, last_week, this_month, last_month
 *                           mode: "between_now" — NOW() must fall between two date fields
 *   meta.query.aggregate  — {function, field} — sum, count, avg, min, max
 *   meta.query.group_by   — string[] used alongside aggregate
 *   meta.query.order_by   — {field, direction} or [{field, direction}, ...]
 *   meta.query.limit      — int max rows (ignored for bare aggregates)
 *
 * DataModel access is READ-ONLY.  No insert / update / delete is ever issued.
 */
class DataModelQueryEngine
{
    private const MAX_ROWS = 100;

    private const SAFE_OPERATORS = ['=', '!=', '<>', '>', '<', '>=', '<=', 'like', 'not like'];

    /** Operators supported by query.conditions */
    private const CONDITION_OPERATORS = [
        '=', '!=', '<>', '>', '<', '>=', '<=',
        'LIKE', 'LIKE%%', 'NOT LIKE',
        'ILIKE', 'ILIKE%%',
        'IN', 'NOT IN',
        'IS NULL', 'IS NOT NULL',
        '~', '!~',
    ];

    // ─── Public entry points ────────────────────────────────────────────────

    /**
     * Execute a single-DataModel lookup (type "get").
     *
     * @param array<string, mixed> $arguments
     * @return array{mode: string, reply?: string, tool_context?: array<string, mixed>}
     */
    public function executeSingle(Tool $tool, array $arguments): array
    {
        $dataModel = $tool->dataModel;

        if (!$dataModel instanceof DataModel) {
            return ['mode' => 'direct', 'reply' => 'Data model belum dikonfigurasi untuk tool ini.'];
        }

        $tableName = trim((string) ($dataModel->table_name ?? ''));
        $connectionName = $this->resolveConnection((string) ($dataModel->connection_name ?? ''));
        $fieldsRaw = (array) ($dataModel->fields ?? []);
        $allowedFields = array_keys($fieldsRaw);

        // Collect required fields and fixed overrides from DataModel field definitions.
        $modelRequiredFields = [];
        $fixedValues = [];
        foreach ($fieldsRaw as $fieldName => $meta) {
            if (is_array($meta) && !empty($meta['required'])) {
                $modelRequiredFields[] = $fieldName;
                if (isset($meta['value']) && trim((string) $meta['value']) !== '') {
                    $fixedValues[$fieldName] = trim((string) $meta['value']);
                }
            }
        }

        // Merge tool-level and model-level required fields (union, no duplicates).
        $toolRequiredFields = (array) data_get($tool->parameters, 'required', []);
        $requiredFields = array_values(array_unique(array_merge($modelRequiredFields, $toolRequiredFields)));

        // Fixed values always override call arguments.
        foreach ($fixedValues as $fieldName => $fixedValue) {
            $arguments[$fieldName] = $fixedValue;
        }

        if ($tableName === '') {
            return ['mode' => 'direct', 'reply' => 'Data model table belum dikonfigurasi.'];
        }

        if ($allowedFields === []) {
            return ['mode' => 'direct', 'reply' => 'Field data model belum dikonfigurasi.'];
        }

        foreach ($requiredFields as $requiredField) {
            if (trim((string) ($arguments[$requiredField] ?? '')) === '') {
                return ['mode' => 'direct', 'reply' => $this->buildMissingDataMessage($tool)];
            }
        }

        $queryConfig = (array) data_get($tool->meta, 'query', []);
        $cfgSelect = (array) ($queryConfig['select'] ?? []);
        $cfgFilters = (array) ($queryConfig['filters'] ?? []);
        $cfgConditions = (array) ($queryConfig['conditions'] ?? []);
        $cfgDateRange = (array) ($queryConfig['date_range'] ?? []);
        $cfgAggregate = (array) ($queryConfig['aggregate'] ?? []);
        $cfgOrderBy = (array) ($queryConfig['order_by'] ?? []);
        $cfgLimit = (int) ($queryConfig['limit'] ?? 0);

        $dateRange = $this->resolveDateRange((string) ($cfgDateRange['range'] ?? ''));

        try {
            $aggFunc = strtolower(trim((string) ($cfgAggregate['function'] ?? '')));
            $aggField = trim((string) ($cfgAggregate['field'] ?? ''));
            $isAggregate = in_array($aggFunc, ['sum', 'count', 'avg', 'min', 'max'], true)
                && $aggField !== ''
                && in_array($aggField, $allowedFields, true);

            if ($isAggregate) {
                $query = DB::connection($connectionName)->table($tableName);
            } else {
                $selectFields = $cfgSelect !== []
                    ? array_values(array_intersect($cfgSelect, $allowedFields))
                    : $allowedFields;
                $query = DB::connection($connectionName)->table($tableName)->select($selectFields);
            }

            $lookupFilters = [];

            // Fields managed by conditions — skip generic exact-match for these.
            $conditionFields = array_unique(array_filter(array_map(
                fn ($c) => trim((string) ($c['field'] ?? '')),
                $cfgConditions
            )));

            // Argument-based WHERE (exact match for fields NOT covered by conditions).
            foreach ($arguments as $field => $value) {
                if (!in_array($field, $allowedFields, true)) {
                    continue;
                }
                if (in_array($field, $conditionFields, true)) {
                    continue; // handled by conditions block
                }
                $normalizedValue = is_string($value) ? trim($value) : $value;
                if ($normalizedValue === '' || $normalizedValue === null) {
                    continue;
                }
                $query->where($field, $normalizedValue);
                $lookupFilters[$field] = $normalizedValue;
            }

            // meta.query.conditions — flexible per-column conditions.
            if ($cfgConditions !== []) {
                $condError = $this->applyConditions($query, $cfgConditions, $allowedFields, $arguments, $lookupFilters);
                if ($condError !== null) {
                    return ['mode' => 'direct', 'reply' => $condError];
                }
            }

            // Static meta.query.filters.
            $this->applyFilters($query, $cfgFilters, $allowedFields, $lookupFilters);

            // meta.query.date_range — named range.
            $dateField = trim((string) ($cfgDateRange['field'] ?? ''));
            if ($dateField !== '' && $dateRange !== null && in_array($dateField, $allowedFields, true)) {
                $query->whereBetween($dateField, [$dateRange['from'], $dateRange['to']]);
                $lookupFilters[$dateField . '_from'] = $dateRange['from'];
                $lookupFilters[$dateField . '_to'] = $dateRange['to'];
            }

            // meta.query.date_range — between_now mode.
            if (trim((string) ($cfgDateRange['mode'] ?? '')) === 'between_now') {
                $startField = trim((string) ($cfgDateRange['start_field'] ?? ''));
                $endField = trim((string) ($cfgDateRange['end_field'] ?? ''));
                if ($startField !== '' && $endField !== ''
                    && in_array($startField, $allowedFields, true)
                    && in_array($endField, $allowedFields, true)) {
                    $now = now()->format('Y-m-d H:i:s');
                    $query->where($startField, '<=', $now)->where($endField, '>=', $now);
                    $lookupFilters['now_between'] = "{$startField} <= {$now} AND {$endField} >= {$now}";
                }
            }

            // meta.query.order_by.
            $this->applyOrderBy($query, $cfgOrderBy, $allowedFields);

            // Execute.
            if ($isAggregate) {
                $aggResult = $query->{$aggFunc}($aggField);

                return [
                    'mode' => 'model',
                    'tool_context' => [
                        'tool_name' => $tool->tool_name,
                        'tool_display_name' => $tool->display_name,
                        'tool_description' => $tool->description,
                        'lookup_filters' => $lookupFilters,
                        'aggregate' => [
                            'function' => $aggFunc,
                            'field' => $aggField,
                            'value' => $aggResult,
                        ],
                    ],
                ];
            }

            if ($cfgLimit > 0) {
                $rows = $query->limit(min($cfgLimit, self::MAX_ROWS))->get();
            } elseif ($queryConfig !== []) {
                $rows = $query->limit(self::MAX_ROWS)->get();
            } else {
                // Legacy default: single row.
                $row = $query->first();

                if ($row === null) {
                    return [
                        'mode' => 'model',
                        'tool_context' => [
                            'tool_name' => $tool->tool_name,
                            'tool_display_name' => $tool->display_name,
                            'tool_description' => $tool->description,
                            'lookup_filters' => $lookupFilters,
                            'resolved_data' => null,
                            'data_found' => false,
                        ],
                    ];
                }

                return [
                    'mode' => 'model',
                    'tool_context' => [
                        'tool_name' => $tool->tool_name,
                        'tool_display_name' => $tool->display_name,
                        'tool_description' => $tool->description,
                        'lookup_filters' => $lookupFilters,
                        'resolved_data' => $this->normalizeData((array) $row),
                    ],
                ];
            }

            if ($rows->isEmpty()) {
                return [
                    'mode' => 'model',
                    'tool_context' => [
                        'tool_name' => $tool->tool_name,
                        'tool_display_name' => $tool->display_name,
                        'tool_description' => $tool->description,
                        'lookup_filters' => $lookupFilters,
                        'resolved_data' => null,
                        'data_found' => false,
                    ],
                ];
            }

            $resolvedRows = $rows->map(fn ($r) => $this->normalizeData((array) $r))->toArray();

            return [
                'mode' => 'model',
                'tool_context' => [
                    'tool_name' => $tool->tool_name,
                    'tool_display_name' => $tool->display_name,
                    'tool_description' => $tool->description,
                    'lookup_filters' => $lookupFilters,
                    'resolved_data' => $resolvedRows,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('AI data model lookup failed', [
                'tool_name' => $tool->tool_name,
                'table_name' => $tableName,
                'connection_name' => $connectionName,
                'filters' => LogSanitizer::redactArguments($arguments),
                'error' => $e->getMessage(),
            ]);

            return ['mode' => 'direct', 'reply' => 'Terjadi kesalahan saat mengambil data.'];
        }
    }

    /**
     * Execute a multi-DataModel lookup (type "get_multiple").
     * Each DataModel in meta['data_model_ids'] is queried independently.
     *
     * @param array<string, mixed> $arguments
     * @return array{mode: string, reply?: string, tool_context?: array<string, mixed>}
     */
    public function executeMultiple(Tool $tool, array $arguments): array
    {
        $dataModelIds = (array) data_get($tool->meta, 'data_model_ids', []);

        if ($dataModelIds === []) {
            return ['mode' => 'direct', 'reply' => 'Data model belum dikonfigurasi untuk tool ini.'];
        }

        $dataModels = DataModel::query()->whereIn('id', $dataModelIds)->get();

        if ($dataModels->isEmpty()) {
            return [
                'mode' => 'model',
                'tool_context' => [
                    'tool_name' => $tool->tool_name,
                    'tool_display_name' => $tool->display_name,
                    'tool_description' => $tool->description,
                    'resolved_data' => null,
                    'data_found' => false,
                ],
            ];
        }

        // Validate tool-level required parameters first.
        $toolRequiredFields = (array) data_get($tool->parameters, 'required', []);
        foreach ($toolRequiredFields as $requiredField) {
            if (trim((string) ($arguments[$requiredField] ?? '')) === '') {
                return ['mode' => 'direct', 'reply' => $this->buildMissingDataMessage($tool)];
            }
        }

        $queryConfig = (array) data_get($tool->meta, 'query', []);
        $cfgSelect = (array) ($queryConfig['select'] ?? []);
        $cfgFilters = (array) ($queryConfig['filters'] ?? []);
        $cfgConditions = (array) ($queryConfig['conditions'] ?? []);
        $cfgDateRange = (array) ($queryConfig['date_range'] ?? []);
        $cfgAggregate = (array) ($queryConfig['aggregate'] ?? []);
        $cfgGroupBy = (array) ($queryConfig['group_by'] ?? []);
        $cfgOrderBy = (array) ($queryConfig['order_by'] ?? []);
        $cfgLimit = (int) ($queryConfig['limit'] ?? 0);

        $dateRange = $this->resolveDateRange((string) ($cfgDateRange['range'] ?? ''));

        $allResults = [];

        foreach ($dataModels as $dataModel) {
            $tableName = trim((string) ($dataModel->table_name ?? ''));
            $connectionName = $this->resolveConnection((string) ($dataModel->connection_name ?? ''));
            $fieldsRaw = (array) ($dataModel->fields ?? []);
            $allowedFields = array_keys($fieldsRaw);

            if ($tableName === '' || $allowedFields === []) {
                continue;
            }

            // Merge fixed values from DataModel field definitions into local arguments.
            $localArguments = $arguments;
            foreach ($fieldsRaw as $fieldName => $meta) {
                if (is_array($meta) && !empty($meta['required'])) {
                    if (isset($meta['value']) && trim((string) $meta['value']) !== '') {
                        $localArguments[$fieldName] = trim((string) $meta['value']);
                    }
                }
            }

            try {
                $lookupFilters = [];

                $aggFunc = strtolower(trim((string) ($cfgAggregate['function'] ?? '')));
                $aggField = trim((string) ($cfgAggregate['field'] ?? ''));
                $isAggregate = in_array($aggFunc, ['sum', 'count', 'avg', 'min', 'max'], true)
                    && $aggField !== ''
                    && in_array($aggField, $allowedFields, true);

                if ($isAggregate && $cfgGroupBy === []) {
                    $query = DB::connection($connectionName)->table($tableName);
                } else {
                    $selectFields = $cfgSelect !== []
                        ? array_values(array_intersect($cfgSelect, $allowedFields))
                        : $allowedFields;
                    $query = DB::connection($connectionName)->table($tableName)->select($selectFields);
                }

                // Fields managed by conditions — skip generic exact-match for these.
                $conditionFields = array_unique(array_filter(array_map(
                    fn ($c) => trim((string) ($c['field'] ?? '')),
                    $cfgConditions
                )));

                // Argument-based WHERE (exact match for fields NOT covered by conditions).
                foreach ($localArguments as $field => $value) {
                    if (!in_array($field, $allowedFields, true)) {
                        continue;
                    }
                    if (in_array($field, $conditionFields, true)) {
                        continue; // handled by conditions block
                    }
                    $normalizedValue = is_string($value) ? trim($value) : $value;
                    if ($normalizedValue === '' || $normalizedValue === null) {
                        continue;
                    }
                    $query->where($field, $normalizedValue);
                    $lookupFilters[$field] = $normalizedValue;
                }

                // meta.query.conditions — flexible per-column conditions.
                if ($cfgConditions !== []) {
                    $condError = $this->applyConditions($query, $cfgConditions, $allowedFields, $localArguments, $lookupFilters);
                    if ($condError !== null) {
                        $allResults[] = ['filters' => [], 'data' => null, 'error' => $condError];
                        continue;
                    }
                }

                // Static filters.
                $this->applyFilters($query, $cfgFilters, $allowedFields, $lookupFilters);

                // Date range.
                $dateField = trim((string) ($cfgDateRange['field'] ?? ''));
                if ($dateField !== '' && $dateRange !== null && in_array($dateField, $allowedFields, true)) {
                    $query->whereBetween($dateField, [$dateRange['from'], $dateRange['to']]);
                    $lookupFilters[$dateField . '_from'] = $dateRange['from'];
                    $lookupFilters[$dateField . '_to'] = $dateRange['to'];
                }

                // between_now mode.
                if (trim((string) ($cfgDateRange['mode'] ?? '')) === 'between_now') {
                    $startField = trim((string) ($cfgDateRange['start_field'] ?? ''));
                    $endField = trim((string) ($cfgDateRange['end_field'] ?? ''));
                    if ($startField !== '' && $endField !== ''
                        && in_array($startField, $allowedFields, true)
                        && in_array($endField, $allowedFields, true)) {
                        $now = now()->format('Y-m-d H:i:s');
                        $query->where($startField, '<=', $now)->where($endField, '>=', $now);
                        $lookupFilters['now_between'] = "{$startField} <= {$now} AND {$endField} >= {$now}";
                    }
                }

                // group_by.
                if ($cfgGroupBy !== []) {
                    $safeGroupBy = array_values(array_intersect($cfgGroupBy, $allowedFields));
                    if ($safeGroupBy !== []) {
                        $query->groupBy($safeGroupBy);
                    }
                }

                // order_by.
                $this->applyOrderBy($query, $cfgOrderBy, $allowedFields);

                // Execute.
                if ($isAggregate && $cfgGroupBy === []) {
                    $aggResult = $query->{$aggFunc}($aggField);

                    $allResults[] = [
                        'filters' => $lookupFilters,
                        'aggregate' => [
                            'function' => $aggFunc,
                            'field' => $aggField,
                            'value' => $aggResult,
                        ],
                        'data' => null,
                    ];
                } elseif ($isAggregate && $cfgGroupBy !== []) {
                    $grammar = $query->getGrammar();
                    $wrappedField = $grammar->wrap($aggField);
                    $wrappedAlias = $grammar->wrap("{$aggFunc}_{$aggField}");
                    $query->selectRaw("{$aggFunc}({$wrappedField}) as {$wrappedAlias}");
                    $rows = $query->limit($cfgLimit > 0 ? min($cfgLimit, self::MAX_ROWS) : self::MAX_ROWS)
                        ->get()
                        ->map(fn ($r) => (array) $r)
                        ->toArray();

                    $allResults[] = [
                        'filters' => $lookupFilters,
                        'data' => $rows ?: null,
                    ];
                } else {
                    if ($cfgLimit > 0) {
                        $rows = $query->limit(min($cfgLimit, self::MAX_ROWS))
                            ->get()
                            ->map(fn ($r) => $this->normalizeData((array) $r))
                            ->toArray();
                    } else {
                        $row = $query->first();
                        $rows = $row !== null ? [$this->normalizeData((array) $row)] : [];
                    }

                    $allResults[] = [
                        'filters' => $lookupFilters,
                        'data' => $rows ?: null,
                    ];
                }
            } catch (\Throwable $e) {
                Log::error('AI multi-model lookup failed for model', [
                    'tool_name' => $tool->tool_name,
                    'model_name' => $dataModel->model_name,
                    'table_name' => $tableName,
                    'error' => $e->getMessage(),
                ]);

                $allResults[] = [
                    'filters' => [],
                    'data' => null,
                    'error' => 'Gagal mengambil data.',
                ];
            }
        }

        $hasData = collect($allResults)->contains(fn ($r) => $r['data'] !== null || isset($r['aggregate']));

        return [
            'mode' => 'model',
            'tool_context' => [
                'tool_name' => $tool->tool_name,
                'tool_display_name' => $tool->display_name,
                'tool_description' => $tool->description,
                'lookup_type' => 'multi_model',
                'results' => $allResults,
                'data_found' => $hasData,
            ],
        ];
    }

    // ─── Shared helpers ─────────────────────────────────────────────────────

    /**
     * Resolve a named date range to {from, to} datetime strings.
     *
     * @return array{from: string, to: string}|null
     */
    public function resolveDateRange(string $range): ?array
    {
        if ($range === '') {
            return null;
        }

        return match ($range) {
            'last_week' => [
                'from' => now()->subWeek()->startOfWeek()->format('Y-m-d 00:00:00'),
                'to' => now()->subWeek()->endOfWeek()->format('Y-m-d 23:59:59'),
            ],
            'this_week' => [
                'from' => now()->startOfWeek()->format('Y-m-d 00:00:00'),
                'to' => now()->endOfWeek()->format('Y-m-d 23:59:59'),
            ],
            'today' => [
                'from' => now()->format('Y-m-d 00:00:00'),
                'to' => now()->format('Y-m-d 23:59:59'),
            ],
            'yesterday' => [
                'from' => now()->subDay()->format('Y-m-d 00:00:00'),
                'to' => now()->subDay()->format('Y-m-d 23:59:59'),
            ],
            'last_month' => [
                'from' => now()->subMonth()->startOfMonth()->format('Y-m-d 00:00:00'),
                'to' => now()->subMonth()->endOfMonth()->format('Y-m-d 23:59:59'),
            ],
            'this_month' => [
                'from' => now()->startOfMonth()->format('Y-m-d 00:00:00'),
                'to' => now()->format('Y-m-d 23:59:59'),
            ],
            default => null,
        };
    }

    /**
     * Recursively normalise a DB row's values:
     * - string "true"/"yes"/"1" → bool true
     * - string "false"/"no"/"0" → bool false
     * - numeric strings from DECIMAL/FLOAT columns → int (if no fractional part) or float
     * - all other values unchanged
     */
    public function normalizeData(mixed $value): mixed
    {
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeData($item);
            }
            return $normalized;
        }

        // PostgreSQL returns 't'/'f' strings for boolean columns
        if ($value === 't') {
            return true;
        }
        if ($value === 'f') {
            return false;
        }

        if (is_string($value) && is_numeric($value)) {
            $float = (float) $value;
            return ($float == (int) $float) ? (int) $float : $float;
        }

        return $value;
    }

    // ─── Private query helpers ───────────────────────────────────────────────

    /**
     * Apply meta.query.filters to the query builder.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array<int, array<string, mixed>> $cfgFilters
     * @param string[] $allowedFields
     * @param array<string, mixed> $lookupFilters  — mutated in place for logging
     */
    private function applyFilters(
        \Illuminate\Database\Query\Builder $query,
        array $cfgFilters,
        array $allowedFields,
        array &$lookupFilters
    ): void {
        foreach ($cfgFilters as $filter) {
            $fField = trim((string) ($filter['field'] ?? ''));
            $fOp = strtolower(trim((string) ($filter['operator'] ?? '=')));
            $fValue = $filter['value'] ?? null;

            if ($fField === '' || !in_array($fField, $allowedFields, true)) {
                continue;
            }

            if (in_array($fOp, ['in', 'not in'], true) && is_array($fValue)) {
                $fOp === 'in'
                    ? $query->whereIn($fField, $fValue)
                    : $query->whereNotIn($fField, $fValue);
            } elseif (in_array($fOp, self::SAFE_OPERATORS, true)) {
                $query->where($fField, $fOp, $fValue);
            } else {
                $query->where($fField, '=', $fValue);
            }

            $lookupFilters[$fField] = ($fOp === '=') ? $fValue : "{$fOp} {$fValue}";
        }
    }

    /**
     * Apply meta.query.order_by (single or array) to the query builder.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array<int|string, mixed> $cfgOrderBy
     * @param string[] $allowedFields
     */
    private function applyOrderBy(
        \Illuminate\Database\Query\Builder $query,
        array $cfgOrderBy,
        array $allowedFields
    ): void {
        $orderByList = isset($cfgOrderBy['field']) ? [$cfgOrderBy] : $cfgOrderBy;

        foreach ($orderByList as $orderItem) {
            $orderField = trim((string) ($orderItem['field'] ?? ''));
            $orderDir = strtolower(trim((string) ($orderItem['direction'] ?? 'desc')));

            if ($orderField !== '' && in_array($orderField, $allowedFields, true)) {
                $query->orderBy($orderField, $orderDir === 'asc' ? 'asc' : 'desc');
            }
        }
    }

    /**
     * Apply meta.query.conditions — flexible per-column conditions driven by
     * static config values or live customer chat arguments.
     *
     * Each condition entry:
     *   field          — column name (must be in DataModel allowed fields)
     *   operator       — =, !=, <>, >, <, >=, <=, LIKE, LIKE%%, NOT LIKE,
     *                    ILIKE, ILIKE%%, IN, NOT IN, IS NULL, IS NOT NULL, ~, !~
     *   source         — "static" (default) | "arg"
     *   value          — used for static source or as fallback when arg is empty
     *   arg            — argument key to read from customer chat (source=arg)
     *   skip_if_empty  — true: silently skip when arg is empty; false (default): use value fallback
     *   required       — true: return error reply when arg is empty and no value fallback
     *   group          — int|string: conditions sharing the same group key are combined as
     *                    AND (... OR ... OR ...) — first item AND, rest OR inside the group
     *
     * @param \Illuminate\Database\Query\Builder         $query
     * @param array<int, array<string, mixed>>           $cfgConditions
     * @param string[]                                   $allowedFields
     * @param array<string, mixed>                       $arguments
     * @param array<string, mixed>                       $lookupFilters  mutated in place
     * @return string|null  null = ok; string = error reply for the customer
     */
    private function applyConditions(
        \Illuminate\Database\Query\Builder $query,
        array $cfgConditions,
        array $allowedFields,
        array $arguments,
        array &$lookupFilters
    ): ?string {
        // Separate ungrouped (standalone AND) from grouped (OR inside AND wrapper).
        /** @var array<int, array<string, mixed>> $ungrouped */
        $ungrouped = [];
        /** @var array<string, list<array<string, mixed>>> $grouped */
        $grouped = [];

        foreach ($cfgConditions as $cond) {
            $groupKey = isset($cond['group']) && $cond['group'] !== '' && $cond['group'] !== null
                ? (string) $cond['group']
                : null;
            if ($groupKey === null) {
                $ungrouped[] = $cond;
            } else {
                $grouped[$groupKey][] = $cond;
            }
        }

        /** Resolve a condition to [field, operator, resolvedValue] or error/skip signal. */
        $resolve = function (array $cond) use ($allowedFields, $arguments): mixed {
            $field    = trim((string) ($cond['field'] ?? ''));
            $operator = strtoupper(trim((string) ($cond['operator'] ?? '=')));
            $source   = strtolower(trim((string) ($cond['source'] ?? 'static')));

            if ($field === '' || !in_array($field, $allowedFields, true)) {
                return null;
            }

            // Null-check operators need no value.
            if (in_array($operator, ['IS NULL', 'IS NOT NULL'], true)) {
                return [$field, $operator, null];
            }

            if ($source === 'arg') {
                $argKey  = trim((string) ($cond['arg'] ?? $field));
                $rawArg  = $arguments[$argKey] ?? null;
                $isEmpty = ($rawArg === null || (is_string($rawArg) && trim($rawArg) === ''));

                if ($isEmpty) {
                    if (!empty($cond['required'])) {
                        return '__error__:Field "' . $field . '" diperlukan untuk melanjutkan.';
                    }
                    if (!empty($cond['skip_if_empty'])) {
                        return null;
                    }
                    if (!array_key_exists('value', $cond)) {
                        return null;
                    }
                    return [$field, $operator, $cond['value']];
                }

                return [$field, $operator, is_string($rawArg) ? trim($rawArg) : $rawArg];
            }

            // Static source.
            if (!array_key_exists('value', $cond)) {
                return null;
            }

            return [$field, $operator, $cond['value']];
        };

        // Process ungrouped (each ANDed directly).
        foreach ($ungrouped as $cond) {
            $result = $resolve($cond);
            if ($result === null) {
                continue;
            }
            if (is_string($result) && str_starts_with($result, '__error__:')) {
                return substr($result, 10);
            }
            /** @var array{0: string, 1: string, 2: mixed} $result */
            [$field, $operator, $value] = $result;
            $this->applySingleCondition($query, false, $field, $operator, $value);
            $lookupFilters["cond_{$field}"] = $value !== null
                ? "{$operator} " . (is_array($value) ? implode(',', $value) : (string) $value)
                : $operator;
        }

        // Process groups (each group is a single AND clause with OR inside).
        foreach ($grouped as $groupKey => $conditions) {
            $resolved = [];
            foreach ($conditions as $cond) {
                $result = $resolve($cond);
                if ($result === null) {
                    continue;
                }
                if (is_string($result) && str_starts_with($result, '__error__:')) {
                    return substr($result, 10);
                }
                /** @var array{0: string, 1: string, 2: mixed} $result */
                $resolved[] = $result;
            }

            if ($resolved === []) {
                continue;
            }

            $resolvedCopy = $resolved;
            $query->where(function (\Illuminate\Database\Query\Builder $inner) use ($resolvedCopy): void {
                foreach ($resolvedCopy as $idx => [$field, $operator, $value]) {
                    $this->applySingleCondition($inner, $idx > 0, $field, $operator, $value);
                }
            });

            $logParts = array_map(
                fn ($r) => $r[2] !== null
                    ? "{$r[0]} {$r[1]} " . (is_array($r[2]) ? implode(',', $r[2]) : (string) $r[2])
                    : "{$r[0]} {$r[1]}",
                $resolved
            );
            $lookupFilters["cond_group_{$groupKey}"] = '(' . implode(' OR ', $logParts) . ')';
        }

        return null;
    }

    /**
     * Apply a single condition to the query builder (AND or OR variant).
     *
     * @param \Illuminate\Database\Query\Builder $query
     */
    private function applySingleCondition(
        \Illuminate\Database\Query\Builder $query,
        bool $useOr,
        string $field,
        string $operator,
        mixed $value
    ): void {
        switch ($operator) {
            case 'IS NULL':
                $useOr ? $query->orWhereNull($field) : $query->whereNull($field);
                return;

            case 'IS NOT NULL':
                $useOr ? $query->orWhereNotNull($field) : $query->whereNotNull($field);
                return;

            case 'LIKE%%':
                $escaped = '%' . $this->escapeLike((string) $value) . '%';
                $useOr
                    ? $query->orWhere($field, 'like', $escaped)
                    : $query->where($field, 'like', $escaped);
                return;

            case 'ILIKE':
                $useOr
                    ? $query->orWhereRaw("{$field} ilike ?", [(string) $value])
                    : $query->whereRaw("{$field} ilike ?", [(string) $value]);
                return;

            case 'ILIKE%%':
                $useOr
                    ? $query->orWhereRaw("{$field} ilike ?", ['%' . $value . '%'])
                    : $query->whereRaw("{$field} ilike ?", ['%' . $value . '%']);
                return;

            case '~':
                $useOr
                    ? $query->orWhereRaw("{$field} ~ ?", [(string) $value])
                    : $query->whereRaw("{$field} ~ ?", [(string) $value]);
                return;

            case '!~':
                $useOr
                    ? $query->orWhereRaw("{$field} !~ ?", [(string) $value])
                    : $query->whereRaw("{$field} !~ ?", [(string) $value]);
                return;

            case 'IN':
                $vals = $this->normalizeInValue($value);
                $useOr ? $query->orWhereIn($field, $vals) : $query->whereIn($field, $vals);
                return;

            case 'NOT IN':
                $vals = $this->normalizeInValue($value);
                $useOr ? $query->orWhereNotIn($field, $vals) : $query->whereNotIn($field, $vals);
                return;

            default:
                // =, !=, <>, >, <, >=, <=, LIKE, NOT LIKE, etc.
                $sqlOp = strtolower($operator);
                $useOr
                    ? $query->orWhere($field, $sqlOp, $value)
                    : $query->where($field, $sqlOp, $value);
        }
    }

    /**
     * Normalise a value for IN / NOT IN operators.
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * Accepts an array, or a comma-separated string.
     *
     * @return array<int, mixed>
     */
    private function normalizeInValue(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }
        if (is_string($value)) {
            return array_values(array_filter(
                array_map('trim', explode(',', $value)),
                fn ($v) => $v !== ''
            ));
        }
        return [$value];
    }

    private function resolveConnection(string $connectionName): string
    {
        $connectionName = trim($connectionName);

        if ($connectionName === '') {
            return 'mysqlgame';
        }

        // If the connection is already registered in Laravel's config, use it directly.
        if (config("database.connections.{$connectionName}") !== null) {
            return $connectionName;
        }

        // Look up the connection in the DatabaseConnection model and register it dynamically.
        $record = DatabaseConnection::query()
            ->where('name', $connectionName)
            ->where('is_active', true)
            ->first();

        if ($record === null) {
            return $connectionName; // Let Laravel throw its own "not configured" error.
        }

        $driverDefaults = match ($record->driver) {
            'pgsql' => [
                'charset'     => 'utf8',
                'prefix'      => '',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode'     => 'prefer',
            ],
            default => [
                'unix_socket'    => '',
                'charset'        => 'utf8mb4',
                'collation'      => 'utf8mb4_unicode_ci',
                'prefix'         => '',
                'prefix_indexes' => true,
                'strict'         => true,
                'engine'         => null,
            ],
        };

        config([
            "database.connections.{$connectionName}" => array_merge($driverDefaults, [
                'driver'   => $record->driver,
                'host'     => $record->host,
                'port'     => $record->port,
                'database' => $record->database,
                'username' => $record->username,
                'password' => $record->decrypted_password,
            ]),
        ]);

        DB::purge($connectionName);

        return $connectionName;
    }

    /**
     * Generate the user-facing missing-data prompt from the tool's parameter schema.
     */
    private function buildMissingDataMessage(Tool $tool): string
    {
        $properties = (array) data_get($tool->parameters, 'properties', []);
        if ($properties === []) {
            return 'Mohon lengkapi data yang diperlukan.';
        }

        $lines = ["Untuk {$tool->display_name}, mohon kirimkan data berikut:"];
        foreach ($properties as $name => $prop) {
            $desc = $prop['description'] ?? $name;
            $lines[] = "- {$desc} ({$name})";
        }

        return implode("\n", $lines);
    }
}
