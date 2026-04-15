<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForbiddenBehaviour extends Model
{
    protected $table = 'forbidden_behaviours';

    protected $fillable = [
        'chat_agent_id',
        'title',
        'instruction',
        'level',
        'is_active',
    ];

    public function chatAgent()
    {
        return $this->belongsTo(ChatAgent::class);
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
