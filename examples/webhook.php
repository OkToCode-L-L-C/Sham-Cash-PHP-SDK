<?php

declare(strict_types=1);

/**
 * Endpoint hosted at the callbackUrl sent with createBill().
 *
 * ShamCash POSTs {"encData":"..."} here when a bill is paid or expired.
 * Pass the raw body through. Re-encoding the JSON can break decryption.
 */

use OkToCode\ShamCash\Client;
use OkToCode\ShamCash\Enum\BillStatus;
use OkToCode\ShamCash\Exception\CryptoException;

require __DIR__ . '/../vendor/autoload.php';

$agentKey = getenv('SHAMCASH_AGENT_KEY');
$secretKey = getenv('SHAMCASH_SECRET_KEY');
$baseUrl = getenv('SHAMCASH_BASE_URL');
if (!is_string($agentKey) || !is_string($secretKey) || !is_string($baseUrl)) {
    fwrite(STDERR, "Set SHAMCASH_AGENT_KEY, SHAMCASH_SECRET_KEY, and SHAMCASH_BASE_URL.\n");
    exit(1);
}

// php://input is the raw request. A framework should pass its raw body, not a re-encoded array.
$rawBody = file_get_contents('php://input');
if ($rawBody === false) {
    http_response_code(400);
    exit(1);
}

$client = new Client(
    agentKey: $agentKey,
    secretKey: $secretKey,
    baseUrl: $baseUrl,
);

try {
    $event = $client->parseWebhook($rawBody);
} catch (CryptoException) {
    // Reject an expired, future, or tampered token. Do not update the order.
    http_response_code(400);
    exit(1);
}

// ShamCash sends this callback only when the bill is paid or expired.
// Apply the change once per billNo and status. A retry of the same event must not run it again.
if ($event->status === BillStatus::Paid) {
    // $event->tranId is the ShamCash payment id. Fulfill that order.
    // fulfillOrder($event->billNo, $event->tranId);
} elseif ($event->status === BillStatus::Expired) {
    // $event->tranId is null. The customer did not pay within 10 minutes.
    // releaseOrder($event->billNo);
}

// Record the outcome, then return HTTP 200 before slow work such as email.
// ShamCash retries when this script does not respond within 10 seconds.
http_response_code(200);
echo 'OK';
