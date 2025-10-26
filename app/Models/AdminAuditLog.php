<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminAuditLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'retention_expires_at' => 'datetime',
    ];
}
