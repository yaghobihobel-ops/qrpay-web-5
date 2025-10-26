<?php

namespace App\Services\Telemetry;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelemetryManager
{
    private array $config;

    private bool $enabled;

    private $tracer = null;

    private $meter = null;

    private $requestCounter = null;

    private $requestDuration = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->enabled = (bool) ($config['enabled'] ?? false);
    }

    public function boot(): void
    {
        if (! $this->enabled) {
            return;
        }

        if (! class_exists(\OpenTelemetry\API\Globals::class)) {
            Log::debug('Telemetry disabled because OpenTelemetry SDK is not installed.');
            $this->enabled = false;
            return;
        }

        $globals = \OpenTelemetry\API\Globals::class;

        try {
            $provider = $globals::tracerProvider();
            if ($provider) {
                $this->tracer = $provider->getTracer('qrpay-http', '1.0.0');
            }
        } catch (Throwable $exception) {
            Log::warning('Failed to bootstrap OpenTelemetry tracer provider', ['exception' => $exception->getMessage()]);
            $this->tracer = null;
        }

        try {
            $meterProvider = $globals::meterProvider();
            if ($meterProvider) {
                $this->meter = $meterProvider->getMeter('qrpay-http', '1.0.0');
                if ($this->meter && method_exists($this->meter, 'createCounter')) {
                    $this->requestCounter = $this->meter->createCounter('http.server.requests');
                }
                if ($this->meter && method_exists($this->meter, 'createHistogram')) {
                    $this->requestDuration = $this->meter->createHistogram('http.server.duration');
                }
            }
        } catch (Throwable $exception) {
            Log::warning('Failed to bootstrap OpenTelemetry meter provider', ['exception' => $exception->getMessage()]);
            $this->meter = null;
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function traceRequest(Request $request, Closure $next)
    {
        if (! $this->enabled || ! $this->tracer) {
            return $next($request);
        }

        $builder = $this->tracer->spanBuilder($request->method() . ' ' . $request->path());
        if (class_exists(\OpenTelemetry\API\Trace\SpanKind::class) && method_exists($builder, 'setSpanKind')) {
            $builder = $builder->setSpanKind(\OpenTelemetry\API\Trace\SpanKind::KIND_SERVER);
        }
        $span = $builder->startSpan();
        $scope = $span->activate();
        $start = microtime(true);

        try {
            $response = $next($request);
            $span->setAttribute('http.method', $request->method());
            $span->setAttribute('http.route', $request->route()?->uri() ?? 'n/a');
            $span->setAttribute('http.status_code', $response->getStatusCode());
            if ($response->getStatusCode() >= 500) {
                $span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR);
            }
            return $response;
        } catch (Throwable $exception) {
            $span->recordException($exception);
            $span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR, $exception->getMessage());
            throw $exception;
        } finally {
            $durationMs = (microtime(true) - $start) * 1000;
            if ($this->requestCounter) {
                try {
                    $this->requestCounter->add(1, ['http.method' => $request->method()]);
                } catch (Throwable $exception) {
                    Log::debug('Failed to emit telemetry counter', ['exception' => $exception->getMessage()]);
                }
            }
            if ($this->requestDuration) {
                try {
                    $this->requestDuration->record($durationMs, ['http.route' => $request->route()?->uri() ?? 'n/a']);
                } catch (Throwable $exception) {
                    Log::debug('Failed to emit telemetry histogram', ['exception' => $exception->getMessage()]);
                }
            }
            $span->end();
            if ($scope && method_exists($scope, 'detach')) {
                $scope->detach();
            }
        }
    }
}
