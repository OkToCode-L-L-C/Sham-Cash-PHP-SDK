<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Crypto;

use OkToCode\ShamCash\Exception\CryptoException;

/**
 * Direct JWE compact serialization using A256GCM.
 *
 * The protected header is {"alg":"dir","enc":"A256GCM","cty":"json"}.
 * The encrypted-key segment is empty. The IV is 12 bytes and the tag is 16 bytes.
 * Additional authenticated data is the ASCII protected-header segment.
 *
 * @internal
 */
final class DirectJwe
{
    public const PROTECTED_HEADER = '{"alg":"dir","enc":"A256GCM","cty":"json"}';

    private const IV_BYTES = 12;

    private const TAG_BYTES = 16;

    /**
     * @param \Closure(): string $ivGenerator
     */
    public function __construct(
        private readonly string $key,
        private readonly \Closure $ivGenerator,
    ) {
    }

    /**
     * @param \Closure(): string|null $ivGenerator
     */
    public static function fromSecret(string $secretKey, ?\Closure $ivGenerator = null): self
    {
        $key = base64_decode($secretKey, true);
        if ($key === false || strlen($key) !== 32) {
            throw new \InvalidArgumentException('secretKey must be a Base64-encoded 32-byte key.');
        }

        return new self($key, $ivGenerator ?? static fn (): string => random_bytes(self::IV_BYTES));
    }

    public function encrypt(string $plaintext): string
    {
        $iv = ($this->ivGenerator)();
        if (strlen($iv) !== self::IV_BYTES) {
            throw new CryptoException('AES-GCM IV must be 12 bytes.');
        }

        $encodedHeader = self::base64UrlEncode(self::PROTECTED_HEADER);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $encodedHeader,
            self::TAG_BYTES,
        );

        if ($ciphertext === false || strlen($tag) !== self::TAG_BYTES) {
            throw new CryptoException('Unable to encrypt the ShamCash payload.');
        }

        return $encodedHeader
            . '..'
            . self::base64UrlEncode($iv)
            . '.'
            . self::base64UrlEncode($ciphertext)
            . '.'
            . self::base64UrlEncode($tag);
    }

    public function decrypt(string $token): string
    {
        $parts = explode('.', $token);
        if (count($parts) !== 5 || $parts[1] !== '') {
            throw new CryptoException('ShamCash token must be a Direct JWE compact serialization.');
        }

        [$encodedHeader, , $encodedIv, $encodedCiphertext, $encodedTag] = $parts;
        $header = self::header($encodedHeader);
        if (
            ($header['alg'] ?? null) !== 'dir'
            || ($header['enc'] ?? null) !== 'A256GCM'
            || ($header['cty'] ?? null) !== 'json'
            || count($header) !== 3
        ) {
            throw new CryptoException('ShamCash token must use Direct A256GCM.');
        }

        $iv = self::base64UrlDecode($encodedIv);
        $ciphertext = self::base64UrlDecode($encodedCiphertext);
        $tag = self::base64UrlDecode($encodedTag);
        if (strlen($iv) !== self::IV_BYTES || strlen($tag) !== self::TAG_BYTES) {
            throw new CryptoException('ShamCash token has an invalid IV or authentication tag.');
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $encodedHeader,
        );

        if ($plaintext === false) {
            throw new CryptoException('Unable to decrypt the ShamCash payload.');
        }

        return $plaintext;
    }

    /**
     * @return array<string, mixed>
     */
    private static function header(string $encodedHeader): array
    {
        $json = self::base64UrlDecode($encodedHeader);
        $header = json_decode($json, true);
        if (!is_array($header) || array_is_list($header)) {
            throw new CryptoException('ShamCash token has an invalid protected header.');
        }

        /** @var array<string, mixed> $header */
        return $header;
    }

    private static function base64UrlEncode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        if ($data === '' || preg_match('/^[A-Za-z0-9_-]+$/', $data) !== 1) {
            throw new CryptoException('ShamCash token contains invalid base64url.');
        }

        $remainder = strlen($data) % 4;
        if ($remainder > 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        if ($decoded === false) {
            throw new CryptoException('ShamCash token contains invalid base64url.');
        }

        return $decoded;
    }
}
