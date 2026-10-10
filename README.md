<p align="center">
  <a href="https://github.com/OkToCode-L-L-C/Sham-Cash-PHP-SDK/actions/workflows/tests.yml"><img src="https://github.com/OkToCode-L-L-C/Sham-Cash-PHP-SDK/actions/workflows/tests.yml/badge.svg" alt="CI"></a>
  <a href="https://packagist.org/packages/oktocode/sham-cash-sdk"><img src="https://img.shields.io/packagist/v/oktocode/sham-cash-sdk?label=packagist" alt="Packagist version"></a>
  <a href="https://packagist.org/packages/oktocode/sham-cash-sdk"><img src="https://img.shields.io/packagist/php-v/oktocode/sham-cash-sdk?label=php" alt="PHP version"></a>
  <a href="https://packagist.org/packages/oktocode/sham-cash-sdk"><img src="https://img.shields.io/packagist/dt/oktocode/sham-cash-sdk?label=downloads" alt="Packagist downloads"></a>
  <a href="LICENSE"><img src="https://img.shields.io/packagist/l/oktocode/sham-cash-sdk?label=license" alt="MIT license"></a>
</p>

<h1 align="center">ShamCash PHP SDK</h1>

<p align="center">
  <a href="https://github.com/OkToCode-L-L-C/Sham-Cash-PHP-SDK/blob/master/README.md"><img src="https://img.shields.io/badge/lang-en-red.svg" alt="en"></a>
  <a href="https://github.com/OkToCode-L-L-C/Sham-Cash-PHP-SDK/blob/master/README.ar.md"><img src="https://img.shields.io/badge/lang-ar-green.svg" alt="ar"></a>
</p>

<p align="center">
  <a href="https://packagist.org/packages/oktocode/sham-cash-sdk"><strong>Packagist</strong></a>
  &nbsp;&middot;&nbsp;
  <a href="https://github.com/OkToCode-L-L-C/Sham-Cash-PHP-SDK"><strong>GitHub</strong></a>
  &nbsp;&middot;&nbsp;
  <a href="https://ok2code.com"><strong>ok2code.com</strong></a>
</p>

<p align="center">
  Create ShamCash bills, refund payments, list transactions, and verify webhooks from PHP.
</p>

<p align="center">
  <strong>SDK Version:</strong> 1.0.0 · <strong>ShamCash API Version:</strong> v1.0.0
</p>

## Prerequisites

Before integrating ShamCash, you must register a verified commercial account, complete the Service Creation process, and obtain your API credentials.

See the [Prerequisites and Service Registration Guide](prerequisite.md) for the required information and registration instructions.

## About

