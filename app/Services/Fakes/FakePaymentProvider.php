<?php

namespace App\Services\Fakes;

use App\Services\Contracts\PaymentProvider;
use Illuminate\Support\Str;

class FakePaymentProvider implements PaymentProvider
{
    protected FakeScenarioRepository $repository;

    public function __construct(?FakeScenarioRepository $repository = null)
    {
        $this->repository = $repository ?? new FakeScenarioRepository();
    }

    protected function scenario(): array
    {
        return $this->repository->load();
    }

    public function listPayments(): array
    {
        return $this->scenario()['payments'] ?? [];
    }

    public function processPayment(array $payload): array
    {
        $scenario = $this->scenario();
        $payments = $scenario['payments'] ?? [];

        $status = $payload['force_status'] ?? ($payload['amount'] ?? 0) > 200 ? 'failed' : 'success';
        $reference = $payload['reference'] ?? 'PAY-' . Str::upper(Str::random(8));

        $record = [
            'reference' => $reference,
            'status' => $status,
            'amount' => $payload['amount'] ?? 0,
            'currency' => $payload['currency'] ?? 'USD',
            'channel' => $payload['channel'] ?? 'card',
            'reason' => $status === 'failed' ? ($payload['reason'] ?? 'Sandbox failure simulation') : null,
        ];

        $payments[] = $record;
        $scenario['payments'] = $payments;
        $this->repository->store($scenario);

        return $record;
    }

    public function refundPayment(string $reference): array
    {
        $scenario = $this->scenario();
        $payments = $scenario['payments'] ?? [];

        foreach ($payments as &$payment) {
            if ($payment['reference'] === $reference) {
                $payment['status'] = 'refunded';
                $scenario['payments'] = $payments;
                $this->repository->store($scenario);

                return [
                    'reference' => $reference,
                    'status' => 'refunded',
                ];
            }
        }

        return [
            'reference' => $reference,
            'status' => 'not_found',
        ];
    }
}
