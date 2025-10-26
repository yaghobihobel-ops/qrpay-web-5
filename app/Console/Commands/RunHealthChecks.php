<?php

namespace App\Console\Commands;

use App\Services\Monitoring\HealthCheckService;
use Illuminate\Console\Command;

class RunHealthChecks extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'monitoring:health-check {--service=}';

    /**
     * The console command description.
     */
    protected $description = 'Run health checks for external providers and internal services.';

    /**
     * Execute the console command.
     */
    public function handle(HealthCheckService $healthCheckService): int
    {
        $service = $this->option('service');
        $results = $healthCheckService->run($service);

        if ($results->isEmpty()) {
            $this->info('No services configured for health checks.');
            return self::SUCCESS;
        }

        $this->table(
            ['Service', 'Type', 'Status', 'Latency (ms)', 'Checked at', 'Details'],
            $results->map(function (array $result) {
                return [
                    $result['service_name'],
                    $result['service_type'],
                    strtoupper($result['status']),
                    $result['latency_ms'] ?? 'n/a',
                    $result['checked_at']->toDateTimeString(),
                    $result['message'],
                ];
            })->toArray()
        );

        if ($results->contains(fn ($result) => $result['status'] === 'down')) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
