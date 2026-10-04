# ShamCash PHP SDK

Standalone Composer library for the ShamCash API. Package `oktocode/sham-cash-sdk`, namespace `OkToCode\ShamCash`, MIT license. The company is OkToCode and the site is https://ok2code.com. Do not rename the package or namespace to ok2code.

This package stays framework-free. Laravel, Symfony, Drupal, and WordPress bridges belong in other repositories.

## Public surface

`Client` is the only entry point. Methods: `createBill`, `getBill`, `refundBill`, `listTransactions`, `eachTransaction`, `parseWebhook`.

Public types are `Client`, the models, the enums, and the exceptions. `Crypto`, `Http`, and `Support` are `@internal`.

One `ApiException` plus `ResultCode`. No subclass per result code.

- Local mistakes throw `InvalidArgumentException` before HTTP.
- HTTP 200 with `succeeded !== true` or `result !== 2500` throws `ApiException`.
- Timeouts, non-200 responses, and bad JSON throw `TransportException`.
- A bad token throws `CryptoException`.

## Wire rules

Every call is `POST {baseUrl}/api/ElectronicPayment/{action}` with `{"encData","agentKey"}`. The product name does not include "Electronic". The path segment `ElectronicPayment` stays, because that is ShamCash's route.

Success is `result === 2500` and `succeeded === true`. HTTP 200 only means the server parsed the request.

Direct JWE compact, header exactly `{"alg":"dir","enc":"A256GCM","cty":"json"}`, empty encrypted-key segment, 12-byte IV, 16-byte tag. AAD is the ASCII protected-header segment. `secretKey` is Base64 for 32 raw bytes, not the Base64 text used as UTF-8.

Amounts stay decimal strings. Write them as JSON numbers without a float cast. Incoming number literals are preserved as strings.

`callbackUrl` and `redirectUrl` are optional client defaults and optional per-call overrides. The `createBill` argument wins independently for each URL. ShamCash still requires both on the wire. If either is missing after the merge, throw `InvalidArgumentException` and send nothing. ShamCash does not append `billNo` to `redirectUrl`.

The caller passes the refund `idempotencyKey` (10–100 characters). The SDK does not invent one and does not retry money calls. Repeat a refund only with the same key. A repeat `createBill` can return `1704`; catch it and call `getBill`.

Do not poll `getBill`. It is a one-shot fallback at least 10 minutes after create, and only when the webhook never arrived.

`parseWebhook` takes the raw body string. Do not re-encode that JSON. The webhook body is `{"encData"}` only. Reject `exp` older than now plus skew, and `iat` more than skew in the future. Default skew is 30 seconds. Token TTL is 300 seconds. Connect timeout is 5 seconds and request timeout is 30 seconds.

Guzzle 7 is a required dependency. The constructor may take a PSR-18 client. TLS verification stays on, including on an injected client. An optional PSR-3 logger may record the operation, HTTP status, and result code. Never log `secretKey`, `encData`, or a decrypted payload.

## Docs and release

Keep `README.md` and `README.ar.md` in sync. Language switching uses the shields.io badges from the multilanguage README pattern: `lang-en` links to `README.md` and `lang-ar` links to `README.ar.md`.

Examples stay in the published package and need comments. `CHANGELOG.md` follows Keep a Changelog. Do not put a `version` field in `composer.json`. Packagist reads git tags. `v1.0.0` is stable `1.0.0`. A path install of the `master` branch is `dev-master`.

Packagist: https://packagist.org/packages/oktocode/sham-cash-sdk

`config.platform.php` is `8.2.0` so `composer.lock` stays installable on CI. CI runs PHPUnit, PHPStan level 8 on `src`, and PSR-12 on PHP 8.2, 8.3, and 8.4. PHP-CS-Fixer covers `src`, `tests`, and `examples`.

Report security issues through the GitHub private advisory. Do not put `secretKey`, `encData`, or a decrypted payload in a public issue.

## Local files

`/local/` is gitignored. `local/sandbox.php` holds dev credentials and calls the dev API. `local/consumer/` is a path-repository install check. Never commit that directory, and never copy those credentials into tracked files.
