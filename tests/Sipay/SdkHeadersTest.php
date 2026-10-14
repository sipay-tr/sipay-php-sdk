<?php

declare(strict_types=1);

namespace Sipay;

use Sipay\Resources\Token;

class SdkHeadersTest extends TestCase
{
    /** @dataProvider requestModes */
    public function testSdkHeadersReachHttpClient(string $method, bool $withAuth): void
    {
        $options = $this->createTestOptions();
        $client = $this->createMockHttpClient();
        $response = $this->createMockResponse(['status_code' => 100]);
        $client->expects($this->once())->method($method)
            ->with(
                'https://provisioning.sipay.com.tr/ccpayment/api/test',
                $this->callback(function (array $headers) use ($withAuth): bool {
                    $this->assertContains('X-Sipay-Plugin: php-sdk', $headers);
                    $this->assertContains('X-Sipay-Plugin-Version: ' . Sipay::VERSION, $headers);
                    $this->assertContains('Accept: application/json', $headers);
                    $this->assertContains('Content-type: application/json', $headers);
                    $this->assertContains('X-Custom: value', $headers);
                    $this->assertSame($withAuth, in_array('Authorization: test-token', $headers, true));

                    return true;
                }),
                ['test' => 'payload'],
            )->willReturn($response);

        $resource = new class ($options, $client) extends SipayResource {
            public function send(string $method, RequestPayload $payload, bool $withAuth)
            {
                return $this->request($method, '/api/test', $payload, ['X-Custom: value'], $withAuth);
            }

            protected function getAuthenticationToken(): Token
            {
                return (new Token())->setToken('test-token');
            }
        };
        $payload = $this->createMock(RequestPayload::class);
        $payload->method('getJsonObject')->willReturn(['test' => 'payload']);
        $this->assertSame($response, $resource->send($method, $payload, $withAuth));
    }

    public function requestModes(): array
    {
        return [
            ['get', false], ['get', true],
            ['post', false], ['post', true],
            ['put', false], ['put', true],
            ['delete', false], ['delete', true],
        ];
    }
}
