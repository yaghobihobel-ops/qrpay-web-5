<?php

namespace App\Traits\Audit;

use App\Services\Audit\AuditLogger;

trait LogsAudit
{
    /**
     * Persist an audit entry for the given action.
     */
    protected function logAuditAction(string $action, array $context = []): void
    {
        try {
            app(AuditLogger::class)->log($action, $context);
        } catch (\Throwable $exception) {
            if (function_exists('report')) {
                report($exception);
            }
        }
    }
}
