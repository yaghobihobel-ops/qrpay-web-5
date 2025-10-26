<?php

namespace App\Http\Middleware;

use App\Models\DeviceFingerprint;
use App\Services\Security\DeviceFingerprintService;
use App\Services\Security\SessionBindingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SensitiveSessionBinding
{
    public function __construct(
        protected SessionBindingService $bindingService,
        protected DeviceFingerprintService $fingerprints
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user() ?? Auth::user();

        if (!$user || !$request->hasSession() || !$this->bindingService->shouldEnforce($user)) {
            return $next($request);
        }

        $session = $request->session();

        if (!$session->has('sensitive.user_id')) {
            $this->bindingService->bind($request, $user);
            return $next($request);
        }

        $boundUserId = $session->get('sensitive.user_id');
        if ($boundUserId && (string) $boundUserId !== (string) $user->getAuthIdentifier()) {
            return $this->deny($request, $user, 'Session ownership mismatch detected.');
        }

        $currentIp = $request->ip();
        $boundIp = $session->get('sensitive.bound_ip');
        $strictIp = (bool) config('security.session.binding.strict_ip', true);
        $proxyRanges = (array) config('security.session.binding.allow_proxy_ranges', []);

        if ($boundIp && $strictIp) {
            $allowlist = array_values(array_filter(array_merge([$boundIp], $proxyRanges)));
            if (!IpUtils::checkIp($currentIp, $allowlist)) {
                return $this->deny($request, $user, 'Session bound to a different network.');
            }
        }

        $boundAgent = $session->get('sensitive.bound_user_agent');
        $currentAgent = (string) $request->userAgent();
        $tolerance = (int) config('security.session.binding.user_agent_tolerance', 8);

        if ($boundAgent) {
            $matches = $tolerance <= 0
                ? hash_equals($boundAgent, $currentAgent)
                : strncmp($boundAgent, $currentAgent, $tolerance) === 0;

            if (!$matches) {
                return $this->deny($request, $user, 'Browser fingerprint mismatch.');
            }
        }

        [$fingerprintHash] = $this->fingerprints->fingerprintFromRequest($request);
        $boundFingerprint = $session->get('sensitive.bound_fingerprint');

        if ($boundFingerprint && !hash_equals($boundFingerprint, $fingerprintHash)) {
            return $this->deny($request, $user, 'Device fingerprint mismatch.');
        }

        if (!$boundFingerprint) {
            $session->put('sensitive.bound_fingerprint', $fingerprintHash);
        }

        if (config('security.device_fingerprinting.require_trusted_for_sensitive', true)) {
            $record = DeviceFingerprint::query()
                ->where('fingerprint', $fingerprintHash)
                ->where('authenticatable_type', $user::class)
                ->where('authenticatable_id', $user->getAuthIdentifier())
                ->first();

            if (!$record || !$record->is_trusted) {
                return $this->deny($request, $user, 'Trusted device required for this account.', 423);
            }
        }

        return $next($request);
    }

    protected function deny(Request $request, $user, string $message, int $status = 403): mixed
    {
        Log::warning('Sensitive session binding violation', [
            'user_id' => $user->getAuthIdentifier(),
            'user_type' => $user::class,
            'ip' => $request->ip(),
            'message' => $message,
        ]);

        $this->logoutAcrossGuards($user);

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $this->bindingService->clear($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        throw new HttpException($status, $message);
    }

    protected function logoutAcrossGuards($user): void
    {
        foreach (array_keys(config('auth.guards', [])) as $guard) {
            $auth = Auth::guard($guard);
            $guardUser = $auth->user();

            if ($guardUser && $guardUser::class === $user::class && (string) $guardUser->getAuthIdentifier() === (string) $user->getAuthIdentifier()) {
                $auth->logout();
            }
        }
    }
}
