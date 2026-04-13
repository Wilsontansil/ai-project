<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
