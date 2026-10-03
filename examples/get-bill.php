<?php

declare(strict_types=1);

/**
 * Read one bill when the webhook never arrived.
 *
 * Call this once, at least 10 minutes after createBill(). Do not poll it.
 * A network failure during create is different: if ShamCash returns 1704,
 * see examples/retry-create-bill.php.
 */

use OkToCode\ShamCash\Client;
use OkToCode\ShamCash\Enum\BillStatus;

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

$bill = $client->getBill('Order-123-1');

if ($bill->status === BillStatus::Paid) {
    // $bill->tranId is set only after a successful payment.
    echo $bill->billNo . ' paid ' . $bill->tranId . PHP_EOL;
} elseif ($bill->status === BillStatus::Expired) {
    echo $bill->billNo . ' expired' . PHP_EOL;
} else {
    echo $bill->billNo . ' ' . $bill->statusName . PHP_EOL;
}
