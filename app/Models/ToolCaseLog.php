<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToolCaseLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'chat_id',
        'channel',
        'tool_name',
        'display_name',
        'trigger_mode',
        'arguments',
        'has_attachment',
        'attachment_url',
        'attachment_meta',
        'customer_id',
        'customer_info',
        'tool_reply',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'arguments'       => 'array',
            'has_attachment'  => 'boolean',
            'attachment_meta' => 'array',
            'customer_info'   => 'array',
            'created_at'      => 'datetime',
        ];
    }
}
