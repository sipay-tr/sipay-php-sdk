<?php

declare(strict_types=1);

namespace Sipay\Models;

use Sipay\TestCase;

class PaymentInvoiceTest extends TestCase
{
    /**
     * Each case configures the card the way its endpoint is actually called, so the
     * assertions run against the branch (new card vs. card token) that endpoint uses.
     */
    public function paymentInvoiceProvider(): array
    {
        $newCard = function (PaymentInvoice $invoice): void {
            $invoice->setNewCard('John Doe', '4508034508034509', '12', '2026', '000');
        };

        $savedCard = function (PaymentInvoice $invoice): void {
            $invoice->setSavedCard('card_token_123', 'customer_1', 'john@doe.com', '905555555555', 'John Doe');
        };

        return [
            'non secure payment' => [NonSecurePaymentInvoice::class, $newCard, 'cc_no', 'card_token'],
            'secure payment' => [SecurePaymentInvoice::class, $newCard, 'cc_no', 'card_token'],
            'saved card payment' => [SavedCardPaymentInvoice::class, $savedCard, 'card_token', 'cc_no'],
        ];
    }

    /**
     * @dataProvider paymentInvoiceProvider
     */
    public function testIpIsSentWhenSet(
        string $invoiceClass,
        callable $configureCard,
        string $expectedCardKey,
        string $unexpectedCardKey
    ): void {
        $invoice = $this->createInvoice($invoiceClass, $configureCard)->setIp('192.168.1.10');

        $jsonObject = $invoice->getJsonObject();
        $requestString = $invoice->toPKIRequestString();

        $this->assertEquals('192.168.1.10', $invoice->getIp());

        $this->assertArrayHasKey($expectedCardKey, $jsonObject);
        $this->assertArrayNotHasKey($unexpectedCardKey, $jsonObject);
        $this->assertEquals('192.168.1.10', $jsonObject['ip']);

        $this->assertStringContainsString($expectedCardKey . '=', $requestString);
        $this->assertStringNotContainsString($unexpectedCardKey . '=', $requestString);
        $this->assertStringContainsString('ip=192.168.1.10', $requestString);
    }

    /**
     * @dataProvider paymentInvoiceProvider
     */
    public function testIpIsOmittedWhenNotSet(string $invoiceClass, callable $configureCard): void
    {
        $invoice = $this->createInvoice($invoiceClass, $configureCard);

        $this->assertNull($invoice->getIp());
        $this->assertArrayNotHasKey('ip', $invoice->getJsonObject());
        $this->assertStringNotContainsString('ip=', $invoice->toPKIRequestString());
    }

    public function testIpDoesNotChangeHashKeyParts(): void
    {
        $invoice = $this->createInvoice(NonSecurePaymentInvoice::class, function (PaymentInvoice $invoice): void {
            $invoice->setNewCard('John Doe', '4508034508034509', '12', '2026', '000');
        });
        $partsWithoutIp = $invoice->generateHashKeyParts();

        $invoice->setIp('192.168.1.10');

        $this->assertEquals($partsWithoutIp, $invoice->generateHashKeyParts());
    }

    private function createInvoice(string $invoiceClass, callable $configureCard): PaymentInvoice
    {
        /** @var PaymentInvoice $invoice */
        $invoice = new $invoiceClass($this->createTestOptions());

        $invoice
            ->setCurrencyCode('TRY')
            ->setInvoiceId('INV-1')
            ->setTotal(100)
            ->setItems([])
            ->setInstallmentsNumber(1);

        $configureCard($invoice);

        return $invoice;
    }
}
