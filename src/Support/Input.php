<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Support;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * @internal
 */
final class Input
{
    public static function baseUrl(string $baseUrl): string
    {
        $url = self::absoluteUrl($baseUrl);
        $parts = parse_url($url);
        if (isset($parts['query']) || isset($parts['fragment'])) {
            throw new InvalidArgumentException('baseUrl must not include a query string or fragment.');
        }

        return rtrim($url, '/');
    }

    public static function absoluteUrl(string $url): string
    {
        $url = trim($url);
        if (self::length($url) > 500) {
            throw new InvalidArgumentException('URL must be at most 500 characters.');
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || !isset($parts['host']) || $parts['host'] === '') {
            throw new InvalidArgumentException('URL must be an absolute http or https URL.');
        }

        return $url;
    }

    public static function billNo(string $billNo): string
    {
        if (trim($billNo) === '') {
            throw new InvalidArgumentException('billNo is required.');
        }

        return $billNo;
    }

    public static function idempotencyKey(string $idempotencyKey): string
    {
        $length = self::length($idempotencyKey);
        if ($length < 10 || $length > 100) {
            throw new InvalidArgumentException('idempotencyKey must be between 10 and 100 characters.');
        }

        return $idempotencyKey;
    }

    public static function date(string $date): string
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $errors = DateTimeImmutable::getLastErrors();
        $hasErrors = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);
        if ($parsed === false || $hasErrors || $parsed->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Date must use the yyyy-MM-dd format.');
        }

        return $date;
    }

    public static function limit(int $limit): int
    {
        if ($limit < 10 || $limit > 2500) {
            throw new InvalidArgumentException('limit must be between 10 and 2500.');
        }

        return $limit;
    }

    public static function afterTranId(int $afterTranId): int
    {
        if ($afterTranId < 0) {
            throw new InvalidArgumentException('afterTranId must be 0 or greater.');
        }

        return $afterTranId;
    }

    private static function length(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, 'UTF-8');
        }

        return strlen($value);
    }
}
