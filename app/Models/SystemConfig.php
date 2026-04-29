<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemConfig extends Model
{
    protected $fillable = ['key', 'value', 'source', 'data_model_id', 'query_sql', 'synced_at'];

    protected $casts = [
        'synced_at' => 'datetime',
    ];

    public function dataModel(): BelongsTo
    {
        return $this->belongsTo(DataModel::class);
    }

    public static function getValue(string $key, ?string $default = null): ?string
    {
        if (!Schema::hasTable('system_configs')) {
            return $default;
        }

        $cached = Cache::get('system_configs');

        if ($cached === null) {
            $cached = static::pluck('value', 'key')->toArray();
            Cache::put('system_configs', $cached, now()->addMinutes(60));
        }

        $value = $cached[$key] ?? null;

        return ($value !== null && $value !== '') ? $value : $default;
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('system_configs');
    }

    /**
     * Run the stored query against the DataModel connection and save the first cell
     * into the value column. Clears the config cache so the new value is served immediately.
     *
     * @throws \RuntimeException with a user-facing message on failure.
     */
    public function syncFromDatamodel(): void
    {
        if ($this->source !== 'datamodel' || empty($this->query_sql)) {
            throw new \RuntimeException('This entry is not configured as a datamodel source.');
        }

        $dataModel = $this->dataModel;
        if ($dataModel === null) {
            throw new \RuntimeException('No DataModel linked to this config entry.');
        }

        $connection = $dataModel->connection_name ?: 'mysqlgame';
        $sql        = trim((string) $this->query_sql);

        $rows = DB::connection($connection)->select($sql);

        if (empty($rows)) {
            throw new \RuntimeException('Query returned no rows.');
        }

        $firstRow   = (array) $rows[0];
        $firstValue = (string) array_values($firstRow)[0];

        $this->update([
            'value'     => $firstValue,
            'synced_at' => now(),
        ]);

        Cache::forget('system_configs');
    }
}
