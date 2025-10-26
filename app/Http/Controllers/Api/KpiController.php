<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analytics\KpiService;
use Illuminate\Http\JsonResponse;

class KpiController extends Controller
{
    public function __construct(private KpiService $service)
    {
    }

    public function __invoke(): JsonResponse
    {
        return response()->json($this->service->getRealtimeSnapshot());
    }
}
