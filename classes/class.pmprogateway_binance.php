<?php

class PMProGateway_binance extends PMProGateway
{
    function PMProGateway($gateway = NULL)
    {
        $this->gateway = $gateway;
        return $this->gateway;
    }

    /**
     * Run on WP init
     */
    static function init()
    {
        //make sure example is a gateway option
        add_filter('pmpro_gateways', array('PMProGateway_binance', 'pmpro_gateways'));

        //add fields to payment settings
        add_filter('pmpro_payment_options', array('PMProGateway_binance', 'pmpro_payment_options'));
        add_filter('pmpro_payment_option_fields', array('PMProGateway_binance', 'pmpro_payment_option_fields'), 10, 2);

        $gateway = pmpro_getOption('gateway');
        if ($gateway == 'binance') {
            add_filter('pmpro_include_billing_address_fields', '__return_false');
            add_filter('pmpro_include_payment_information_fields', '__return_false');
            add_filter('pmpro_required_billing_fields', array('PMProGateway_binance', 'pmpro_required_billing_fields'));
            add_filter('pmpro_checkout_default_submit_button', array('PMProGateway_binance', 'pmpro_checkout_default_submit_button'));
            add_filter('pmpro_checkout_before_change_membership_level', array('PMProGateway_binance', 'pmpro_checkout_before_change_membership_level'), 10, 2);
        }
    }

    /**
     * Make sure example is in the gateways list
     */
    static function pmpro_gateways($gateways)
    {
        if (empty($gateways['binance'])) {
            $gateways['binance'] = __('Binance Pay', BINANCEPMP);
        }

        return $gateways;
    }

    /**
     * Set payment options for payment settings page.
     */
    static function getGatewayOptions()
    {
        $options = array(
            'binance_api_key',
            'binance_secret_key',
            'binance_error_page_id',
            //'cryptocompare_api_key',
            'currency',
        );

        return $options;
    }

    /**
     * Set payment options for payment settings page.
     */
    static function pmpro_payment_options($options)
    {
        //get options
        $binance_options = self::getGatewayOptions();

        //merge with others
        $options = array_merge($binance_options, $options);

        return $options;
    }

    /**
     * Display fields for binance options.
     */
    static function pmpro_payment_option_fields($options, $gateway)
    {
        ?>
        <tr class="pmpro_settings_divider gateway gateway_binance" <?php echo $gateway != 'binance' ? 'style="display: none;"' : ''; ?>>
            <td colspan="2">
                <hr>
                <h2>Binance Pay Settings</h2>
            </td>
        </tr>
        <tr class="gateway gateway_binance" <?php echo $gateway != 'binance' ? 'style="display: none;"' : ''; ?>>
            <th scope="row" valign="top">
                <label for="binance_api_key">
                    <?php esc_html_e('Binance Api Key:', BINANCEPMP); ?>
                </label>
            </th>
            <td colspan="2">
                <input type="text" id="binance_api_key" name="binance_api_key"
                       value="<?php echo $options['binance_api_key'] ?? ''; ?>">
            </td>
        </tr>
        <tr class="gateway gateway_binance" <?php echo $gateway != 'binance' ? 'style="display: none;"' : ''; ?>>
            <th scope="row" valign="top">
                <label for="binance_secret_key">
                    <?php esc_html_e('Binance Secret Key:', BINANCEPMP); ?>
                </label>
            </th>
            <td>
                <input type="text" id="binance_secret_key" name="binance_secret_key"
                       value="<?php echo $options['binance_secret_key'] ?? ''; ?>">
            </td>
        </tr>
        <tr class="gateway gateway_binance" <?php echo $gateway != 'binance' ? 'style="display: none;"' : ''; ?>>
            <th scope="row" valign="top">
                <label for="binance_error_page_id">
                    <?php esc_html_e('Payment error page:', BINANCEPMP); ?>
                </label>
            </th>
            <td>
                <?php
                wp_dropdown_pages(
                    array(
                        'name' => 'binance_error_page_id',
                        'show_option_none' => '-- ' . __('Choose One', 'paid-memberships-pro') . ' --',
                        'selected' => $options['binance_error_page_id'],
                        'post_type' => 'page',
                        'post_status' => 'publish'
                    )
                );
                ?>
            </td>
        </tr>
        <?php
        /*
        <tr class="gateway gateway_binance" <?php echo $gateway != 'binance' ? 'style="display: none;"' : ''; ?>>
            <th scope="row" valign="top">
                <label for="cryptocompare_api_key">
                    <?php esc_html_e('Cryptocompare Api Key:', BINANCEPMP); ?>
                </label>
            </th>
            <td>
                <input type="text" id="cryptocompare_api_key" name="cryptocompare_api_key"
                value="<?php echo $options['cryptocompare_api_key'] ?? ''; ?>">
            </td>
        </tr>
         */
    }

    /**
     * Remove required billing fields
     */
    public static function pmpro_required_billing_fields($fields)
    {
        unset($fields['bfirstname']);
        unset($fields['blastname']);
        unset($fields['baddress1']);
        unset($fields['bcity']);
        unset($fields['bstate']);
        unset($fields['bzipcode']);
        unset($fields['bphone']);
        unset($fields['bemail']);
        unset($fields['bcountry']);
        unset($fields['CardType']);
        unset($fields['AccountNumber']);
        unset($fields['ExpirationMonth']);
        unset($fields['ExpirationYear']);
        unset($fields['CVV']);

        return $fields;
    }

