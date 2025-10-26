<?php

namespace App\Services\Auth;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use Laravel\Passport\RefreshTokenRepository;
use Laravel\Passport\Token;
use Laravel\Passport\TokenRepository;

class TokenService
{
    protected string $jwtSecret;

    protected int $defaultTtl;

    public function __construct(
        protected TokenRepository $tokenRepository,
        protected RefreshTokenRepository $refreshTokenRepository
    ) {
        $this->jwtSecret = $this->parseKey(config('app.key'));
        $this->defaultTtl = (int) config('auth.jwt_ttl', 3600);
    }

    public function issuePersonalAccessToken(Authenticatable $user, string $tokenName = 'API Token', array $abilities = ['*'], ?CarbonInterface $expiresAt = null): array
    {
        $tokenResult = $user->createToken($tokenName, $abilities);
        $token = $tokenResult->token;

        if ($expiresAt) {
            $token->expires_at = $expiresAt;
            $token->save();
        }

        return [
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'token_id' => $token->id,
            'expires_at' => optional($token->expires_at)->toDateTimeString(),
            'abilities' => $token->scopes,
        ];
    }

    public function revokePersonalAccessToken(Token|string|int $token): void
    {
        $tokenId = $token instanceof Token ? $token->id : $token;

        if (! $tokenId) {
            return;
        }

        $this->tokenRepository->revokeAccessToken((string) $tokenId);
        $this->refreshTokenRepository->revokeRefreshTokensByAccessTokenId((string) $tokenId);
    }

    public function refreshPersonalAccessToken(Authenticatable $user, Token|string|int|null $token, string $tokenName = 'API Token', array $abilities = ['*'], ?CarbonInterface $expiresAt = null): array
    {
        if ($token) {
            $this->revokePersonalAccessToken($token);
        }

        return $this->issuePersonalAccessToken($user, $tokenName, $abilities, $expiresAt);
    }

    public function issueJwtToken(Authenticatable $user, array $claims = [], ?int $ttlSeconds = null): array
    {
        $now = CarbonImmutable::now();
        $ttl = $ttlSeconds ?? $this->defaultTtl;
        $jti = (string) Str::uuid();

        $scopes = array_values(Arr::wrap($claims['scopes'] ?? ['*']));
        $roles = array_values(Arr::wrap($claims['roles'] ?? ($this->resolveRoles($user))));
        $permissions = array_values(Arr::wrap($claims['permissions'] ?? ($this->resolvePermissions($user))));
        $additionalClaims = Arr::except($claims, ['scopes', 'roles', 'permissions']);

        $payload = array_merge($additionalClaims, [
            'iss' => config('app.url'),
            'sub' => $user->getAuthIdentifier(),
            'iat' => $now->getTimestamp(),
            'nbf' => $now->getTimestamp(),
            'exp' => $now->addSeconds($ttl)->getTimestamp(),
            'jti' => $jti,
            'scopes' => $scopes,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);

        $token = $this->encodeJwt($payload);

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $payload['exp'],
            'jti' => $jti,
        ];
    }

    public function parseJwtToken(string $token, bool $checkExpiry = true, bool $checkRevocation = true): array
    {
        [$_header, $payload] = $this->decodeJwt($token);

        if ($checkExpiry && isset($payload['exp'])) {
            $expiry = CarbonImmutable::createFromTimestamp($payload['exp']);
            if ($expiry->isPast()) {
                throw new InvalidArgumentException('Token has expired.');
            }
        }

        if ($checkRevocation && isset($payload['jti']) && $this->isJwtTokenRevoked($payload['jti'])) {
            throw new InvalidArgumentException('Token has been revoked.');
        }

        return $payload;
    }

    public function revokeJwtToken(string $token): void
    {
        $payload = $this->parseJwtToken($token, false, false);

        if (! isset($payload['jti'])) {
            return;
        }

        $expiresAt = isset($payload['exp'])
            ? CarbonImmutable::createFromTimestamp($payload['exp'])
            : CarbonImmutable::now()->addSeconds($this->defaultTtl);

        Cache::put($this->getJwtCacheKey($payload['jti']), true, $expiresAt);
    }

    public function refreshJwtToken(string $token, ?int $ttlSeconds = null): array
    {
        $payload = $this->parseJwtToken($token, true, true);

        $userModel = config('auth.providers.users.model');
        $user = $userModel::query()->find($payload['sub']);

        if (! $user) {
            throw (new ModelNotFoundException())->setModel($userModel, [$payload['sub']]);
        }

        $this->revokeJwtToken($token);

        $claims = Arr::except($payload, ['iss', 'sub', 'iat', 'nbf', 'exp', 'jti']);
        $claims['scopes'] = $payload['scopes'] ?? ['*'];
        $claims['roles'] = $payload['roles'] ?? [];
        $claims['permissions'] = $payload['permissions'] ?? [];

        return $this->issueJwtToken($user, $claims, $ttlSeconds);
    }

    public function isJwtTokenRevoked(string $tokenOrJti): bool
    {
        $jti = Str::contains($tokenOrJti, '.')
            ? ($this->decodeJwt($tokenOrJti)[1]['jti'] ?? null)
            : $tokenOrJti;

        if (! $jti) {
            return false;
        }

        return Cache::has($this->getJwtCacheKey($jti));
    }

    protected function encodeJwt(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $segments = [
            $this->base64UrlEncode($this->jsonEncode($header)),
            $this->base64UrlEncode($this->jsonEncode($payload)),
        ];

        $signature = hash_hmac('sha256', implode('.', $segments), $this->jwtSecret, true);
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    protected function decodeJwt(string $token): array
    {
        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            throw new InvalidArgumentException('Token structure is invalid.');
        }

        [$header64, $payload64, $signature64] = $segments;

        $headerJson = $this->base64UrlDecode($header64);
        $payloadJson = $this->base64UrlDecode($payload64);
        $signature = $this->base64UrlDecode($signature64);

        $expected = hash_hmac('sha256', $header64 . '.' . $payload64, $this->jwtSecret, true);

        if (! hash_equals($expected, $signature)) {
            throw new InvalidArgumentException('Token signature is invalid.');
        }

        try {
            $header = json_decode($headerJson, true, 512, JSON_THROW_ON_ERROR);
            $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Token payload is invalid.', previous: $exception);
        }

        return [$header, $payload];
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;

        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        if ($decoded === false) {
            throw new InvalidArgumentException('Token segment is invalid base64.');
        }

        return $decoded;
    }

    protected function jsonEncode(array $data): string
    {
        try {
            return json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Unable to encode payload.', previous: $exception);
        }
    }

    protected function parseKey(?string $key): string
    {
        $key ??= '';

        if (Str::startsWith($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);

            if ($decoded === false) {
                throw new InvalidArgumentException('Invalid base64 application key.');
            }

            return $decoded;
        }

        return $key;
    }

    protected function getJwtCacheKey(string $jti): string
    {
        return 'auth:jwt:blacklist:' . $jti;
    }

    protected function resolveRoles(Authenticatable $user): array
    {
        return method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [];
    }

    protected function resolvePermissions(Authenticatable $user): array
    {
        return method_exists($user, 'getPermissionNames') ? $user->getPermissionNames() : [];
    }
}
