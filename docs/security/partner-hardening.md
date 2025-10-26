# Partner Integration Hardening

This document describes the security controls that protect QRPay's partner integrations with Alipay, BluBank, and Yoomonea. These controls are enforced at runtime and through CI/CD automation.

## Key Management and Rotation

* Secrets for Alipay, BluBank, and Yoomonea are no longer stored in `.env` files. They are retrieved on-demand from the configured Vault/HSM via the `App\Services\Security\KeyManagementService` service.
* Secrets are cached for a short TTL to limit Vault roundtrips while avoiding stale credentials.
* The `keys:rotate` Artisan command rotates partner secrets using the Vault transit engine. The command runs on a configurable cron schedule (default: every six hours) and can also be invoked manually: `php artisan keys:rotate {service?} [--force]`.
* Rotation history is tracked in cache to avoid unnecessary rotations and to ensure backoff in case of Vault maintenance.

## Mutual TLS, IP Allowlisting, and Request Signing

* Partner calls must include the `X-QRPay-Service` header identifying the integration. Configuration lives in `config/partner_security.php` and is driven by environment variables documented in `.env.example`.
* `App\Http\Middleware\Api\EnsurePartnerSecurity` enforces:
  * Mutual TLS using the web server-provided `SSL_CLIENT_VERIFY` and subject DN metadata. Subject patterns support literal, wildcard, and regex matching.
  * IP allowlisting using CIDR-aware checks via Symfony's `IpUtils` helper.
  * HMAC request signing with timestamp leeway validation. Secrets are resolved from Vault so that rotation is seamless.
* Responses automatically include the `X-QRPay-Partner-Security` header indicating successful enforcement for observability.

## Session Binding and Device Fingerprinting for Sensitive Users

* Sensitive accounts (users flagged with `is_sensitive` or belonging to a role in `security.sensitive_user_roles`) now bind sessions to IP, user agent, and device fingerprint via the `SensitiveSessionBinding` middleware.
* The `SessionBindingService` persists the binding snapshot on login and clears it when the session is invalidated.
* Device fingerprints must be explicitly trusted for sensitive sessions when `DEVICE_REQUIRE_TRUSTED_FOR_SENSITIVE=true`. A mismatch triggers a logout and HTTP 4xx response.

## Continuous Security Testing

* The GitHub Actions workflow `.github/workflows/security-scans.yml` runs Semgrep (SAST) and OWASP ZAP baseline scans (DAST) on every push, pull request, and weekly on `main`.
* Workflow artifacts include SARIF reports that can be imported into security tooling.

## Operational Checklist

1. Populate the Vault/HSM secrets paths listed in `.env.example`.
2. Configure reverse proxies to pass the TLS client verification headers to Laravel.
3. Distribute client certificates to Alipay, BluBank, and Yoomonea according to their respective subject DNs.
4. Update allowlists when partner IP ranges change.
5. Monitor the security workflow results and the `keys:rotate` scheduled job for failures.
