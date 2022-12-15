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
}