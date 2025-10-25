# Observability Stack (OpenTelemetry + Jaeger + Prometheus)

This document describes how to enable end-to-end observability for QRPay services using OpenTelemetry for instrumentation, Jaeger for tracing, and Prometheus for metrics collection.

## Architecture Overview

```
[Services] --(OTLP gRPC/HTTP)--> [OpenTelemetry Collector] --(gRPC)--> [Jaeger Collector] --> [Jaeger Query/UI]
                                           \
                                            \--(Prometheus Remote Write / Scrape)--> [Prometheus] --> [Alertmanager]
```

* **Services**: PHP (Laravel), Node.js, and Go components instrumented with OpenTelemetry SDKs/auto-instrumentation agents.
* **OpenTelemetry Collector**: Central ingestion point running as a deployment per environment (`otel-collector-{env}`) with pipelines for traces and metrics.
* **Jaeger**: Receives traces from the collector and provides UI/API for query and retention.
* **Prometheus**: Scrapes/receives metrics exposed by the collector and service endpoints.
* **Alertmanager**: Handles alert routing to PagerDuty and Slack.

## Instrumenting Services

### PHP/Laravel (Web & API)

1. Install the OpenTelemetry PHP auto-instrumentation package via Composer:
   ```bash
   composer require open-telemetry/opentelemetry-auto-laravel
   ```
2. Publish the configuration and enable auto-instrumentation in `config/opentelemetry.php`:
   ```php
   return [
       'enabled' => env('OTEL_ENABLED', true),
       'service_name' => env('OTEL_SERVICE_NAME', 'qrpay-web'),
       'exporter' => 'otlp',
       'exporters' => [
           'otlp' => [
               'protocol' => 'grpc',
               'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://otel-collector:4317'),
               'headers' => [],
           ],
       ],
   ];
   ```
3. Instrument HTTP clients, database calls, Redis, and queue jobs by enabling the relevant integrations in the package config.
4. Set environment variables in each environment (dev/staging/prod):
   ```env
   OTEL_ENABLED=true
   OTEL_SERVICE_NAME=qrpay-web
   OTEL_EXPORTER_OTLP_ENDPOINT=http://otel-collector:4317
   ```

### Node.js Services (e.g., Payment SDK)

1. Add dependencies:
   ```bash
   npm install --save @opentelemetry/sdk-node @opentelemetry/auto-instrumentations-node @opentelemetry/exporter-trace-otlp-grpc @opentelemetry/exporter-metrics-otlp-grpc
   ```
2. Create `telemetry.js` bootstrap file:
   ```js
   const { NodeSDK } = require('@opentelemetry/sdk-node');
   const { OTLPTraceExporter } = require('@opentelemetry/exporter-trace-otlp-grpc');
   const { OTLPMetricExporter } = require('@opentelemetry/exporter-metrics-otlp-grpc');
   const { Resource } = require('@opentelemetry/resources');
   const { SemanticResourceAttributes } = require('@opentelemetry/semantic-conventions');
   const { getNodeAutoInstrumentations } = require('@opentelemetry/auto-instrumentations-node');

   const sdk = new NodeSDK({
     resource: new Resource({
       [SemanticResourceAttributes.SERVICE_NAME]: process.env.OTEL_SERVICE_NAME || 'qrpay-sdk',
     }),
     traceExporter: new OTLPTraceExporter({
       url: process.env.OTEL_EXPORTER_OTLP_ENDPOINT || 'http://otel-collector:4317',
     }),
     metricExporter: new OTLPMetricExporter({
       url: process.env.OTEL_EXPORTER_OTLP_ENDPOINT || 'http://otel-collector:4317',
     }),
     instrumentations: [getNodeAutoInstrumentations()],
   });

   sdk.start();
   ```
3. Require the bootstrap at service startup: `require('./telemetry');`
4. Export `OTEL_SERVICE_NAME` per service (`alipay-adapter`, `blubank-gateway`, `yoomonea-service`).

### Go Services

1. Import the OTel SDK modules and configure the OTLP exporter in the main package.
2. Use instrumentation packages for HTTP handlers, gRPC clients/servers, and database drivers.
3. Set environment variables `OTEL_EXPORTER_OTLP_ENDPOINT`, `OTEL_SERVICE_NAME`, `OTEL_RESOURCE_ATTRIBUTES`.

## OpenTelemetry Collector Configuration

Create a base configuration shared via ConfigMap (`otel-collector-config.yaml`):

```yaml
receivers:
  otlp:
    protocols:
      grpc:
      http:
  prometheus:
    config:
      scrape_configs:
        - job_name: qrpay-services
          static_configs:
            - targets: ['qrpay-web:9464', 'alipay-service:9464', 'blubank-service:9464', 'yoomonea-service:9464']

processors:
  batch:
    send_batch_size: 8192
    timeout: 10s
  memory_limiter:
    check_interval: 1s
    limit_mib: 512
    spike_limit_mib: 128

exporters:
  otlp/jaeger:
    endpoint: jaeger-collector:14250
    tls:
      insecure: true
  prometheusremotewrite:
    endpoint: http://prometheus:9090/api/v1/write
  logging:
    loglevel: warn

service:
  telemetry:
    logs:
      level: info
  pipelines:
    traces:
      receivers: [otlp]
      processors: [memory_limiter, batch]
      exporters: [otlp/jaeger, logging]
    metrics:
      receivers: [otlp, prometheus]
      processors: [memory_limiter, batch]
      exporters: [prometheusremotewrite, logging]
```

Deploy the collector using Helm or Kubernetes manifests with environment-specific overrides (endpoints, credentials).

## Jaeger Deployment

* Use the official `jaegertracing/jaeger` Helm chart with the `production` template.
* Set retention to 7 days (staging) / 30 days (production).
* Configure storage backend (e.g., Elasticsearch or ClickHouse) based on existing infrastructure.
* Secure the UI with OAuth/SAML via reverse proxy.

## Prometheus & Alertmanager

* Deploy Prometheus using kube-prometheus-stack or existing monitoring cluster.
* Scrape `otel-collector` `/metrics` endpoint and service exporters.
* Configure Alertmanager receivers:
  ```yaml
  route:
    receiver: pagerduty
    routes:
      - matchers:
          - severity="warning"
        receiver: slack
  receivers:
    - name: pagerduty
      pagerduty_configs:
        - routing_key: ${PAGERDUTY_ROUTING_KEY}
    - name: slack
      slack_configs:
        - api_url: ${SLACK_WEBHOOK}
          channel: '#qrpay-ops-alerts'
  ```

## Logging Integration (Optional)

Use the collector or Fluent Bit to forward logs to Loki/ELK with trace context propagation via `trace_id` and `span_id` fields.

## Rollout Checklist

1. Roll out auto-instrumentation in non-prod; validate trace spans and metrics.
2. Deploy collector, Jaeger, Prometheus updates in staging. Confirm service discovery and sampling.
3. Define SLO dashboards and alert rules before production rollout.
4. Enable production instrumentation gradually with feature flags; monitor overhead.
5. Document onboarding for new services and update runbooks.

## Maintenance

* Rotate credentials quarterly.
* Perform capacity reviews monthly.
* Add new services to `static_configs` or service discovery.
* Keep SDK versions updated (quarterly cadence).
