<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\KpiService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(private KpiService $service)
    {
    }

    public function __invoke(): View
    {
        return view('analytics.dashboard', [
            'snapshot' => $this->service->getRealtimeSnapshot(),
            'metabaseUrl' => config('services.metabase.dashboard_url'),
            'grafanaUrl' => config('services.grafana.dashboard_url'),
        ]);
    }
}
