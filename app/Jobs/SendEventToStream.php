<?php

namespace App\Jobs;

use App\Services\Messaging\EventStreamClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEventToStream implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  string  $destination
     * @param  string  $payload
     * @param  array<string, string|null>  $headers
     */
    public function __construct(
        protected string $destination,
        protected string $payload,
        protected array $headers = []
    ) {
    }

    public function handle(EventStreamClient $client): void
    {
        try {
            $client->publish($this->destination, $this->payload, $this->headers);
        } catch (\Throwable $throwable) {
            Log::error('Queue worker failed to deliver event stream message', [
                'destination' => $this->destination,
                'error' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }
}
