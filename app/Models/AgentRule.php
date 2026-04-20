<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentRule extends Model
{
    protected $table = 'agent_rules';

    protected $fillable = [
        'chat_agent_id',
        'title',
        'instruction',
        'type',
        'category',
        'level',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function chatAgent()
    {
        return $this->belongsTo(ChatAgent::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGuidelines($query)
    {
        return $query->where('type', 'guideline');
    }

    public function scopeForbidden($query)
    {
        return $query->where('type', 'forbidden');
    }
}
