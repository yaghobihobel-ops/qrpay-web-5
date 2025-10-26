<?php

namespace Database\Factories;

use App\Models\PricingRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class PricingRuleFactory extends Factory
{
    protected $model = PricingRule::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'provider' => 'internal-ledger',
            'currency' => 'USD',
            'transaction_type' => 'make-payment',
            'user_level' => 'standard',
            'base_currency' => 'USD',
            'rate_provider' => null,
            'spread_bps' => 0,
            'status' => true,
            'conditions' => null,
        ];
    }
}
