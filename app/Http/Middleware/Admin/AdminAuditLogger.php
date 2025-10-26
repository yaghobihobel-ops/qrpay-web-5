<?php

namespace App\Http\Middleware\Admin;

use App\Models\AdminAuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminAuditLogger
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! $request->user('admin')) {
            return $response;
        }

        $admin = $request->user('admin');
        $region = Str::upper(data_get($admin->address, 'country', 'GLOBAL'));
        $retentionDays = config("compliance.audit_log_retention.{$region}") ?? config('compliance.audit_log_retention.GLOBAL');

        AdminAuditLog::create([
            'admin_id' => $admin->id,
            'action' => $request->method() . ' ' . $request->route()->getName(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => $request->except(['password', 'password_confirmation']),
            'status_code' => $response->getStatusCode(),
            'retention_expires_at' => now()->addDays($retentionDays),
        ]);

        return $response;
    }
}
