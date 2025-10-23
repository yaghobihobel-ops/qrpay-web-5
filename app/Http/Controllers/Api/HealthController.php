<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HealthCheck;
use App\Services\Monitoring\HealthCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function __construct(protected HealthCheckService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $results = $this->service->checkAll();
        $transformed = array_map(function (array $result) {
            if (isset($result['checked_at']) && method_exists($result['checked_at'], 'toIso8601String')) {
                $result['checked_at'] = $result['checked_at']->toIso8601String();
            }

            return $result;
        }, $results);

        $historyLimit = $request->integer('history', config('monitoring.defaults.history_limit'));
        $history = HealthCheck::query()
            ->latest('checked_at')
            ->limit($historyLimit)
            ->get()
            ->map(function (HealthCheck $check) {
                return [
                    'provider' => $check->provider,
                    'status' => $check->status,
                    'latency_ms' => $check->latency,
                    'status_code' => $check->status_code,
                    'message' => $check->message,
                    'checked_at' => optional($check->checked_at)->toIso8601String(),
                ];
            });

        return response()->json([
            'status' => $this->service->determineOverallStatus($results),
            'checked_at' => now()->toIso8601String(),
            'services' => $transformed,
            'history' => $history,
        ]);
    }
}
