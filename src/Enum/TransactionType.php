<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Enum;

enum TransactionType: int
{
    case Payment = 1;
    case Refund = 2;
}
