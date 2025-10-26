<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AuditLogger
{
    /**
     * Keys that should be masked when logging payload data.
     *
     * @var array<int, string>
     */
    protected array $sensitiveKeys = [
        'password',
        'secret',
        'token',
        'authorization',
        'card',
        'cvv',
        'pin',
        'otp',
    ];

    public function __construct(protected Request $request)
    {
    }

    /**
     * Persist an audit log entry.
     */
    public function log(string $action, array $context = []): AuditLog
    {
        [$userId, $userType] = $this->resolveUser($context['user'] ?? null);

        $payload = $this->prepareData($context['payload'] ?? []);
        $result = $this->prepareData($context['result'] ?? []);

        return AuditLog::create([
            'action' => $action,
            'user_id' => $userId,
            'user_type' => $userType,
            'ip_address' => $context['ip'] ?? $this->request->ip(),
            'payload' => $payload ?: null,
            'result' => $result ?: null,
            'status' => $context['status'] ?? null,
        ]);
    }

    /**
     * Resolve a user instance into the stored identifiers.
     */
    protected function resolveUser(mixed $user): array
    {
        if ($user instanceof Authenticatable) {
            return [$user->getAuthIdentifier(), $user::class];
        }

        if (is_array($user) && isset($user['id'], $user['type'])) {
            return [$user['id'], (string) $user['type']];
        }

        return [null, null];
    }

    /**
     * Prepare payload/result data for storage.
     */
    protected function prepareData(mixed $data): array
    {
        if (is_null($data)) {
            return [];
        }

        if (!is_array($data)) {
            $data = Arr::wrap($data);
        }

        return $this->maskSensitiveData($data);
    }

    /**
     * Recursively mask sensitive data in arrays.
     */
    protected function maskSensitiveData(array $data): array
    {
        $masked = [];

        foreach ($data as $key => $value) {
            if ($this->shouldMaskKey((string) $key)) {
                $masked[$key] = $this->maskValue($value);
                continue;
            }

            if (is_array($value)) {
                $masked[$key] = $this->maskSensitiveData($value);
                continue;
            }

            if (is_object($value)) {
                $masked[$key] = $this->maskSensitiveData((array) $value);
                continue;
            }

            $masked[$key] = $value;
        }

        return $masked;
    }

    /**
     * Determine if a given key should be masked.
     */
    protected function shouldMaskKey(string $key): bool
    {
        $normalized = Str::of($key)->lower();

        foreach ($this->sensitiveKeys as $sensitiveKey) {
            if ($normalized->contains($sensitiveKey)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mask the provided value.
     */
    protected function maskValue(mixed $value): string
    {
        return '***';
    }
}
