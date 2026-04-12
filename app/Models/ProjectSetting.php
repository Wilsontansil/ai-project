<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ProjectSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'label',
        'group',
        'type',
    ];

    /**
     * Get a setting value by key, with optional fallback.
     */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        if (!Schema::hasTable('project_settings')) {
            return $default;
        }

        $cached = Cache::get('project_settings');

        if ($cached === null) {
            $cached = static::pluck('value', 'key')->toArray();
            Cache::put('project_settings', $cached, now()->addMinutes(60));
        }

        $value = $cached[$key] ?? null;

        return ($value !== null && $value !== '') ? $value : $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function setValue(string $key, ?string $value): void
    {
        static::where('key', $key)->update(['value' => $value]);
        Cache::forget('project_settings');
    }

    /**
     * Clear the settings cache (call after bulk update).
     */
    public static function clearCache(): void
    {
        Cache::forget('project_settings');
    }
}
