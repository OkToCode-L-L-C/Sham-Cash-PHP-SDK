<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Http;

use JsonException;
use OkToCode\ShamCash\Client;
use OkToCode\ShamCash\Enum\ResultCode;
use OkToCode\ShamCash\Exception\ApiException;
use OkToCode\ShamCash\Exception\TransportException;
use OkToCode\ShamCash\Support\Json;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final class Transport
{
    public function __construct(
        private readonly ClientInterface $http,
        private readonly RequestFactoryInterface&StreamFactoryInterface $messages,
        private readonly string $baseUrl,
        private readonly string $agentKey,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{raw: array<string, mixed>, preserved: array<string, mixed>}
     */
    public function post(string $operation, string $action, string $encData): array
    {
        $body = Json::encode([
            'encData' => $encData,
            'agentKey' => $this->agentKey,
        ]);
        $request = $this->messages
            ->createRequest('POST', $this->baseUrl . '/api/ElectronicPayment/' . $action)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('User-Agent', Client::USER_AGENT)
            ->withBody($this->messages->createStream($body));

        try {
            $response = $this->http->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
            $this->log($operation, null, null, true);

            throw new TransportException('ShamCash request could not be completed.', null, $exception);
        }

        $status = $response->getStatusCode();
        if ($status !== 200) {
            $this->log($operation, $status, null, true);

            throw new TransportException(
                sprintf('ShamCash returned HTTP %d for %s.', $status, $operation),
                $status,
            );
        }

        try {
            $rawBody = (string) $response->getBody();
            $decoded = Json::decodeObject($rawBody);
            $preserved = Json::decodeObjectPreservingNumbers($rawBody);
        } catch (JsonException $exception) {
            $this->log($operation, $status, null, true);

            throw new TransportException('ShamCash response was not a JSON object.', $status, $exception);
        }

        if (!is_int($decoded['result'] ?? null) || !is_bool($decoded['succeeded'] ?? null)) {
            $this->log($operation, $status, null, true);

            throw new TransportException('ShamCash response is missing result.', $status);
        }

        $result = $decoded['result'];
        $message = is_string($decoded['message'] ?? null) ? $decoded['message'] : '';
        $this->log($operation, $status, $result);

        if ($decoded['succeeded'] !== true || $result !== ResultCode::Success->value) {
            throw new ApiException($result, ResultCode::tryFrom($result), $message, $decoded);
        }

        return ['raw' => $decoded, 'preserved' => $preserved];
    }

    private function log(string $operation, ?int $httpStatus, ?int $result, bool $failed = false): void
    {
        $context = [
            'operation' => $operation,
            'http_status' => $httpStatus,
            'result' => $result,
        ];

        if ($failed) {
            $this->logger->warning('ShamCash request failed', $context);

            return;
        }

        $this->logger->info('ShamCash request completed', $context);
    }
}
