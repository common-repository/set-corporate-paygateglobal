<?php

defined('ABSPATH') or exit;

global $wpdb;
$table_name = $wpdb->prefix . "SetCorp_data";
$charset_collate = $wpdb->get_charset_collate();


// Make sure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	return;
}


/**
 * Add the gateway to WC Available Gateways
 * 
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + offline gateway
 */
function setcorp_add_paygateglobal_to_gateways($gateways)
{
	$gateways[] = 'SETCORP_PayGateGlabal_Gateway';
	return $gateways;
}


/**
 * Adds plugin page links
 * 
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function setcorp_paygateglobal_gateway_plugin_links($links)
{

	$plugin_links = array(
		'<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=paygateglobal_gateway') . '">' . __('Configure', 'wc-paygateglobal_gateway') . '</a>'
	);
	return array_merge($plugin_links, $links);
}


add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'setcorp_paygateglobal_gateway_plugin_links');

/**
 * PayGateGlobal Payment Gateway
 *
 * Provides an Online Payment by Flooz and Tmoney .
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class 		WC_PayGateGlobal_Payment
 * @extends		WC_Payment_Gateway
 * @version		2.1.0
 * @package		WooCommerce/Classes/Payment
 * @author 		SET Corporate
 */
add_action('plugins_loaded', 'setcorp_PayGateGlobal_init', 11);

add_filter('woocommerce_payment_gateways', 'setcorp_add_paygateglobal_to_gateways');


function Notify($table_name,$wpdb){
	$expire = setcorp_HasExpired($table_name, 1, $wpdb);
						if ($expire) {
							//the trial period has expided
							// we removed the plugin from the payment method
							add_action('admin_notices','setcorp_show_notice');

							
						} else {
							//the trial period still valid
						}
}


function setcorp_PayGateGlobal_init()
{
	
	class SETCORP_PayGateGlabal_Gateway extends WC_Payment_Gateway
	{
		
		public $apikey;
		public $paygateglobal_description;

		public $failed;
		public $iconpath;

		/**
		 * Constructor for the gateway.
		 */
		public function __construct()
		{

			if(file_exists(plugins_url("../assets/PayGateGlobal_logo.png",__FILE__))){
				$this->iconpath=plugins_url("../assets/PayGateGlobal_logo.PNG", __FILE__);
			}else{
				$this->iconpath=plugins_url("PayGateGlobal_logo.PNG", __FILE__);
			}

			$this->id                 = 'paygateglobal_gateway';
			$this->icon               = $this->iconpath;
			$this->has_fields         = true;
			$this->method_title       = __('SET Corporate MobilePay', 'wc-paygateglobal_gateway');
			$this->method_description = __("Permet aux clients de payer avec Moov Money ou Tmoney.<br/>", "wc-paygateglobal_gateway");

			
			// Load the settings.
			$this->setcorp_init_form_fields();
			$this->init_settings();


			// Define user set variables
			$this->title        = $this->get_option('title', 'Paiement sur PayGate Global');
			$this->description  = $this->get_option('description');
			$this->instructions = $this->get_option('instructions', $this->description);
			$this->apikey = $this->get_option('Api_Key');
			$this->paygateglobal_description = $this->get_option('Paygate_description', 'Paiement sur ' . setcorp_GetSiteTitle());

			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		}

		
		/**
		 * Initialize Gateway Settings Form Fields
		 */
		public function setcorp_init_form_fields()
		{

			$this->form_fields = apply_filters('wc-paygateglobal_gateway_form_fields', array(

				'enabled' => array(
					'title'   => __('Activer/Desactiver', 'wc-paygateglobal_gateway'),
					'type'    => 'checkbox',
					'label'   => __('Activer Paiement PayGateGlobal', 'wc-paygateglobal_gateway'),
					'default' => 'yes'
				),

				'Api_Key' => array(
					'title'       => __('Clé API PayGate', 'wc-paygateglobal_gateway'),
					'type'        => 'text',
					'description' => __("La clé d'activation de l'api", 'wc-paygateglobal_gateway'),
					'default'     => __("Clé d'activation", 'wc-paygateglobal_gateway'),
					'desc_tip'    => true,
				),
				'Paygate_description' => array(
					'title'       => __('Description PayGate', 'wc-paygateglobal_gateway'),
					'type'        => 'text',
					'description' => __("Le message qui sera afficher au client lors du paiement sur PayGate", 'wc-paygateglobal_gateway'),
					'default'     => __("Paiement sur " . setcorp_GetSiteTitle(), 'wc-paygateglobal_gateway'),
					'desc_tip'    => true,
				),

				'title' => array(
					'title'       => __('Titre', 'wc-paygateglobal_gateway'),
					'type'        => 'text',
					'description' => __('Cela contrôle le titre du mode de paiement que le client voit lors du paiement.', 'wc-paygateglobal_gateway'),
					'default'     => __('Paiement sur PayGate Global', 'wc-paygateglobal_gateway'),
					'desc_tip'    => true,
				),

				'description' => array(
					'title'       => __('Description', 'wc-paygateglobal_gateway'),
					'type'        => 'textarea',
					'description' => __('Description du mode de paiement que le client verra lors de votre paiement.', 'wc-paygateglobal_gateway'),
					'default'     => __("Vous serez redirigé vers le portail de paiement de PayGate Global", 'wc-paygateglobal_gateway'),
					'desc_tip'    => true,
				),

				'instructions' => array(
					'title'       => __('Instructions', 'wc-paygateglobal_gateway'),
					'type'        => 'textarea',
					'description' => __('Vous serez redirigé vers le portail de paiement de PayGate Global', 'wc-paygateglobal_gateway'),
					'default'     => '',
					'desc_tip'    => true,
				),
			));
		}

		


		/**
		 * Override process_payment of the main gateway
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment($order_id)
		{


			if ($this->failed) {
				
				return;
			}

			//get the currency
			//$currencies=get_woocommerce_currencies();
			$currency_code=get_woocommerce_currency();
			
			$order = wc_get_order($order_id);

			if($currency_code!="XOF"){
				wc_add_notice("Changer la devise en XOF (Franc de la BCEAO) et réessayez s'il vous plaît!",'error');
				$order->update_status('cancelled');
				return;
			}

			////////////////////////////////////////////////////
			
			//somme à payer
			$order_total_cost = $order->get_total();

			//transaction_interne_id
			$paygate_interne_transaction_id = setcorp_TransactionUniqueId();

			//orderid
			$data = $order_id;
			//woocommerce think you page url
			$url = $this->get_return_url($order);


			//callback url
			$callback_url = get_rest_url() . "SetCorporate/v1/payment?order_id=$data|$url|$paygate_interne_transaction_id";


			$urldata = "https://paygateglobal.com/v1/page?token=" . $this->apikey . "&amount=" . round($order_total_cost) . "&description=" . $this->paygateglobal_description . "&identifier=" . $paygate_interne_transaction_id . "&url=" . $callback_url;



			//  redirect
			return array(
				'result' 	=> 'success',
				'redirect'	=> $urldata
			);
			
		}

		
		
	}
	
}


/**
 * Get the site base url
 * @return string
 */
function setcorp_BaseUrl()
{
	return sprintf(
		"%s://%s",
		isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
		$_SERVER['HTTP_HOST']
	);
}


/**
 * Get the site name
 * @return string
 */
function setcorp_GetSiteTitle()
{
	return get_bloginfo('name');
}



/**
 * Genarate a unique identify for the payment transaction
 */
function setcorp_TransactionUniqueId()
{
	return uniqid(true);
}

