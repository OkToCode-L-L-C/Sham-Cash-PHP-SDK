<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Tests;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class FakeHttpClient implements ClientInterface
{
    /** @var list<RequestInterface> */
    public array $requests = [];

    /** @param list<ResponseInterface> $responses */
    public function __construct(private array $responses)
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        $response = array_shift($this->responses);
        if (!$response instanceof ResponseInterface) {
            throw new FakeNetworkException('No response queued.');
        }

        return $response;
    }
}
