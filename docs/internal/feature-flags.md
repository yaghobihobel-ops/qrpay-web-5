# Feature Flag Operations

This document describes how to manage feature flags for the payment services platform.

## Available Flags

| Feature | Env Variable | Description |
| --- | --- | --- |
| `currency_service` | `FEATURE_CURRENCY_SERVICE` | Controls access to the new multi-currency experience. |
| `withdrawal_service` | `FEATURE_WITHDRAWAL_SERVICE` | Enables the redesigned withdrawal orchestration flow. |
| `exchange_service` | `FEATURE_EXCHANGE_SERVICE` | Toggles the beta exchange module for early adopters. |

## Enabling a Feature

1. Open the environment configuration for the target deployment.
2. Set the corresponding `FEATURE_…` variable to `true`.
3. Run `php artisan config:cache` (or redeploy) so Laravel picks up the new value.

## Disabling a Feature

1. Set the `FEATURE_…` variable to `false`.
2. Clear the configuration cache: `php artisan config:clear`.
3. Redeploy or reload the application as needed.

## Runtime Checks

Inject the `App\Services\FeatureToggle` service into your class and call `isEnabled()` or `isDisabled()` to guard logic.

```php
use App\Services\FeatureToggle;

public function __construct(private FeatureToggle $features)
{
}

public function handle()
{
    if ($this->features->isDisabled(FeatureToggle::FEATURE_EXCHANGE_SERVICE)) {
        return response()->json(['message' => 'Exchange is not available'], 403);
    }

    // Feature-specific logic...
}
```

Use `$features->all()` to expose toggle state in diagnostics or observability endpoints.

## Canary & Rollback

When deploying through the CI/CD workflow, leverage the `deploy_canary` job for staged rollouts. If issues appear, trigger the `rollback` workflow with the release tag to revert quickly.
