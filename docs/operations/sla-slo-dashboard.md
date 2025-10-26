# SLA/SLO Dashboard and Alerting Strategy

This guide covers the target service level objectives and monitoring dashboards for QRPay integrations, along with PagerDuty and Slack alert routing.

## Services Covered

* Alipay integration
* BluBank integration
* Yoomonea integration
* Core payment APIs (`qrpay-web`, settlement, reconciliation)
* Supporting services (authentication, notifications)

## Key Metrics

| SLI Category | Metric Source | Target SLO | Notes |
| --- | --- | --- | --- |
| Availability | `http_server_requests_seconds_count` (Prometheus) filtered by `status` < 500 | 99.9% monthly | Computed per service. |
| Latency | `http_server_requests_seconds_bucket` p95 | `< 400ms` (Alipay), `< 500ms` (BluBank), `< 600ms` (Yoomonea) | Configure multi-window burn-rate alerts. |
| Error Rate | OTLP traces span status `ERROR` / total spans | `< 0.1%` | Derived from Jaeger/Tempo or Prometheus metrics via collector. |
| Throughput | `payment_success_total` | Monitor trend; no SLO target but used for anomaly detection. |
| Dependency Health | Synthetic checks metrics `synthetic_check_up` | 99.5% | Run via Blackbox exporter. |

## Grafana Dashboard Layout

Create a Grafana dashboard `QRPay - SLA/SLO` with the following rows:

1. **Overview**
   * SLO summary panels per service (availability, latency, error rate).
   * Burn-down charts using `prometheus` queries:
     ```promql
     slo:error_budget_burn_rate:ratio_rate5m{service="alipay"}
     ```
2. **Service Drill-down** (repeat panel for each integration)
   * Request rate and saturation (CPU, memory, worker queue depth).
   * Upstream dependency SLIs (bank endpoints, third-party APIs).
3. **User Impact**
   * Conversion funnel metrics from product analytics to correlate outages.
4. **Alerts**
   * Table showing current open alerts from Alertmanager via JSON API data source.

## Prometheus Recording & Alerting Rules

Define recording rules per service (example for Alipay):

```yaml
groups:
  - name: slo-alipay
    interval: 30s
    rules:
      - record: slo:availability:ratio_rate5m{service="alipay"}
        expr: sum(rate(http_server_requests_seconds_count{service="alipay",status!~"5.."}[5m]))
          /
          sum(rate(http_server_requests_seconds_count{service="alipay"}[5m]))
      - record: slo:latency:p95:5m{service="alipay"}
        expr: histogram_quantile(0.95, sum(rate(http_server_requests_seconds_bucket{service="alipay"}[5m])) by (le))
```

Alert rules:

```yaml
  - alert: AlipayAvailabilityBurnRateHigh
    expr: slo:error_budget_burn_rate:ratio_rate5m{service="alipay"} > 2
      and slo:error_budget_burn_rate:ratio_rate1h{service="alipay"} > 1
    for: 5m
    labels:
      severity: critical
      service: alipay
    annotations:
      summary: "Alipay availability burn rate is above threshold"
      description: "Check Jaeger for error spans and upstream dependency status."
```

Repeat for BluBank and Yoomonea with service-specific thresholds.

## PagerDuty Integration

1. Create PagerDuty services for each integration and map to `service` label.
2. Configure Alertmanager `pagerduty_configs` with routing keys.
3. Use `severity` label to determine urgency levels:
   * `critical` -> high-urgency, PagerDuty notification.
   * `warning` -> Slack-only notifications.

## Slack Notifications

* Use `slack_configs` in Alertmanager to post to `#qrpay-ops-alerts`.
* Template message:
  ```yaml
  text: |
    :rotating_light: *{{ .CommonLabels.alertname }}* ({{ .CommonLabels.service }})
    Severity: {{ .CommonLabels.severity }}
    Description: {{ .CommonAnnotations.description }}
    Runbook: {{ .CommonAnnotations.runbook_url }}
  ```
* Provide `runbook_url` annotation linking to the incident response runbook.

## Operational Process

* Review SLO reports weekly in the Reliability sync.
* Update thresholds quarterly or after major architecture changes.
* Ensure every new service defines SLIs before production launch.

## Data Quality

* Validate metrics availability across environments via synthetic alerts.
* Implement data freshness alert if Prometheus scrape gap > 5m.

## Access Control

* Grafana: assign `Ops` team edit permissions, `Engineering` view permissions.
* PagerDuty: maintain on-call schedules for `Payments`, `Banking`, and `Platform` rotations.
