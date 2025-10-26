# Self-Healing Automation

This package defines automated remediation flows that guard the QRPay production cluster.  They rely on Prometheus alerting, Alertmanager webhooks, and Argo Workflows to execute targeted recovery actions without manual intervention.

## Components

| File | Description |
| --- | --- |
| `namespace.yaml` | Dedicated `resilience` namespace for automation components. |
| `prometheus-rules.yaml` | Alerting rules that flag error-rate surges in the payment service and datacenter availability loss. |
| `alertmanager-config.yaml` | Routes alerts to the `argo-events` webhook endpoint used by the remediation workflows. |
| `argo-workflows.yaml` | Workflow templates for restarting the payment service and shifting ingress traffic between data centers. |
| `virtualservice-failover.yaml` | Istio configuration that defines failover priorities per data center. |

Deploy these resources with `kubectl apply -k ops/self-healing`.  The manifests assume that Argo Workflows & Argo Events are already installed in the cluster with a `workflow` EventBus listening for Alertmanager webhooks.

## Alert-to-action flow

1. Prometheus evaluates the error-rate rules.
2. Alertmanager fires the `PaymentHighErrorRate` or `PrimaryRegionOutage` alerts and forwards them to Argo Events.
3. Argo Events sensors launch the respective Argo Workflow template.
4. The workflow container runs `kubectl` to restart the payment deployment or updates the Istio VirtualService weights, closing the loop automatically.

