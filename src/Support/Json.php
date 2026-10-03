<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Support;

use JsonException;

/**
 * @internal
 */
final class Json
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function encode(array $payload): string
    {
        $markers = [];
        foreach ($payload as &$value) {
            self::replaceDecimals($value, $markers);
        }
        unset($value);

        try {
            $json = json_encode(
                $payload,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
        } catch (JsonException $exception) {
            throw new \InvalidArgumentException('Unable to encode the ShamCash payload.', 0, $exception);
        }

        return strtr($json, $markers);
    }

    /**
     * @return array<string, mixed>
     */
    public static function decodeObject(string $json): array
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new JsonException('JSON value must be an object.');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    public static function decodeObjectPreservingNumbers(string $json): array
    {
        $converted = preg_replace_callback(
            '/"(?:\\\\.|[^"\\\\])*"|-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?/',
            static function (array $match): string {
                $token = $match[0];
                if (str_starts_with($token, '"')) {
                    return $token;
                }

                return '"' . $token . '"';
            },
            $json,
        );

        if (!is_string($converted)) {
            throw new JsonException('JSON value must be an object.');
        }

        return self::decodeObject($converted);
    }

    /**
     * @param array<string, string> $markers
     */
    private static function replaceDecimals(mixed &$value, array &$markers): void
    {
        if ($value instanceof DecimalValue) {
            $marker = '__D' . bin2hex(random_bytes(8));
            $markers['"' . $marker . '"'] = $value->literal;
            $value = $marker;

            return;
        }

        if (!is_array($value)) {
            return;
        }

        foreach ($value as &$item) {
            self::replaceDecimals($item, $markers);
        }
        unset($item);
    }
}
