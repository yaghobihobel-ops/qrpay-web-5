<?php

namespace App\Console\Commands;

use App\Services\Security\KeyManagementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RotateApiKeys extends Command
{
    protected $signature = 'keys:rotate {service? : Limit rotation to a single integration} {--force : Force rotation regardless of schedule}';

    protected $description = 'Rotate partner API keys stored in Vault/HSM.';

    public function __construct(private KeyManagementService $keys)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $service = $this->argument('service');
        $force = (bool) $this->option('force');
        $services = $service ? [$service] : $this->keys->listServices();

        if (empty($services)) {
            $this->warn('No services configured for key rotation.');
            return self::SUCCESS;
        }

        $exitCode = self::SUCCESS;

        foreach ($services as $name) {
            try {
                if (!$this->keys->supportsRotation($name)) {
                    $this->info("[{$name}] rotation skipped (not enabled).");
                    continue;
                }

                $rotated = $this->keys->rotate($name, $force);

                if ($rotated) {
                    $this->info("[{$name}] secret rotated successfully.");
                } else {
                    $this->comment("[{$name}] rotation not required at this time.");
                }
            } catch (Throwable $e) {
                $exitCode = self::FAILURE;
                $this->error("[{$name}] rotation failed: {$e->getMessage()}");
                Log::error('Key rotation failure', [
                    'service' => $name,
                    'exception' => $e,
                ]);
            }
        }

        return $exitCode;
    }
}
