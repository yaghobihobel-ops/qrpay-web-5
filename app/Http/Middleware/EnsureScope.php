<?php

namespace App\Http\Middleware;

use App\Services\Auth\TokenService;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsureScope
{
    public function __construct(protected TokenService $tokenService)
    {
    }

    public function handle(Request $request, Closure $next, ...$requirements)
    {
        $normalized = $this->normalizeRequirements($requirements);
        $jwtPayload = $this->getJwtPayload($request);

        $user = $request->user();

        if (! $user && isset($jwtPayload['sub'])) {
            $user = $this->resolveUserFromPayload($jwtPayload);
            if ($user) {
                Auth::setUser($user);
            }
        }

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.', [], 'api');
        }

        $this->validateScopes($user, $normalized['scopes'] ?? [], $jwtPayload);
        $this->validateRoles($user, $normalized['roles'] ?? [], $jwtPayload);
        $this->validatePermissions($user, $normalized['permissions'] ?? [], $jwtPayload);

        return $next($request);
    }

    protected function validateScopes(Authenticatable $user, array $scopes, array $jwtPayload): void
    {
        if (empty($scopes)) {
            return;
        }

        foreach ($scopes as $scope) {
            if ($this->userTokenHasScope($user, $scope)) {
                continue;
            }

            if ($this->jwtScopeAllows($jwtPayload, $scope)) {
                continue;
            }

            throw new AccessDeniedHttpException('Insufficient scope.');
        }
    }

    protected function validateRoles(Authenticatable $user, array $roles, array $jwtPayload): void
    {
        if (empty($roles)) {
            return;
        }

        $hasRole = method_exists($user, 'hasAnyRole') && $user->hasAnyRole($roles);

        if (! $hasRole) {
            $jwtRoles = Arr::wrap($jwtPayload['roles'] ?? []);
            $hasRole = ! empty(array_intersect($roles, $jwtRoles));
        }

        if (! $hasRole) {
            throw new AccessDeniedHttpException('Missing required role.');
        }
    }

    protected function validatePermissions(Authenticatable $user, array $permissions, array $jwtPayload): void
    {
        if (empty($permissions)) {
            return;
        }

        $hasPermission = method_exists($user, 'hasAnyPermission') && $user->hasAnyPermission($permissions);

        if (! $hasPermission) {
            $jwtPermissions = Arr::wrap($jwtPayload['permissions'] ?? []);
            $hasPermission = ! empty(array_intersect($permissions, $jwtPermissions));
        }

        if (! $hasPermission) {
            throw new AccessDeniedHttpException('Missing required permission.');
        }
    }

    protected function userTokenHasScope(Authenticatable $user, string $scope): bool
    {
        if (! method_exists($user, 'tokenCan')) {
            return false;
        }

        if ($user->tokenCan('*')) {
            return true;
        }

        return $user->tokenCan($scope);
    }

    protected function jwtScopeAllows(array $payload, string $scope): bool
    {
        $scopes = Arr::wrap($payload['scopes'] ?? []);

        return in_array('*', $scopes, true) || in_array($scope, $scopes, true);
    }

    protected function getJwtPayload(Request $request): array
    {
        if ($request->attributes->has('jwt_payload')) {
            return $request->attributes->get('jwt_payload', []);
        }

        $token = $request->bearerToken();

        if (! $token || ! Str::contains($token, '.')) {
            $request->attributes->set('jwt_payload', []);

            return [];
        }

        try {
            $payload = $this->tokenService->parseJwtToken($token);
        } catch (InvalidArgumentException $exception) {
            throw new AuthenticationException($exception->getMessage(), [], 'api');
        }

        $request->attributes->set('jwt_payload', $payload);

        return $payload;
    }

    protected function resolveUserFromPayload(array $payload): ?Authenticatable
    {
        if (! isset($payload['sub'])) {
            return null;
        }

        $userModel = config('auth.providers.users.model');

        return $userModel::query()->find($payload['sub']);
    }

    protected function normalizeRequirements(array $requirements): array
    {
        $normalized = [
            'scopes' => [],
            'roles' => [],
            'permissions' => [],
        ];

        foreach ($requirements as $requirement) {
            if (! is_string($requirement) || $requirement === '') {
                continue;
            }

            $type = 'scopes';
            $value = $requirement;

            if (str_contains($requirement, ':')) {
                [$rawType, $rawValue] = explode(':', $requirement, 2);
                $type = $this->mapRequirementType($rawType);
                $value = $rawValue;
            }

            if (! isset($normalized[$type])) {
                continue;
            }

            $normalized[$type] = array_values(array_unique(array_merge(
                $normalized[$type],
                $this->splitRequirementValues($value)
            )));
        }

        return $normalized;
    }

    protected function mapRequirementType(string $type): string
    {
        return match (strtolower(trim($type))) {
            'role', 'roles' => 'roles',
            'permission', 'permissions' => 'permissions',
            'scope', 'scopes' => 'scopes',
            default => 'scopes',
        };
    }

    protected function splitRequirementValues(string $value): array
    {
        $segments = preg_split('/[|,]/', $value) ?: [];

        return array_values(array_filter(array_map('trim', $segments))); // remove empty values
    }
}
