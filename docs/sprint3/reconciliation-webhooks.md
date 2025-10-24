# Sprint 3 – Webhook Intake & Reconciliation Foundation

## Objectives
- Enforce provider specific HMAC validation for all inbound callbacks without touching the legacy controllers.
- Persist every webhook into a dedicated reconciliation ledger with idempotency guarantees.
- Provide aggregated settlement telemetry to support T+0/T+1 reporting.

## Key Components
- **`routes/api/webhooks.php`** exposes `/webhooks/{country}/{channel}/{provider}` for provider-agnostic intake.
- **`ProviderWebhookController`** resolves the configured adapter per country, validates HMAC signatures, derives idempotency keys, and writes reconciliation records.
- **`WebhookSignatureValidator`** centralises signature handling with provider overrides defined in `config/webhooks.php`.
- **`ReconciliationRecorder`** normalises headers/payloads and upserts `ReconciliationEvent` entries to guarantee idempotency.
- **`SettlementReportGenerator`** delivers aggregate counts for operations, surfacing signature validity to operations dashboards.

## Data Model
`database/migrations/2024_01_01_000000_create_reconciliation_events_table.php` introduces `reconciliation_events`:
- country, channel, provider identifiers
- provider reference, event type, status
- HMAC validation outcomes & metadata snapshots
- occurred/processed timestamps for SLA tracking

## Next Steps
- Build admin UI tables that consume the reconciliation ledger.
- Wire provider specific transformers to convert payloads into canonical transaction updates.
- Extend alerting around `signature_valid = false` and delayed settlement windows.
