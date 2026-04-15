<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_name',
        'slug',
        'description',
        'table_name',
        'connection_name',
        'fields',
    ];

    protected $casts = [
        'fields' => 'array',
    ];

    public function databaseConnection(): BelongsTo
    {
        return $this->belongsTo(DatabaseConnection::class, 'connection_name', 'name');
    }
}
