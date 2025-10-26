<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Risk\OperationalDecisionEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RiskDecisionController extends Controller
{
    public function __construct(private OperationalDecisionEngine $engine)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $decision = $this->engine->evaluate($request->all());

        return response()->json([
            'data' => $decision,
        ]);
    }
}
