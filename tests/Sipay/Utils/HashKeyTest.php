<?php

declare(strict_types=1);

namespace Sipay\Utils;

use Sipay\Exceptions\InvalidArgumentException;
use Sipay\TestCase;

class HashKeyTest extends TestCase
{
    private const SECRET = 'example_app_secret_0123456789abcd';

    private const OTHER_SECRET = 'another_app_secret_0123456789abcd';

    /**
     * The fixed vector from the PFINT-1202 test cases, shared with Sipay's reference implementations.
     */
    public function testGenerateHashKeyV2MatchesTheReferenceVector(): void
    {
        $hashKey = HashKey::generateHashKeyV2(['100.0000', 1, 'TRY', '$2y$10$EXAMPLEmerchantKey', 'INV-1'], self::SECRET);

        $this->assertSame('8ff8d35824948e3279608f69748bab5f6d5f2a4577b57344935e2898c9dd8674', $hashKey);
    }

    public function testGenerateHashKeyV2SignsNullAsAnEmptyString(): void
    {
        $this->assertSame(
            hash_hmac('sha256', '|INV-1|merchant', self::SECRET),
            HashKey::generateHashKeyV2([null, 'INV-1', 'merchant'], self::SECRET)
        );
    }

    public function testGenerateHashKeyV2SignsAmountsExactlyAsGiven(): void
    {
        $this->assertNotSame(
            HashKey::generateHashKeyV2(['100.00', 'INV-1'], self::SECRET),
            HashKey::generateHashKeyV2(['100', 'INV-1'], self::SECRET)
        );
    }

    public function testGenerateHashKeyV2RejectsAValueWithTheSeparator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A value signed in the hash key must not contain "|".');