    /**
     * Swap in our submit buttons.
     */
    static function pmpro_checkout_default_submit_button($show)
    {
        global $gateway, $pmpro_requirebilling;

        //show our submit buttons
        ?>
        <span id="pmpro_submit_span">
				<input type="hidden" name="submit-checkout" value="1"/>
				<input type="submit"
                       class="<?php echo pmpro_get_element_class('pmpro_btn pmpro_btn-submit-checkout', 'pmpro_btn-submit-checkout'); ?>"
                       value="<?php if ($pmpro_requirebilling) {
                           _e('Check Out with Binance Pay', BINANCEPMP);
                       } else {
                           _e('Submit and Confirm', BINANCEPMP);
                       } ?> &raquo;"/>
			</span>
        <?php

        //don't show the default
        return false;
    }

    /**
     * Process checkout.
     */
    function process(&$order)
    {
        if (empty($order->code)) {
            $order->code = $order->getRandomCode();
        }

        //just save, the user will go to binance to pay
        $order->status = 'pending';
        $order->saveOrder();

        return true;
    }

    /**
     * Instead of change membership levels, send users to BinancePay to pay.
     */
    static function pmpro_checkout_before_change_membership_level($user_id, $morder)
    {
        global $wpdb;

        //If no order, no need to pay
        if (empty($morder)) {
            return;
        }

        $morder->user_id = $user_id;
        $morder->saveOrder();

        //Save discount code use
        if (isset($morder->membership_level) && !empty($morder->membership_level->code_id)) {
            $discount_code_id = (int)$morder->membership_level->code_id;
            $wpdb->query("INSERT INTO $wpdb->pmpro_discount_codes_uses (code_id, user_id, order_id, timestamp) VALUES('" . $discount_code_id . "', '" . $user_id . "', '" . $morder->id . "', now())");
        }

        $morder->Gateway->sendToBinancePay($morder);
    }


    function sendToBinancePay(&$order)
    {
        $order_id = $order->code;
        $membership_id = $order->membership_level->id;
        $membership_name = $order->membership_level->name;
        $chars_for_nonce = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $api_key = pmpro_getOption('binance_api_key');
        $secret_key = pmpro_getOption('binance_secret_key');

        // Generate nonce
        $nonce = '';
        for ($i = 1; $i <= 32; $i++) {
            $pos = mt_rand(0, strlen($chars_for_nonce) - 1);
            $char = $chars_for_nonce[$pos];
            $nonce .= $char;
        }

        // Get price in USDT
        $price = PMProGateway_binance::getUSDTFromUSD($order->PaymentAmount);
        $price = round(floatval($price), 2);

        // Request body
        $url = admin_url("admin-ajax.php") . '?';
        $cancelData = array(
            'action' => 'binancepay-ins',
            'merchantTradeNo' => $order_id,
        );
        $callbackUrl = $url . http_build_query($cancelData);

        $request = array(
            'env' => array(
                'terminalType' => 'WEB',
            ),
            'merchantTradeNo' => $order_id,
            'orderAmount' => $price,
            'currency' => 'USDT',
            'goods' => array(
                'goodsType' => '02',
                'goodsCategory' => 'Z000',
                'referenceGoodsId' => $membership_id,
                'goodsName' => $membership_name,
            ),
            'cancelUrl' => $callbackUrl,
            'returnUrl' => $callbackUrl,
            // Shot time for tests
            'orderExpireTime' => round(microtime(true) * 1000) + 100000
        );

        $json_request = json_encode($request);

        // Generate payload
        $timestamp = round(microtime(true) * 1000);
        $payload = $timestamp . "\n" . $nonce . "\n" . $json_request . "\n";

        // Generate signature
        $signature = strtoupper(hash_hmac('SHA512', $payload, $secret_key));

        //curl
        $ch = curl_init();
        $headers = array();
        $headers[] = "Content-Type: application/json";
        $headers[] = "BinancePay-Timestamp: $timestamp";
        $headers[] = "BinancePay-Nonce: $nonce";
        $headers[] = "BinancePay-Certificate-SN: $api_key";
        $headers[] = "BinancePay-Signature: $signature";

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, 'https://bpay.binanceapi.com/binancepay/openapi/v2/order');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_request);
        $result = json_decode(curl_exec($ch));
        curl_close($ch);

        if (isset($result->status) && $result->status == 'FAIL' || !isset($result->status)) {
            $order->status = 'error';
            $order->saveOrder();
        } elseif ($result->status == 'SUCCESS') {
            // Redirect to Binance Pay
            $order->notes = $result->data->universalUrl;
            $order->saveOrder();
            wp_redirect($result->data->universalUrl);
            exit;
        }
    }

    static function getUSDTFromUSD($usd)
    {
        $url = 'https://min-api.cryptocompare.com/data/pricemulti?';

        $data = array(
            'fsyms' => 'USDT',
            'tsyms' => 'USD',
            //'api_key' => pmpro_getOption('cryptocompare_api_key'),
        );

        $url_params = http_build_query($data);

        $ch = curl_init($url . $url_params);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $output = json_decode(curl_exec($ch));
        curl_close($ch);

        if (isset($output->Response) && $output->Response == 'Error') {
            return $usd;
        } else {
            return $output->USDT->USD * $usd;
        }
    }

}