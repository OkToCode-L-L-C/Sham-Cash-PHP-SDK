<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Tests;

use OkToCode\ShamCash\Support\Clock;

final class FrozenClock implements Clock
{
    public function __construct(private readonly int $now)
    {
    }

    public function now(): int
    {
        return $this->now;
    }
}
