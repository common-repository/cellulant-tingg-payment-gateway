<?php

class TinggPaymentGatewayUtils
{
    /**
     * @param $iv string
     * @param $secret string
     * @param $payload array
     * @return string
     */
    public static function encryptCheckoutRequest($iv, $secret, $payload)
    {
        $secret_key = hash('sha256', $secret);
        $iv_key = substr(hash('sha256', $iv), 0, 16);

        $encrypted = openssl_encrypt(
            json_encode($payload, true),
            'AES-256-CBC',
            $secret_key,
            0,
            $iv_key
        );

        return base64_encode($encrypted);
    }
}

