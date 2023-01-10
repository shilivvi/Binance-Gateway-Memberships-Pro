<?php

//In case the file is loaded directly
if (!defined("ABSPATH")) {
    global $isapage;
    $isapage = true;

    define('WP_USE_THEMES', false);
    require_once(dirname(__FILE__) . '/../../../../wp-load.php');
}

pmpro_doing_webhook('binance', true);

// Get request headers
$headers = array_change_key_case(getallheaders(), CASE_LOWER);

// Check necessary headers
if (!isset($headers['binancepay-nonce']) && !isset($headers['binancepay-timestamp']) && !isset($headers['binancepay-signature'])) {
    binanceWebhookExit(false);
}

$request_body = file_get_contents('php://input');
$error_page_id = pmpro_getOption('binance_error_page_id');
$api_key = pmpro_getOption('binance_api_key');
$secret_key = pmpro_getOption('binance_secret_key');
$binance_client = new BinancePayClient($api_key, $secret_key);

// Verify notification
$valid = $binance_client->verifyWebhookNotice($headers['binancepay-timestamp'], $headers['binancepay-nonce'], $headers['binancepay-signature'], $request_body);

if (!$valid) {
    // Validation failed
    binanceWebhookExit(false);
}

$request_body = json_decode($request_body);
$biz_type = $request_body->bizType ?? '';
$biz_status = $request_body->bizStatus ?? '';
$biz_data = json_decode($request_body->data);

if (!empty($biz_type) && !empty($biz_status) && $biz_type === 'PAY') {
    $merchantTradeNo = $biz_data->merchantTradeNo ?? '';

    if (empty($merchantTradeNo)) {
        // Exit
        binanceWebhookExit(false);
    }

    $order = new MemberOrder($merchantTradeNo);
    $order->getUser();
    $order->getMembershipLevel();

    if ($biz_status === 'PAY_SUCCESS') {
        if (!empty ($morder) && !empty($morder->status) && $morder->status === 'success') {
            // Checkout was already processed
            $morder->notes = '';
            $morder->saveOrder();
        } else {
            // Extend membership if renewal.
            // Added manually because pmpro_checkout_level filter is not run.
            $morder->membership_level = pmpro_checkout_level_extend_memberships($morder->membership_level);

            // Set the start date to current_time('mysql') but allow filters (documented in preheaders/checkout.php)
            $start_date = apply_filters("pmpro_checkout_start_date", "'" . current_time('mysql') . "'", $morder->user_id, $morder->membership_level);

            // Fix expiration date
            if (!empty($morder->membership_level->expiration_number)) {
                $end_date = "'" . date_i18n("Y-m-d", strtotime("+ " . $morder->membership_level->expiration_number . " " . $morder->membership_level->expiration_period, current_time("timestamp"))) . "'";
            } else {
                $end_date = "NULL";
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
                'startdate' => $start_date,
                'enddate' => $end_date
            );

            if (pmpro_changeMembershipLevel($custom_level, $morder->user_id) !== false) {
                $morder->status = 'success';
                $morder->notes = '';
                $morder->saveOrder();
                $morder->getMemberOrderByID($morder->id);

                do_action("pmpro_after_checkout", $morder->user_id, $morder);

                $pmpro_email = new PMProEmail();
                $pmpro_email->sendInvoiceEmail(get_userdata($morder->user_id), $morder);
            }
        }
    } elseif ($biz_status === 'PAY_CLOSED') {
        $order->updateStatus('failed');
        $order->notes = '';
        $order->saveOrder();
    }

    binanceWebhookExit(true);
}

pmpro_unhandled_webhook();

binanceWebhookExit(false);


function binanceWebhookExit($success)
{
    $returnData = array(
        'returnMessage' => null
    );

    if ($success) {
        $returnData['returnCode'] = 'SUCCESS';
    } else {
        $returnData['returnCode'] = 'FAIL';
    }

    // return 200
    http_response_code(200);
    header("Content-type: application/json; charset=utf-8");
    echo json_encode($returnData);
    exit;
}