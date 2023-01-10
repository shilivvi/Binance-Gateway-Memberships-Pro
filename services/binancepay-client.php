<?php

class BinancePayClient
{
    /**
     * API url
     *
     * @var string
     */
    private $apiUrl = 'https://bpay.binanceapi.com/binancepay/openapi/';

    /**
     * API key to access binance
     *
     * @var string
     */
    private $apiKey = null;

    /**
     * Secret key to access binance
     *
     * @var string
     */
    private $secretKey = null;

    /**
     * Personal data object. Stores data for a request
     *
     * @var array
     */
    private $clintPDO = null;


    /**
     * Constructor
     *
     * @param string $apiKey
     * @param string $secretKey
     */
    public function __construct(string $apiKey, string $secretKey)
    {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
    }

    /**
     * Generate random token 22 characters long
     *
     * @return string
     */
    private function getNonce()
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $nonce = '';

        for ($i = 1; $i <= 32; $i++) {
            $pos = mt_rand(0, strlen($chars) - 1);
            $nonce .= $chars[$pos];
        }

        return $nonce;
    }
}