<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
    use HasFactory;

    protected $fillable = [
        'tool_name',
        'display_name',
        'description',
        'class_name',
        'slug',
        'is_enabled',
        'meta',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'meta' => 'array',
    ];

    /**
     * Create a new instance of the tool's service class.
     */
    public function newServiceInstance(): ?object
    {
        if (!class_exists($this->class_name)) {
            return null;
        }

        return new ($this->class_name)();
    }
}
