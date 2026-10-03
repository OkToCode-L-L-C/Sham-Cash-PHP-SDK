<?php

declare(strict_types=1);

/**
 * Refund part or all of a paid bill.
 *
 * idempotencyKey is required and must be 10 to 100 characters.
 * If this request times out, send the same key again. ShamCash returns the
 * original refund when that key already succeeded, and it does not lock the
 * key when the refund failed. The currency is the currency of the original bill.
 */

use OkToCode\ShamCash\Client;

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
);

$idempotencyKey = 'order-123-refund-1';

$refund = $client->refundBill(
    billNo: 'Order-123-1',
    amount: '10.50',
    idempotencyKey: $idempotencyKey,
    note: 'Customer return',
);

// $refund->tranId is the ShamCash id for this refund movement, not the original payment.
echo $refund->tranId . PHP_EOL;
