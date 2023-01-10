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
}