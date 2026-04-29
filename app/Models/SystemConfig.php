<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SystemConfig extends Model
{
    protected $fillable = ['key', 'value'];

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
}
