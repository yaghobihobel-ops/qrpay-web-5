<?php

namespace App\Services\Messaging;

use App\DataTransferObjects\EventStreamMessage;
use App\Jobs\SendEventToStream;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class EventStreamPublisher
{
    public function __construct(
        protected EventStreamClient $client,
        protected array $config = []
    ) {
    }

    public function publish(EventStreamMessage $message): void
    {
        $transport = $message->toTransportPayload();
        $destination = $transport['destination'];
        $payload = $transport['body'];
        $headers = Arr::wrap($transport['headers']);

        if (($this->config['async'] ?? true) === true) {
            SendEventToStream::dispatch($destination, $payload, $headers)
                ->onQueue($this->config['queue'] ?? 'default');

            return;
        }

        $this->sendSync($destination, $payload, $headers);
    }

    protected function sendSync(string $destination, string $payload, array $headers = []): void
    {
        try {
            $this->client->publish($destination, $payload, $headers);
        } catch (\Throwable $throwable) {
            Log::error('Failed to publish event stream message', [
                'destination' => $destination,
                'error' => $throwable->getMessage(),
            ]);
        }
    }
}
