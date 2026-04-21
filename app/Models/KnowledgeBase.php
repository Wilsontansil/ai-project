<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    protected $table = 'knowledge_base';

    protected $fillable = [
        'title',
        'content',
        'source',
        'file_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
