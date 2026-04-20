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
        'mode',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'tags' => 'array',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function behavior(): HasOne
    {
        return $this->hasOne(CustomerBehavior::class);
    }
}
