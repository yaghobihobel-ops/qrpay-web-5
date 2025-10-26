<?php

namespace App\Services\Analytics\Clients;

use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BigQueryAnalyticsClient implements AnalyticsClientInterface
{
    public function __construct(private ClientInterface $httpClient)
    {
    }

    /**
     * @param array<string, mixed> $event
     */
    public function ingest(array $event): void
    {
        $connection = config('analytics.connections.bigquery');

        if (!data_get($connection, 'enabled')) {
            Log::channel('single')->debug('BigQuery analytics disabled, buffering event.', [
                'event' => $event,
            ]);
            $this->buffer($event);

            return;
        }

        $endpoint = data_get($connection, 'endpoint');
        $project = data_get($connection, 'project');
        $dataset = data_get($connection, 'dataset');
        $table = data_get($connection, 'table');

        if (! $endpoint || ! $project || ! $dataset || ! $table) {
            Log::warning('BigQuery analytics missing configuration, buffering event.');
            $this->buffer($event);

            return;
        }

        $payload = [
            'rows' => [
                [
                    'json' => $event,
                ],
            ],
        ];

        try {
            $this->httpClient->request('POST', rtrim($endpoint, '/')."/projects/{$project}/datasets/{$dataset}/tables/{$table}:insertAll", [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 5,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Failed to push analytics event to BigQuery, buffering instead.', [
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
