<?php

namespace App\Services\Security;

use App\Models\DeviceFingerprint;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class DeviceFingerprintService
{
    public function register(Request $request, $authenticatable): DeviceFingerprint
    {
        [$fingerprint, $metadata] = $this->fingerprintFromRequest($request);

        $record = DeviceFingerprint::firstOrNew([
            'fingerprint' => $fingerprint,
            'authenticatable_type' => $authenticatable::class,
            'authenticatable_id' => $authenticatable->getKey(),
        ]);

        $record->fill([
            'device_name' => $metadata['device_name'] ?? null,
            'metadata' => $metadata,
            'last_seen_at' => now(),
        ]);

        if (! $record->exists) {
            $record->is_trusted = false;
        }

        $record->save();

        $this->enforceTrustedLimit($authenticatable);

        return $record;
    }

    public function trustCurrent(Request $request, $authenticatable): ?DeviceFingerprint
    {
        [$fingerprint] = $this->fingerprintFromRequest($request);

        $record = DeviceFingerprint::query()
            ->where('fingerprint', $fingerprint)
            ->where('authenticatable_type', $authenticatable::class)
            ->where('authenticatable_id', $authenticatable->getKey())
            ->first();

        if (! $record) {
            return null;
        }

        if (! $record->is_trusted) {
            $record->is_trusted = true;
            $record->save();
        }

        return $record;
    }

    public function fingerprintFromRequest(Request $request): array
    {
        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());

        $metadata = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'accept_language' => $request->header('Accept-Language'),
            'device_name' => trim(($agent->device() ?: '') . ' ' . ($agent->platform() ?: '')),
        ];

        $raw = implode('|', array_filter([
            $metadata['ip'],
            $metadata['user_agent'],
            $metadata['accept_language'],
            $metadata['device_name'],
            config('security.device_fingerprinting.salt'),
        ]));

        return [hash('sha256', $raw), $metadata];
    }

    protected function enforceTrustedLimit($authenticatable): void
    {
        $max = (int) config('security.device_fingerprinting.max_trusted_devices', 5);
        if ($max <= 0) {
            return;
        }

        $query = DeviceFingerprint::query()
            ->where('authenticatable_type', $authenticatable::class)
            ->where('authenticatable_id', $authenticatable->getKey())
            ->where('is_trusted', true)
            ->orderByDesc('last_seen_at');

        $idsToKeep = $query->limit($max)->pluck('id');

        DeviceFingerprint::query()
            ->where('authenticatable_type', $authenticatable::class)
            ->where('authenticatable_id', $authenticatable->getKey())
            ->where('is_trusted', true)
            ->whereNotIn('id', $idsToKeep)
            ->update(['is_trusted' => false]);
    }
}
