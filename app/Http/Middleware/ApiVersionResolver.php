<?php

namespace App\Http\Middleware;

use App\Services\Gateway\RequestRouter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiVersionResolver
{
    public function __construct(private RequestRouter $requestRouter)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $version = $this->requestRouter->prepare($request);
        $request->attributes->set('resolved_api_version', $version);

        $this->rewritePathIfNecessary($request, $version);

        return $next($request);
    }

    protected function rewritePathIfNecessary(Request $request, string $version): void
    {
        $path = ltrim($request->getPathInfo(), '/');

        if (!Str::startsWith($path, 'api/')) {
            return;
        }

        $segments = explode('/', $path);

        if (isset($segments[1]) && in_array($segments[1], $this->requestRouter->supportedVersions(), true)) {
            $request->attributes->set('resolved_api_version', $segments[1]);
            return;
        }

        array_splice($segments, 1, 0, [$version]);
        $newPath = '/' . implode('/', $segments);

        $request->server->set('REQUEST_URI', $newPath);
        $request->server->set('PATH_INFO', $newPath);
        $request->server->set('ORIG_PATH_INFO', $newPath);
    }
}
