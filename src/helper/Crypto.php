<?php

namespace bronsted;

use Exception;
use SodiumException;

class Crypto
{
    /**
     * Get a secret key for encrypt/decrypt
     */
    public static function generateSecretKey(): string
    {
        return sodium_bin2base64(sodium_crypto_secretbox_keygen(), SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    /**
     * Encrypt the message
     *
     * @param string $message
     * @param string $secret
     * @return string
     * @throws SodiumException
     * @throws Exception
     */
    public static function encrypt(string $message, string $secret): string
    {
        $key = sodium_base642bin($secret, SODIUM_BASE64_VARIANT_ORIGINAL);

        // create a nonce for this operation. it will be stored and recovered in the message itself
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        // encrypt message and combine with nonce
        $cipher = base64_encode($nonce . sodium_crypto_secretbox($message, $nonce, $key));

        // cleanup
        sodium_memzero($message);
        sodium_memzero($key);

        return $cipher;
    }

    /**
     * Decrypt the message
     *
     * @param string $encrypted
     * @param string $secret
     * @return string
     * @throws SodiumException
     * @throws Exception
     */
    public static function decrypt(string $encrypted, string $secret): string
    {
        $key = sodium_base642bin($secret, SODIUM_BASE64_VARIANT_ORIGINAL);

        // unpack base64 message
        $decoded = base64_decode($encrypted);

        // check for general failures
        if ($decoded === false) {
            throw new Exception('The encoding failed');
        }

        // pull nonce and ciphertext out of unpacked message
        $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

        // decrypt it and account for extra padding from $block_size (enforce 512 byte limit)
        $message = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);

        // check for encrpytion failures
        if ($message === false) {
            throw new Exception('The message was tampered with in transit');
        }

        // cleanup
        sodium_memzero($ciphertext);
        sodium_memzero($key);

        return $message;
    }
}
