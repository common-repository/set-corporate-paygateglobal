<?php

/**
 * @package SET Corporate MobilePay
 * @version 2.2.1
 */
/*
Plugin Name: SET Corporate MobilePay
Description: Vous permet de recevoir des paiements sur votre site avec <strong>Moov Money</strong> ou <strong>Tmoney</strong>.Pour commencer: Allez dans la configuration pour configurer le API Key
Author: SET Corporate
Version: 2.2.1
Requires PHP:7
License:GPLv2 or later
*/

require_once plugin_dir_path(__FILE__) . 'includes/SetCorporatePayGateGlobal-function.php';
require_once plugin_dir_path(__FILE__) . 'includes/PaymentValidator.php';


function setcorp_paygateglobal_gateway_plugin_setting_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paygateglobal_gateway' ) . '">' . __( 'Réglages', 'wc-paygateglobal_gateway' ) . '</a>'
	);
	return array_merge($plugin_links, $links);
}



add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'setcorp_paygateglobal_gateway_plugin_setting_links');