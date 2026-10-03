<?php

declare(strict_types=1);

/**
 * Recover a createBill() call whose response was lost.
 *
 * Result 1704 means ShamCash already stored this billNo. Load that bill
 * instead of inventing a new number. callbackUrl and redirectUrl follow the
 * same rule as examples/create-bill.php: client defaults, overridable per call.
 */

use OkToCode\ShamCash\Client;
use OkToCode\ShamCash\Enum\Currency;
use OkToCode\ShamCash\Enum\ResultCode;
use OkToCode\ShamCash\Exception\ApiException;

require __DIR__ . '/../vendor/autoload.php';

$agentKey = getenv('SHAMCASH_AGENT_KEY');
$secretKey = getenv('SHAMCASH_SECRET_KEY');
$baseUrl = getenv('SHAMCASH_BASE_URL');
if (!is_string($agentKey) || !is_string($secretKey) || !is_string($baseUrl)) {
    fwrite(STDERR, "Set SHAMCASH_AGENT_KEY, SHAMCASH_SECRET_KEY, and SHAMCASH_BASE_URL.\n");
    exit(1);
}

$client = new Client(
    agentKey: $agentKey,
    secretKey: $secretKey,
    baseUrl: $baseUrl,
    callbackUrl: 'https://agent.example/webhooks/shamcash',
    redirectUrl: 'https://agent.example/checkout/return',
);

$billNo = 'Order-123-1';

try {
    // callbackUrl and redirectUrl are optional here.
    // Leave them out to use the client defaults, or pass one to override it for this bill.
    $bill = $client->createBill(
        billNo: $billNo,
        amount: '10.50',
        currency: Currency::Usd,
        callbackUrl: 'https://agent.example/webhooks/shamcash',
        redirectUrl: 'https://agent.example/orders/123/return',
    );
} catch (ApiException $exception) {
    if ($exception->result !== ResultCode::BillNoAlreadyExists) {
        throw $exception;
    }

    // The first request reached ShamCash. Fetch the bill that was stored.
    $bill = $client->getBill($billNo);
}

echo $bill->paymentUrl . PHP_EOL;
