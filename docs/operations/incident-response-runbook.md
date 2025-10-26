# Incident Response Runbook

This runbook describes the standard operating procedure for responding to production incidents affecting QRPay services.

## Roles

| Role | Responsibilities |
| --- | --- |
| Incident Commander (IC) | Owns resolution plan, coordinates responders, communicates status. |
| Communications Lead | Posts updates to Slack `#status-qrpay` and customer channels. |
| Subject Matter Expert (SME) | Troubleshoots the technical components (Alipay, BluBank, Yoomonea, etc.). |
| Scribe | Documents timeline, actions, decisions in the incident ticket. |

Assign on-call rotations in PagerDuty:
* Week 1: Payments IC
* Week 2: Banking IC
* Week 3: Platform IC

## Incident Lifecycle

1. **Detection**
   * Alerts from Prometheus/Alertmanager -> PagerDuty.
   * Manual reports from Support channel -> create PagerDuty incident.
2. **Acknowledgement**
   * IC acknowledges alert within 5 minutes.
   * Start a dedicated Slack channel `#inc-<incident-number>` via PagerDuty integration.
3. **Triage**
   * Confirm scope (impacted services, geography, partner banks).
   * Validate SLO dashboards for anomalies.
   * Collect traces from Jaeger for high-latency/error spans.
4. **Mitigation**
   * Apply runbook steps per service (see Service Playbooks below).
   * Execute feature flag toggles, traffic reroute, or rollback as needed.
5. **Communication**
   * Post updates every 15 minutes in Slack and internal status page.
   * Update external status page if customer impact > 15 minutes.
6. **Resolution**
   * Confirm metrics and traces returned to normal.
   * Capture final status in PagerDuty and close the incident.
7. **Post-Incident**
   * Schedule postmortem within 48 hours.
   * Ensure knowledge base article is created/updated.

## Service Playbooks

### Alipay Integration

* Check `alipay` namespace in Grafana for latency spikes.
* Verify upstream endpoint health (synthetic checks) and authentication tokens.
* Temporary mitigation: route traffic to backup provider if available.

### BluBank Integration

* Inspect `blubank` queue backlog metrics and worker logs.
* Validate settlement batch status; restart workers if stuck.
* Coordinate with BluBank support via established escalation contacts.

### Yoomonea Service

* Review `yoomonea` trace spans for database timeouts.
* Scale pods horizontally if CPU usage > 80% for 5m.
* Failover to secondary region using Terraform workspace `payments-secondary`.

### Core QRPay Web

* Confirm Laravel queue and cache health.
* Run smoke test script `scripts/health-check.sh`.
* Rollback via `deployctl rollback --service qrpay-web --env prod` if necessary.

## Tooling Checklist

* PagerDuty service mapping complete for all services.
* Slack automation configured for channel creation.
* Status page integration with incident API.

## GameDay Exercises

| Exercise | Scenario | Frequency |
| --- | --- | --- |
| Alipay Timeout Storm | Simulate upstream latency > 2s | Quarterly |
| BluBank Settlement Failure | Queue backlog + payment reconciliation failure | Quarterly |
| Yoomonea Regional Outage | Disable primary region workloads | Semi-annual |
| QRPay Web Cache Eviction | Flush Redis cache unexpectedly | Quarterly |

Each GameDay must include:
* Pre-brief with goals and success criteria.
* Execution in staging with synthetic traffic.
* Debrief documenting findings and action items.

## Incident Tracking

* All incidents tracked in Jira project `OPS` with ticket type `Incident`.
* Use template containing summary, impact, root cause, mitigation, follow-up tasks.

## Post-Incident Actions

* Ensure follow-up tasks are assigned and tracked to completion.
* Update runbooks/playbooks after every incident.

## Escalation Matrix

| Severity | Definition | Escalation |
| --- | --- | --- |
| SEV0 | Complete outage or regulatory impact | Notify CTO, Head of Compliance immediately. |
| SEV1 | Major functionality loss affecting > 50% users | Escalate to Directors of Engineering, Product. |
| SEV2 | Partial degradation affecting specific integration | Notify integration owners. |
| SEV3 | Minor bug or alert noise | Handle within squad, log for backlog. |

## Communication Templates

Internal Slack update:
```
:rotating_light: *Incident {{INCIDENT_ID}}* - {{SERVICE}}
Impact: {{SUMMARY}}
Mitigation: {{CURRENT_ACTION}}
Next Update: {{TIME}}
```

Customer status page:
```
We are investigating elevated errors for {{SERVICE}}. Payments may fail intermittently. Next update in 30 minutes.
```

## Metrics for Process Health

* MTTA (Mean Time to Acknowledge) target: < 5 minutes.
* MTTR (Mean Time to Resolve) target: < 30 minutes for SEV1/SEV2.
* GameDay completion rate: 100% scheduled exercises executed.

## Document Ownership

* Primary Owner: Reliability Engineering Lead.
* Review Cycle: quarterly or after major incidents.
