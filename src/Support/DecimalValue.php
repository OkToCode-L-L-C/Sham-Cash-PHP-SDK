<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Support;

/**
 * A decimal literal that JSON encoding writes as a number, not a float or a string.
 *
 * @internal
 */
final class DecimalValue
{
    public function __construct(public readonly string $literal)
    {
    }
}
