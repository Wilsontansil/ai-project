<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
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
     * Encrypt value at rest when type is 'secret'.
     */
    public function setValueAttribute(?string $value): void
    {
        if ($this->type === 'secret' && $value !== null && $value !== '') {
            $this->attributes['value'] = Crypt::encryptString($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }

    /**
     * Get a setting value by key, with optional fallback.
     * Automatically decrypts secret-type values (with fallback for existing plaintext).
     */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        if (!Schema::hasTable('project_settings')) {
            return $default;
        }

        $cached = Cache::get('project_settings');

        if ($cached === null) {
            $rows = static::select('key', 'value', 'type')->get();
            $cached = [];
            foreach ($rows as $row) {
                $raw = $row->attributes['value'] ?? null;
                if ($row->type === 'secret' && $raw !== null && $raw !== '') {
                    try {
                        $cached[$row->key] = Crypt::decryptString($raw);
                    } catch (DecryptException) {
                        // Existing plaintext value — use as-is until re-saved
                        $cached[$row->key] = $raw;
                    }
                } else {
                    $cached[$row->key] = $raw;
                }
            }
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
        $setting = static::where('key', $key)->first();
        if ($setting) {
            $setting->value = $value; // goes through mutator
            $setting->save();
        }
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
