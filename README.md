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

- **Format:** a valid IPv4 or IPv6 address. Invalid values are rejected by the API with `Cardholder IP is invalid.`
- **Required or optional:** optional in the SDK. The API requires it only when the "Cardholder IP" field is marked mandatory in your merchant billing settings; payments without it are then rejected with `Cardholder IP is required.`

> **Note:**
> If your application runs behind a load balancer or reverse proxy, `REMOTE_ADDR` holds the proxy's address. Resolve the client IP from a trusted forwarding header (e.g. `X-Forwarded-For`) instead.


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

