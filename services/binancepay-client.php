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
     * Create order and return link this order or false on error
     * https://developers.binance.com/docs/binance-pay/api-order-create-v2
     *
     * @param array $requestData
     * @return array|false
     */
    public function createOrder(array $requestData)
    {
        $this->clintPDO = $requestData;
        $response = $this->doRequest('v2/order');

        if ($response === false) {
            return false;
        }

        return [
            'url' => $response->data->universalUrl,
            'transaction_id' => $response->data->prepayId
        ];
    }

    /**
     *  Get order by prepayId or merchantTradeNo
     *  https://developers.binance.com/docs/binance-pay/api-order-query-v2
     *
     * @param array $requestData
     * @return string|false
     */
    public function getOrderStatus(array $requestData)
    {
        $this->clintPDO = $requestData;
        $response = $this->doRequest('v2/order/query');

        if ($response === false) {
            return false;
        }

        return $response->data->status;
    }

    /**
     * Binance recommend verifying the signature using the public key issued from Binance Pay
     * https://developers.binance.com/docs/binance-pay/webhook-common
     *
     * 1. Build payload
     * 2. Decode the Signature
     * 3. Get public key
     * 4. Verify the content with public key
     *
     * @param string $timestamp
     * @param string $nonce
     * @param string $signature
     * @param string $bodyData
     * @return bool
     */
    public function verifyWebhookNotice(string $timestamp, string $nonce, string $signature, string $bodyData)
    {
        // Build payload
        $payload = $timestamp . "\n" . $nonce . "\n" . $bodyData . "\n";

        // Decode the Signature
        $decodedSignature = base64_decode($signature);

        // Get public key
        $publicKey = $this->getPublicKey();

        if ($publicKey === false) {
            return false;
        }

        // Verify the content with public key
        if (openssl_verify($payload, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256)) {
            return true;
        }

        return false;
    }

    /**
     * Get public key
     * https://developers.binance.com/docs/binance-pay/webhook-query-certificate
     *
     * @return false|string
     */
    public function getPublicKey()
    {
        $response = $this->doRequest('certificates');

        if ($response === false) {
            return false;
        }

        return $response->data[0]->certPublic;
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