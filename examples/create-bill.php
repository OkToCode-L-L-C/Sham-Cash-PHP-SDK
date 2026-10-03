<?php

declare(strict_types=1);

/**
 * Create a ShamCash bill and print the payment URL.
 *
 * callbackUrl and redirectUrl on the client are defaults for every bill.
 * Pass either one to createBill() to override that default for a single bill.
 * The value on the call wins. ShamCash still receives both URLs.
 */

use OkToCode\ShamCash\Client;
use OkToCode\ShamCash\Enum\Currency;

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
    // Webhook endpoint ShamCash calls when the bill is paid or expired.
    callbackUrl: 'https://agent.example/webhooks/shamcash',
    // Browser return URL. ShamCash does not append the bill number.
    redirectUrl: 'https://agent.example/checkout/return',
);

// No callbackUrl or redirectUrl here, so both client defaults are used.
$bill = $client->createBill(
    billNo: 'Order-123-1',
    amount: '10.50',
    currency: Currency::Usd,
    note: 'Order 123',
);

// Optional overrides for this bill only. Omit a URL to keep the client default.
$billForThisOrder = $client->createBill(
    billNo: 'Order-456-1',
    amount: '20.00',
    currency: Currency::Syp,
    callbackUrl: 'https://agent.example/webhooks/shamcash/orders',
    redirectUrl: 'https://agent.example/orders/456/return',
);

// Send the customer to paymentUrl in the system browser.
echo $bill->paymentUrl . PHP_EOL;
echo $billForThisOrder->paymentUrl . PHP_EOL;
