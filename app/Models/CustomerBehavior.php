<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerBehavior extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'intent',
        'sentiment',
        'frequency_score',
        'last_intent_at',
        'extra',
    ];

    protected $casts = [
        'last_intent_at' => 'datetime',
        'extra' => 'array',
        'frequency_score' => 'float',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
