<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Support;

use OkToCode\ShamCash\Enum\BillStatus;
use OkToCode\ShamCash\Enum\Currency;
use OkToCode\ShamCash\Enum\TransactionType;
use OkToCode\ShamCash\Exception\TransportException;
use OkToCode\ShamCash\Model\Bill;
use OkToCode\ShamCash\Model\Refund;
use OkToCode\ShamCash\Model\RefundResult;
use OkToCode\ShamCash\Model\Transaction;
use OkToCode\ShamCash\Model\TransactionPage;
use OkToCode\ShamCash\Model\WebhookNotification;

/**
 * @internal
 */
final class ResponseMapper
{
    /**
     * @param array<string, mixed> $envelope
     * @param array<string, mixed> $preservedEnvelope
     */
    public static function bill(array $envelope, array $preservedEnvelope): Bill
    {
        $raw = self::dataObject($envelope);
        $data = self::dataObject($preservedEnvelope);
        $statusId = Fields::int($data, 'statusId');
        $currencyId = Fields::int($data, 'currencyId');
        $preservedRefunds = Fields::objectList($data, 'refunds');
        $rawRefunds = Fields::objectList($raw, 'refunds');
        if (count($preservedRefunds) !== count($rawRefunds)) {
            throw new TransportException('ShamCash response refunds could not be read.');
        }

        $refunds = [];
        foreach ($preservedRefunds as $index => $refund) {
            $refunds[] = new Refund(
                Fields::int($refund, 'tranId'),
                Fields::decimal($refund, 'amount'),
                Fields::text($refund, 'createdDate'),
                Fields::text($refund, 'createdTime'),
                Fields::text($refund, 'rem'),
                $rawRefunds[$index],
            );
        }

        return new Bill(
            $statusId,
            BillStatus::tryFrom($statusId),
            Fields::text($data, 'statusName'),
            Fields::text($data, 'billNo'),
            Fields::decimal($data, 'amount'),
            $currencyId,
            Currency::tryFrom($currencyId),
            Fields::text($data, 'createdDate'),
            Fields::text($data, 'createdTime'),
            Fields::nullableInt($data, 'tranId'),
            Fields::text($data, 'paymentUrl'),
            Fields::text($data, 'rem'),
            $refunds,
            $raw,
        );
    }

    /**
     * @param array<string, mixed> $envelope
     * @param array<string, mixed> $preservedEnvelope
     */
    public static function refundResult(array $envelope, array $preservedEnvelope): RefundResult
    {
        $raw = self::dataObject($envelope);
        $data = self::dataObject($preservedEnvelope);

        return new RefundResult(Fields::int($data, 'tranId'), $raw);
    }

    /**
     * @param array<string, mixed> $envelope
     * @param array<string, mixed> $preservedEnvelope
     */
    public static function transactionPage(array $envelope, array $preservedEnvelope): TransactionPage
    {
        $raw = self::dataObject($envelope);
        $data = self::dataObject($preservedEnvelope);
        $preservedRows = Fields::objectList($data, 'list');
        $rawRows = Fields::objectList($raw, 'list');
        if (count($preservedRows) !== count($rawRows)) {
            throw new TransportException('ShamCash response transactions could not be read.');
        }

        $transactions = [];
        foreach ($preservedRows as $index => $row) {
            $currencyId = Fields::int($row, 'currencyId');
            $tranTypeId = Fields::int($row, 'tranTypeId');
            $transactions[] = new Transaction(
                Fields::text($row, 'billNo'),
                Fields::decimal($row, 'amount'),
                $currencyId,
                Currency::tryFrom($currencyId),
                Fields::text($row, 'createdDate'),
                Fields::text($row, 'createdTime'),
                Fields::int($row, 'tranId'),
                $tranTypeId,
                TransactionType::tryFrom($tranTypeId),
                $rawRows[$index],
            );
        }

        return new TransactionPage(
            $transactions,
            Fields::bool($data, 'hasMore'),
            Fields::int($data, 'lastReturnedTranId'),
            $raw,
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $raw
     */
    public static function webhook(array $payload, array $raw): WebhookNotification
    {
        $statusId = Fields::int($payload, 'statusId', true);

        return new WebhookNotification(
            Fields::text($payload, 'billNo', true),
            Fields::nullableInt($payload, 'tranId', true),
            $statusId,
            BillStatus::tryFrom($statusId),
            Fields::int($payload, 'iat', true),
            Fields::int($payload, 'exp', true),
            $raw,
        );
    }

    /**
     * @param array<string, mixed> $envelope
     *
     * @return array<string, mixed>
     */
    private static function dataObject(array $envelope): array
    {
        $data = $envelope['data'] ?? null;
        if (!is_array($data) || array_is_list($data)) {
            throw new TransportException('ShamCash success response did not include a data object.');
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}
