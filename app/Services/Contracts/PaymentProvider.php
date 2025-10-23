<?php

namespace App\Services\Contracts;

interface PaymentProvider
{
    public function listPayments(): array;

    public function processPayment(array $payload): array;

    public function refundPayment(string $reference): array;
}
