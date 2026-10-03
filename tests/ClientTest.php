<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Tests;

use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use OkToCode\ShamCash\Client;
use OkToCode\ShamCash\Crypto\DirectJwe;
use OkToCode\ShamCash\Enum\BillStatus;
use OkToCode\ShamCash\Enum\Currency;
use OkToCode\ShamCash\Enum\ResultCode;
use OkToCode\ShamCash\Enum\TransactionType;
use OkToCode\ShamCash\Exception\ApiException;
use OkToCode\ShamCash\Exception\CryptoException;
use OkToCode\ShamCash\Exception\TransportException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LogLevel;

final class ClientTest extends TestCase
{
    private const NOW = 1777643724;

    #[Test]
    public function itCreatesABillWithClientUrlDefaults(): void
    {
        $http = $this->http($this->billJson());
        $logger = new RecordingLogger();
        $bill = $this->client($http, $logger)->createBill('BN-123', '10.50', Currency::Usd, 'Order 123');

        self::assertSame('BN-123', $bill->billNo);
        self::assertSame('10.50', $bill->amount);
        self::assertSame(1, $bill->statusId);
        self::assertSame(BillStatus::Pending, $bill->status);
        self::assertSame(Currency::Usd, $bill->currency);
        self::assertNull($bill->tranId);
        self::assertSame([], $bill->refunds);
        self::assertSame('kept', $bill->raw['futureField']);
        self::assertSame('https://pay.shamcash.test/bills/BN-123', $bill->paymentUrl);

        $request = $http->requests[0];
        self::assertSame(
            'https://shamcash.test/root/api/ElectronicPayment/createBill',
            (string) $request->getUri(),
        );
        self::assertSame(Client::USER_AGENT, $request->getHeaderLine('User-Agent'));
        $plaintext = $this->plaintext($request);
        self::assertStringContainsString('"amount":10.50', $plaintext);
        self::assertStringContainsString('"iat":1777643724', $plaintext);
        self::assertStringContainsString('"exp":1777644024', $plaintext);
        self::assertStringContainsString('"callbackUrl":"https://agent.example/webhooks/shamcash"', $plaintext);
        self::assertStringContainsString('"redirectUrl":"https://agent.example/checkout/return"', $plaintext);
        self::assertSame('agent-key', $this->envelope($request)['agentKey']);

        $context = $logger->records[0]['context'];
        self::assertSame([
            'operation' => 'createBill',
            'http_status' => 200,
            'result' => 2500,
        ], $context);
        self::assertStringNotContainsString('a2tra2tra2tra2tra2tra2tra2tra2tra2s=', (string) json_encode($context));
        self::assertArrayNotHasKey('encData', $context);
    }

    #[Test]
    public function itWritesLargeAmountsAsJsonNumbers(): void
    {
        $http = $this->http($this->billJson('90071992547409.99'));
        $this->client($http)->createBill('BN-123', '90071992547409.99', Currency::Usd);

        self::assertStringContainsString('"amount":90071992547409.99', $this->plaintext($http->requests[0]));
    }

    #[Test]
    public function itUsesAPerCallUrlOverride(): void
    {
        $http = $this->http($this->billJson());
        $this->client($http)->createBill(
            billNo: 'BN-123',
            amount: '10.50',
            currency: Currency::Usd,
            redirectUrl: 'https://agent.example/orders/456/return',
        );

        $plaintext = $this->plaintext($http->requests[0]);
        self::assertStringContainsString('"callbackUrl":"https://agent.example/webhooks/shamcash"', $plaintext);
        self::assertStringContainsString('"redirectUrl":"https://agent.example/orders/456/return"', $plaintext);
    }

    #[Test]
    public function itRejectsABillWhenNeitherUrlIsSet(): void
    {
        $http = new FakeHttpClient([]);
        $client = new Client(
            agentKey: 'agent-key',
            secretKey: self::secret(),
            baseUrl: 'https://shamcash.test/root',
            httpClient: $http,
            clock: new FrozenClock(self::NOW),
        );

        $this->expectException(InvalidArgumentException::class);
        try {
            $client->createBill('BN-123', '10.50', Currency::Usd);
        } finally {
            self::assertSame([], $http->requests);
        }
    }

