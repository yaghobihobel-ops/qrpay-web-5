# Analytics and Risk Platform Enhancements

This document outlines the new analytics, recommendation, and risk modelling capabilities introduced to the platform.

## Event Streaming ETL

* **Service**: `App\Services\Analytics\EventPipeline`
* **Queue Job**: `App\Jobs\ProcessAnalyticsEvent`
* **Console Command**: `php artisan analytics:dispatch-event checkout.completed --payload='{"transaction_id":123}'`
* **Configuration**: `config/analytics.php`

Events are enriched with ingestion metadata and sent to BigQuery or ClickHouse using the configured connection. When a datastore is unavailable the events are automatically appended to `storage/app/analytics-buffer.ndjson`. Run the `flushBuffer` method (for example through a scheduled command) to retry delivery once connectivity is restored.

### Environment Variables

```ini
ANALYTICS_CONNECTION=bigquery
ANALYTICS_BIGQUERY_ENABLED=true
ANALYTICS_BIGQUERY_ENDPOINT=https://bigquery.googleapis.com/bigquery/v2
ANALYTICS_BIGQUERY_PROJECT=your-project
ANALYTICS_BIGQUERY_DATASET=payments
ANALYTICS_BIGQUERY_TABLE=events
ANALYTICS_BIGQUERY_SERVICE_ACCOUNT=/path/to/service-account.json
ANALYTICS_CLICKHOUSE_ENABLED=false
ANALYTICS_CLICKHOUSE_HOST=clickhouse.internal
ANALYTICS_CLICKHOUSE_DATABASE=analytics
ANALYTICS_CLICKHOUSE_TABLE=events
```

## Realtime KPI Dashboard

* **View**: `resources/views/analytics/dashboard.blade.php`
* **Controller**: `App\Http\Controllers\Analytics\DashboardController`
* **API Endpoint**: `GET /api/analytics/kpis`

The KPI service reads aggregated metrics from `transactions`, `provider_latency_metrics`, and `transaction_errors` tables, caches the results for one minute, and surfaces them via REST or the new authenticated dashboard. Embed Metabase or Grafana charts by setting `METABASE_DASHBOARD_URL` and `GRAFANA_DASHBOARD_URL`.

## Payment Route Recommendation Engine

* **Service**: `App\Services\Payments\Recommendation\PaymentRouteRecommender`
* **API Endpoint**: `POST /api/payments/routes/recommend`

Submit an array of candidate routes (including `fee`, `expected_settlement_minutes`, and `reliability`) and optional weighting preferences to receive the highest-scoring path for a user payment or settlement.

## Operational Risk Models

* **Fraud predictor**: `App\Services\Risk\FraudPredictor`
* **FX volatility predictor**: `App\Services\Risk\FxVolatilityPredictor`
* **Decision engine**: `App\Services\Risk\OperationalDecisionEngine`
* **API Endpoint**: `POST /api/risk/decision`

Model weights are stored under `storage/app/models` and can be retrained with `scripts/train_models.py`. Predictions feed into operational decisions to approve, review, or decline payments depending on fraud probability and currency volatility.
