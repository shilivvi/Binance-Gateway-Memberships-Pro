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

error_log(print_r($_REQUEST, 1));



// return 200
$returnData = array(
    'returnCode' => 'SUCCESS',
    'returnMessage' => null
);
http_response_code(200);
header("Content-type: application/json; charset=utf-8");
echo json_encode($returnData);