    #[Test]
    public function itMapsABillWithRefunds(): void
    {
        $refunds = <<<'JSON'
        [
          {
            "tranId": 3919459,
            "amount": 50.11,
            "createdDate": "2026-05-25",
            "createdTime": "12:43",
            "rem": "test refund 1"
          }
        ]
        JSON;
        $http = $this->http($this->billJson('5000.00', 5, 'partly refunded', 3827551, $refunds));
        $bill = $this->client($http)->getBill('BN-123');

        self::assertSame(BillStatus::PartlyRefunded, $bill->status);
        self::assertSame(3827551, $bill->tranId);
        self::assertSame('50.11', $bill->refunds[0]->amount);
        self::assertSame(3919459, $bill->refunds[0]->tranId);
        self::assertSame('test refund 1', $bill->refunds[0]->raw['rem']);
    }

    #[Test]
    public function itRefundsABillWhenTheIdempotencyKeyIsValid(): void
    {
        $http = $this->http(<<<'JSON'
        {
          "result": 2500,
          "succeeded": true,
          "data": {"tranId": 3919468},
          "message": "Success"
        }
        JSON);
        $result = $this->client($http)->refundBill('BN-123', '10.50', 'idempotency-key-1', 'Customer return');

        self::assertSame(3919468, $result->tranId);
        self::assertSame(3919468, $result->raw['tranId']);
        self::assertStringContainsString('"idempotencyKey":"idempotency-key-1"', $this->plaintext($http->requests[0]));
    }

    #[Test]
    public function itRejectsAShortIdempotencyKeyBeforeHttp(): void
    {
        $http = new FakeHttpClient([]);

        $this->expectException(InvalidArgumentException::class);
        try {
            $this->client($http)->refundBill('BN-123', '10.50', 'short');
        } finally {
            self::assertSame([], $http->requests);
        }
    }

    #[Test]
    public function itListsTransactionsAndFollowsTheCursor(): void
    {
        $http = new FakeHttpClient([
            new Response(200, [], $this->transactionJson(true, 3919445, 3827711)),
            new Response(200, [], $this->transactionJson(false, 3919500, 3919500)),
        ]);
        $seen = [];
        foreach ($this->client($http)->eachTransaction('2026-01-01', '2026-01-20', 1000) as $transaction) {
            $seen[] = $transaction->tranId;
        }

        self::assertSame([3827711, 3919500], $seen);
        self::assertSame(TransactionType::Payment, TransactionType::tryFrom(1));
        $second = $this->plaintext($http->requests[1]);
        self::assertStringContainsString('"afterTranId":3919445', $second);
        self::assertStringContainsString('"fromDate":"2026-01-01"', $second);
        self::assertStringContainsString('"limit":1000', $second);
    }

    #[Test]
    public function itThrowsApiExceptionForBusinessFailures(): void
    {
        $duplicate = $this->http($this->errorJson(1704, 'Bill No Already Exists'));
        try {
            $this->client($duplicate)->createBill('BN-123', '10.50', Currency::Usd);
            self::fail('Expected a duplicate bill error.');
        } catch (ApiException $exception) {
            self::assertSame(ResultCode::BillNoAlreadyExists, $exception->result);
            self::assertSame(1704, $exception->resultCode);
            self::assertSame('Bill No Already Exists', $exception->apiMessage);
        }

        $missing = $this->http($this->errorJson(1703, 'Bill Not Found'));
        try {
            $this->client($missing)->getBill('BN-404');
            self::fail('Expected a missing bill error.');
        } catch (ApiException $exception) {
            self::assertSame(ResultCode::BillNotFound, $exception->result);
        }
    }

    #[Test]
    public function itThrowsTransportExceptionForHttpFailures(): void
    {
        try {
            $this->client($this->http('{"error":"bad"}', 400))->createBill('BN-123', '10.50', Currency::Usd);
            self::fail('Expected HTTP 400.');
        } catch (TransportException $exception) {
            self::assertSame(400, $exception->statusCode);
        }

        try {
            $this->client($this->http('unavailable', 500))->getBill('BN-123');
            self::fail('Expected HTTP 500.');
        } catch (TransportException $exception) {
            self::assertSame(500, $exception->statusCode);
        }
    }

