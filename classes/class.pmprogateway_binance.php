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
        if($gateway == 'binance')
        {
            add_filter('pmpro_include_billing_address_fields', '__return_false');
            add_filter('pmpro_include_payment_information_fields', '__return_false');
            add_filter('pmpro_required_billing_fields', array('PMProGateway_binance', 'pmpro_required_billing_fields'));
            add_filter('pmpro_checkout_default_submit_button', array('PMProGateway_binance', 'pmpro_checkout_default_submit_button'));
            //add_filter('pmpro_checkout_order', array('PMProGateway_example', 'pmpro_checkout_order'));
            //add_filter('pmpro_include_cardtype_field', array('PMProGateway_example', 'pmpro_include_billing_address_fields'));
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

        error_log(print_r(pmpro_getOption('binance_api_key'), 1));

        //show our submit buttons
        ?>
        <span id="pmpro_submit_span">
				<input type="hidden" name="submit-checkout" value="1" />
				<input type="submit" class="<?php echo pmpro_get_element_class( 'pmpro_btn pmpro_btn-submit-checkout', 'pmpro_btn-submit-checkout' ); ?>" value="<?php if($pmpro_requirebilling) { _e('Check Out with Binance Pay', BINANCEPMP ); } else { _e('Submit and Confirm', BINANCEPMP );}?> &raquo;" />
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
        if(empty($order->code)){
            $order->code = $order->getRandomCode();
        }

        //just save, the user will go to binance to pay
        $order->status = "pending";
        $order->saveOrder();

        return true;
    }
}