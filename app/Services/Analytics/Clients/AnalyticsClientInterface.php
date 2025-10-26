<?php

namespace App\Services\Analytics\Clients;

interface AnalyticsClientInterface
{
    /**
     * @param array<string, mixed> $event
     */
    public function ingest(array $event): void;
}
