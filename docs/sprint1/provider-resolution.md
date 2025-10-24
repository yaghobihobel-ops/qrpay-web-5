# Country-aware provider resolution

## Overview

To keep the Laravel core untouched while enabling country-specific behaviour, we
introduced a dedicated `CountryProviderResolver`. The resolver coordinates three
layers of bindings when selecting an implementation for a provider contract:

1. **Country overrides** declared in `config/qrpay_providers.php['countries']` or
   future admin-managed records.
2. **Module defaults** returned by an enabled `CountryModuleInterface`
   implementation.
3. **Global fallbacks** defined in `config/qrpay_providers.php['bindings']`.

This ordering guarantees that production overrides win, while mock providers
packaged with a module remain available during development and testing.

## Usage

```php
use App\Contracts\Providers\PaymentProviderInterface;
use App\Support\Providers\CountryProviderResolver;

$resolver = app(CountryProviderResolver::class);
$provider = $resolver->resolve(PaymentProviderInterface::class, 'IR');
```

If the `IR` module is disabled or no override exists, the resolver falls back to
any globally bound implementation. Developers can also inspect the resolved
class without instantiating it via `classFor()`.

## Supporting admin configuration

`config/qrpay_providers.php` now contains a `countries` section. Admin forms can
write class references (or future stored bindings) to this structure, enabling
per-country activation without redeploying the core application. The resolver is
agnostic about the storage layer, so migrating from config files to database
settings later will not require touching the service container wiring.

## Introspection helpers

`CountryModuleRegistry::providerMapByCountry()` returns the default provider
classes exposed by every registered module (enabled or disabled). Admin APIs can
use this helper to populate dropdowns, compare overrides, or audit available
adapters per region.
