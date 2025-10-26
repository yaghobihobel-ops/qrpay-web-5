<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeTier extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'min_amount' => 'decimal:8',
        'max_amount' => 'decimal:8',
        'percent_fee' => 'decimal:4',
        'fixed_fee' => 'decimal:8',
    ];

    public function pricingRule(): BelongsTo
    {
        return $this->belongsTo(PricingRule::class);
    }
}
