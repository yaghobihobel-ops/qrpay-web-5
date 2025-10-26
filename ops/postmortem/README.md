# Automated Postmortem Reports

This module collects telemetry, alerts, and chaos experiment metadata to produce repeatable postmortem reports after every major incident.  The automation is built around a Kubernetes `Workflow` that calls the `generate_postmortem.py` helper script.

## Components

| File | Description |
| --- | --- |
| `workflow.yaml` | Argo Workflow that orchestrates data collection and invokes the postmortem generator script. |
| `datasources.yaml` | Declarative mapping of telemetry systems (Prometheus, Tempo, Loki) and incident metadata buckets. |
| `README.md` | Overview and usage instructions. |

Run the workflow with `kubectl create -f ops/postmortem/workflow.yaml -n resilience` after an incident to compile the Markdown report.

The script expects a JSON payload containing:

```json
{
  "incident_id": "INC-2024-05-23",
  "title": "Primary region outage",
  "start": "2024-05-23T02:14:00Z",
  "end": "2024-05-23T02:52:00Z",
  "impact_summary": "Checkout failures for 7% of transactions",
  "root_cause_hypothesis": "Ingress controller crashloop caused by config drift"
}
```

