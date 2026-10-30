# Changelog

All notable changes to this project are documented in this file.

## [1.2.0] - Unreleased

Uses hash v2. Install this version before the Sipay hash v1 cutoff ([PS-3655](https://sipay.atlassian.net/browse/PS-3655)).

### Changed

- Every request that carries a `hash_key` is signed with hash v2: `hex(HMAC-SHA256(app_secret, fields joined with '|'))`. This covers card payments (2D, 3D and saved card), complete payment, confirm payment, status check, refund, card save, card update and card delete. Hash v1 is not sent as a fallback.
- `total` on card payments and `amount` on refunds are sent as the same string that the hash signs, not as a JSON number.
- A signed value that contains `|` now throws `Sipay\Exceptions\InvalidArgumentException` before the request is sent.

### Added

- `HashKey::generateHashKeyV2()`, `HashKey::validateHashKeyV2()` and `HashKey::isV2()`.
- `HashKey::validateResponseHashKey()` checks the v2 `hash_key` of a 3D redirect, a 2D response or, with `HashKey::SALE_WEBHOOK_FIELDS`, a sale webhook.
- `NonSecurePayment::validateHashKey()`, and `getTotal()` / `getCurrencyCode()` for the signed values that a v2 response carries.
- Samples `validate_return_url_hash_key.php` and `validate_sale_webhook_hash_key.php`. `create_non_secure_payment.php` now checks the response hash.

### Deprecated

- `HashKey::validateHashKey()` (v1 response check) and `HashKey::generateHashKey()` (v1). They will be removed in the first release after the Sipay hash v1 cutoff.

### Upgrade Notes

- Release order: Sipay must accept hash v2 in production before you deploy this version. If it does not, every payment fails with an invalid hash error.
- No new setting. The same API key, API secret and merchant key are used.
- If you check the response hash with `HashKey::validateHashKey()`, move to `HashKey::validateResponseHashKey()` (or `NonSecurePayment::validateHashKey()` for 2D). Requests from this version get v2 response hashes, which the v1 check cannot read. Do not mark an order as paid when the hash is v1, missing or changed.
- A v2 response also carries `total` and `currency_code`. Compare them with your own order.
- Payment links (`/purchase/link`) still use hash v1.

## [1.1.0] - 2026-10-01

### Added

- Card holder IP address for card payments: `setIp()` / `getIp()` on `NonSecurePaymentInvoice`, `SecurePaymentInvoice` and `SavedCardPaymentInvoice`, sent to the API as `ip` ([PS-3458](https://sipay.atlassian.net/browse/PS-3458)). `setIp()` accepts a single valid IPv4 or IPv6 address, converts IPv4-mapped IPv6 (`::ffff:…`) to IPv4, and throws `Sipay\Exceptions\InvalidArgumentException` for anything else.
- Payment samples now send the card holder IP address.

### Upgrade Notes

- No breaking changes. `ip` is only sent when `setIp()` is called, so existing integrations keep working.
- If your merchant account has the "Cardholder IP" billing field set to mandatory, payments without a valid IP are rejected. Call `setIp()` with the end user's IP address (not your server's) on every card payment. See the "Card Holder IP Address" section in the README.

## [1.0.0] - 2025-05-23

- Initial release.
