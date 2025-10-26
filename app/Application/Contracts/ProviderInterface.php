<?php

namespace App\Application\Contracts;

interface ProviderInterface
{
    public function supports(string $driver): bool;

    public function handle(array $payload): array;
}
