# Postmortem and Knowledge Base Process

Establishes the system for capturing lessons learned from incidents and sharing operational knowledge across teams.

## Objectives

* Drive continuous improvement by analyzing root causes.
* Ensure corrective actions are tracked and verified.
* Provide easily discoverable documentation for future responders.

## Postmortem Workflow

1. **Trigger**
   * Any SEV0/SEV1 incident requires a postmortem.
   * SEV2 incidents require a postmortem if MTTR > 60 minutes or repeated within 30 days.
2. **Owner Assignment**
   * IC assigns a postmortem owner (usually the SME) within 24 hours of resolution.
3. **Draft Creation**
   * Use Confluence template `QRPay - Incident Postmortem` or Markdown file in `docs/postmortems/{year}/{incident-id}.md`.
   * Populate timeline, impact, detection, response, root cause, contributing factors, lessons learned, and follow-up tasks.
4. **Review Meeting**
   * Schedule within 5 business days.
   * Include IC, SMEs, Product, Support, and Compliance if applicable.
5. **Action Items**
   * Track in Jira (`OPS` project, issue type `Task`, label `postmortem`).
   * Assign due dates and owners.
6. **Verification**
   * Reliability Engineering reviews action item completion weekly.
   * Update postmortem with verification notes.
7. **Publishing**
   * Link finalized postmortem in Knowledge Base index and relevant runbooks.

## Knowledge Base Structure

```
knowledge-base/
  index.md
  services/
    alipay.md
    blubank.md
    yoomonea.md
  playbooks/
    incident-response.md
    deployment-checklists.md
  postmortems/
    2024/
      OPS-1234-alipay-latency.md
```

* Store documentation in Git (docs directory) and sync with Confluence.
* Each service page must include architecture overview, common failure modes, runbook links, SLIs/SLOs.

## Automation

* Create GitHub Action `postmortem-checklist` to ensure required fields exist before merging a postmortem PR.
* Use PagerDuty webhook to auto-create a draft postmortem issue in Jira with incident metadata.

## Templates

### Postmortem Template (Markdown)
```
# Incident {{INCIDENT_ID}} - {{TITLE}}

## Summary
*Date:* {{DATE}}
*Services Impacted:* {{SERVICES}}
*Duration:* {{DURATION}}

## Impact
Describe user impact, financial implications, and regulatory considerations.

## Timeline
| Time | Event | Actor |
| --- | --- | --- |
| 10:05 UTC | Alert triggered (AlipayAvailabilityBurnRateHigh) | PagerDuty |

## Detection
How was the incident detected? Were alerts sufficient?

## Root Cause
Explain technical and organizational factors.

## Mitigation
List steps taken to restore service.

## Corrective Actions
| Item | Owner | Due Date | Status |
| --- | --- | --- | --- |

## Lessons Learned
* What went well?
* What went poorly?
* Where did we get lucky?

## Follow-up
Reference Jira tasks, runbook updates, architectural changes.
```

### Knowledge Base Index Template
```
# QRPay Knowledge Base Index

## Services
- [Alipay Integration](services/alipay.md)
- [BluBank Integration](services/blubank.md)
- [Yoomonea Service](services/yoomonea.md)

## Playbooks
- [Incident Response](playbooks/incident-response.md)
- [Deployment Checklists](playbooks/deployment-checklists.md)

## Postmortems
- [OPS-1234 Alipay Latency Spike](postmortems/2024/OPS-1234-alipay-latency.md)
```

## Governance

* Quarterly review of knowledge base for accuracy.
* Enforce documentation updates as part of production change checklist.
* Archive outdated documents after 18 months.

## Tooling Recommendations

* Use Confluence API script to sync Git docs nightly.
* Implement search indexing via Algolia for quick lookup.
* Provide Slack `/kb` command to retrieve runbook links.

## Metrics

* Postmortem completion rate (target: 100% for SEV0/SEV1).
* Action item completion within 30 days (target: 90%).
* Knowledge base page views per month to ensure usage.
