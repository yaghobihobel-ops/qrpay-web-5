# Observability Stack

These manifests provision a full OpenTelemetry-based observability stack for the QRPay service cluster.  They provision a dedicated namespace, a collector that fans out data to Tempo/Loki/Prometheus, and runtime configuration that enables the Laravel services to emit metrics, traces, and logs.

## Components

| File | Description |
| --- | --- |
| `namespace.yaml` | Creates the `observability` namespace that isolates the shared monitoring components. |
| `otel-collector.yaml` | Deploys an `OpenTelemetryCollector` instance with pipelines for traces, metrics, and logs. |
| `service-monitor.yaml` | Exposes collector metrics to the in-cluster Prometheus instance. |
| `instrumentation-configmap.yaml` | Provides shared environment variables that auto-instrument the Laravel PHP services. |
| `sidecar-patch.yaml` | Patch snippet to inject the OpenTelemetry collector sidecar when manual sidecar injection is preferred over the operator Instrumentation CR. |

Apply the whole stack via `kubectl apply -k ops/observability` after ensuring the [OpenTelemetry Operator](https://github.com/open-telemetry/opentelemetry-operator) is installed in the cluster.

## Runtime expectations

* Application pods load the instrumentation environment variables either through the shared ConfigMap or via direct env injection in Helm values.
* Metrics are scraped by Prometheus (or a Prometheus-compatible backend) from the collector endpoint `:9464`.
* Traces and logs are forwarded by the collector to OTLP backends defined in the `otel-collector.yaml` manifest.
* Grafana dashboards can source both the Prometheus metrics and Tempo traces to correlate activity across services.

## Enabling application telemetry

The Laravel application will emit telemetry once these manifests are applied and the new `TelemetryServiceProvider` is registered (see `app/Providers/TelemetryServiceProvider.php`).  If additional services need instrumentation, simply mount the `otel-runtime-config` ConfigMap and add the container arguments shown in `sidecar-patch.yaml`.

