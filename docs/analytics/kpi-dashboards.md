# KPI Dashboards Playbook

This playbook documents the curated analytics assets that surface the KPIs requested by the product, finance and operations teams.

## Data source

All dashboards read from the canonical `finance.transactions_stream` dataset that is hydrated by the event pipeline. The dataset is available in both ClickHouse (`qrpay.transactions_stream` table) and BigQuery (`finance.transactions_stream` table) with an identical schema.

Key columns:

| Column | Type | Description |
| --- | --- | --- |
| `event_id` | STRING | Unique identifier of the emitted event |
| `event_type` | STRING | Event type (e.g. `transactions.payment.completed`) |
| `occurred_at` | TIMESTAMP | When the transaction occurred |
| `context.customer_id` | STRING | Identifier of the end user/agent/merchant |
| `payload.transaction.amount` | FLOAT | Transaction amount in the original currency |
| `payload.transaction.currency.code` | STRING | Currency code |
| `payload.transaction.status` | INTEGER | Payment gateway status code |
| `payload.transaction.attribute` | STRING | Directional attribute (SEND / RECEIVED) |

## Grafana dashboards

Three Grafana dashboards have been templated under `docs/analytics/grafana/`:

1. **Product Growth Overview** (`product_team_dashboard.json`)
   - Conversion funnel from payment initiation to settlement.
   - Exchange utilisation split by corridor and loyalty segment.
   - Withdraw completion times and drop-off analysis.

2. **Finance Health Monitor** (`finance_team_dashboard.json`)
   - Net revenue and fee take rate.
   - Liquidity exposure by currency and outstanding withdrawal queue.
   - Daily reconciliation variance and dispute ageing.

3. **Operations Command Center** (`operations_team_dashboard.json`)
   - Real-time success rate and alert panels fed by the event stream.
   - SLA breach tracker for withdrawals.
   - Agent/merchant level monitoring for loyalty cohorts.

Each dashboard ships with Grafana templating variables for `environment`, `currency`, `loyalty_segment` and `country`. Import the JSON files into Grafana (`Dashboards → Import`) and set the data source to either the BigQuery or ClickHouse connection.

## Metabase collections

The `docs/analytics/metabase/` folder contains equivalent models for teams that prefer Metabase. The dashboards are split into three collections (`Product`, `Finance`, `Operations`) with SQL questions that reference the shared dataset.

### Deployment steps

1. Configure the warehouse target via environment variables:
   ```bash
   export DATA_WAREHOUSE_DRIVER=clickhouse
   export CLICKHOUSE_URL=https://clickhouse.internal:8443
   export CLICKHOUSE_TABLE=finance.transactions_stream
   ```
2. Run the event pipeline worker:
   ```bash
   npm run event-pipeline
   ```
3. Import the desired dashboard JSON into Grafana or Metabase.
4. (Optional) Schedule a refresh cadence aligned with business reporting (e.g. hourly for operations, daily for finance).

### KPI catalogue

| Team | KPI | Query location |
| --- | --- | --- |
| Product | Payment conversion rate, loyalty uplift | `docs/analytics/grafana/product_team_dashboard.json` Panel IDs 1-3 |
| Finance | Net revenue, FX exposure | `docs/analytics/grafana/finance_team_dashboard.json` Panels 4-7 |
| Operations | SLA breaches, success rate | `docs/analytics/grafana/operations_team_dashboard.json` Panels 2-6 |

The dashboards intentionally rely on the same metric definitions to maintain a single source of truth across teams.
