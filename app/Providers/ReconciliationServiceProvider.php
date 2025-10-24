<?php

namespace App\Providers;

use App\Support\Providers\ProviderChannelMapper;
use App\Support\Reconciliation\ReconciliationRecorder;
use App\Support\Reconciliation\SettlementReportGenerator;
use App\Support\Webhooks\WebhookSignatureValidator;
use Illuminate\Support\ServiceProvider;

class ReconciliationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProviderChannelMapper::class);
        $this->app->singleton(ReconciliationRecorder::class);
        $this->app->singleton(SettlementReportGenerator::class);

        $this->app->singleton(WebhookSignatureValidator::class, function ($app) {
            return new WebhookSignatureValidator(config('webhooks', []));
        });
    }
}
