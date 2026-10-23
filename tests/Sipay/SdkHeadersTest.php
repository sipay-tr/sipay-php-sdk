<?php

declare(strict_types=1);

namespace Sipay;

class SdkHeadersTest extends TestCase
{
    private const API_URL = 'https://provisioning.sipay.com.tr/ccpayment/api/test';

    private const TOKEN_URL = 'https://provisioning.sipay.com.tr/ccpayment/api/token';

    private $requests = [];

    /** @dataProvider requestModes */
    public function testSdkHeadersReachHttpClient(string $method, bool $withAuth): void
    {
        $this->send($method, ['X-Custom: value'], $withAuth);

        $expected = [[$method, self::API_URL, ['test' => 'payload']]];
        if ($withAuth) {
            array_unshift($expected, ['post', self::TOKEN_URL, ['app_id' => 'test_key', 'app_secret' => 'test_secret']]);
        }
        $this->assertSame($expected, array_map(function (array $request): array {
            return [$request[0], $request[1], $request[3]];
        }, $this->requests));

        foreach ($this->requests as [, , $headers]) {
            $this->assertHeaderSentOnce('X-Sipay-Plugin: php-sdk', $headers);
            $this->assertHeaderSentOnce('X-Sipay-Plugin-Version: ' . Sipay::VERSION, $headers);
            $this->assertHeaderSentOnce('User-Agent: sipay-php-sdk/' . Sipay::VERSION . ' PHP/' . PHP_VERSION, $headers);
            $this->assertHeaderSentOnce('Accept: application/json', $headers);
            $this->assertHeaderSentOnce('Content-type: application/json', $headers);
        }

        [, , $tokenHeaders] = $this->requests[0];
        [, , $apiHeaders] = end($this->requests);
        $this->assertHeaderSentOnce('X-Custom: value', $apiHeaders);
        $this->assertSame($withAuth, in_array('Authorization: test-token', $apiHeaders, true));
        if ($withAuth) {
            $this->assertNotContains('Authorization: test-token', $tokenHeaders);
        }
    }

    public function testCallerHeadersReplaceSdkHeadersCaseInsensitively(): void
    {
        $this->send('post', ['x-sipay-plugin: woocommerce', 'X-SIPAY-PLUGIN-VERSION: 2.1.0'], false);

        [, , $headers] = $this->requests[0];
        $this->assertHeaderSentOnce('x-sipay-plugin: woocommerce', $headers);
        $this->assertHeaderSentOnce('X-SIPAY-PLUGIN-VERSION: 2.1.0', $headers);
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

    private function send(string $method, array $headers, bool $withAuth): void
    {
        $client = $this->createMockHttpClient();
        foreach (['get', 'post', 'put', 'delete'] as $httpMethod) {
            $client->method($httpMethod)->willReturnCallback(
                function (string $url, array $headers, $params) use ($httpMethod) {
                    $this->requests[] = [$httpMethod, $url, $headers, $params];

                    return self::TOKEN_URL === $url
                        ? $this->createSuccessfulTokenResponse('test-token')
                        : $this->createMockResponse(['status_code' => 100]);
                }
            );
        }

        $resource = new class ($this->createTestOptions(), $client) extends SipayResource {
            public function send(string $method, RequestPayload $payload, array $headers, bool $withAuth)
            {
                return $this->request($method, '/api/test', $payload, $headers, $withAuth);
            }
        };
        $payload = $this->createMock(RequestPayload::class);
        $payload->method('getJsonObject')->willReturn(['test' => 'payload']);
        $resource->send($method, $payload, $headers, $withAuth);
    }

    private function assertHeaderSentOnce(string $expected, array $headers): void
    {
        $name = strtolower(explode(':', $expected, 2)[0]);
        $sent = array_filter($headers, function (string $header) use ($name): bool {
            return strtolower(trim(explode(':', $header, 2)[0])) === $name;
        });
        $this->assertSame([$expected], array_values($sent));
    }
}
