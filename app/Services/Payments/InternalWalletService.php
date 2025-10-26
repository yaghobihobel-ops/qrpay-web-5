<?php

namespace App\Services\Payments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InternalWalletService
{
    /**
     * Ensure the wallet has sufficient balance for the given amount.
     */
    public function assertSufficientBalance(Model $wallet, float $amount): void
    {
        if ($amount <= 0) {
            throw new RuntimeException(__('The requested amount must be greater than zero.'));
        }

        $wallet->refresh();
        if ($wallet->balance < $amount) {
            throw new RuntimeException(__('Insufficient balance in the selected wallet.'));
        }
    }

    /**
     * Reserve funds for a pending checkout.
     */
    public function reserveFunds(Model $wallet, float $amount): void
    {
        $this->assertSufficientBalance($wallet, $amount);
    }

    /**
     * Capture funds after a successful payment.
     */
    public function capture(Model $wallet, float $amount): void
    {
        DB::transaction(function () use ($wallet, $amount) {
            $wallet->refresh();
            $this->assertSufficientBalance($wallet, $amount);

            $wallet->balance -= $amount;
            $wallet->save();
        });
    }

    /**
     * Refund a payment back to the wallet.
     */
    public function refund(Model $wallet, float $amount): void
    {
        DB::transaction(function () use ($wallet, $amount) {
            $wallet->refresh();
            $wallet->balance += $amount;
            $wallet->save();
        });
    }
}
