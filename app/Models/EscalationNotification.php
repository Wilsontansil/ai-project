<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EscalationNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'channel', 'chat_id', 'reason',
        'last_message', 'is_read', 'resolved_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
