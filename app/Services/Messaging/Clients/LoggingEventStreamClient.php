<?php

namespace App\Services\Messaging\Clients;

use App\Services\Messaging\EventStreamClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LoggingEventStreamClient implements EventStreamClient
{
    public function __construct(protected array $config = [])
    {
    }

    public function publish(string $destination, string $payload, array $headers = []): void
    {
        $channel = $this->config['channel'] ?? 'stack';
        Log::channel($channel)->info('event-stream.outgoing', [
            'destination' => $destination,
            'headers' => $headers,
            'payload' => $payload,
        ]);

        $disk = $this->config['disk'] ?? 'local';
        $path = $this->resolvePath($destination);
        $directory = trim(dirname($path), '.');
        if (!empty($directory)) {
            Storage::disk($disk)->makeDirectory($directory);
        }

        Storage::disk($disk)->append($path, $payload);
    }

    protected function resolvePath(string $destination): string
    {
        $path = $this->config['path'] ?? 'event-stream';
        if (Str::endsWith($path, '.jsonl')) {
            return $path;
        }

        return trim($path, '/') . '/' . Str::slug($destination) . '.jsonl';
    }
}
