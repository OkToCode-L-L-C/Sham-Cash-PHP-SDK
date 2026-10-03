<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Model;

final readonly class Refund
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $tranId,
        public string $amount,
        public string $createdDate,
        public string $createdTime,
        public string $rem,
        public array $raw,
    ) {
    }
}
