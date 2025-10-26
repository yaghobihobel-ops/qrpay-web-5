<?php

namespace App\Services\Security;

use App\Models\DeviceFingerprint;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SessionBindingService
{
    public function __construct(protected DeviceFingerprintService $fingerprints)
    {
    }

    public function shouldEnforce(?Authenticatable $user): bool
    {
        if (!$user) {
            return false;
        }

        $sensitiveRoles = config('security.sensitive_user_roles', []);
        $role = Arr::get($user, 'role');
        $isSensitive = (bool) Arr::get($user, 'is_sensitive', false);

        return $isSensitive || ($role && in_array($role, $sensitiveRoles, true));
    }

    public function bind(Request $request, Authenticatable $user, ?DeviceFingerprint $fingerprint = null): void
    {
        if (!$request->hasSession() || !$this->shouldEnforce($user)) {
            return;
        }

        $session = $request->session();

        $session->put('sensitive.user_id', $user->getAuthIdentifier());
        $session->put('sensitive.bound_ip', $request->ip());
        $session->put('sensitive.bound_user_agent', (string) $request->userAgent());
        $session->put('sensitive.bound_at', now()->toIso8601String());

        if ($fingerprint) {
            $session->put('sensitive.bound_fingerprint', $fingerprint->fingerprint);
            $session->put('sensitive.fingerprint_trusted', (bool) $fingerprint->is_trusted);
        } else {
            [$hash] = $this->fingerprints->fingerprintFromRequest($request);
            $session->put('sensitive.bound_fingerprint', $hash);
        }
    }

    public function clear(Request $request): void
    {
        if (!$request->hasSession()) {
            return;
        }

        $request->session()->forget([
            'sensitive.user_id',
            'sensitive.bound_ip',
            'sensitive.bound_user_agent',
            'sensitive.bound_fingerprint',
            'sensitive.fingerprint_trusted',
            'sensitive.bound_at',
        ]);
    }
}
