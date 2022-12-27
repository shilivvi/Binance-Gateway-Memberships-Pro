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

//error_log(print_r($_REQUEST, 1));

$bizType = pmpro_getParam('bizType', 'REQUEST');
$bizStatus = pmpro_getParam('bizStatus', 'REQUEST');
$data = pmpro_getParam('data', 'REQUEST');

if(isset($bizType) && $bizType == 'PAY'){
    if(isset($bizStatus) && $bizStatus == 'PAY_SUCCESS'){
        if(isset($data['merchantTradeNo'])){
            $merchantTradeNo = $data['merchantTradeNo'];
            $morder = new MemberOrder($merchantTradeNo);
            $morder->getUser();
            $morder->getMembershipLevel();

            if (!empty ($morder) && !empty($morder->status) && $morder->status === 'success') {
                // Checkout was already processed
                $morder->notes = '';
                $morder->saveOrder();
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

                    do_action("pmpro_after_checkout", $morder->user_id, $morder);

                    $pmproemail = new PMProEmail();
                    $pmproemail->sendInvoiceEmail(get_userdata($morder->user_id), $morder);
                }
            }
        }
    }elseif (isset($bizStatus) && $bizStatus == 'PAY_CLOSED'){
        if(isset($data['merchantTradeNo'])){
            $merchantTradeNo = $data['merchantTradeNo'];
            $morder = new MemberOrder($merchantTradeNo);
            $morder->updateStatus('failed');
            $morder->notes = '';
            $morder->saveOrder();
        }
    }
}

pmpro_unhandled_webhook();

// return 200
$returnData = array(
    'returnCode' => 'SUCCESS',
    'returnMessage' => null
);
http_response_code(200);
header("Content-type: application/json; charset=utf-8");
echo json_encode($returnData);
exit;