<?php

namespace App\Services\Analytics;

use App\Services\Analytics\Clients\AnalyticsClientInterface;
use App\Services\Analytics\Clients\BigQueryAnalyticsClient;
use App\Services\Analytics\Clients\ClickHouseAnalyticsClient;
use Carbon\CarbonImmutable;
use GuzzleHttp\Client;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;

class EventPipeline
{
    private AnalyticsClientInterface $client;

    public function __construct(?AnalyticsClientInterface $client = null)
    {
        $this->client = $client ?? $this->resolveClient();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function ingest(array $payload): void
    {
        $enriched = $this->enrichPayload($payload);

        try {
            $this->client->ingest($enriched);
        } catch (\Throwable) {
            $this->bufferPayload($enriched);
        }
    }

    public function flushBuffer(): int
    {
        $relative = $this->bufferPath();

        try {
            $contents = Storage::disk('local')->get($relative);
        } catch (FileNotFoundException) {
            return 0;
        }

        $lines = array_filter(explode("\n", trim($contents)));
        $processed = 0;

        foreach ($lines as $line) {
            try {
                $decoded = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                continue;
            }

            $this->client->ingest($decoded);
            $processed++;
        }

        Storage::disk('local')->delete($relative);

        return $processed;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function enrichPayload(array $payload): array
    {
        $timestamp = CarbonImmutable::now('UTC');

        return array_merge($payload, [
            'ingested_at' => $timestamp->toIso8601String(),
            'event_id' => $payload['event_id'] ?? Str::uuid()->toString(),
            'source' => $payload['source'] ?? config('app.name'),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function bufferPayload(array $payload): void
    {
        $relative = $this->bufferPath();

        try {
            $encoded = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return;
        }

        try {
            Storage::disk('local')->append($relative, $encoded);
        } catch (\Throwable) {
            // Swallow filesystem errors to avoid interrupting callers when buffering fails.
        }
    }

    private function bufferPath(): string
    {
        $path = config('analytics.buffer_path');

        return str_replace(storage_path('app/'), '', (string) $path);
    }

    private function resolveClient(): AnalyticsClientInterface
    {
        return match (config('analytics.default_connection')) {
            'clickhouse' => new ClickHouseAnalyticsClient(new Client()),
            default => new BigQueryAnalyticsClient(new Client()),
        };
    }
}
