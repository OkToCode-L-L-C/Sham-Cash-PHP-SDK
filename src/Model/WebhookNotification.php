<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Model;

use OkToCode\ShamCash\Enum\BillStatus;

final readonly class WebhookNotification
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $billNo,
        public ?int $tranId,
        public int $statusId,
        public ?BillStatus $status,
        public int $iat,
        public int $exp,
        public array $raw,
    ) {
    }
}
