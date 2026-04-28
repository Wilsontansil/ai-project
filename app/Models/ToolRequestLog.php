<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToolRequestLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tool_name',
        'display_name',
        'endpoint_url',
        'request_payload',
        'response_status',
        'response_body',
        'latency_ms',
        'success',
        'error',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_body'   => 'array',
            'success'         => 'boolean',
            'latency_ms'      => 'float',
            'created_at'      => 'datetime',
        ];
    }
}
