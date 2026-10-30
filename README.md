# Sipay Payments PHP-SDK

Sipay, is a payment gateway that provides a secure and easy-to-use payment infrastructure. 

This package is a PHP SDK for the Sipay API.

> **Important:**
> This package is still in development. Please use it with caution and report any issues you encounter.

# Requirements

- PHP 7.4+
- ext-curl, ext-json, ext-openssl extensions

## Installation
Run the following command under your project to install the package via [Composer](https://getcomposer.org/download/)
```bash
composer require sipay-tr/sipay-php-sdk
```

## Getting Started

You can create a new instance of the Sipay class by passing the required parameters to the constructor. You can find the required parameters in the sample code below.

```php
$sipayOptions = new \Sipay\SipayOptions(
    '<API KEY>',
    '<API SECRET>',
    '<MERCHANT KEY>',
    '<MERCHANT ID>',
    'https://app.sipay.com.tr/ccpayment'
);
$sipay = new Sipay($sipayOptions);
```

> **Note:** 
> For Test API server and credentials, you can check the [Sipay API Documentation](https://apidocs.sipay.com.tr/#tag/Erisim-URL'leri)

## Examples
Included in the project are a number of examples that cover almost all use-cases. Refer to the `samples` folder for more info.

### Card Holder IP Address

Card payments (`NonSecurePaymentInvoice`, `SecurePaymentInvoice` and `SavedCardPaymentInvoice`) accept the IP address of the card holder, i.e. the end user making the payment, not your server's IP. Set it with `setIp()` and it is sent to the API as the `ip` field.

```php
$paymentInvoice
    // ...
    ->setIp('203.0.113.10');
```

- **Format:** a single valid IPv4 or IPv6 address, the same rule the API applies (values it rejects fail with `Cardholder IP is invalid.`). `setIp()` checks this up front and throws `Sipay\Exceptions\InvalidArgumentException` for anything else, such as an empty string or a comma-separated list. An IPv4-mapped IPv6 address (`::ffff:203.0.113.7`) is converted to plain IPv4.
- **Required or optional:** optional in the SDK. The API requires it only when the "Cardholder IP" field is marked mandatory in your merchant billing settings; payments without it are then rejected with `Cardholder IP is required.`

> **Note:**
> Behind a load balancer or reverse proxy, `REMOTE_ADDR` is the proxy's address. Use your framework's client IP helper with trusted proxies configured (e.g. Symfony `Request::getClientIp()`, Laravel `$request->ip()`). Don't pass the raw `X-Forwarded-For` header: it can contain several addresses, and the client controls it unless your own proxy overwrites it.

### Hash Key (v2)

Every request that carries a `hash_key` (card payments 2D, 3D and saved card, complete payment, confirm payment, status check, refund, card save, card update and card delete) is signed with **hash v2**: `hex(HMAC-SHA256(app_secret, fields joined with '|'))`, 64 hex characters. The SDK builds it for you from your `SipayOptions`. No new setting is needed.

- **Amounts are sent as strings.** Hash v2 signs each value exactly as it is sent (`100` and `100.00` give different hashes). The SDK therefore sends `total` and the refund `amount` as the same string it signs. A float total of `100.0` is sent as `"100"`. Pass a string such as `'100.00'` to `setTotal()` if you want a fixed format.
- **No `|` in a signed value.** `invoice_id`, the card holder name and the other signed values must not contain `|`. The SDK throws `Sipay\Exceptions\InvalidArgumentException` before it sends such a request.
- **Payment links stay on v1.** `/purchase/link` does not accept hash v2 yet, so `PaymentLinkInvoice` still signs with v1.

#### Checking the response hash

When the request is signed with v2, Sipay signs the payment result with v2 too. Check this hash before you mark an order as paid, and also compare the total and currency with your own order.

```php
use Sipay\Utils\HashKey;

// 2D payment
$payment = $sipay->nonSecurePaymentResource()->create($request);
$isValid = $payment->validateHashKey('<API SECRET>');

// 3D payment: on your return_url / cancel_url
$isValid = HashKey::validateResponseHashKey($_POST ?: $_GET, '<API SECRET>');

// Sale webhook: it signs "status" in place of "payment_status"
$isValid = HashKey::validateResponseHashKey($_POST, '<API SECRET>', HashKey::SALE_WEBHOOK_FIELDS);
```

The hash signs `payment_status` (`status` for the webhook), `total`, `invoice_id`, `order_id` and `currency_code`, in that order, exactly as received. A v1 hash, a missing hash or a changed value fails the check. See `samples/validate_return_url_hash_key.php` and `samples/validate_sale_webhook_hash_key.php`.

> **Deprecated:**
> `HashKey::validateHashKey()` (the v1 response check) and `HashKey::generateHashKey()` (v1) still work until the Sipay hash v1 cutoff, and will be removed in the first release after it. A v1 hash is not authenticated: whoever holds one valid hash can change the payment status in it without the app secret. See the upgrade notes in [CHANGELOG.md](CHANGELOG.md).


## Testing

You can run the tests as following command below:
```bash
./vendor/bin/phpunit
```

To run a test file, you can use the following example command:

```bash
.vendor/bin/phpunit tests/Sipay/Resources/TokenTest.php 
```

To run a test method in test file, you can use the following example command:

```bash
./vendor/bin/phpunit --filter testRetrieveWithSuccessfulResponse tests/Sipay/Resources/CardListTest.php
```

## Releasing

Releases are created with the **Release** workflow (Actions → Release → Run workflow):

- Pick a `patch`, `minor` or `major` bump; the next `vX.Y.Z` tag is computed from the latest stable tag.
- CI (code style + tests on all supported PHP versions) must pass before the tag and GitHub release are created.
- Release notes are generated automatically since the previous stable release; anything entered in `notes` is prepended to them.
- Enable `dry_run` to see the computed version and run CI without tagging or releasing.

Stable releases can only be created from `main`. To test a version before releasing it, enable `prerelease`: this creates a release candidate such as `v1.1.0-rc.1` (then `rc.2`, ...), and can be run from any branch that contains this workflow and every stable release (merge `main` into it first). Release candidates are marked as pre-releases on GitHub and are not installed by default on Packagist; install one explicitly:

```bash
composer require sipay-tr/sipay-php-sdk:1.1.0-rc.1
```

