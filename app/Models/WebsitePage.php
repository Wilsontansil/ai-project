<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsitePage extends Model
{
    protected $fillable = [
        'url',
        'title',
        'content',
        'meta',
        'status',
        'error_message',
        'last_scraped_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'last_scraped_at' => 'datetime',
    ];
}
