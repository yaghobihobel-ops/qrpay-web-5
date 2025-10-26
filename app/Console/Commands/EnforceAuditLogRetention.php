<?php

namespace App\Console\Commands;

use App\Models\AdminAuditLog;
use Illuminate\Console\Command;

class EnforceAuditLogRetention extends Command
{
    protected $signature = 'audit:enforce-retention';

    protected $description = 'Remove expired admin audit logs according to jurisdiction retention policies.';

    public function handle(): int
    {
        $count = AdminAuditLog::query()
            ->whereNotNull('retention_expires_at')
            ->where('retention_expires_at', '<', now())
            ->delete();

        $this->info("Removed {$count} expired audit logs.");

        return self::SUCCESS;
    }
}
