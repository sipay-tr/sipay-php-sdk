<?php

declare(strict_types=1);

namespace Sipay\Resources\Mapper;

use Sipay\Resources\NonSecurePayment;
use Sipay\TestCase;

class NonSecurePaymentMapperTest extends TestCase
{
    private const SECRET = 'test_secret';

    private function mapResponse(array $data): NonSecurePayment
    {
        $jsonResponse = json_encode([
            'status_code' => 100,
            'status_description' => 'Payment Successfully Completed',
            'data' => $data,
        ]);

        return (new NonSecurePaymentMapper($jsonResponse, new NonSecurePayment()))->mapJsonBody();
    }

    private function v2ResponseData(): array
    {
        return [
            'sipay_status' => 1,
            'order_no' => 'ORD-1',
            'order_id' => 'ORD-1',
            'invoice_id' => 'INV-1',
            'payment_status' => 1,
            'amount' => 150,
            'total' => '150',
            'currency_code' => 'TRY',
            'hash_key' => hash_hmac('sha256', '1|150|INV-1|ORD-1|TRY', self::SECRET),
        ];
    }

    public function testMapsTheFieldsAV2ResponseSigns(): void
    {
        $payment = $this->mapResponse($this->v2ResponseData());

        $this->assertSame('150', $payment->getTotal());
        $this->assertSame('TRY', $payment->getCurrencyCode());
    }

    public function testValidateHashKeyAcceptsAValidV2Response(): void
    {
        $this->assertTrue($this->mapResponse($this->v2ResponseData())->validateHashKey(self::SECRET));
    }

    public function testValidateHashKeyRejectsAChangedStatus(): void
    {
        $data = $this->v2ResponseData();
        $data['payment_status'] = 0;

        $this->assertFalse($this->mapResponse($data)->validateHashKey(self::SECRET));
    }

    public function testValidateHashKeyRejectsAnotherSecret(): void
    {
        $this->assertFalse($this->mapResponse($this->v2ResponseData())->validateHashKey('another_secret'));
    }

    public function testValidateHashKeyRejectsAResponseWithoutHash(): void
    {
        $data = $this->v2ResponseData();
        unset($data['hash_key']);

        $this->assertFalse($this->mapResponse($data)->validateHashKey(self::SECRET));
    }
}
