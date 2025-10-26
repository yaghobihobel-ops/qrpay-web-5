<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRateLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'status',
        'from_cache',
        'message',
        'payload',
    ];

    protected $casts = [
        'status' => 'boolean',
        'from_cache' => 'boolean',
        'payload' => 'array',
    ];
}
