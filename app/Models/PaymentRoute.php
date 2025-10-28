<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRoute extends Model
{
    use HasFactory;

    protected $table = 'payment_routes';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'provider',
        'currency',
        'destination_country',
        'priority',
        'fee',
        'max_amount',
        'sla_thresholds',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'priority' => 'int',
        'fee' => 'float',
        'max_amount' => 'float',
        'is_active' => 'bool',
        'sla_thresholds' => 'array',
    ];
}
