<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemConfig extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description',
        'source_type',
        'data_model_id',
        'lookup_field',
        'lookup_value',
        'result_field',
    ];

    protected $attributes = [
        'source_type' => 'manual',
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        if (!Schema::hasTable('system_configs')) {
            return $default;
        }

        $version = (int) Cache::get('system_configs_version', 1);
        $cacheKey = "system_config_value:v{$version}:{$key}";
        $value = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($key) {
            $config = static::query()->where('key', $key)->first();
            if ($config === null) {
                return null;
            }

            return $config->resolveEffectiveValue();
        });

        return ($value !== null && $value !== '') ? $value : $default;
    }

    /**
     * Get value + description for a config key (used by PromptBuilder to give AI context).
     * Returns ['value' => string|null, 'description' => string|null].
     *
     * @return array{value: string|null, description: string|null}
     */
    public static function getValueWithDescription(string $key): array
    {
        if (!Schema::hasTable('system_configs')) {
            return ['value' => null, 'description' => null];
        }

        $version = (int) Cache::get('system_configs_version', 1);
        $cacheKey = "system_config_desc:v{$version}:{$key}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($key): array {
            $config = static::query()->where('key', $key)->first();
            if ($config === null) {
                return ['value' => null, 'description' => null];
            }

            return [
                'value'       => $config->resolveEffectiveValue(),
                'description' => ($config->description !== null && $config->description !== '') ? $config->description : null,
            ];
        });
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], [
            'source_type' => 'manual',
            'value' => $value,
            'data_model_id' => null,
            'lookup_field' => null,
            'lookup_value' => null,
            'result_field' => null,
        ]);
        static::bumpCacheVersion();
    }

    protected static function booted(): void
    {
        static::saved(function () {
            static::bumpCacheVersion();
        });

        static::deleted(function () {
            static::bumpCacheVersion();
        });
    }

    public static function bumpCacheVersion(): void
    {
        $current = (int) Cache::get('system_configs_version', 1);
        Cache::forever('system_configs_version', $current + 1);
    }

    public function resolveEffectiveValue(): ?string
    {
        if (($this->source_type ?? 'manual') !== 'datamodel_lookup') {
            return $this->value;
        }

        return $this->resolveDataModelLookupValue();
    }

    private function resolveDataModelLookupValue(): ?string
    {
        $context = $this->buildLookupContext();
        if ($context === null) {
            return null;
        }

        return $this->executeLookupQuery($context);
    }

    /**
     * @return array{table:string, connection:string, lookup_field:string, lookup_value:string, result_field:string, fields:array<string,mixed>}|null
     */
    private function buildLookupContext(): ?array
    {
        $dataModelId = (int) ($this->data_model_id ?? 0);
        $dataModel = $dataModelId > 0 ? DataModel::query()->find($dataModelId) : null;
        if ($dataModel === null) {
            return null;
        }

        $context = [
            'table' => trim((string) ($dataModel->table_name ?? '')),
            'connection' => trim((string) ($dataModel->connection_name ?? '')),
            'lookup_field' => trim((string) ($this->lookup_field ?? '')),
            'lookup_value' => (string) ($this->lookup_value ?? ''),
            'result_field' => trim((string) ($this->result_field ?? '')),
            'fields' => (array) ($dataModel->fields ?? []),
        ];

        if (!$this->isLookupConfigValid($context['table'], $context['lookup_field'], $context['result_field'], $context['fields'])) {
            return null;
        }

        return $context;
    }

    /**
     * @param array{table:string, connection:string, lookup_field:string, lookup_value:string, result_field:string, fields:array<string,mixed>} $context
     */
    private function executeLookupQuery(array $context): ?string
    {
        try {
            $query = DB::connection($context['connection'] !== '' ? $context['connection'] : null)
                ->table($context['table'])
                ->where($context['lookup_field'], $context['lookup_value']);

            $this->applyDataModelFixedFilters($query, $context['fields']);

            $row = $query->select([$context['result_field']])->first();
            $value = $row !== null ? data_get((array) $row, $context['result_field']) : null;

            return $value !== null ? (string) $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $fieldsRaw
     */
    private function isLookupConfigValid(string $table, string $lookupField, string $resultField, array $fieldsRaw): bool
    {
        if ($table === '' || $lookupField === '' || $resultField === '') {
            return false;
        }

        $allowedFields = array_keys($fieldsRaw);
        return in_array($lookupField, $allowedFields, true) && in_array($resultField, $allowedFields, true);
    }

    /**
     * @param array<string, mixed> $fieldsRaw
     */
    private function applyDataModelFixedFilters($query, array $fieldsRaw): void
    {
        foreach ($fieldsRaw as $fieldName => $meta) {
            if (!is_array($meta) || empty($meta['required'])) {
                continue;
            }

            if (isset($meta['value']) && trim((string) $meta['value']) !== '') {
                $query->where($fieldName, trim((string) $meta['value']));
            }
        }
    }
}
