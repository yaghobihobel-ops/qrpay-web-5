<?php

namespace Database\Factories;

use App\Models\FeeTier;
use App\Models\PricingRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeeTierFactory extends Factory
{
    protected $model = FeeTier::class;

    public function definition(): array
    {
        return [
            'pricing_rule_id' => PricingRule::factory(),
            'min_amount' => 0,
            'max_amount' => null,
            'percent_fee' => 1.0,
            'fixed_fee' => 0.5,
            'priority' => 0,
        ];
    }
}
