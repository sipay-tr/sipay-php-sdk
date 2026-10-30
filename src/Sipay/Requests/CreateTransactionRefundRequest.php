<?php

declare(strict_types=1);

namespace Sipay\Requests;

use Sipay\JsonBuilder;
use Sipay\RequestPayload;
use Sipay\Requests\Concerns\InteractsHashKey;
use Sipay\Requests\Contracts\WithHashKey;
use Sipay\RequestStringBuilder;
use Sipay\Utils\HashKey;

class CreateTransactionRefundRequest extends RequestPayload implements WithHashKey
{
    use InteractsHashKey;

    private $invoiceId;

    private $amount;

    private $appId;

    private $appSecret;

    private $refundTransactionId;

    private $refundWebHookKey;

    public function getInvoiceId()
    {
        return $this->invoiceId;
    }

    public function setInvoiceId($invoiceId)
    {
        $this->invoiceId = $invoiceId;

        return $this;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    public function getRefundTransactionId()
    {
        return $this->refundTransactionId;
    }

    public function setRefundTransactionId($refundTransactionId)
    {
        $this->refundTransactionId = $refundTransactionId;

        return $this;
    }

    public function getRefundWebHookKey()
    {
        return $this->refundWebHookKey;
    }

    public function setRefundWebHookKey($refundWebHookKey)
    {
        $this->refundWebHookKey = $refundWebHookKey;

        return $this;
    }

    /**
     * Hash v2 signs the amount exactly as it is sent, so it is sent as the same string.
     * Without an amount it is not sent and is signed as ''.
     */
    protected function getSignedAmount(): ?string
    {
        return $this->getAmount() === null ? null : HashKey::formatValue($this->getAmount());
    }

    public function generateHashKeyParts(): array
    {
        return  [
            $this->getSignedAmount(),
            $this->getInvoiceId(),
            $this->getMerchantKey(),
        ];
    }

    public function getJsonObject()
    {
        return JsonBuilder::create()
            ->add("invoice_id", $this->getInvoiceId())
            ->add("amount", $this->getSignedAmount())
            ->add("app_id", $this->getApiKey())
            ->add("app_secret", $this->getApiSecret())
            ->add("merchant_key", $this->getMerchantKey())
            ->add("hash_key", $this->getHashKey())
            ->add("refund_transaction_id", $this->getRefundTransactionId())
            ->add("refund_web_hook_key", $this->getRefundWebHookKey())
            ->getObject();
    }

    public function toPKIRequestString()
    {
        return RequestStringBuilder::create()
            ->append("invoice_id", $this->getInvoiceId())
            ->append("amount", $this->getSignedAmount())
            ->append("app_id", $this->getApiKey())
            ->append("app_secret", $this->getApiSecret())
            ->append("merchant_key", $this->getMerchantKey())
            ->append("hash_key", $this->getHashKey())
            ->append("refund_transaction_id", $this->getRefundTransactionId())
            ->append("refund_web_hook_key", $this->getRefundWebHookKey())
            ->getRequestString();
    }
}