        HashKey::generateHashKeyV2(['100.00', 'INV|1'], self::SECRET);
    }

    public function testGenerateHashKeyV2RejectsANonScalarValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        HashKey::generateHashKeyV2([['merchant']], self::SECRET);
    }

    public function testValidateHashKeyV2AcceptsAValidHash(): void
    {
        $parts = ['100.00', 1, 'TRY', 'merchant', 'INV-1'];
        $hashKey = HashKey::generateHashKeyV2($parts, self::SECRET);

        $this->assertTrue(HashKey::validateHashKeyV2($hashKey, $parts, self::SECRET));
        $this->assertTrue(HashKey::validateHashKeyV2(strtoupper($hashKey), $parts, self::SECRET));
    }

    public function testValidateHashKeyV2RejectsAChangedField(): void
    {
        $hashKey = HashKey::generateHashKeyV2(['100.00', 'INV-1'], self::SECRET);

        $this->assertFalse(HashKey::validateHashKeyV2($hashKey, ['100.01', 'INV-1'], self::SECRET));
        $this->assertFalse(HashKey::validateHashKeyV2($hashKey, ['INV-1', '100.00'], self::SECRET));
    }

    public function testValidateHashKeyV2RejectsAnotherSecret(): void
    {
        $hashKey = HashKey::generateHashKeyV2(['100.00', 'INV-1'], self::OTHER_SECRET);

        $this->assertFalse(HashKey::validateHashKeyV2($hashKey, ['100.00', 'INV-1'], self::SECRET));
    }

    public function brokenHashProvider(): array
    {
        $hashKey = HashKey::generateHashKeyV2(['100.00', 'INV-1'], self::SECRET);
        $flipped = ($hashKey[0] === 'a' ? 'b' : 'a') . substr($hashKey, 1);

        return [
            'one hex digit changed' => [$flipped],
            '63 characters' => [substr($hashKey, 0, 63)],
            '65 characters' => [$hashKey . 'a'],
            'non-hex character' => ['g' . substr($hashKey, 1)],
            'empty' => [''],
            'v1 bundle' => [HashKey::generateHashKey(['100.00', 'INV-1'], self::SECRET)],
        ];
    }

    /**
     * @dataProvider brokenHashProvider
     */
    public function testValidateHashKeyV2RejectsABrokenHash(string $hashKey): void
    {
        $this->assertFalse(HashKey::validateHashKeyV2($hashKey, ['100.00', 'INV-1'], self::SECRET));
    }

    public function testValidateHashKeyV2RejectsAReceivedValueWithTheSeparator(): void
    {
        $hashKey = HashKey::generateHashKeyV2(['100.00', 'INV', '1'], self::SECRET);

        $this->assertFalse(HashKey::validateHashKeyV2($hashKey, ['100.00', 'INV|1'], self::SECRET));
    }

    public function testValidateResponseHashKeyChecksAPaymentResponse(): void
    {
        $response = [
            'payment_status' => '1',
            'total' => '150',
            'invoice_id' => 'INV-1',
            'order_id' => 'ORD-1',
            'currency_code' => 'TRY',
            'status_description' => 'not signed',
        ];
        $response['hash_key'] = hash_hmac('sha256', '1|150|INV-1|ORD-1|TRY', self::SECRET);

        $this->assertTrue(HashKey::validateResponseHashKey($response, self::SECRET));

        $response['payment_status'] = '0';
        $this->assertFalse(HashKey::validateResponseHashKey($response, self::SECRET));
    }

    public function testValidateResponseHashKeyChecksASaleWebhook(): void
    {
        $webhook = [
            'status' => 'Completed',
            'total' => '150.00',
            'invoice_id' => 'INV-1',
            'order_id' => 'ORD-1',
            'currency_code' => 'TRY',
            'hash_key' => hash_hmac('sha256', 'Completed|150.00|INV-1|ORD-1|TRY', self::SECRET),
        ];

        $this->assertTrue(HashKey::validateResponseHashKey($webhook, self::SECRET, HashKey::SALE_WEBHOOK_FIELDS));
        $this->assertFalse(HashKey::validateResponseHashKey($webhook, self::SECRET));
    }

    public function testValidateResponseHashKeySignsAMissingFieldAsAnEmptyString(): void
    {
        $response = [
            'payment_status' => 1,
            'total' => '150',
            'invoice_id' => 'INV-1',
            'currency_code' => 'TRY',
            'hash_key' => hash_hmac('sha256', '1|150|INV-1||TRY', self::SECRET),
        ];

        $this->assertTrue(HashKey::validateResponseHashKey($response, self::SECRET));
    }

    public function invalidResponseProvider(): array
    {
        $fields = ['payment_status' => '1', 'total' => '150', 'invoice_id' => 'INV-1', 'order_id' => 'ORD-1', 'currency_code' => 'TRY'];
        $hashKey = hash_hmac('sha256', '1|150|INV-1|ORD-1|TRY', self::SECRET);

        return [
            'no hash_key' => [$fields],
            'hash_key is not a string' => [$fields + ['hash_key' => [$hashKey]]],
            'a signed field is an array' => [['total' => ['150']] + $fields + ['hash_key' => $hashKey]],
            'v1 hash_key' => [$fields + ['hash_key' => HashKey::generateHashKey(array_values($fields), self::SECRET)]],
        ];
    }

    /**
     * @dataProvider invalidResponseProvider
     */
    public function testValidateResponseHashKeyRejectsAnInvalidResponse(array $response): void
    {
        $this->assertFalse(HashKey::validateResponseHashKey($response, self::SECRET));
    }

    public function testIsV2TellsTheVersionsApartByShape(): void
    {
        $this->assertTrue(HashKey::isV2(str_repeat('a', 64)));
        $this->assertTrue(HashKey::isV2(str_repeat('A', 64)));
        $this->assertFalse(HashKey::isV2(HashKey::generateHashKey(['1', '2'], self::SECRET)));
    }

    public function testDeprecatedV1ValidationStillDecryptsAV1ResponseHash(): void
    {
        $hashKey = HashKey::generateHashKey(['1', '100.00', 'INV-1', 'ORD-1', 'TRY'], self::SECRET);

        $this->assertSame(['1', '100.00', 'INV-1', 'ORD-1', 'TRY'], HashKey::validateHashKey($hashKey, self::SECRET));
    }
}
