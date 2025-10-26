<?php

namespace App\Console\Commands;

use App\Models\AdminAuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class EnforceAuditLogRetention extends Command
{
    protected $signature = 'audit:enforce-retention {--report : Output the identifiers of the deleted records in chunks}';

    protected $description = 'Remove expired admin audit logs according to jurisdiction retention policies.';

    public function handle(): int
    {
        $deletionQuery = AdminAuditLog::query()
            ->whereNotNull('retention_expires_at')
            ->where('retention_expires_at', '<', now());

        try {
            DB::beginTransaction();

            if ($this->option('report')) {
                (clone $deletionQuery)
                    ->orderBy('id')
                    ->chunkById(1000, function ($logs) {
                        $ids = $logs->pluck('id')->implode(', ');
                        if ($ids !== '') {
                            $this->line("Purging audit log IDs: {$ids}");
                        }
                    });
            }

            $count = $deletionQuery->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            report($e);
            $this->error('Failed to enforce audit log retention: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("Removed {$count} expired audit logs.");

        return self::SUCCESS;
    }
}
