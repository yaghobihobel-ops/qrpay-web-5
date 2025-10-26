# QRPay Platform Operations Toolkit

This directory contains Kubernetes and automation assets that implement observability, auto-healing, chaos engineering, and automated postmortem generation for the QRPay microservices platform.

## Structure

- `observability/` – OpenTelemetry collector deployment, instrumentation configuration, and Prometheus ServiceMonitor.
- `self-healing/` – Prometheus rules and Argo Workflows that restart failing services and shift traffic across regions.
- `chaos/` – LitmusChaos experiments and a weekly Argo CronWorkflow for resilience testing.
- `postmortem/` – Workflow and configuration used to assemble automated incident reports.

Each subdirectory provides a `README.md` with environment-specific guidance and can be deployed via `kubectl apply -k ops/<module>`.

