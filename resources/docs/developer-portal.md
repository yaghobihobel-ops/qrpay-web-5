# QRPay Developer Portal

Welcome to the QRPay integration guide for external developers. This document explains
how to explore the API contract, authenticate requests, and bootstrap the official
SDK generators that are shipped with the platform.

## API surface

- **Specification**: The OpenAPI 3.0 contract for the latest public release is available at
  [`/public/docs/openapi/v2/openapi.yaml`](../../public/docs/openapi/v2/openapi.yaml).
- **Interactive documentation**: Navigate to [`/docs`](/docs) on any deployed
  environment to explore the contract via ReDoc. The page is served from the Laravel
  frontend and reflects the version committed to the repository.
- **Change management**: The API version encoded in the specification (`info.version`)
  follows semantic versioning. Backwards-incompatible updates increment the major
  segment while additive updates increment the minor segment.

## Environments

| Environment | Base URL | Notes |
|-------------|----------|-------|
| Production  | `https://api.qrpay.example.com/v2` | Stable endpoints for live traffic. |
| Sandbox     | `https://sandbox.qrpay.example.com/v2` | Mirrors production configuration with mock payment providers. |
| Local       | `http://localhost/api` | Default when running the Laravel app locally. |

All requests must be made over HTTPS in shared environments. Local development can use
HTTP during testing.

## Authentication

QRPay issues personal access tokens through the `/user/login` endpoint. Include the token
in the `Authorization` header using the `Bearer {token}` format. Tokens inherit the
lifetime configured in the admin panel. When a token expires, the client must authenticate
again.

For passwordless or multi-factor flows, consult the OTP and Google Authenticator endpoints
under the `/user` prefix in the OpenAPI contract.

## SDK generation

The repository ships npm scripts that wrap the official
[`openapi-generator`](https://openapi-generator.tech/) CLI. Run the following commands from
the project root after installing Node.js 18 or newer:

```bash
npm install
npm run generate:sdk:ts      # Generates the TypeScript Axios SDK in sdks/typescript
npm run generate:sdk:php     # Generates the PHP SDK in sdks/php
npm run generate:sdk:python  # Generates the Python SDK in sdks/python
```

Use `npm run generate:sdk` to build all SDKs sequentially. Generated clients are excluded
from version control by default. Distribute the compiled packages to your integration teams
or publish them to private registries as required.

## Webhooks and callbacks

Payment gateways may need to reach your integration with asynchronous callbacks. Ensure
that callback URLs are reachable and secured with TLS. Reconcile webhook payloads using the
transaction reference returned by the QRPay API.

## Support

If you require production credentials or have integration questions, please reach out to the
QRPay technical support desk at `support@qrpay.example.com` with your merchant identifier and
contact details.
