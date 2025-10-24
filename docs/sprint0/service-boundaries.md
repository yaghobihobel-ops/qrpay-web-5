# Sprint 0 – Service Boundary Inventory

## Payments & Merchant APIs
- `App/Http/Controllers/PaymentGateway/QrPay/v1/PaymentController` orchestrates token issuance, preview, and confirmation for hosted payments. It couples Stripe/Sudo/legacy virtual card checks directly in the controller, making it a prime candidate for a `PaymentProviderInterface` adapter layer.【F:app/Http/Controllers/PaymentGateway/QrPay/v1/PaymentController.php†L1-L120】【F:app/Http/Controllers/PaymentGateway/QrPay/v1/PaymentController.php†L521-L590】
- Merchant configuration and sandbox logic rely on `MerchantConfiguration`, `SandboxWallet`, and `GatewaySetting` models loaded inline. Extracting these interactions behind contracts will let future country modules decide which provider to invoke at runtime.【F:app/Http/Controllers/PaymentGateway/QrPay/v1/PaymentController.php†L33-L120】

## Wallet Top-up & Withdrawals
- `App/Http/Controllers/User/MobileTopupController` mixes business rules (limit checks, FX conversions, fee computation) with direct Airtime API calls via `AirtimeHelper`. This path is the template for a `TopUpProviderInterface` once we decouple helper usage from controller logic.【F:app/Http/Controllers/User/MobileTopupController.php†L1-L160】【F:app/Http/Controllers/User/MobileTopupController.php†L200-L320】
- Manual vs automatic top-up routing is currently implemented through route redirection based on constants; moving that decision tree into the routing engine will allow country modules to register additional providers without altering controllers.【F:app/Http/Controllers/User/MobileTopupController.php†L39-L80】

## FX & Remittance
- Exchange rate handling depends on `Admin\ExchangeRate` records referenced in the remittance controller and top-up flows. The rule engine should query an `FXProviderInterface` that abstracts both the DB rate cache and any external API refreshes.【F:app/Http/Controllers/User/MobileTopupController.php†L220-L288】【F:app/Http/Controllers/User/RemitanceController.php†L360-L440】
- `GlobalController@currencyRate` exposes a public endpoint that reads `ExchangeRate` models directly; wrapping this behind an interface ensures consistent rate sourcing for both API and internal routing logic.【F:app/Http/Controllers/GlobalController.php†L1-L200】

## KYC / Compliance
- `App/Http/Controllers/User/AuthorizationController` drives KYC submission and status updates by loading dynamic field definitions from `SetupKyc` and persisting JSON payloads. Introducing a `KYCProviderInterface` lets us plug local compliance vendors while keeping the controller focused on form orchestration.【F:app/Http/Controllers/User/AuthorizationController.php†L1-L120】【F:app/Http/Controllers/User/AuthorizationController.php†L120-L200】
- Virtual card enrollment depends on `StrowalletCustomerKyc` checks within `StrowalletVirtualController`, showing another integration point for third-party KYC providers tied to card issuance.【F:app/Http/Controllers/User/StrowalletVirtualController.php†L1-L120】【F:app/Http/Controllers/User/StrowalletVirtualController.php†L160-L240】

## Card Issuance
- `StrowalletVirtualController` coordinates customer creation, card issuance, reload, and notification flows, backed by `VirtualCardApi` configuration and on-ledger wallet updates. This controller will consume the future `CardIssuerInterface` while the provider packages encapsulate vendor-specific endpoints.【F:app/Http/Controllers/User/StrowalletVirtualController.php†L1-L160】【F:app/Http/Controllers/User/StrowalletVirtualController.php†L240-L320】
- The same controller triggers user/admin notifications (`VirtualCard\CreateMail`, `ActivityNotification`) and handles fee deductions, which should remain in core logic while issuer-specific implementations provide balances, limits, and status transitions.【F:app/Http/Controllers/User/StrowalletVirtualController.php†L240-L360】

## Crypto Bridge & Routing Hooks
- Crypto fallback currently lives inside payment controllers as conditional checks against `VirtualCard`/`SudoVirtualCard` models. Formalizing a `CryptoBridgeInterface` allows us to externalize on/off-ramp handling and share audit trails with the routing engine.【F:app/Http/Controllers/PaymentGateway/QrPay/v1/PaymentController.php†L521-L620】
- Smart routing can hook into existing constants (`PaymentGatewayConst`, `GlobalConst`) and transaction helpers without altering DB schemas, provided we centralize provider selection logic in a new service class instead of scattering conditionals across controllers.【F:app/Constants/PaymentGatewayConst.php†L1-L120】【F:app/Constants/GlobalConst.php†L1-L60】

## Immediate Refactoring Guards
- Preserve current request validation and notification triggers; the first iteration of interfaces should wrap existing helpers rather than replacing them to avoid breaking user flows.
- Document every controller-method-to-interface mapping in a shared spreadsheet so sprint teams can claim ownership without colliding changes.
