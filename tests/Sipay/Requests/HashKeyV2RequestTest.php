<?php

declare(strict_types=1);

namespace Sipay\Requests;

use Sipay\Models\NonSecurePaymentInvoice;
use Sipay\Models\PaymentLinkInvoice;
use Sipay\Models\SavedCardPaymentInvoice;
use Sipay\Models\SecurePaymentInvoice;
use Sipay\SipayOptions;
use Sipay\TestCase;
use Sipay\Utils\HashKey;

/**
 * Sipay checks a v2 hash by joining the values it received, in the endpoint's field order
 * (PFINT-1202). Each case does the same with the request body the SDK sends.
 */
class HashKeyV2RequestTest extends TestCase
{
    private const PAYMENT_FIELDS = ['total', 'installments_number', 'currency_code', 'merchant_key', 'invoice_id'];

    public function requestProvider(): array
    {
        $newCard = function (SipayOptions $options, string $invoiceClass, $total) {
            return (new $invoiceClass($options))
                ->setNewCard('John Doe', '4508034508034509', '12', '2026', '000')
                ->setItems([])
                ->setCurrencyCode('TRY')
                ->setInstallmentsNumber(1)
                ->setInvoiceId('INV-1')
                ->setTotal($total);
        };

        return [
            'paySmart2D' => [function (SipayOptions $options) use ($newCard) {
                return (new CreateNonSecurePaymentRequest($options))
                    ->setPaymentInvoice($newCard($options, NonSecurePaymentInvoice::class, 100.5));
            }, self::PAYMENT_FIELDS],
            'paySmart3D' => [function (SipayOptions $options) use ($newCard) {
                return (new CreateSecurePaymentRequest($options))
                    ->setPaymentInvoice($newCard($options, SecurePaymentInvoice::class, '100.00'));
            }, self::PAYMENT_FIELDS],
            'payByCardToken' => [function (SipayOptions $options) {
                $invoice = (new SavedCardPaymentInvoice($options))
                    ->setSavedCard('card_token_123', 'customer_1', 'john@doe.com', '905555555555', 'John Doe')
                    ->setItems([])
                    ->setCurrencyCode('TRY')
                    ->setInstallmentsNumber('1')
                    ->setInvoiceId('INV-1')
                    ->setTotal(300);

                return (new CreateSavedCardPaymentRequest($options))->setPaymentInvoice($invoice);
            }, self::PAYMENT_FIELDS],
            'payment/complete' => [function (SipayOptions $options) {
                return (new CompletePaymentRequest($options))
                    ->setInvoiceId('INV-1')
                    ->setOrderId('ORD-1')
                    ->setStatus('complete');
            }, ['merchant_key', 'invoice_id', 'order_id', 'status']],
            'confirmPayment' => [function (SipayOptions $options) {
                return (new ConfirmPaymentRequest($options))
                    ->setInvoiceId('INV-1')
                    ->setTotal(100.5)
                    ->setStatus(1);
            }, ['merchant_key', 'invoice_id', 'status']],
            'checkstatus' => [function (SipayOptions $options) {
                return (new CheckTransactionStatusRequest($options))
                    ->setInvoiceId('INV-1')
                    ->setIncludePendingStatus(true);
            }, ['invoice_id', 'merchant_key']],
            'refund' => [function (SipayOptions $options) {
                return (new CreateTransactionRefundRequest($options))
                    ->setInvoiceId('INV-1')
                    ->setAmount(10.25);
            }, ['amount', 'invoice_id', 'merchant_key']],
            'refund without an amount' => [function (SipayOptions $options) {
                return (new CreateTransactionRefundRequest($options))->setInvoiceId('INV-1');
            }, ['amount', 'invoice_id', 'merchant_key']],
            'saveCard' => [function (SipayOptions $options) {
                return (new CreateCardRequest($options))
                    ->setCardHolderName('John Doe')
                    ->setCardNumber(4508034508034509)
                    ->setExpiryMonth(12)
                    ->setExpiryYear(2026)
                    ->setCustomerNumber(1)
                    ->setCustomerName('John Doe')
                    ->setCustomerEmail('john@doe.com')
                    ->setCustomerPhone('905555555555');
            }, ['merchant_key', 'customer_number', 'card_holder_name', 'card_number', 'expiry_month', 'expiry_year']],
            'editCard' => [function (SipayOptions $options) {
                return (new UpdateCardRequest($options))
                    ->setCardToken('card_token_123')
                    ->setCustomerNumber('customer_1')
                    ->setExpiryMonth('12')
                    ->setExpiryYear('2026');
            }, ['merchant_key', 'customer_number', 'card_token']],
            'deleteCard' => [function (SipayOptions $options) {
                return (new DeleteCardRequest($options))
                    ->setCardToken('card_token_123')
                    ->setCustomerNumber('customer_1');
            }, ['merchant_key', 'customer_number', 'card_token']],
        ];
    }

    /**
     * @dataProvider requestProvider
     */
    public function testRequestSendsAV2HashOverTheValuesItSends(callable $createRequest, array $fields): void
    {
        $options = $this->createTestOptions();
        $body = $createRequest($options)->getJsonObject();

        // Through JSON, as the API receives it.
        $received = json_decode(json_encode($body), true);

        $data = implode('|', array_map(function (string $field) use ($received) {
            return isset($received[$field]) ? (string)$received[$field] : '';
        }, $fields));

        $this->assertTrue(HashKey::isV2($received['hash_key']));
        $this->assertSame(hash_hmac('sha256', $data, $options->getApiSecret()), $received['hash_key']);
    }

    public function testPaymentTotalIsSentAsTheSignedString(): void
    {
        $invoice = (new NonSecurePaymentInvoice($this->createTestOptions()))
            ->setNewCard('John Doe', '4508034508034509', '12', '2026', '000')
            ->setItems([])
            ->setTotal(100.0);

        $this->assertSame('100', $invoice->getJsonObject()['total']);
        $this->assertSame('100', $invoice->generateHashKeyParts()[0]);
        $this->assertStringContainsString('total=100,', $invoice->toPKIRequestString());
    }

    public function testPaymentTotalIsOmittedWhenNotSet(): void
    {
        $invoice = (new NonSecurePaymentInvoice($this->createTestOptions()))
            ->setNewCard('John Doe', '4508034508034509', '12', '2026', '000')
            ->setItems([]);

        $this->assertArrayNotHasKey('total', $invoice->getJsonObject());
    }

    public function testRefundAmountIsSentAsTheSignedString(): void
    {
        $request = (new CreateTransactionRefundRequest($this->createTestOptions()))
            ->setInvoiceId('INV-1')
            ->setAmount(10.5);

        $this->assertSame('10.5', $request->getJsonObject()['amount']);
    }

    public function testPaymentLinkStaysOnHashV1(): void
    {
        $invoice = (new PaymentLinkInvoice($this->createTestOptions()))
            ->setInvoiceId('INV-1')
            ->setTotal(100)
            ->setCurrencyCode('TRY');

        $this->assertFalse(HashKey::isV2($invoice->getHashKey()));
        $this->assertCount(3, explode(':', $invoice->getHashKey()));
    }
}
