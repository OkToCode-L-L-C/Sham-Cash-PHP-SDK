<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Model;

use OkToCode\ShamCash\Enum\BillStatus;
use OkToCode\ShamCash\Enum\Currency;

final readonly class Bill
{
    /**
     * @param list<Refund> $refunds
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $statusId,
        public ?BillStatus $status,
        public string $statusName,
        public string $billNo,
        public string $amount,
        public int $currencyId,
        public ?Currency $currency,
        public string $createdDate,
        public string $createdTime,
        public ?int $tranId,
        public string $paymentUrl,
        public string $rem,
        public array $refunds,
        public array $raw,
    ) {
    }
}
