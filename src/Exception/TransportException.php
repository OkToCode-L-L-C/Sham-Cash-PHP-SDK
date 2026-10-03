<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Exception;

final class TransportException extends ShamCashException
{
    public function __construct(string $message, public readonly ?int $statusCode = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }
}
