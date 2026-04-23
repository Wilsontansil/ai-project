<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChatAgent extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'system_prompt',
        'model',
        'max_tokens',
        'temperature',
        'message_await_seconds',
        'is_enabled',
        'is_default',
        'escalation_enabled',
        'meta',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_default' => 'boolean',
        'escalation_enabled' => 'boolean',
        'temperature' => 'float',
        'message_await_seconds' => 'integer',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $agent) {
            if (empty($agent->slug)) {
                $agent->slug = Str::slug($agent->name);
            }
        });
    }

    /**
     * Get the default agent, or the first enabled one.
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first()
            ?? static::where('is_enabled', true)->first();
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function agentRules()
    {
        return $this->hasMany(AgentRule::class);
    }

    public function knowledgeBases()
    {
        return $this->hasMany(KnowledgeBase::class);
    }
}
