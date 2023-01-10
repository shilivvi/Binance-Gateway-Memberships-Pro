<?php
/*
Plugin Name: 		Binance Gateway Addon for Memberships Pro
Description: 		Binance Gateway Addon for Memberships Pro
Version: 			0.4
Author: 			PROYKEY
License: 			GPLv2
License URI: 		https://proykey.by
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
        require_once(PMPRO_BINANCE_GATEWAY_DIR . "/services/binancepay-client.php");

        // Load classes init method
        add_action('init', array('PMProGateway_binance', 'init'));

        // Register webhooks
        add_action('wp_ajax_nopriv_binancepay-ins', 'pmpro_wp_ajax_binancepay_ins');
        add_action('wp_ajax_binancepay-ins', 'pmpro_wp_ajax_binancepay_ins');

        add_action('wp_ajax_nopriv_binancepay-webhook', 'pmpro_wp_ajax_binancepay_webhook');
        add_action('wp_ajax_binancepay-webhook', 'pmpro_wp_ajax_binancepay_webhook');
    }

}

function pmpro_wp_ajax_binancepay_ins()
{
    require_once(PMPRO_BINANCE_GATEWAY_DIR . "/services/binancepay-ins.php");
    exit;
}

function pmpro_wp_ajax_binancepay_webhook()
{
    require_once(PMPRO_BINANCE_GATEWAY_DIR . "/services/binancepay-webhook.php");
    exit;
}