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
        'data_model_id',
        'query_sql',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function chatAgent()
    {
        return $this->belongsTo(ChatAgent::class);
    }

    public function dataModel()
    {
        return $this->belongsTo(DataModel::class);
    }
}
