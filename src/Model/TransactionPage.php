<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Model;

final readonly class TransactionPage
{
    /**
     * @param list<Transaction> $transactions
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public array $transactions,
        public bool $hasMore,
        public int $lastReturnedTranId,
        public array $raw,
    ) {
    }
}
