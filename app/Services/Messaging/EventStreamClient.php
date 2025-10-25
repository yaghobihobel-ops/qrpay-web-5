<?php

namespace App\Services\Messaging;

interface EventStreamClient
{
    /**
     * Publish a raw message to the configured stream.
     *
     * @param  string  $destination
     * @param  string  $payload
     * @param  array<string, string|null>  $headers
     */
    public function publish(string $destination, string $payload, array $headers = []): void;
}
