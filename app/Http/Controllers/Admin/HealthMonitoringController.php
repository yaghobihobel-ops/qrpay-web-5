<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HealthCheck;
use App\Services\Monitoring\HealthCheckService;
use Illuminate\Http\Request;

class HealthMonitoringController extends Controller
{
    public function __construct(protected HealthCheckService $service)
    {
    }

    public function index(Request $request)
    {
        $page_title = __('Service health dashboard');
        $results = $this->service->checkAll();
        $history = HealthCheck::query()
            ->latest('checked_at')
            ->limit($request->integer('history', config('monitoring.defaults.history_limit')))
            ->get();

        return view('admin.sections.monitoring.health', [
            'page_title' => $page_title,
            'results' => $results,
            'overall' => $this->service->determineOverallStatus($results),
            'history' => $history->groupBy('provider'),
        ]);
    }
}
