<?php

namespace App\Providers;

use App\Support\Queue\QueueMetricsRecorder;
use Illuminate\Support\ServiceProvider;

class QueueMonitoringServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        QueueMetricsRecorder::register();
    }
}
