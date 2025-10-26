<?php

use App\Services\Gateway\RequestRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('version-info', function (Request $request, RequestRouter $router) {
    $router->prepare($request);

    return response()->json([
        'version' => 'v2',
    ]);
});

Route::any('{path}', function (Request $request, RequestRouter $router, string $path) {
    return $router->forwardToLegacy($request, $path);
})->where('path', '.*');
