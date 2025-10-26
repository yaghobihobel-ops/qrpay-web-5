<?php

namespace App\Console\Commands;

use App\Services\Analytics\EventPipeline;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchAnalyticsEvent extends Command
{
    protected $signature = 'analytics:dispatch-event {event : The event name} {--payload=}';

    protected $description = 'Dispatch a structured analytics event to the configured datastore.';

    public function handle(EventPipeline $pipeline): int
    {
        $payload = $this->option('payload');

        /** @var array<string, mixed> $decoded */
        $decoded = [];

        if ($payload) {
            $decoded = json_decode($payload, true);

            if (! is_array($decoded)) {
                $this->error('Payload must be valid JSON.');

                return self::FAILURE;
            }
        }

        $event = array_merge($decoded, [
            'event_name' => $this->argument('event'),
        ]);

        $pipeline->ingest($event);

        Log::info('Analytics event dispatched from console command.', $event);

        $this->info('Event dispatched successfully.');

        return self::SUCCESS;
    }
}
