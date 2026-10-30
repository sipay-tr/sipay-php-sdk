<?php

declare(strict_types=1);

namespace Sipay\Utils;

use Sipay\Exceptions\InvalidArgumentException;

class HashKey
{
    /**
     * Fields Sipay signs, in order, in a v2 payment response: the 2D response and the 3D
     * redirect to the return / cancel URL.
     */
    public const PAYMENT_RESPONSE_FIELDS = ['payment_status', 'total', 'invoice_id', 'order_id', 'currency_code'];

    /**
     * Fields Sipay signs, in order, in a v2 sale webhook. Only the first field differs from
     * a payment response: the webhook sends the transaction state name as "status".
     */
    public const SALE_WEBHOOK_FIELDS = ['status', 'total', 'invoice_id', 'order_id', 'currency_code'];

    private const SEPARATOR = '|';

    /**
     * Hash v2: hex(HMAC-SHA256(app_secret, fields joined with '|')), 64 hex characters.
     *
     * Each value is signed exactly as it is sent, so the request must carry the same string
     * (e.g. a total of "100.00" and "100" give different hashes). A null value is signed as ''.
     *
     * @throws InvalidArgumentException when a value is not a scalar, or contains '|'
     */
    public static function generateHashKeyV2(array $parts, string $app_secret): string
    {
        $values = [];

        foreach ($parts as $part) {
            $value = self::formatValue($part);

            if (strpos($value, self::SEPARATOR) !== false) {
                // The value is deliberately not echoed: it may be a card number or a holder name.
                throw new InvalidArgumentException('A value signed in the hash key must not contain "|".');
            }

            $values[] = $value;
        }

        return hash_hmac('sha256', implode(self::SEPARATOR, $values), $app_secret);
    }

    /**
     * Checks a v2 hash against the values it signs, in the order Sipay signs them. Values are
     * compared exactly as received. A v1 hash, or anything that is not 64 hex characters, fails.
     */
    public static function validateHashKeyV2(string $hashKey, array $parts, string $app_secret): bool
    {
        if (!self::isV2($hashKey)) {
            return false;
        }

        try {
            $expected = self::generateHashKeyV2($parts, $app_secret);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return hash_equals($expected, strtolower($hashKey));
    }

    /**
     * Checks the v2 hash_key of a Sipay response against the values in the same response:
     * the 3D redirect parameters (e.g. $_POST / $_GET on the return URL), the 2D response data,
     * or, with SALE_WEBHOOK_FIELDS, the sale webhook payload. A missing field is signed as ''.
     */
    public static function validateResponseHashKey(
        array $response,
        string $app_secret,
        array $fields = self::PAYMENT_RESPONSE_FIELDS
    ): bool {
        $hashKey = $response['hash_key'] ?? null;

        if (!is_string($hashKey)) {
            return false;
        }

        $parts = [];

        foreach ($fields as $field) {
            $value = $response[$field] ?? null;

            if ($value !== null && !is_scalar($value)) {
                return false;
            }

            $parts[] = $value;
        }

        return self::validateHashKeyV2($hashKey, $parts, $app_secret);
    }

    /**
     * A v2 hash is 64 hex characters; a v1 hash is an "iv:salt:ciphertext" bundle.
     */
    public static function isV2(string $hashKey): bool
    {
        return preg_match('/^[0-9a-f]{64}$/i', $hashKey) === 1;
    }

    /**
     * The string a value is signed as, and should be sent as.
     *
     * @throws InvalidArgumentException when the value is not a scalar or null
     */
    public static function formatValue($value): string
    {
        if ($value === null) {
            return '';
        }

        if (!is_scalar($value)) {
            throw new InvalidArgumentException('A value signed in the hash key must be a scalar.');
        }

        return (string)$value;
    }

    /**
     * Hash v1 (AES-256-CBC bundle). Sipay rejects it after the hash v1 cutoff.
     *
     * @deprecated since 1.2.0, use generateHashKeyV2(). Will be removed in the first release
     *             after the Sipay hash v1 cutoff.
     */
    public static function generateHashKey(array $parts, string $app_secret): string
    {

        //$data = $total . '|' . $installment . '|' . $currency_code . '|' . $merchant_key . '|' . $invoice_id;
        $data = implode("|", $parts);

        $iv = substr(sha1((string)mt_rand()), 0, 16);
        $password = sha1($app_secret);

        $salt = substr(sha1((string)mt_rand()), 0, 4);
        $saltWithPassword = hash('sha256', $password . $salt);

        $encrypted = openssl_encrypt(
            "$data",
            'aes-256-cbc',
            "$saltWithPassword",
            0,
            $iv
        );
        $msg_encrypted_bundle = "$iv:$salt:$encrypted";
        $msg_encrypted_bundle = str_replace('/', '__', $msg_encrypted_bundle);

        return $msg_encrypted_bundle;
    }

    /**
     * Decrypts a v1 response hash. A v1 hash is not authenticated: whoever holds one valid
     * hash can change the status in it without the app secret, so do not trust its result alone.
     *
     * @deprecated since 1.2.0, use validateResponseHashKey() / validateHashKeyV2(). Sipay sends
     *             v2 response hashes for v2 requests, and only v2 after the hash v1 cutoff. Will be
     *             removed in the first release after the cutoff.
     */
    public static function validateHashKey(string $hashKey, string $secretKey): array
    {
        $status = $currencyCode = "";
        $total = $invoiceId = $orderId = 0;

        if (!empty($hashKey)) {
            $hashKey = str_replace('__', '/', $hashKey);
            $password = sha1($secretKey);

            $components = explode(':', $hashKey);
            if (count($components) > 2) {
                $iv = $components[0] ?? "";
                $salt = $components[1] ?? "";
                $salt = hash('sha256', $password . $salt);
                $encryptedMsg = $components[2] ?? "";

                $decryptedMsg = openssl_decrypt($encryptedMsg, 'aes-256-cbc', $salt, 0, $iv);

                if (is_string($decryptedMsg) && strpos($decryptedMsg, '|') !== false) {
                    $array = explode('|', $decryptedMsg);
                    $status = $array[0] ?? 0;
                    $total = $array[1] ?? 0;
                    $invoiceId = $array[2] ?? '0';
                    $orderId = $array[3] ?? 0;
                    $currencyCode = $array[4] ?? '';
                }
            }
        }

        return [$status, $total, $invoiceId, $orderId, $currencyCode];
    }
}
