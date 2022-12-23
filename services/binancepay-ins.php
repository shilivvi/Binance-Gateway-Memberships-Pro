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

$merchantTradeNo = pmpro_getParam('merchantTradeNo', 'REQUEST');

if (empty($merchantTradeNo)) {
    //validation failed
    wp_redirect(home_url());
    exit;
}

$orderStatus = getOrderInformation($merchantTradeNo);

if ($orderStatus == 'PAID') {
    $morder = new MemberOrder($merchantTradeNo);
    $morder->getUser();
    $morder->getMembershipLevel();

    if (!empty ($morder) && !empty($morder->status) && $morder->status === 'success') {
        // Checkout was already processed
        wp_redirect(home_url('account'));
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
            $morder->status = "success";
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

    $error_page_id = pmpro_getOption('binance_error_page_id');

    if (empty($error_page_id)) {
        wp_redirect(home_url());
    } else {
        wp_redirect(get_permalink($error_page_id));
    }

    exit;
}

pmpro_unhandled_webhook();
wp_redirect(home_url('account'));
exit;

function getOrderInformation($order_id)
{
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

    // Request body
    $request = array(
        'merchantTradeNo' => $order_id,
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
    curl_setopt($ch, CURLOPT_URL, 'https://bpay.binanceapi.com/binancepay/openapi/v2/order/query');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_request);
    $result = json_decode(curl_exec($ch));
    curl_close($ch);

    if (isset($result->status) && $result->status == 'FAIL' || !isset($result->status)) {
        $morder = new MemberOrder($order_id);
        $morder->updateStatus('error');
        wp_redirect(home_url());
        exit;
    } elseif ($result->status == 'SUCCESS') {
        return $result->data->status;
    }
}