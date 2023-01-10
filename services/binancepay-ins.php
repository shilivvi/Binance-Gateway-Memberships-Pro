<?php

//In case the file is loaded directly
if (!defined("ABSPATH")) {
    global $isapage;
    $isapage = true;

    define('WP_USE_THEMES', false);
    require_once(dirname(__FILE__) . '/../../../../wp-load.php');
}

// Some globals
global $wpdb, $gateway_environment, $logstr;

pmpro_doing_webhook('binance', true);

$error_page_id = pmpro_getOption('binance_error_page_id');
$api_key = pmpro_getOption('binance_api_key');
$secret_key = pmpro_getOption('binance_secret_key');
$merchantTradeNo = pmpro_getParam('merchantTradeNo', 'REQUEST');

if (empty($merchantTradeNo)) {
    //validation failed
    if (empty($error_page_id)) {
        wp_redirect(home_url());
    } else {
        wp_redirect(get_permalink($error_page_id));
    }
    exit;
}

$binance_client = new BinancePayClient($api_key, $secret_key);
$orderStatus = $binance_client->getOrderStatus(array('merchantTradeNo' => $merchantTradeNo));

error_log(print_r($orderStatus, 1));

if ($orderStatus == 'PAID') {
    $morder = new MemberOrder($merchantTradeNo);
    $morder->getUser();
    $morder->getMembershipLevel();

    if (!empty ($morder) && !empty($morder->status) && $morder->status === 'success') {
        // Checkout was already processed
        $morder->notes = '';
        $morder->saveOrder();

        if (empty($error_page_id)) {
            wp_redirect(home_url());
        } else {
            wp_redirect(get_permalink($error_page_id));
        }
    } else {
        // Extend membership if renewal.
        // Added manually because pmpro_checkout_level filter is not run.
        $morder->membership_level = pmpro_checkout_level_extend_memberships($morder->membership_level);

        // Set the start date to current_time('mysql') but allow filters (documented in preheaders/checkout.php)
        $startdate = apply_filters("pmpro_checkout_start_date", "'" . current_time('mysql') . "'", $morder->user_id, $morder->membership_level);

        // Fix expiration date
        if (!empty($morder->membership_level->expiration_number)) {
            $enddate = "'" . date_i18n("Y-m-d", strtotime("+ " . $morder->membership_level->expiration_number . " " . $morder->membership_level->expiration_period, current_time("timestamp"))) . "'";
        } else {
            $enddate = "NULL";
        }

        $custom_level = array(
            'user_id' => $morder->user_id,
            'membership_id' => $morder->membership_level->id,
            'code_id' => '',
            'initial_payment' => $morder->membership_level->initial_payment,
            'billing_amount' => $morder->membership_level->billing_amount,
            'cycle_number' => $morder->membership_level->cycle_number,
            'cycle_period' => $morder->membership_level->cycle_period,
            'billing_limit' => $morder->membership_level->billing_limit,
            'trial_amount' => $morder->membership_level->trial_amount,
            'trial_limit' => $morder->membership_level->trial_limit,
            'startdate' => $startdate,
            'enddate' => $enddate
        );

        if (pmpro_changeMembershipLevel($custom_level, $morder->user_id) !== false) {
            $morder->status = 'success';
            $morder->notes = '';
            $morder->saveOrder();
            $morder->getMemberOrderByID($morder->id);

            do_action("pmpro_after_checkout", $morder->user_id, $morder);

            $pmproemail = new PMProEmail();
            $pmproemail->sendInvoiceEmail(get_userdata($morder->user_id), $morder);
        }

        wp_redirect(pmpro_url("confirmation", "?level=" . $morder->membership_level->id));
    }

    exit;
}

if ($orderStatus == 'EXPIRED' || $orderStatus == 'CANCELED' || $orderStatus == 'ERROR') {
    $morder = new MemberOrder($merchantTradeNo);
    $morder->updateStatus('failed');
    $morder->notes = '';
    $morder->saveOrder();

    if (empty($error_page_id)) {
        wp_redirect(home_url());
    } else {
        wp_redirect(get_permalink($error_page_id));
    }

    exit;
}

pmpro_unhandled_webhook();
if (empty($error_page_id)) {
    wp_redirect(home_url());
} else {
    wp_redirect(get_permalink($error_page_id));
}
exit;