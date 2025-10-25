<?php

namespace App\Services\Messaging\Clients;

use App\Services\Messaging\EventStreamClient;
use Illuminate\Support\Facades\Log;

class NatsEventStreamClient implements EventStreamClient
{
    public function __construct(protected array $config = [])
    {
    }

    public function publish(string $destination, string $payload, array $headers = []): void
    {
        if (!class_exists(\Nats\Connection::class)) {
            Log::warning('NATS php client not available. Falling back to log sink.', [
                'destination' => $destination,
            ]);

            (new LoggingEventStreamClient($this->config))->publish($destination, $payload, $headers);
            return;
        }

        $options = new \Nats\ConnectionOptions();
        $endpoint = $this->config['url'] ?? 'nats://127.0.0.1:4222';
        $parsed = parse_url($endpoint);

        if (!empty($parsed['host'])) {
            $options = $options->setHost($parsed['host']);
        }

        if (!empty($parsed['port'])) {
            $options = $options->setPort((int) $parsed['port']);
        }

        if (!empty($this->config['user'])) {
            $options = $options->setUser($this->config['user']);
        }

        if (!empty($this->config['pass'])) {
            $options = $options->setPass($this->config['pass']);
        }

        $connection = new \Nats\Connection($options);
        $connection->connect();

        $connection->publish($destination, $payload, $headers['content_type'] ?? 'application/json');
        $connection->flush();
        $connection->close();
    }
}
