<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Tests;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

final class FakeNetworkException extends RuntimeException implements ClientExceptionInterface
{
}
