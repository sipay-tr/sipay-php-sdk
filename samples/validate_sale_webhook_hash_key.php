<?php

declare(strict_types=1);

require_once('test_config.php');
/**
 * @var Sipay\SipayOptions $sipayOptions
 */

use Sipay\Utils\HashKey;

// Your sale webhook endpoint (sale_web_hook_key). The webhook signs status, total, invoice_id,
// order_id and currency_code: "status" in place of the redirect's "payment_status".
$payload = $_POST;

if (!HashKey::validateResponseHashKey($payload, $sipayOptions->getApiSecret(), HashKey::SALE_WEBHOOK_FIELDS)) {
    http_response_code(400);
    exit('Invalid webhook hash.');
}

dump($payload['status'], $payload['total'], $payload['invoice_id']);