Implemented by [OkToCode](https://ok2code.com).

The SDK encrypts each request, calls the bill, refund, and transaction endpoints, and decrypts the webhook ShamCash sends back to your server.

- **Bills and refunds.** Create a bill, load it, and refund it with an idempotency key you choose.
- **Transactions.** Read one page, or walk every page with `eachTransaction()`.
- **Webhooks.** Pass the raw POST body to `parseWebhook()` and branch on paid or expired.
- **Safe amounts.** Money stays a decimal string on the way in and on the way out.

## Install

```bash
composer require oktocode/sham-cash-sdk
```

Package: [oktocode/sham-cash-sdk](https://packagist.org/packages/oktocode/sham-cash-sdk). The namespace is `OkToCode\ShamCash`. PHP 8.2 or newer is required.

## Configure the client

ShamCash gives you an agent key, a Base64-encoded 32-byte secret, and a base URL during service creation. Keep the secret on the server.

```php
use OkToCode\ShamCash\Client;
use OkToCode\ShamCash\Enum\Currency;

$client = new Client(
    agentKey: getenv('SHAMCASH_AGENT_KEY'),
    secretKey: getenv('SHAMCASH_SECRET_KEY'),
    baseUrl: getenv('SHAMCASH_BASE_URL'),
    callbackUrl: 'https://agent.example/webhooks/shamcash',
    redirectUrl: 'https://agent.example/checkout/return',
);
```

Create one client per merchant. The SDK builds a Guzzle client with a 5 second connect timeout and a 30 second request timeout, and it verifies TLS. Pass your own PSR-18 client when a test or a later bridge needs a different HTTP stack, and keep TLS verification enabled on that client.

## Create a bill

Create the bill when the customer chooses ShamCash, not when the cart is first saved. ShamCash expires an unpaid bill after 10 minutes.

```php
$bill = $client->createBill(
    billNo: 'Order-123-1',
    amount: '10.50',
    currency: Currency::Usd,
    note: 'Order 123',
);

header('Location: ' . $bill->paymentUrl);
```

Send the customer to `paymentUrl` in the system browser. An embedded web view breaks the ShamCash app deep link.

`Currency::Usd` is `1` and `Currency::Syp` is `2`. Amounts are decimal strings such as `"10.50"`.

Use a fresh `billNo` when the customer retries. A suffix such as `Order-123-1` and `Order-123-2` avoids "bill number already exists" for the same order.

## Callback and redirect URLs

`callbackUrl` is where ShamCash POSTs the encrypted webhook. Set it on the client when every bill uses the same endpoint.

`redirectUrl` is where the user's browser returns after checkout. ShamCash does not append `billNo`. Put the order id in this URL when the return page needs it.

Both are optional on the client and optional on `createBill()`. For each URL, the SDK uses the `createBill()` argument when it is passed, and otherwise uses the client value. ShamCash still requires both in the encrypted payload. If either URL is still empty, `createBill()` throws `InvalidArgumentException` and sends nothing.

```php
$client->createBill(
    billNo: 'Order-123-1',
    amount: '10.50',
    currency: Currency::Usd,
);

$client->createBill(
    billNo: 'Order-456-1',
    amount: '20.00',
    currency: Currency::Usd,
    callbackUrl: 'https://agent.example/webhooks/shamcash/orders',
    redirectUrl: 'https://agent.example/orders/456/return',
);
```

The first bill uses both client defaults. The second bill replaces `callbackUrl` and `redirectUrl` for that bill only. Omit either argument to keep the client value. See [examples/create-bill.php](examples/create-bill.php).

## Read a bill

```php
$bill = $client->getBill('Order-123-1');
```

Call this once, at least 10 minutes after creating the bill, and only when the webhook never arrived. Do not poll it. See [examples/get-bill.php](examples/get-bill.php).

`$bill->status` is a `BillStatus` when ShamCash sends a known status id: pending, refund, expired, paid, or partly refunded. `$bill->raw` is the decoded object, including any field this SDK does not map yet.

## Refund

Pass an idempotency key of 10 to 100 characters. If the request times out, send the same key again. ShamCash returns the original refund when that key already succeeded, and it leaves the key unused when the refund failed.

```php
$refund = $client->refundBill(
    billNo: $bill->billNo,
    amount: '10.50',
    idempotencyKey: $idempotencyKey,
    note: 'Customer return',
);
```

The refund uses the bill currency. The total of all refunds cannot exceed the amount paid. See [examples/refund-bill.php](examples/refund-bill.php).

## Transactions

```php
$page = $client->listTransactions('2026-01-01', '2026-01-20', afterTranId: 0, limit: 1000);

foreach ($client->eachTransaction('2026-01-01', '2026-01-20', limit: 1000) as $transaction) {
    // $transaction->tranType is payment or refund when the type id is known.
}
```

`eachTransaction()` follows `hasMore` and sends the previous `lastReturnedTranId` as `afterTranId`. `limit` must be from 10 to 2500. The default is 500. See [examples/list-transactions.php](examples/list-transactions.php).

## Webhooks

ShamCash POSTs `{ "encData": "..." }` to `callbackUrl` when a bill is paid or expired. Pass that raw body to the SDK. Re-encoding the JSON can change `encData` and break decryption.

```php
use OkToCode\ShamCash\Enum\BillStatus;
use OkToCode\ShamCash\Exception\CryptoException;

$rawBody = file_get_contents('php://input');

try {
    $event = $client->parseWebhook($rawBody);
} catch (CryptoException) {
    http_response_code(400);
    exit;
}

if ($event->status === BillStatus::Paid) {
    // $event->tranId is the ShamCash payment id.
    // fulfillOrder($event->billNo, $event->tranId);
} elseif ($event->status === BillStatus::Expired) {
    // $event->tranId is null. The customer did not pay within 10 minutes.
    // releaseOrder($event->billNo);
}

http_response_code(200);
```

Apply that change once per `billNo` and status. ShamCash retries when it does not get HTTP 200 within 10 seconds, so a second delivery of the same event must not fulfill the order again. Return 200 before slow work such as sending email.

`parseWebhook()` rejects a token whose `exp` has passed or whose `iat` is too far in the future. The default clock skew is 30 seconds.

[examples/webhook.php](examples/webhook.php) is the full endpoint.

## Errors

Local mistakes, such as a bad amount or a missing URL, throw `InvalidArgumentException` before any HTTP call.

ShamCash business failures still use HTTP 200. They throw `ApiException`. Read `$exception->result` (`ResultCode`) and `$exception->resultCode`. HTTP 400, 404, 415, 500, timeouts, and invalid JSON throw `TransportException`. A bad token throws `CryptoException`.

A dropped response on create can still store the bill. The next attempt then returns result `1704`. Load the bill you already created:

```php
use OkToCode\ShamCash\Enum\ResultCode;
use OkToCode\ShamCash\Exception\ApiException;

try {
    $bill = $client->createBill(billNo: $billNo, amount: '10.50', currency: Currency::Usd);
} catch (ApiException $exception) {
    if ($exception->result !== ResultCode::BillNoAlreadyExists) {
        throw $exception;
    }

    $bill = $client->getBill($billNo);
}
```

See [examples/retry-create-bill.php](examples/retry-create-bill.php).

The SDK does not retry. `getBill()` and `listTransactions()` are safe to call again. Repeat `refundBill()` only with the same idempotency key.

## Development

```bash
composer install
composer test
composer phpstan
composer cs
```
