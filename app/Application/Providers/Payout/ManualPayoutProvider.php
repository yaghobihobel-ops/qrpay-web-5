<?php

namespace App\Application\Providers\Payout;

use App\Application\Contracts\ProviderInterface;

class ManualPayoutProvider implements ProviderInterface
{
    public function supports(string $driver): bool
    {
        return $driver === 'manual';
    }

    public function handle(array $payload): array
    {
        return [
            'status' => true,
            'data' => $payload['data'] ?? [],
            'message' => $payload['message'] ?? [],
        ];
    }
}
