<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Support;

/**
 * @internal
 */
interface Clock
{
    public function now(): int;
}
