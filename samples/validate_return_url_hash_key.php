<?php

declare(strict_types=1);

require_once('test_config.php');
/**
 * @var Sipay\SipayOptions $sipayOptions
 */

use Sipay\Utils\HashKey;

// Your return_url / cancel_url for a 3D payment. Sipay redirects the card holder here with the
// result. The browser carries these values, so check the hash before you trust them.
$params = $_POST ?: $_GET;

if (!HashKey::validateResponseHashKey($params, $sipayOptions->getApiSecret())) {
    exit('Invalid response hash: do not mark the order as paid.');
}

// The hash signs payment_status, total, invoice_id, order_id and currency_code. Also compare the
// total and currency with your own order, and confirm the payment server to server
// (samples/check_transaction_status.php) before you ship it.
dump($params['payment_status'], $params['total'], $params['currency_code']);