    #[Test]
    public function itParsesPaidAndExpiredWebhooks(): void
    {
        $client = $this->client(new FakeHttpClient([]));
        $paid = $client->parseWebhook($this->webhookBody([
            'billNo' => 'BN-123',
            'tranId' => 3580921,
            'statusId' => 4,
            'iat' => self::NOW,
            'exp' => self::NOW + 300,
        ]));
        self::assertSame(BillStatus::Paid, $paid->status);
        self::assertSame(3580921, $paid->tranId);

        $expired = $client->parseWebhook($this->webhookBody([
            'billNo' => 'BN-123',
            'tranId' => null,
            'statusId' => 3,
            'iat' => self::NOW,
            'exp' => self::NOW + 300,
        ]));
        self::assertSame(BillStatus::Expired, $expired->status);
        self::assertNull($expired->tranId);
    }

    #[Test]
    public function itRejectsAnExpiredWebhook(): void
    {
        $client = $this->client(new FakeHttpClient([]));

        $this->expectException(CryptoException::class);
        $client->parseWebhook($this->webhookBody([
            'billNo' => 'BN-123',
            'tranId' => 1,
            'statusId' => 4,
            'iat' => 1000,
            'exp' => 1300,
        ]));
    }

    #[Test]
    public function itRejectsAWebhookIssuedInTheFuture(): void
    {
        $client = $this->client(new FakeHttpClient([]));

        $this->expectException(CryptoException::class);
        $client->parseWebhook($this->webhookBody([
            'billNo' => 'BN-123',
            'tranId' => 1,
            'statusId' => 4,
            'iat' => self::NOW + 31,
            'exp' => self::NOW + 331,
        ]));
    }

    #[Test]
    public function itRejectsAWebhookWithoutEncData(): void
    {
        $this->expectException(CryptoException::class);
        $this->client(new FakeHttpClient([]))->parseWebhook('{}');
    }

    #[Test]
    public function itRejectsAWebhookThatIsNotDirectA256Gcm(): void
    {
        $header = rtrim(strtr(base64_encode('{"alg":"none","enc":"A256GCM","cty":"json"}'), '+/', '-_'), '=');
        $body = json_encode(['encData' => $header . '..aaaa.bbbb.cccc'], JSON_THROW_ON_ERROR);

        $this->expectException(CryptoException::class);
        $this->client(new FakeHttpClient([]))->parseWebhook($body);
    }

    #[Test]
    public function itRejectsAZeroAmountBeforeHttp(): void
    {
        $http = new FakeHttpClient([]);

        $this->expectException(InvalidArgumentException::class);
        try {
            $this->client($http)->createBill('BN-123', '0.00', Currency::Usd);
        } finally {
            self::assertSame([], $http->requests);
        }
    }

    #[Test]
    public function itKeepsALargeAmountExact(): void
    {
        $http = $this->http($this->billJson());
        $this->client($http)->createBill('BN-123', '9999999999999999.99', Currency::Usd);

        self::assertStringContainsString('"amount":9999999999999999.99', $this->plaintext($http->requests[0]));
    }

    #[Test]
    public function itTrimsCredentialsCopiedFromTheEnvironment(): void
    {
        $http = $this->http($this->billJson());
        $client = new Client(
            agentKey: " agent-key\n",
            secretKey: "\n" . self::secret() . "\n",
            baseUrl: 'https://shamcash.test/root',
            callbackUrl: 'https://agent.example/webhooks/shamcash',
            redirectUrl: 'https://agent.example/checkout/return',
            httpClient: $http,
            clock: new FrozenClock(self::NOW),
        );

        $bill = $client->createBill('BN-123', '10.50', Currency::Usd);

        self::assertSame('BN-123', $bill->billNo);
        self::assertSame('agent-key', $this->envelope($http->requests[0])['agentKey']);
    }

