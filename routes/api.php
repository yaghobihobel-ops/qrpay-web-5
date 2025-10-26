<?php

use App\Services\Gateway\RequestRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require base_path('routes/api/v1.php');
});

Route::prefix('v2')->group(function () {
    require base_path('routes/api/v2.php');
});

Route::fallback(function (Request $request, RequestRouter $router) {
    $version = $request->attributes->get('resolved_api_version');

    if (!$version) {
        $version = $router->supportedVersions()[0] ?? 'v1';
    }

    return response()->json([
        'message' => 'Resource not found.',
        'version' => $version,
    ], 404);
});
