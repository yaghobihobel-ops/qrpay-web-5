<?php

namespace App\Http\Middleware\Api;

use App\Services\Security\KeyManagementService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EnsurePartnerSecurity
{
    public function __construct(private KeyManagementService $keys)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $header = config('partner_security.header', 'X-QRPay-Service');
        $serviceName = $request->headers->get($header);

        if (!$serviceName) {
            return $next($request);
        }

        $service = strtolower($serviceName);
        $config = config("partner_security.services.{$service}");

        if (!$config || !($config['enabled'] ?? true)) {
            return $next($request);
        }

        $this->assertMutualTls($request, $config, $service);
        $this->assertIpAllowlist($request, $config, $service);
        $this->assertSignature($request, $config, $service);

        return tap($next($request), function ($response) use ($service) {
            if (method_exists($response, 'headers')) {
                $response->headers->set('X-QRPay-Partner-Security', strtoupper($service));
            }
        });
    }

    protected function assertMutualTls(Request $request, array $config, string $service): void
    {
        $mtls = $config['mutual_tls'] ?? [];
        if (!($mtls['required'] ?? false)) {
            return;
        }

        $verified = $request->server('SSL_CLIENT_VERIFY') === 'SUCCESS'
            || $request->server('HTTP_X_SSL_CLIENT_VERIFY') === 'SUCCESS';

        if (!$verified) {
            $this->abort($service, 'Client TLS certificate verification failed.', 495);
        }

        $allowed = $mtls['allowed_subjects'] ?? [];
        if ($allowed) {
            $subject = $request->server('SSL_CLIENT_S_DN')
                ?? $request->server('HTTP_X_SSL_CLIENT_S_DN');

            if (!$subject || !$this->matchesSubject($subject, $allowed)) {
                $this->abort($service, 'Client TLS certificate subject rejected.', 496);
            }
        }
    }

    protected function assertIpAllowlist(Request $request, array $config, string $service): void
    {
        $allowlist = array_filter($config['ip_allowlist'] ?? []);
        if (!$allowlist) {
            return;
        }

        $ip = $request->ip();
        if (!IpUtils::checkIp($ip, $allowlist)) {
            $this->abort($service, 'Origin IP not permitted.', 497);
        }
    }

    protected function assertSignature(Request $request, array $config, string $service): void
    {
        $signing = $config['signing'] ?? [];
        if (!($signing['enabled'] ?? false)) {
            return;
        }

        $signatureHeader = $signing['header'] ?? 'X-Signature';
        $timestampHeader = $signing['timestamp_header'] ?? 'X-Timestamp';
        $signature = $request->headers->get($signatureHeader);
        $timestamp = $request->headers->get($timestampHeader);

        if (!$signature || !$timestamp) {
            $this->abort($service, 'Signed headers missing.', 401);
        }

        if (!ctype_digit((string) $timestamp)) {
            $this->abort($service, 'Invalid signature timestamp.', 401);
        }

        $leeway = (int) ($signing['leeway'] ?? 300);
        $now = now()->timestamp;
        if (abs($now - (int) $timestamp) > max($leeway, 30)) {
            $this->abort($service, 'Signature timestamp outside of tolerance window.', 401);
        }

        $field = $signing['secret_field'] ?? 'signing_secret';
        $secret = $this->keys->getSecret($service, $field);

        if (!is_string($secret) || $secret === '') {
            $this->abort($service, 'Signing secret unavailable.', 500);
        }

        $algorithm = $signing['algorithm'] ?? 'sha256';
        $payload = $timestamp . '.' . $request->getContent();
        $expected = base64_encode(hash_hmac($algorithm, $payload, $secret, true));

        if (!hash_equals($expected, (string) $signature)) {
            $this->abort($service, 'Request signature verification failed.', 401);
        }
    }

    protected function matchesSubject(string $subject, array $allowed): bool
    {
        foreach ($allowed as $pattern) {
            $pattern = trim($pattern);
            if ($pattern === '') {
                continue;
            }

            if (Str::startsWith($pattern, '/') && Str::endsWith($pattern, '/')) {
                if (@preg_match($pattern, $subject)) {
                    return true;
                }
            } elseif (Str::is($pattern, $subject)) {
                return true;
            } elseif (strcmp($pattern, $subject) === 0) {
                return true;
            }
        }

        return false;
    }

    protected function abort(string $service, string $message, int $status): void
    {
        Log::warning('Partner security enforcement triggered', [
            'service' => $service,
            'message' => $message,
            'status' => $status,
        ]);

        throw new HttpException($status, $message);
    }
}
