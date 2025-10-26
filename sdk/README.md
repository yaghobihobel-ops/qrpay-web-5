# QRPay SDK Distribution

This directory documents the OpenAPI-based SDK generation workflow. Generated artifacts are published to GitHub Packages under the `qrpay` organization and are built from the versioned OpenAPI specification located at `public/docs/openapi/qrpay-api-1.0.0.yaml`.

## Available SDKs

| Language | Package | Install | Documentation |
| --- | --- | --- | --- |
| TypeScript | `@qrpay/sdk` | `npm install @qrpay/sdk` | https://github.com/qrpay/ts-sdk/packages/qrpay-sdk |
| Python | `qrpay-sdk` | `pip install qrpay-sdk --extra-index-url https://pip.pkg.github.com/qrpay/simple/` | https://github.com/qrpay/python-sdk/packages/qrpay-sdk |
| PHP | `qrpay/sdk` | `composer require qrpay/sdk` | https://github.com/qrpay/php-sdk/packages/qrpay-sdk |

> **Note:** GitHub authentication is required to consume private packages. Set the relevant registry credentials before installing.

## Generating SDKs Locally

Install [OpenAPI Generator CLI](https://openapi-generator.tech/docs/installation) or use Docker, then run the helper script:

```bash
./sdk/generate.sh
```

Artifacts are generated into `sdk/dist/<language>` and can be published via CI using GitHub Actions.

## Publishing Guidance

1. Bump the `info.version` field in the OpenAPI spec and tag the repo.
2. Regenerate SDKs.
3. Run the language-specific publish commands:
   - **TypeScript**: `npm publish --registry=https://npm.pkg.github.com`
   - **Python**: `python -m build && twine upload --repository-url https://upload.pypi.org/legacy/ dist/*`
   - **PHP**: `composer publish`
4. Update the changelog and notify subscribers through the developer portal.

For automated releases, reference `.github/workflows/sdk-publish.yml` (create as needed) to authenticate with GitHub Packages and push versioned artifacts.
