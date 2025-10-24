# Sprint 0 – Country Module Architecture Outline

## Package Layout
- Each country lives under `modules/Country/{ISO}` with its own `src/`, `database/migrations/`, and `config/` directories. Laravel's package discovery can autoload these via `modules/Country/{ISO}/composer.json` once we enable path repositories in the root `composer.json`.
- Admin UI assets (Blade, Vue, or Inertia) for a country ship under `modules/Country/{ISO}/resources/views` and are published to `resources/views/vendor/qrpay/{ISO}` so we avoid mutating the core admin panel.

## Configuration Contract
- `modules/Country/{ISO}/config/providers.php` maps capabilities (`payment`, `topup`, `fx`, `kyc`, `card`, `crypto`) to provider class names implementing the shared interfaces from Sprint 0 Contract Blueprint.【F:docs/sprint0/contracts-plan.md†L5-L32】
- A central registry (`config/qrpay.php`) lists available countries and toggles. Admin panel writes overrides into a `country_settings` table; the bootstrapping service merges DB overrides with module defaults at runtime.

## Database Extensions
- Country modules may ship additive migrations for metadata (fee tiers, local holidays, KYC templates). These migrations must write to namespaced tables (e.g., `country_ir_fee_profiles`) to avoid altering core schemas.
- Shared enums/constants remain untouched; modules provide translation tables to map local codes to core constants (e.g., transaction types defined in `PaymentGatewayConst`).【F:app/Constants/PaymentGatewayConst.php†L1-L120】

## Routing & Feature Flags
- Admin routes mount under `/admin/countries/{ISO}` via a service provider inside each module. Feature flags determine whether country-specific forms appear; if a module is disabled, routes/controllers remain unregistered.
- API routes expose provider health checks and sandbox toggles so QA can simulate rails A/B/C without leaving the admin UI.

## Deployment Strategy
- Treat modules as Composer packages managed in the same monorepo (path repository). This keeps vendor boundaries clear and allows future closed-source modules to be swapped in without editing the core.
- Introduce CI checks ensuring every module declares contracts and publishes config, views, and translations during `php artisan vendor:publish` once the PHP environment is aligned.

## Manual vs Deferred Work
- Environment blockers (PHP 8.4) can be resolved later; module scaffolding (directories, composer path entries) is safe to prepare now because it does not require vendor installation.
- Admin enable/disable UI can be mocked with static config until migrations and dynamic storage are ready, preventing mid-sprint blockers even if the final database toggles are implemented manually by ops.
