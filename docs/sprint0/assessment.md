# Sprint 0 – Repository Assessment

## Runtime & Framework Baseline
- **Laravel**: `laravel/framework` locked at `^9.19` in `composer.json`, aligning with Laravel 9.x expectations.
- **PHP requirement**: Project declares `^8.0.2`, but `composer install` currently fails because the container ships PHP 8.4.12 while several dependencies (e.g., `lcobucci/clock` 2.3.0 via Passport) only support PHP 8.1/8.2.
- **Node toolchain**: Vite 3.x build with Bootstrap 5/Sass per `package.json`. No frontend tests defined.

## Application Structure (Laravel)
- `app/` contains domain logic with feature-specific folders such as `Http/Controllers/{Admin,Agent,Merchant,PaymentGateway,User,Api}` along with `Jobs`, `Notifications`, and helper traits.
- `routes/` is segmented by actor/channel (`admin.php`, `agent.php`, `merchant.php`, `user.php`, etc.), plus nested API route groups under `routes/api/` for mobile/REST surfaces.
- `config/` includes numerous provider-specific configs (e.g., payment gateways, exchange rates) that will influence future abstraction layers.
- `resources/` holds Blade views and installer assets; localization files live under `lang/` with multiple languages already present (e.g., `en`, `hi`).
- `tests/` directory includes base `TestCase.php` with `Feature`/`Unit` subdirectories, though no automated suite was executed due to dependency installation issues.

## Current Installation & Test Status
- `composer install` halts with platform conflicts (`lcobucci/clock` requires PHP `~8.1`/`~8.2`; environment is PHP 8.4.12). Until the runtime is downgraded or a compatibility layer (Composer `platform` config) is introduced, Laravel CLI (`php artisan`) and PHPUnit cannot bootstrap because `vendor/` is absent.
- No Node or Flutter commands were executed; Flutter sources are not present in this repository.

## Key Risks & Follow-up Actions
1. **Dependency Compatibility** – Need reproducible PHP 8.1/8.2 environment or Composer `config.platform.php` override before we can run artisan commands, migrations, or tests.
2. **Baseline Testing** – After resolving dependencies, execute `phpunit` and any existing feature tests to validate the untouched baseline.
3. **Documentation Alignment** – `README.md` references upgrade guidance from v4.9.0 to v5.0.0; confirm against CodeCanyon vendor docs while planning interface extraction in later tasks.

This document closes Sprint 0 Task 1 by capturing the repository's starting point and the blockers that must be cleared prior to introducing abstraction layers.
