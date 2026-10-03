<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Enum;

enum BillStatus: int
{
    case Pending = 1;
    case Refund = 2;
    case Expired = 3;
    case Paid = 4;
    case PartlyRefunded = 5;
}
