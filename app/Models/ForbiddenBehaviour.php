<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForbiddenBehaviour extends Model
{
    protected $table = 'forbidden_behaviours';

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
