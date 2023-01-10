<?php

class PMProGateway_binance extends PMProGateway
{
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
    static function pmpro_payment_options($options)
    {
        //get options
        $binance_options = self::getGatewayOptions();

        //merge with others
        $options = array_merge($binance_options, $options);

        return $options;
    }

    /**
     * Set payment options for payment settings page.
     */
    static function getGatewayOptions()
    {
        $options = array(
            'gateway_environment',
            'binance_api_key',
            'binance_secret_key',
            'binance_error_page_id',
            'binance_success_page_id',
            'cryptocompare_api_key',
            'binance_currency',
            'currency',
        );

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
                    <?php esc_html_e('Api Key:', BINANCEPMP); ?>
                </label>
            </th>
            <td colspan="2">
                <input type="text" id="binance_api_key" name="binance_api_key"
                       value="<?php echo $options['binance_api_key'] ?? ''; ?>">
                <p class="description">
                    <?php _e('You can get this key in the <a href="https://merchant.binance.com/en/dashboard/developers/api-keys" target="_blank">Binance Pay dashboard</a>', BINANCEPMP); ?>
                </p>
            </td>
        </tr>
        <tr class="gateway gateway_binance" <?php echo $gateway != 'binance' ? 'style="display: none;"' : ''; ?>>
            <th scope="row" valign="top">
                <label for="binance_secret_key">
                    <?php esc_html_e('Secret Key:', BINANCEPMP); ?>
                </label>
            </th>
            <td>
                <input type="text" id="binance_secret_key" name="binance_secret_key"
                       value="<?php echo $options['binance_secret_key'] ?? ''; ?>">
                <p class="description">
                    <?php _e('You can get this key in the <a href="https://merchant.binance.com/en/dashboard/developers/api-keys" target="_blank">Binance Pay dashboard</a>', BINANCEPMP); ?>
                </p>
            </td>
        </tr>
        <tr class="gateway gateway_binance" <?php echo $gateway != 'binance' ? 'style="display: none;"' : ''; ?>>
            <th scope="row" valign="top">
                <label for="cryptocompare_api_key">
                    <?php esc_html_e('Cryptocompare Api Key:', BINANCEPMP); ?>
                </label>
            </th>
            <td>
                <input type="text" id="cryptocompare_api_key" name="cryptocompare_api_key"
                       value="<?php echo $options['cryptocompare_api_key'] ?? ''; ?>">
                <p class="description">
                    <?php _e('The plugin uses <a href="https://www.cryptocompare.com/" target="_blank">CryptoCompare</a> to transfer currency to cryptocurrency. The plugin uses a free version of this service, but you can buy the <a href="https://min-api.cryptocompare.com/pricing" target="_blank">API key</a> and specify it here, which will remove the restrictions of the free version.', BINANCEPMP); ?>
                </p>
            </td>
        </tr>
        <tr class="gateway gateway_binance" <?php echo $gateway != 'binance' ? 'style="display: none;"' : ''; ?>>
            <th scope="row" valign="top">
                <label for="binance_currency">
                    <?php esc_html_e('Currency in the order:', BINANCEPMP); ?>
                </label>
            </th>
            <td>
                <select name="binance_currency" id="binance_currency">
                    <option value=""></option>
                    <?php
                    $supported_currencies = array('BUSD', 'USDT', 'MBOX');
                    foreach ($supported_currencies as $currency) {
                        if ($currency === $options['binance_currency']) {
                            echo '<option value="' . $currency . '" selected>' . $currency . '</option>';
                        } else {
                            echo '<option value="' . $currency . '">' . $currency . '</option>';
                        }
                    }
                    ?>
                </select>
                <p class="description">
                    <?php _e("Binance pay allows you to create an order to pay in only three crypto currencies (BUSD, USDT, MBOX). Select the cryptocurrency from which the order will be created, and USDT will be used by default. The currency you can select in the 'Currency and Tax Settings' section is the main one for the site system. The plugin simply transfers the main currency of the site into one of the crypto currencies you choose to create the order.", BINANCEPMP); ?>
                </p>
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
                <p class="description">
                    <?php _e('User will be redirected to this page with payment problem, by default is home page', BINANCEPMP); ?>
                </p>
            </td>
        </tr>
        <tr class="gateway gateway_binance" <?php echo $gateway != 'binance' ? 'style="display: none;"' : ''; ?>>
            <th scope="row" valign="top">
                <label for="binance_success_page_id">
                    <?php esc_html_e('Payment success page:', BINANCEPMP); ?>
                </label>
            </th>
            <td>
                <?php
                wp_dropdown_pages(
                    array(
                        'name' => 'binance_success_page_id',
                        'show_option_none' => '-- ' . __('Choose One', 'paid-memberships-pro') . ' --',
                        'selected' => $options['binance_success_page_id'],
                        'post_type' => 'page',
                        'post_status' => 'publish'
                    )
                );
                ?>
                <p class="description">
                    <?php _e('User will be redirected to this page after successful payment, default is invoice page', BINANCEPMP); ?>
                </p>
            </td>
        </tr>
        <?php
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

    function PMProGateway($gateway = NULL)
    {
        $this->gateway = $gateway;
        return $this->gateway;
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

    function sendToBinancePay(&$order)
    {
        $api_key = pmpro_getOption('binance_api_key');
        $secret_key = pmpro_getOption('binance_secret_key');
        $binance_client = new BinancePayClient($api_key, $secret_key);

        // Get price in cryptocurrency
        $binance_currency = pmpro_getOption('binance_currency');
        $pmpro_currency = pmpro_getOption('currency');
        $converted_price = PMProGateway_binance::getUSDTFromUSD($pmpro_currency, $binance_currency, (float)$order->PaymentAmount);
        $converted_price = round(floatval($converted_price), 2);

        // Get callback url
        $callback_data = array(
            'action' => 'binancepay-ins',
            'merchantTradeNo' => $order->code,
        );
        $callback_url = admin_url("admin-ajax.php") . '?' . http_build_query($callback_data);

        // Request body
        $request = array(
            'env' => array(
                'terminalType' => 'WEB',
            ),
            'merchantTradeNo' => $order->code,
            'orderAmount' => $converted_price,
            'currency' => 'USDT',
            'goods' => array(
                'goodsType' => '02',
                'goodsCategory' => 'Z000',
                'referenceGoodsId' => $order->membership_level->id,
                'goodsName' => $order->membership_level->name,
            ),
            'cancelUrl' => $callback_url,
            'returnUrl' => $callback_url,
        );

        // If test environment
        if (pmpro_getOption('gateway_environment') === 'sandbox') {
            // Shot expire time
            $request['orderExpireTime'] = round(microtime(true) * 1000) + (60000 * 2);
        }

        $pay_url = $binance_client->createOrder($request);

        if ($pay_url) {
            // Redirect to Binance Pay
            $order->notes = $pay_url;
            $order->saveOrder();
            wp_redirect($pay_url);
            exit;
        } else {
            $order->status = 'error';
            $order->saveOrder();
        }
    }


    /**
     * This feature converts currency
     *
     * @param string $from
     * @param string $to
     * @param float $amount
     * @return float|false
     */
    static function getUSDTFromUSD(string $from, string $to, float $amount)
    {
        $url = 'https://min-api.cryptocompare.com/data/pricemulti?';

        if(empty($to)){
            $to = 'USDT';
        }

        $data = array(
            'fsyms' => $to,
            'tsyms' => $from,
        );

        if (!empty(pmpro_getOption('cryptocompare_api_key'))) {
            $data['api_key'] = pmpro_getOption('cryptocompare_api_key');
        }

        $url_params = http_build_query($data);

        $ch = curl_init($url . $url_params);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $output = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (isset($output['Response']) && $output['Response'] == 'Error') {
            return false;
        } else {
            return $output[$to][$from] * $amount;
        }
    }

}