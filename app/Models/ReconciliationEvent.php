<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReconciliationEvent extends Model
{
    protected $fillable = [
        'country_code',
        'channel',
        'provider_key',
        'provider_class',
        'event_type',
        'provider_reference',
        'status',
        'signature_valid',
        'idempotency_key',
        'payload',
        'headers',
        'validation_details',
        'occurred_at',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'validation_details' => 'array',
        'signature_valid' => 'boolean',
        'occurred_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
