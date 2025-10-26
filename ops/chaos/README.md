# Chaos Testing Playbooks

Periodic chaos testing validates that the QRPay payment pipeline survives real-world failures.  These manifests leverage [LitmusChaos](https://litmuschaos.io/) experiments that run on a weekly cadence via `CronWorkflow` to inject faults into critical components.

## Components

| File | Description |
| --- | --- |
| `namespace.yaml` | Creates the dedicated `chaos` namespace. |
| `chaosengine-payment.yaml` | Defines a LitmusChaos `ChaosEngine` that targets the payment deployment and coordinates probes. |
| `cronworkflow.yaml` | Schedules controlled experiments that alternate between pod-kill and network-latency scenarios. |

Before applying the manifests, install the LitmusChaos operator (`helm repo add litmuschaos ...`) and make sure the QRPay services expose readiness probes so that automated verification can run post-experiment.

Run `kubectl apply -k ops/chaos` to provision the recurring experiments.

