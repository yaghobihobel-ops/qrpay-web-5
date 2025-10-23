<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'status',
        'latency',
        'status_code',
        'message',
        'meta',
        'checked_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'checked_at' => 'datetime',
        'latency' => 'integer',
        'status_code' => 'integer',
    ];
}
