<?php

namespace App\Console\Commands;

use App\Services\Edge\CacheInvalidationCoordinator;
use Illuminate\Console\Command;
use Throwable;

class EdgeCacheSubscriberCommand extends Command
{
    protected $signature = 'edge-cache:listen';

    protected $description = 'Listen for edge cache invalidation messages via Redis Pub/Sub';

    public function __construct(private readonly CacheInvalidationCoordinator $coordinator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $channel = $this->coordinator->channelName();
        $this->info(sprintf('Listening for edge cache invalidation messages on [%s]', $channel));

        try {
            $this->coordinator->subscribe(function (string $message): void {
                $this->line(sprintf('Processed cache invalidation message: %s', $message));
            });
        } catch (Throwable $exception) {
            $this->error('Failed to subscribe to edge cache invalidation channel.');
            $this->line($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
