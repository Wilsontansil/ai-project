<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    protected $table = 'knowledge_base';

    protected $fillable = [
        'chat_agent_id',
        'title',
        'content',
        'source',
        'file_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function chatAgent()
    {
        return $this->belongsTo(ChatAgent::class);
    }
}
