<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Model;

final readonly class RefundResult
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $tranId,
        public array $raw,
    ) {
    }
}
