<?php

declare(strict_types=1);

namespace OkToCode\ShamCash\Tests\Crypto;

use InvalidArgumentException;
use OkToCode\ShamCash\Crypto\DirectJwe;
use OkToCode\ShamCash\Exception\CryptoException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DirectJweTest extends TestCase
{
    private const KEY = 'kkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkk';

    #[Test]
    public function itRoundTripsADirectJweWithAFixedIv(): void
    {
        $iv = str_repeat("\x01", 12);
        $jwe = new DirectJwe(self::KEY, static fn (): string => $iv);
        $token = $jwe->encrypt('{"billNo":"BN-123"}');
        $parts = explode('.', $token);

        self::assertCount(5, $parts);
        self::assertSame('', $parts[1]);
        self::assertSame(self::base64Url(DirectJwe::PROTECTED_HEADER), $parts[0]);
        self::assertSame(self::base64Url($iv), $parts[2]);
        self::assertSame('{"billNo":"BN-123"}', $jwe->decrypt($token));
    }

    #[Test]
    public function itRejectsATamperedCiphertext(): void
    {
        $jwe = $this->jwe();
        $parts = explode('.', $jwe->encrypt('{"billNo":"BN-123"}'));
        $parts[3] = ($parts[3][0] === 'A' ? 'B' : 'A') . substr($parts[3], 1);

        $this->expectException(CryptoException::class);
        $jwe->decrypt(implode('.', $parts));
    }

    #[Test]
    public function itRejectsTheWrongKey(): void
    {
        $token = $this->jwe()->encrypt('{"billNo":"BN-123"}');
        $other = new DirectJwe(str_repeat('z', 32), static fn (): string => str_repeat("\x01", 12));

        $this->expectException(CryptoException::class);
        $other->decrypt($token);
    }

    #[Test]
    public function itRejectsASecretThatIsNot32Bytes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DirectJwe::fromSecret(base64_encode('short'));
    }

    #[Test]
    public function itRejectsATokenThatIsNotDirectA256Gcm(): void
    {
        $header = self::base64Url('{"alg":"none","enc":"A256GCM","cty":"json"}');

        $this->expectException(CryptoException::class);
        $this->jwe()->decrypt($header . '..aaaa.bbbb.cccc');
    }

    #[Test]
    public function itRejectsANonEmptyEncryptedKeySegment(): void
    {
        $header = self::base64Url(DirectJwe::PROTECTED_HEADER);

        $this->expectException(CryptoException::class);
        $this->jwe()->decrypt($header . '.key.aaaa.bbbb.cccc');
    }

    private function jwe(): DirectJwe
    {
        return new DirectJwe(self::KEY, static fn (): string => str_repeat("\x01", 12));
    }

    private static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
