<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Support;

/**
 * @internal
 */
final class SystemClock implements Clock
{
    public function now(): int
    {
        return time();
    }
}
