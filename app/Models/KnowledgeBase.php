<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    use HasFactory;

    protected $table = 'knowledge_base';

    protected $fillable = [
        'category',
        'title',
        'content',
        'tags',
        'confidence_score',
        'source',
        'source_file',
        'is_active',
    ];

    protected $casts = [
        'tags' => 'array',
        'confidence_score' => 'float',
        'is_active' => 'boolean',
    ];
}
