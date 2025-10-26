<?php

namespace App\Traits\Security;

use App\Notifications\Security\ExcessiveLoginAttemptsNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

trait LogsSecurityEvents
{
    protected function logSecurityInfo(string $event, array $context = []): void
    {
        $this->writeSecurityLog('info', $event, $context);
    }

    protected function logSecurityWarning(string $event, array $context = []): void
    {
        $this->writeSecurityLog('warning', $event, $context);
    }

    protected function logSecurityError(string $event, array $context = []): void
    {
        $this->writeSecurityLog('error', $event, $context);
    }

    protected function logSecurityAlert(string $event, array $context = []): void
    {
        $this->writeSecurityLog('alert', $event, $context);
    }

    protected function notifyLoginThresholdExceeded(Request $request, string $identifier, int $attempts, array $context = []): void
    {
        $threshold = (int) config('security.login_attempts.threshold', 5);

        if ($threshold <= 0 || $attempts !== $threshold) {
            return;
        }

        $mail = config('security.login_attempts.mail');
        $slack = config('security.login_attempts.slack_webhook');

        if (! $mail && ! $slack) {
            return;
        }

        $payload = array_merge($context, [
            'identifier' => $identifier,
            'attempts' => $attempts,
            'ip' => $request->ip(),
        ]);

        $this->logSecurityAlert('login_threshold_exceeded', $payload);

        if ($mail) {
            Notification::route('mail', $mail)->notify(new ExcessiveLoginAttemptsNotification(
                $identifier,
                $request->ip(),
                $attempts,
                now()
            ));
        }

        if ($slack) {
            Notification::route('slack', $slack)->notify(new ExcessiveLoginAttemptsNotification(
                $identifier,
                $request->ip(),
                $attempts,
                now()
            ));
        }
    }

    private function writeSecurityLog(string $level, string $event, array $context = []): void
    {
        $request = request();

        $payload = array_merge([
            'event' => $event,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ], $context);

        Log::channel('security')->{$level}($event, $payload);
    }
}
