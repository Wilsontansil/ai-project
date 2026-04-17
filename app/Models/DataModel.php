<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $model_name
 * @property string|null $slug
 * @property string|null $description
 * @property string|null $table_name
 * @property string|null $connection_name
 * @property array<string, mixed>|null $fields
 */
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
