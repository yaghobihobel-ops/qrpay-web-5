# Sprint 5 – Localisation & Experience Foundations

## Goals
- Centralise locale metadata so modules can request country-specific defaults without mutating Laravel core.
- Seed the platform with the languages required for IR, CN, TR, RU, and AF expansion.
- Expose locale catalog data to the admin and frontend layers for RTL awareness and future UX toggles.

## Key Decisions
- Introduced `config/localization.php` as the single source of truth for locale metadata (direction, formatting separators, native names).
- Added `LocaleManager` which draws from configuration and the country module registry to determine default and supported locales per country.
- Registered a dedicated `LocalizationServiceProvider` so locale metadata is available through the container and shared with Blade views.
- Normalised language middleware/session handling so `local` and `lang` session keys stay in sync and gracefully fall back to the configured default.
- Extended seeders to provision Persian, Chinese, Russian, Turkish, and Pashto language records while keeping English as the active default.

## Next Steps
- Surface locale selections inside the admin country configuration UI once the management screens are scaffolded.
- Update API responses for Flutter to advertise `supported_locales` per country so the mobile client can switch copy and layout dynamically.
- Layer number/date formatting helpers that leverage the metadata now exposed by `LocaleManager`.
