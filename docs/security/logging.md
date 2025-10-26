# Audit Logging & Retention

## Overview
The platform captures privileged actions and authentication context to satisfy AML/KYC obligations across China, Iran, Russia, and Turkey. Audit events are written to:

- `admin_audit_logs`: Administrative actions, routed through `AdminAuditLogger` middleware.
- `device_fingerprints`: Device registration, trust state, and metadata.
- `compliance_screenings`: AML/KYC evaluation results, including triggered rules and risk scores.
- `*_login_logs`: User, merchant, agent, and admin login history with device fingerprints.

## Retention
Retention windows are defined in `config/compliance.php` under `audit_log_retention`:

| Region | Retention (days) |
| --- | --- |
| Global | 365 |
| China | 730 |
| Iran | 365 |
| Russia | 548 |
| Turkey | 730 |

The `audit:enforce-retention` artisan command prunes expired records daily at 01:00 UTC via the scheduler configured in `App\Console\Kernel`.

## Access Controls
- Access to the admin dashboard enforces MFA and device fingerprinting before `admin_audit_logs` entries are written.
- Sensitive API keys are encrypted at rest via the `EncryptedJson` cast, ensuring that audit dumps do not expose plaintext secrets.
- Session hardening middleware invalidates idle sessions and rotates cookies, reducing hijack risk.

## Review Procedures
- Compliance team reviews `admin_audit_logs` weekly for anomalous actions.
- Device fingerprint mismatches trigger SEV-2 incidents per the IRP.
- Exporting audit data for regulators requires dual control: IC approval and compliance sign-off.

