<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payments\Recommendation\PaymentRouteRecommender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentRouteRecommendationController extends Controller
{
    public function __construct(private PaymentRouteRecommender $recommender)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        /** @var array<int, array<string, mixed>> $routes */
        $routes = $request->input('routes', []);
        $preferences = (array) $request->input('preferences', []);

        $recommendation = $this->recommender->recommend(collect($routes), $preferences);

        return response()->json([
            'data' => $recommendation,
        ]);
    }
}
