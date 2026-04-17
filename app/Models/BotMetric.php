<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotMetric extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'metric_type',
        'channel',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
