<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Model;

use OkToCode\ShamCash\Enum\Currency;
use OkToCode\ShamCash\Enum\TransactionType;

final readonly class Transaction
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $billNo,
        public string $amount,
        public int $currencyId,
        public ?Currency $currency,
        public string $createdDate,
        public string $createdTime,
        public int $tranId,
        public int $tranTypeId,
        public ?TransactionType $tranType,
        public array $raw,
    ) {
    }
}
