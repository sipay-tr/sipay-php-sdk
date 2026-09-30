# Changelog

All notable changes to this project are documented in this file.

## [Unreleased]

### Added

- Card holder IP address for card payments: `setIp()` / `getIp()` on `NonSecurePaymentInvoice`, `SecurePaymentInvoice` and `SavedCardPaymentInvoice`, sent to the API as `ip` ([PS-3458](https://sipay.atlassian.net/browse/PS-3458)).
- Payment samples now send the card holder IP address.

### Upgrade Notes

- No breaking changes. `ip` is only sent when `setIp()` is called, so existing integrations keep working.
- If your merchant account has the "Cardholder IP" billing field set to mandatory, payments without a valid IP are rejected. Call `setIp()` with the end user's IP address (not your server's) on every card payment. See the "Card Holder IP Address" section in the README.

## [1.0.0]

- Initial release.
