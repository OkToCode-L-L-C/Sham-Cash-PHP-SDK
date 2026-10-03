<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Enum;

enum ResultCode: int
{
    case GeneralError = 1000;
    case BalanceNotEnough = 1304;
    case ReceiverBlocked = 1322;
    case EpayCustomError = 1700;
    case AgentKeyInvalid = 1701;
    case AgentNotActive = 1702;
    case BillNotFound = 1703;
    case BillNoAlreadyExists = 1704;
    case EncryptedDataInvalid = 1705;
    case AccountBlocked = 1706;
    case OriginBillNotPaid = 1707;
    case RefundExceedsBalance = 1708;
    case Success = 2500;
}
