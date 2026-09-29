<?php

declare(strict_types=1);

namespace Sipay\Models;

use Sipay\TestCase;

class PaymentInvoiceTest extends TestCase
{
    public function paymentInvoiceProvider(): array
    {
        return [
            'non secure payment' => [NonSecurePaymentInvoice::class],
            'secure payment' => [SecurePaymentInvoice::class],
            'saved card payment' => [SavedCardPaymentInvoice::class],
        ];
    }

    /**
     * @dataProvider paymentInvoiceProvider
     */
    public function testIpIsSentWhenSet(string $invoiceClass): void
    {
        $invoice = $this->createInvoice($invoiceClass)->setIp('192.168.1.10');

        $this->assertEquals('192.168.1.10', $invoice->getIp());
        $this->assertEquals('192.168.1.10', $invoice->getJsonObject()['ip']);
        $this->assertStringContainsString('ip=192.168.1.10', $invoice->toPKIRequestString());
    }

    /**
     * @dataProvider paymentInvoiceProvider
     */
    public function testIpIsOmittedWhenNotSet(string $invoiceClass): void
    {
        $invoice = $this->createInvoice($invoiceClass);

        $this->assertNull($invoice->getIp());
        $this->assertArrayNotHasKey('ip', $invoice->getJsonObject());
        $this->assertStringNotContainsString('ip=', $invoice->toPKIRequestString());
    }

    public function testIpDoesNotChangeHashKeyParts(): void
    {
        $invoice = $this->createInvoice(NonSecurePaymentInvoice::class);
        $partsWithoutIp = $invoice->generateHashKeyParts();

        $invoice->setIp('192.168.1.10');

        $this->assertEquals($partsWithoutIp, $invoice->generateHashKeyParts());
    }

    private function createInvoice(string $invoiceClass): PaymentInvoice
    {
        /** @var PaymentInvoice $invoice */
        $invoice = new $invoiceClass($this->createTestOptions());

        return $invoice
            ->setCurrencyCode('TRY')
            ->setInvoiceId('INV-1')
            ->setTotal(100)
            ->setItems([])
            ->setInstallmentsNumber(1);
    }
}
