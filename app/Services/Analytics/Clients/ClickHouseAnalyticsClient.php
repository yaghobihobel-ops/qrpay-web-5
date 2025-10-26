<?php

namespace App\Services\Analytics\Clients;

use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClickHouseAnalyticsClient implements AnalyticsClientInterface
{
    public function __construct(private ClientInterface $httpClient)
    {
    }

    /**
     * @param array<string, mixed> $event
     */
    public function ingest(array $event): void
    {
        $connection = config('analytics.connections.clickhouse');

        if (!data_get($connection, 'enabled')) {
            Log::debug('ClickHouse analytics disabled, buffering event.', [
                'event' => $event,
            ]);
            $this->buffer($event);

            return;
        }

        $host = data_get($connection, 'host');
        $port = data_get($connection, 'port', 8123);
        $database = data_get($connection, 'database');
        $table = data_get($connection, 'table');

        if (! $host || ! $database || ! $table) {
            Log::warning('ClickHouse analytics missing configuration, buffering event.');
            $this->buffer($event);

            return;
        }

        $query = sprintf(
            'INSERT INTO %s.%s FORMAT JSONEachRow',
            $database,
            $table,
        );

        try {
            $this->httpClient->request('POST', sprintf('http://%s:%s', $host, $port), [
                'query' => ['query' => $query],
                'body' => json_encode($event, JSON_THROW_ON_ERROR)."\n",
                'auth' => array_filter([
                    data_get($connection, 'username'),
                    data_get($connection, 'password'),
                ]),
                'timeout' => 5,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Failed to push analytics event to ClickHouse, buffering instead.', [
                'exception' => $exception->getMessage(),
            ]);
            $this->buffer($event);
        }
    }

    /**
     * @param array<string, mixed> $event
     */
    private function buffer(array $event): void
    {
        $path = config('analytics.buffer_path');
        $relative = str_replace(storage_path('app/'), '', $path);
        Storage::disk('local')->append($relative, json_encode($event, JSON_THROW_ON_ERROR));
    }
}
