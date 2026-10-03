<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Support;

use InvalidArgumentException;

/**
 * @internal
 */
final class Decimal
{
    public static function billAmount(string $amount): DecimalValue
    {
        return new DecimalValue(self::assert(
            $amount,
            'Bill amount must be at least 0.01 with at most 2 decimal places.',
        ));
    }

    public static function refundAmount(string $amount): DecimalValue
    {
        return new DecimalValue(self::assert(
            $amount,
            'Refund amount must be greater than 0 with at most 2 decimal places.',
        ));
    }

    private static function assert(string $amount, string $message): string
    {
        if (preg_match('/^(?:0|[1-9]\d{0,15})(?:\.\d{1,2})?$/', $amount) !== 1 || !self::hasValue($amount)) {
            throw new InvalidArgumentException($message);
        }

        return $amount;
    }

    private static function hasValue(string $amount): bool
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $cents = ltrim($whole . str_pad($fraction, 2, '0'), '0');

        return $cents !== '';
    }
}
