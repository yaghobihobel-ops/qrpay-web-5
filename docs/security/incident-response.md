# Incident Response Plan (IRP)

## Objectives
- Provide a repeatable playbook for responding to security incidents involving payments, compliance, or infrastructure.
- Satisfy regulatory expectations for operations in China, Iran, Russia, and Turkey.

## Roles & Responsibilities
- **Incident Commander (IC):** Leads the incident, assigns tasks, coordinates with compliance and legal teams.
- **Security Engineering:** Performs containment, forensic triage, and remediation.
- **Compliance Officer:** Liaises with AML/KYC teams, documents regulatory notifications, and tracks customer outreach obligations.
- **Communications Lead:** Manages messaging to users, partners, and regulators.
- **SRE / Infrastructure:** Restores services, validates post-incident hardening, and collects runtime telemetry.

## Severity Classification
| Severity | Description | Initial Response Time | Notification Requirements |
| --- | --- | --- | --- |
| SEV-1 | Active compromise or data exfiltration impacting regulated data. | Immediate | Notify regulators in affected jurisdictions within 24h; notify customers within 48h. |
| SEV-2 | Contained compromise with no confirmed data loss; suspicious AML/KYC breach. | 15 minutes | Compliance officer review; regulatory notice if incident escalates. |
| SEV-3 | Misconfiguration or failed control with no exploitation. | 4 hours | Document in post-incident report; no external notification unless mandated. |

## Playbook
1. **Detection & Triage**
   - Alerts originate from device fingerprint anomalies, AML/KYC rule triggers, audit log anomalies, or infrastructure monitoring.
   - IC validates severity, opens incident channel, and tags stakeholders.
2. **Containment**
   - Freeze suspicious accounts, revoke API credentials (using encrypted config), and rotate secrets.
   - Force MFA re-verification on untrusted devices using `DeviceFingerprintService::trustCurrent` as recovery gate.
3. **Eradication & Recovery**
   - Deploy hotfixes, rotate session keys, and re-run compliance screenings.
   - Collect forensic artifacts from `device_fingerprints`, `admin_audit_logs`, and `compliance_screenings` tables.
4. **Communication**
   - Deliver regulator-specific reports referencing regional retention requirements (CN: 730d, IR:365d, RU:548d, TR:730d).
   - Use pre-approved customer messaging templates stored with communications lead.
5. **Post-Incident Review**
   - Complete root cause analysis within 5 business days.
   - Update `security_password_rules` or session policies if gaps are identified.
   - File retrospective in docs/security with mitigation tasks.

## Testing & Validation
- **Tabletop Exercises:** Quarterly simulations covering AML rule escalations and device takeover scenarios.
- **Red Team Checks:** Annual compromise drill verifying MFA and audit log integrity.
- **Automated Tests:** See `tests/Feature/Compliance/AmlRuleEngineTest.php` and `tests/Feature/Security/DeviceFingerprintTest.php`.

## Long-Term Log Retention
- `admin_audit_logs` enforce retention schedule via `audit:enforce-retention` scheduled task.
- Raw SIEM exports retained off-site per jurisdictional requirements (minimum 365 days).
- Access to logs is restricted; modifications recorded via `AdminAuditLogger` middleware.

