<?php
/*
Plugin Name: 		Binance Gateway Addon for Memberships Pro
Plugin URI: 		https://test.com
Description: 		Description
Version: 			1.1.8
Author: 			Author
License: 			GPLv2
License URI: 		http://www.gnu.org/licenses/gpl-2.0.html
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

define("PMPRO_BINANCE_GATEWAY_DIR", dirname(__FILE__));
define("BINANCEPMP", "binance_pmp_gateway");

if (!function_exists('binance_pmp_gateway_load')) {

    // Load plugin
    add_action('plugins_loaded', 'binance_pmp_gateway_load', 20);

    function binance_pmp_gateway_load()
    {
        // Check paid memberships pro
        if (!class_exists('PMProGateway')) {
            return;
        }

        require_once(PMPRO_BINANCE_GATEWAY_DIR . "/classes/class.pmprogateway_binance.php");

        // load classes init method
        add_action('init', array('PMProGateway_binance', 'init'));
    }

}