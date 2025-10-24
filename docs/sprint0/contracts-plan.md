# Sprint 0 – Provider Contract Blueprint

## Namespace & File Layout
- Place shared contracts in `app/Contracts/Payments`, `app/Contracts/Wallet`, `app/Contracts/Compliance`, and `app/Contracts/FX` to keep separation by capability while staying within Laravel's PSR-4 autoloading already defined in `composer.json` (`"App\\": "app/"`).【F:composer.json†L23-L40】
- Register bindings via dedicated service providers (`app/Providers/PaymentProviderService.php`, etc.) and load them in `config/app.php` under the `providers` array so we avoid touching core framework providers.【F:config/app.php†L147-L220】

## Interface Sketches
- `PaymentProviderInterface`: `init()`, `authorize()`, `capture()`, `refund()`, `getStatus()`, `webhookVerify()`, `payout()` for settlements to merchant wallets.
- `TopUpProviderInterface`: `createInvoice()`, `confirm()`, `cancel()`, `quoteFees()` to standardize airtime/bill pay providers.
- `KYCProviderInterface`: `start(sessionContext)`, `submitDocs(payload)`, `status(reference)`, `riskScore(reference)`.
- `FXProviderInterface`: `quote(pair, amount, side)`, `convert(quoteId)`, `settlementReport(range)` to decouple rate sourcing from controllers.
- `CardIssuerInterface`: `issueVirtual(profile)`, `issuePhysical(profile)`, `activate(cardId)`, `block(cardId, reason)`, `updateLimits(cardId, payload)`.
- `CryptoBridgeInterface`: `onRamp(order)`, `offRamp(order)`, `proofs(orderId)`, `chainTxStatus(txId)` to track blockchain settlements.

## Binding Strategy
- Use config-driven aliases: e.g., `config/payment_providers.php` mapping country + capability to provider classes. Admin panel can update DB table (`country_providers`) that resolves to config cache.
- Service providers should read `config('qrpay.providers')` (new file) which merges DB overrides with `.env` defaults, keeping the core config arrays immutable.
- Include health-check methods (e.g., `ping()`) inside each provider to support the upcoming routing dashboard without forcing controller changes.

## Testing Considerations
- Provide stub implementations under `app/Providers/Mocks` that implement each interface with fixed responses. These will back the initial country modules (IR/CN/TR) until real APIs are integrated.
- Contract tests can live in `tests/Contracts/*`, asserting every provider returns the expected DTO shape even when using mocks. This ensures Flutter/mobile clients keep a consistent API surface after we swap providers.

## Interim Decision
- Because we cannot run Artisan to auto-discover providers yet, initial registration will be manual edits to `config/app.php`. Once the PHP version issue is resolved, convert bindings to auto-discovery packages (per-country) to reduce core diffs.
