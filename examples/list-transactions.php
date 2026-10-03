<?php

declare(strict_types=1);

/**
 * Read the ShamCash ledger for a date range.
 *
 * listTransactions() returns one page. Pass lastReturnedTranId as afterTranId
 * to fetch the next page. eachTransaction() follows that cursor for you.
 * limit must be from 10 to 2500. The default is 500.
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

$fromDate = '2026-01-01';
$toDate = '2026-01-20';

// One page. afterTranId 0 starts at the beginning of the range.
$page = $client->listTransactions($fromDate, $toDate, afterTranId: 0, limit: 1000);

foreach ($page->transactions as $transaction) {
    $type = $transaction->tranType === null ? (string) $transaction->tranTypeId : $transaction->tranType->name;
    echo $transaction->tranId . ' ' . $type . ' ' . $transaction->billNo . ' ' . $transaction->amount . PHP_EOL;
}

// When hasMore is true, the next page starts after the last id from this page.
if ($page->hasMore) {
    $nextPage = $client->listTransactions(
        $fromDate,
        $toDate,
        afterTranId: $page->lastReturnedTranId,
        limit: 1000,
    );
    echo 'Next page starts after ' . $nextPage->lastReturnedTranId . PHP_EOL;
}

// Or skip the manual cursor and use this loop instead. It reads the same range again.
foreach ($client->eachTransaction($fromDate, $toDate, limit: 1000) as $transaction) {
    echo $transaction->billNo . ' ' . $transaction->amount . PHP_EOL;
}
