
Immediate Older Version: 4.9.0
Current Version: 5.0.0

1. Google reCAPTCHA integration added.
2. Resolved Mobile top-up issues.
3. Added the Hindi language.
4. Updated Google 2FA For User & Merchant App.
5. Added PayStack Webhook URL.
6. Added Live Exchange Currency Rate API(Currency Layer).

Please Use This Commands On Your Terminal To Update Full System
1. To Run project Please Run This Command On Your Terminal
    composer update && composer dumpautoload && php artisan migrate:fresh --seed && php artisan passport:install --force

## Local environment requirements

To install the PHP dependencies successfully you now need a PHP runtime that satisfies the packages bundled with Laravel Passport:

- **PHP 8.1 or newer** (8.1, 8.2, 8.3, or 8.4 all work)
- Composer 2.x

If you are using Laragon or another local stack make sure PHP 8.1+ is selected before running Composer. With an older PHP binary Composer will refuse to install `lcobucci/clock`, which is required by Passport.

Once PHP 8.1+ is active you can bootstrap the project with:

```bash
composer install
php artisan key:generate
```

You can then run the existing update command (`composer update && ...`) whenever you need to refresh the installation after syncing new changes.
### FX

- Official foreign exchange providers for Iran (NIMA), China (PBOC), Turkey (TCMB) and Russia (CBR) can be configured from `config/exchange.php`. Each provider is queried in the order defined by `fallback_order`, so outages automatically fail over to the next service.
- Refresh rates with `php artisan exchange:update`. Pass `--symbols=USD,EUR` to limit the currencies fetched or `--sync` to process without queue workers.
- Updated rates are cached (see the `exchange.cache_store` option) and persisted for payments/withdrawals through the `SyncExchangeRates` job.
- Run the FX integration tests with `php artisan test --group=exchange` or `php artisan test --filter=ExchangeRateIntegrationTest` to validate fallback behaviour and persistence.
## Branch synchronization

All recent pull request changes have been merged into the `main` branch so it now mirrors the reviewed updates from the feature workflows. This ensures the default branch contains the latest application improvements without requiring additional manual steps.

## Event streaming & analytics

The application now emits structured JSON events for payments, currency exchanges and withdrawals. Configure the broker by setting the `EVENT_STREAM_*` variables in `.env`. By default the events are written to `storage/app/event-stream/` for local inspection.

Run the processing worker (Laravel Octane alternative) with Node.js to hydrate the warehouse:

```bash
npm install
npm run event-pipeline
```

Warehouse targets are configurable through `DATA_WAREHOUSE_DRIVER` (`filesystem`, `bigquery`, `clickhouse`). Dashboard templates for Grafana and Metabase are located under `docs/analytics/`.

## Airwallex operational workflow

Operational access to Airwallex has been moved out of public HTTP routes. Configure your credentials in `.env` using the `AIRWALLEX_*` variables and run the secured artisan command instead of calling `/token`, `/get-holder`, or `/create-holder` directly.

```bash
php artisan airwallex token
php artisan airwallex cardholders --filters='{"cardholder_status":"READY"}'
php artisan airwallex create-cardholder --payload=/path/to/cardholder.json
```

The legacy routes remain available only for authenticated users in local or testing environments and require POST data when creating a cardholder. Production environments must rely on the artisan command or queued jobs to interact with Airwallex.
