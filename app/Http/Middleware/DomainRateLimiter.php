<?php

namespace App\Http\Middleware;

use App\Services\Monitoring\DomainInstrumentation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class DomainRateLimiter
{
    public function __construct(protected DomainInstrumentation $instrumentation)
    {
    }

    public function handle(Request $request, Closure $next, string $domain, ?string $operation = null)
    {
        $config = config($domain, []);
        $provider = $request->input('provider')
            ?? $request->route('provider')
            ?? data_get($config, 'credentials.provider', 'default');

        $rateKey = $this->resolveRateKey($request, $domain, $operation);

        $this->instrumentation->enforceRateLimit($domain, (string) $provider, $rateKey, $config);

        $response = $next($request);

        $this->appendHeaders($response, $domain, $provider, $config, $rateKey);

        return $response;
    }

    protected function resolveRateKey(Request $request, string $domain, ?string $operation = null): string
    {
        $identifier = $request->user()?->getAuthIdentifier();

        if (!$identifier) {
            $identifier = $request->ip();
        }

        $operation = $operation ?: 'http';

        return sprintf('domain_rate:%s:%s:%s', $domain, $operation, sha1((string) $identifier));
    }

    protected function appendHeaders($response, string $domain, string $provider, array $config, string $key): void
    {
        $maxAttempts = data_get($config, 'rate_limit.max_attempts');
        $retryAfter = RateLimiter::availableIn($key);

        if (method_exists($response, 'headers')) {
            if ($maxAttempts) {
                $response->headers->set('X-RateLimit-Limit', $maxAttempts);
            }

            if ($retryAfter > 0) {
                $response->headers->set('Retry-After', $retryAfter);
            }

            $response->headers->set('X-Service-Domain', Str::snake($domain));
            $response->headers->set('X-Service-Provider', $provider);
        }
    }
}