    #[Test]
    public function itWrapsANetworkFailureAsAWarning(): void
    {
        $logger = new RecordingLogger();

        try {
            $this->client(new FakeHttpClient([]), $logger)->getBill('BN-123');
            self::fail('Expected a transport failure.');
        } catch (TransportException $exception) {
            self::assertNull($exception->statusCode);
        }

        self::assertSame(LogLevel::WARNING, $logger->records[0]['level']);
        self::assertSame('getBill', $logger->records[0]['context']['operation']);
    }

    #[Test]
    public function itStopsWhenATransactionPageIsEmpty(): void
    {
        $body = <<<'JSON'
        {
          "result": 2500,
          "succeeded": true,
          "data": {"list": [], "hasMore": true, "lastReturnedTranId": 10},
          "message": "Success"
        }
        JSON;

        $this->expectException(TransportException::class);
        iterator_to_array($this->client($this->http($body))->eachTransaction());
    }

    #[Test]
    public function itRejectsATransactionIdThatDoesNotFit(): void
    {
        $json = str_replace('"tranId": null', '"tranId": 9999999999999999999', $this->billJson());

        $this->expectException(TransportException::class);
        $this->client($this->http($json))->getBill('BN-123');
    }

    private function client(FakeHttpClient $http, ?RecordingLogger $logger = null): Client
    {
        return new Client(
            agentKey: 'agent-key',
            secretKey: self::secret(),
            baseUrl: 'https://shamcash.test/root/',
            callbackUrl: 'https://agent.example/webhooks/shamcash',
            redirectUrl: 'https://agent.example/checkout/return',
            httpClient: $http,
            logger: $logger,
            clock: new FrozenClock(self::NOW),
        );
    }

    private function http(string $body, int $status = 200): FakeHttpClient
    {
        return new FakeHttpClient([new Response($status, [], $body)]);
    }

    private function plaintext(RequestInterface $request): string
    {
        return DirectJwe::fromSecret(self::secret())->decrypt($this->envelope($request)['encData']);
    }

    /** @return array{encData: string, agentKey: string} */
    private function envelope(RequestInterface $request): array
    {
        $decoded = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        /** @var array{encData: string, agentKey: string} $decoded */
        return $decoded;
    }

    private function billJson(
        string $amount = '10.50',
        int $statusId = 1,
        string $statusName = 'pending',
        ?int $tranId = null,
        string $refunds = '[]',
    ): string {
        $tran = $tranId === null ? 'null' : (string) $tranId;

        return <<<JSON
        {
          "result": 2500,
          "succeeded": true,
          "data": {
            "statusId": {$statusId},
            "statusName": "{$statusName}",
            "billNo": "BN-123",
            "amount": {$amount},
            "currencyId": 1,
            "createdDate": "2026-05-19",
            "createdTime": "14:37",
            "paymentUrl": "https://pay.shamcash.test/bills/BN-123",
            "rem": "Example",
            "tranId": {$tran},
            "refunds": {$refunds},
            "futureField": "kept"
          },
          "message": "Success"
        }
        JSON;
    }

    private function transactionJson(bool $hasMore, int $lastTranId, int $tranId): string
    {
        $more = $hasMore ? 'true' : 'false';

        return <<<JSON
        {
          "result": 2500,
          "succeeded": true,
          "data": {
            "list": [
              {
                "billNo": "BN-123",
                "amount": 5000.00,
                "currencyId": 2,
                "createdDate": "2026-05-19",
                "createdTime": "07:29",
                "tranId": {$tranId},
                "tranTypeId": 1,
                "tranType": "payment"
              }
            ],
            "hasMore": {$more},
            "lastReturnedTranId": {$lastTranId}
          },
          "message": "Success"
        }
        JSON;
    }

    private function errorJson(int $result, string $message): string
    {
        return <<<JSON
        {"result": {$result}, "succeeded": false, "data": null, "message": "{$message}"}
        JSON;
    }

    /** @param array<string, mixed> $payload */
    private function webhookBody(array $payload): string
    {
        $token = DirectJwe::fromSecret(self::secret())->encrypt(
            json_encode($payload, JSON_THROW_ON_ERROR),
        );

        return json_encode(['encData' => $token], JSON_THROW_ON_ERROR);
    }

    private static function secret(): string
    {
        return base64_encode(str_repeat('k', 32));
    }
}
