<?php

namespace App\Listeners;

use App\Events\ServiceExecutionFailed;
use App\Mail\SecurityAlertMail;
use App\Services\Domain\ProviderOverrideRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SecurityAlertListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(protected ProviderOverrideRepository $overrideRepository)
    {
    }

    public function handle(ServiceExecutionFailed $event): void
    {
        $context = $event->context;
        $config = $context->config;

        $recipient = $this->overrideRepository->resolveValue(
            $context->domain,
            $context->provider,
            'security.alert_recipient',
            data_get($config, 'security.alert_recipient')
        );

        if (!$recipient) {
            Log::warning('security_alert_missing_recipient', $context->toLogPayload());
            return;
        }

        $cooldown = (int) data_get($config, 'security.alert_cooldown_seconds', 900);
        $cacheKey = sprintf('security_alert_notified:%s:%s:%s', $context->domain, $context->provider, $context->operation);

        if ($cooldown > 0 && Cache::has($cacheKey)) {
            return;
        }

        $mail = new SecurityAlertMail($event->context, $event->exception, $event->failureCount, $event->threshold);
        Mail::to($recipient)->queue($mail);

        if ($cooldown > 0) {
            Cache::put($cacheKey, true, $cooldown);
        }
    }
}
