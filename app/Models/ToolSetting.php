<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToolSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tool_name',
        'display_name',
        'description',
        'is_enabled',
        'meta',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'meta' => 'array',
    ];
}

