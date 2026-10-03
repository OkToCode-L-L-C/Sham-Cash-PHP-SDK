<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Support;

use OkToCode\ShamCash\Exception\CryptoException;
use OkToCode\ShamCash\Exception\ShamCashException;
use OkToCode\ShamCash\Exception\TransportException;

/**
 * @internal
 */
final class Fields
{
    /**
     * @param array<string, mixed> $data
     */
    public static function text(array $data, string $key, bool $webhook = false): string
    {
        $value = $data[$key] ?? null;
        if (!is_string($value)) {
            throw self::missing($key, $webhook);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function decimal(array $data, string $key): string
    {
        $value = $data[$key] ?? null;
        if (is_int($value)) {
            return (string) $value;
        }

        if (is_string($value) && preg_match('/^-?\d+(?:\.\d+)?$/', $value) === 1) {
            return $value;
        }

        throw self::missing($key, false);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function int(array $data, string $key, bool $webhook = false): int
    {
        $value = self::nullableInt($data, $key, $webhook);
        if ($value === null) {
            throw self::missing($key, $webhook);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function nullableInt(array $data, string $key, bool $webhook = false): ?int
    {
        if (!array_key_exists($key, $data) || $data[$key] === null) {
            return null;
        }

        $value = $data[$key];
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            $integer = (int) $value;
            if ((string) $integer === $value) {
                return $integer;
            }

            throw self::missing($key, $webhook);
        }

        throw self::missing($key, $webhook);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function bool(array $data, string $key): bool
    {
        $value = $data[$key] ?? null;
        if (!is_bool($value)) {
            throw self::missing($key, false);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<array<string, mixed>>
     */
    public static function objectList(array $data, string $key): array
    {
        $value = $data[$key] ?? [];
        if (!is_array($value) || !array_is_list($value)) {
            throw self::missing($key, false);
        }

        $objects = [];
        foreach ($value as $item) {
            if (!is_array($item) || array_is_list($item)) {
                throw self::missing($key, false);
            }

            /** @var array<string, mixed> $item */
            $objects[] = $item;
        }

        return $objects;
    }

    private static function missing(string $key, bool $webhook): ShamCashException
    {
        $message = sprintf('ShamCash response is missing a valid %s.', $key);
        if ($webhook) {
            return new CryptoException($message);
        }

        return new TransportException($message);
    }
}
