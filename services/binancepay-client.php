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

    /**
     * Do request to binance pay API
     *
     * @param string $endPoint
     * @return false|object
     */
    private function doRequest(string $endPoint)
    {
        // Create url for request
        $urlRequest = $this->apiUrl . $endPoint;

        // Get nonce
        $nonce = $this->getNonce();

        // Get timestamp
        $timestamp = round(microtime(true) * 1000);

        // Convert request data to jsom format
        $requestData = json_encode($this->clintPDO);

        // Create payload
        $payload = $timestamp . "\n" . $nonce . "\n" . $requestData . "\n";

        // Generate signature
        $signature = strtoupper(hash_hmac('SHA512', $payload, $this->secretKey));

        //curl
        $ch = curl_init();
        $headers = array();
        $headers[] = "Content-Type: application/json";
        $headers[] = "BinancePay-Timestamp: $timestamp";
        $headers[] = "BinancePay-Nonce: $nonce";
        $headers[] = "BinancePay-Certificate-SN: $this->apiKey";
        $headers[] = "BinancePay-Signature: $signature";

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $urlRequest);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $result = json_decode(curl_exec($ch));
        curl_close($ch);

        if ($result->status == 'SUCCESS') {
            return $result;
        } else {
            return false;
        }
    }
}