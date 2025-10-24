# Sprint 0 – Documentation Sources & Review Plan

## Bundled Vendor Documentation
- `README.md` provides the official upgrade guide from v4.9.0 to v5.0.0, including commands the vendor expects for a clean deployment (`composer update`, `php artisan migrate:fresh`, Passport install, etc.).【F:README.md†L1-L16】
- `qrpay-documentations.html` ships with the repository and mirrors the CodeCanyon help center. It covers admin panel navigation, wallet features, QR payment flows, and environment setup in an offline, searchable format.【F:qrpay-documentations.html†L1-L20】

**Action**: Host the HTML doc on an internal wiki (or serve locally) so product, compliance, and engineering can reference baseline behaviors while we extend the platform.

## Configuration Artifacts Worth Cross-Checking
- Payment providers (`config/paypal.php`, `config/flutterwave.php`, `config/pagadito.php`) contain current credential expectations and callback URL formats. Extract these into provider interface reference tables before abstraction.【F:config/paypal.php†L1-L40】【F:config/flutterwave.php†L1-L40】【F:config/pagadito.php†L1-L38】
- `config/services.php` lists OAuth/mail/SMS integrations that might influence KYC/AML modules (e.g., Nexmo, Twilio). Inventory which ones are actively used in controllers before refactoring.【F:config/services.php†L1-L44】
- Exchange rate and currency settings live under `config/starting-point.php` and `app/Constants/GlobalConst.php`, informing multi-currency defaults and transaction limits.【F:config/starting-point.php†L1-L60】【F:app/Constants/GlobalConst.php†L1-L60】

**Action**: Create a spreadsheet (or Notion table) that maps every config key to the eventual country/provider plug-in toggle so ops can adjust via admin later instead of `.env` edits.

## Gaps & External References Needed
- The vendor docs stop at single-country assumptions. For multi-country compliance, gather regulatory checklists per jurisdiction (OFAC, EU, local FIU). These will inform KYC/AML provider contracts.
- Secure architecture runbooks are missing. Draft ops procedures covering backup, log retention, and incident response when we reach Sprint 6.

**Interim Decision**: Because Composer cannot finish installing yet, defer code-linked doc generation (e.g., Swagger/OpenAPI dumps) until the PHP version issue is resolved. Manual extraction via static review is acceptable short term as long as we log missing automation in the engineering backlog.
