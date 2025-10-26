<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceFingerprint extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
        'is_trusted' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function authenticatable()
    {
        return $this->morphTo();
    }
}
