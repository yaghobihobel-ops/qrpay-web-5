<?php

namespace Database\Factories;

use App\Constants\PaymentGatewayConst;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'trx_id' => strtoupper(Str::random(12)),
            'type' => PaymentGatewayConst::TYPEADDMONEY,
            'request_amount' => $this->faker->randomFloat(2, 10, 1000),
            'payable' => $this->faker->randomFloat(2, 10, 1000),
            'available_balance' => $this->faker->randomFloat(2, 10, 1000),
            'status' => PaymentGatewayConst::STATUSSUCCESS,
            'attribute' => PaymentGatewayConst::RECEIVED,
            'details' => null,
        ];
    }

    public function forUser(int $userId): self
    {
        return $this->state([
            'user_id' => $userId,
        ]);
    }
}
