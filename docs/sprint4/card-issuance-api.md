# Sprint 4 – Card Issuance API Skeleton

## Overview
This sprint introduces the modular card issuance surface that relies on the `CardIssuerInterface` contract and the country-aware provider resolver. A lightweight API controller exposes the lifecycle endpoints while mock issuers inside each starter country module showcase end-to-end flows without modifying existing core controllers.

## Key Components
- `App\Support\Cards\CardIssuanceManager` orchestrates provider resolution and delegates lifecycle calls (`issueVirtual`, `issuePhysical`, `activate`, `block`, `limits`).
- `App\Support\Cards\Exceptions\CardIssuerNotConfiguredException` standardises error reporting when a country lacks an issuer binding.
- `App\Http\Controllers\Api\User\CardIssuanceController` validates requests, injects authenticated context, and returns helper-formatted API responses.
- `Modules\Country\Shared\Providers\AbstractMockCardIssuer` offers reusable mock responses; IR, CN, and TR modules extend it to surface country-specific currencies and metadata.

## API Endpoints
All routes live under `api/user/cards` and inherit the existing user middleware stack plus `api.kyc`.

| Method | Path | Description |
| --- | --- | --- |
| `POST` | `/api/user/cards/issue` | Issue a virtual or physical card (`type` of `VIRTUAL` or `PHYSICAL`). |
| `POST` | `/api/user/cards/activate` | Activate a previously issued card. |
| `POST` | `/api/user/cards/block` | Block or freeze a card (temporary or permanent). |
| `POST` | `/api/user/cards/limits` | Update/retrieve card limits for reconciliation. |

### Common Request Fields
- `country` (required): ISO country code that selects the country module.
- `card_id`: Required for follow-up actions (`activate`, `block`, `limits`).
- `limits`: Optional associative array supporting `daily`, `monthly`, and `per_transaction` caps.
- `metadata`, `customer_reference`, `kyc_tier`: Optional payload forwarded to issuers.

### Response Shape
Each endpoint wraps provider output via `Helpers::success`, returning the resolved `card` payload alongside the country code and (for issue) the requested type.

## Country Module Defaults
| Country | Issuer Class | Currency |
| --- | --- | --- |
| IR | `Modules\Country\IR\Providers\MockIrCardIssuer` | IRR |
| CN | `Modules\Country\CN\Providers\MockCnCardIssuer` | CNY |
| TR | `Modules\Country\TR\Providers\MockTrCardIssuer` | TRY |

Modules can later swap the mock class for a real adapter without touching the API surface.

## Error Handling
If a module lacks a `CardIssuerInterface` binding the manager raises `CardIssuerNotConfiguredException`, leading to a helper-formatted error response. Unexpected failures are logged via `report()` and return a generic localized message.

## Next Steps
- Wire admin configuration to toggle issuers per country and surface metadata inside the dashboard.
- Extend the mock issuer to persist card state (in-memory or database) for integration tests.
- Align Flutter app screens with the new `/cards` endpoints once API contracts stabilise.
