<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform',
        'platform_user_id',
        'phone_number',
        'name',
        'first_seen_at',
        'last_seen_at',
        'total_messages',
        'tags',
        'needs_human',
        'escalation_reason',
        'escalated_at',
        'resolved_at',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'escalated_at' => 'datetime',
        'resolved_at' => 'datetime',
        'tags' => 'array',
        'needs_human' => 'boolean',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function behavior(): HasOne
    {
        return $this->hasOne(CustomerBehavior::class);
    }

    public function escalationNotifications(): HasMany
    {
        return $this->hasMany(EscalationNotification::class);
    }
}
