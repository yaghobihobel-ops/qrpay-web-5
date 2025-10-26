<?php

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $pricing_rule_id
 * @property float|null $min_amount
 * @property float|null $max_amount
 * @property string $fee_type
 * @property float $fee_amount
 * @property string|null $fee_currency
 * @property int $priority
 */
class FeeTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'pricing_rule_id',
        'min_amount',
        'max_amount',
        'fee_type',
        'fee_amount',
        'fee_currency',
        'priority',
    ];

    protected $casts = [
        'min_amount' => 'decimal:8',
        'max_amount' => 'decimal:8',
        'fee_amount' => 'decimal:8',
        'priority' => 'integer',
    ];

    public function matchesAmount(float $amount): bool
    {
        if ($this->min_amount !== null && $amount < (float) $this->min_amount) {
            return false;
        }

        if ($this->max_amount !== null && $amount > (float) $this->max_amount) {
            return false;
        }

        return true;
    }
}
