<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'currency',
        'destination_country',
        'priority',
        'fee',
        'max_amount',
        'sla_thresholds',
    ];

    protected $casts = [
        'priority' => 'integer',
        'fee' => 'decimal:4',
        'max_amount' => 'decimal:2',
        'sla_thresholds' => 'array',
    ];
}
