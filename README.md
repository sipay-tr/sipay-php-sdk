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

