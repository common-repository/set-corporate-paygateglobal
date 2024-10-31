<?php

require_once 'SetCorporatePayGateGlobal-function.php';

add_action('rest_api_init', function () {
    register_rest_route('SetCorporate/v1', 'payment', array(
        'methods' => 'GET',
        'callback' => 'setcorp_Validation',
        'permission_callback' => '__return_true'
    ));
});

function setcorp_Validation($datas)
{
    $gateway=new SETCORP_PayGateGlabal_Gateway();

    $ApiToken = $gateway->apikey;
    $orderid = $datas->get_param('order_id');
    $donnee=explode('|',$orderid);

    $orderid=$donnee[0];
    $url=$donnee[1];
    $paygate_interne_transaction_id=$donnee[2];

    //donné à envoyer à la requête
    $response = wp_remote_post(
        'https://paygateglobal.com/api/v2/status',
        array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => array('auth_token' => $ApiToken, 'identifier' => $paygate_interne_transaction_id),
            'cookies' => array()
        )
    );

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        return "Vérifiez votre connexion s'il vous plaît";
    } else {
        $rep = json_decode($response['body'], true);
        //===========donnee recueillis===========
        $tx_reference = $rep['tx_reference'];
        $identifier = $rep['identifier'];
        $amount = $rep['amount'];
        $payment_reference = $rep['payment_reference'];
        $payment_method = $rep['payment_method'];
        $datetime = $rep['datetime'];
        $status = $rep['status'];


        //order
        $order = new WC_Order($orderid);
        

        //Check if the payment is successfull

        if ($status == 0) {
            //success
            setcorp_SuccessPayments($order,$tx_reference,$gateway,$orderid);
        } else {
            //Not paied
            setcorp_FaildPayment($order,$tx_reference,$gateway);
        }

    }
}

function setcorp_SuccessPayments(WC_Order $order,$trasationid,SETCORP_PayGateGlabal_Gateway $gateway,$orderid)
{
    $order->update_status('Processing');
    wc_reduce_stock_levels($orderid);
    $order->payment_complete($trasationid);
    
    //return wp_redirect($gateway->get_return_url($order));
    $returnurl=$gateway->get_return_url($order);
    header("Location:$returnurl");
    exit;
}

function setcorp_FaildPayment(WC_Order $order,$trasationid,SETCORP_PayGateGlabal_Gateway $gateway)
{
    $order->update_status('failed');
    //redirect the user to the checkout page to allow him to retry the payment process
    $location=wc_get_checkout_url();
    //return wp_redirect($location);
    header("Location:$location");
    exit;

}


function setcorp_Notice(){
    $screen=get_current_screen();
    if(!$screen || 'cart'!==$screen->base){
        return;
    }
    echo'<div class="notice notice-success is-dismissible"><p>';
    echo sprintf( __('success payment'));
    echo'</p></div>';
}

