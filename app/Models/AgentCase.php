<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentCase extends Model
{
    protected $fillable = [
        'title',
        'instruction',
        'level',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
