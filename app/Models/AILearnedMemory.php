<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AILearnedMemory extends Model
{
    use HasFactory;

    protected $table = 'ai_learned_memories';

    protected $fillable = [
        'pattern',
        'learned_response',
        'category',
        'hit_count',
        'confidence',
        'is_approved',
        'is_active',
    ];

    protected $casts = [
        'hit_count' => 'integer',
        'confidence' => 'float',
        'is_approved' => 'boolean',
        'is_active' => 'boolean',
    ];
}
