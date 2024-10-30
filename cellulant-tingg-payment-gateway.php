<?php
/**
 * Plugin Name: Cellulant Tingg Payment Gateway
 * Plugin URI:  https://tingg.africa/services/#developers
 * Description: A WordPress-WooCommerce plugin for merchants to integrate Tingg payment gateway on their online shops offering their customers payment options across Africa.
 * Version:     1.0.0
 * Author:      Cellulant Corporation
 * Author URI:  https://tingg.africa
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/*
 * Required files
 */
require('includes/TinggPaymentGatewayConstants.php');
require('includes/TinggPaymentGatewayUtils.php');

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'add_tingg_gateway_class');
function add_tingg_gateway_class($gateways)
{
    $gateways[] = 'WC_Gateway_Tingg';
    return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'init_tingg_gateway_class');
function init_tingg_gateway_class() 
{
 
    class WC_Gateway_Tingg extends WC_Payment_Gateway 
    {
        //WC_Payment_Gateway
        public $id;
        public $icon;
        public $supports;
        public $has_fields;
        public $method_title;
        public $method_description;

        //Payment gateway configurations
        public $title;
        public $iv_key;
        public $enabled;
        public $testmode;
        public $access_key;
        public $secret_key;
        public $description;
        public $service_code;
        public $checkout_url;
        public $payment_period;

        public function __construct()
        {
			$this->icon = '';
			$this->has_fields = true;
			$this->id = TinggWordPressConstants::PAYMENT_GATEWAY;
			$this->method_title = ucfirst(TinggWordPressConstants::BRAND_NAME);
			$this->method_description = ucfirst(TinggWordPressConstants::PAYMENT_GATEWAY_DESCRIPTION);

			$this->supports = array(
				'products'
			);

			$this->init_form_fields();

			$this->init_settings();
			$this->title = $this->get_option('title');
			$this->enabled = $this->get_option('enabled');
			$this->description = $this->get_option('description');
			$this->testmode = 'yes' === $this->get_option('testmode');
			$this->payment_period = $this->get_option('payment_period');

			$this->iv_key = $this->testmode ? $this->get_option('test_iv_key') : $this->get_option('live_iv_key');
			$this->secret_key = $this->testmode ? $this->get_option('test_secret_key') : $this->get_option('live_secret_key');
			$this->access_key = $this->testmode ? $this->get_option('test_access_key') : $this->get_option('live_access_key');
			$this->checkout_url = $this->testmode ? $this->get_option('test_checkout_url') : $this->get_option('live_checkout_url');
			$this->service_code = $this->testmode ? $this->get_option('test_service_code') : $this->get_option('live_service_code');

			// action hook to saves the settings
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

			// custom JavaScript to obtain a token
			add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

			//payment gateway webhook
			add_action('woocommerce_api_tingg_payment_webhook', array($this, 'webhook'));
		}
 
		/**
 		 * Plugin form field options
 		 */
        public function init_form_fields()
        {
			$this->form_fields = array(
				'enabled' => array(
					'title'       => 'Enable/Disable',
					'label'       => 'Enable Payment Gateway',
					'type'        => 'checkbox',
					'default'     => 'no'
				),
				'title' => array(
					'title'       => 'Title',
					'type'        => 'text',
					'description' => 'This controls the title which the user sees during checkout.',
					'default'     => ucfirst(TinggWordPressConstants::BRAND_NAME),
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => 'Description',
					'type'        => 'textarea',
					'description' => 'This controls the description which the user sees during checkout.',
					'default'     => 'Pay with banks, mobile money, and cards throughout Africa.',
				),
				'payment_period' => array(
					'title' => 'Payment period',
					'type' => 'number',
					'description' => 'This sets the amount of time in minutes before a checkout request on an order expires',
					'default' => '1440'
				),
				'testmode' => array(
					'title'       => 'Test mode',
					'label'       => 'Enable Test Mode',
					'type'        => 'checkbox',
					'description' => 'Place the payment gateway in test mode using test API keys.',
					'default'     => 'yes',
					'desc_tip'    => true,
				),
				// test fields
                'test_checkout_url' => array(
					'title' => 'Test Checkout URL',
					'type' => 'text'
				),
				'test_service_code' => array(
					'title' => 'Test Service Code',
					'type' => 'text'
				),
				'test_iv_key' => array(
					'title'       => 'Test IV Key',
					'type'        => 'text'
				),
				'test_secret_key' => array(
					'title'       => 'Test Secret Key',
					'type'        => 'text',
				),
				'test_access_key' => array(
					'title'       => 'Test Access Key',
					'type'        => 'text',
				),
                // live fields
                'live_checkout_url' => array(
					'title' => 'Live Checkout URL',
					'type' => 'text'
				),
                'live_service_code' => array(
					'title' => 'Live Service Code',
					'type' => 'text'
				),
				'live_iv_key' => array(
					'title' => 'Live IV Key',
					'type' => 'text'
				),
				'live_secret_key' => array(
					'title' => 'Live Secret Key',
					'type' => 'text'
				),
				'live_access_key' => array(
					'title' => 'Live Access Key',
					'type' => 'text',
				)
			);
		}
		 
		/*
		 * Custom JavaScript
		 */
		public function payment_scripts() 
		{
			// we need JavaScript to process a token only on cart/checkout pages
			if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
				return;
			}
		
			// if our payment gateway is disabled, we do not have to enqueue JS too
			if ($this->enabled === 'no') {
				return;
			}
		}
 
		/*
 		 * Fields validation
		 */
        public function validate_fields()
        {
			$billing_country = sanitize_text_field($_POST['billing_country']);

			$supported_countries = array_map(function ($value) {
				return $value['countryCode'];
			}, TinggWordPressConstants::COUNTRIES);

			if (!in_array($billing_country, $supported_countries)) {
				wc_add_notice('<strong>Billing Country</strong> is not supported. Please contact support for further assitance', 'error');
				return false;
			}

			return true;
		}
 
		/*
		 * Processing the payment here
		 */
        public function process_payment( $order_id )
        {
			global $woocommerce;

			// we need it to get any order details
			$order = wc_get_order( $order_id );

			// checkout tranasction description
			$order_excerpt = array_reduce($order->get_items(), function($carry, $item) {
				$format = '%d x %s, ';

				$quantity = $item->get_quantity();

				$product = $item->get_product();
    			$product_name = $product->get_name();
				return $carry .= sprintf($format, $quantity, $product_name);
			});

			// filter to get selected country
			$order_country = array_filter(TinggWordPressConstants::COUNTRIES, function($item) use($order) {
				return $item['countryCode'] == $order->get_billing_country();
			});

			// array with parameters for API interaction
			$payload = array(
				"accessKey" => $this->access_key,
				"accountNumber" => $order->get_id(),
				"serviceCode" => $this->service_code,
				"requestAmount" => $order->get_total(),
				"MSISDN" => $order->get_billing_phone(),
				"merchantTransactionID" => $order->get_id(),
				"customerEmail" => $order->get_billing_email(),
				"customerLastName" => $order->get_billing_last_name(),
				"customerFirstName" => $order->get_billing_first_name(),
				"requestDescription" => rtrim(trim($order_excerpt), ','),
				"currencyCode" => $order_country[array_keys($order_country)[0]]['currencyCode'],
				"dueDate" => date("Y-m-d H:i:s", strtotime("+" . $this->payment_period . " minutes")),

				// webhooks
				"failRedirectUrl" => get_permalink(get_page_by_path('shop')),
				"successRedirectUrl" => $order->get_checkout_order_received_url(),
				"paymentWebhookUrl" => get_site_url() . '/wc-api/tingg_payment_webhook',
			);

			$checkout_payment_url = sprintf(
				$this->checkout_url . "?params=%s&accessKey=%s&countryCode=%s",
                TinggPaymentGatewayUtils::encryptCheckoutRequest($this->iv_key, $this->secret_key, $payload),
				$this->access_key, 
				$order->get_billing_country()
			);

			//clear the cart
			// $woocommerce->cart->empty_cart();
			
			// redirct to Tingg checkout express
			return array('result' => 'success', 'redirect' => $checkout_payment_url);
		}
 
		/*
		 * Payment webhook callback
		 */
        public function webhook()
        {
			$callback_json_payload = file_get_contents('php://input');
			$payload = json_decode($callback_json_payload, true);
			$order = wc_get_order($payload['accountNumber']);
            //successful payments
			if (in_array($payload["requestStatusCode"], [176, 178])) {
				// mark order as fully paid
				if ($payload["requestStatusCode"] == 178) {
					$order->payment_complete();
				}

				$order->reduce_order_stock();

				// add a note to the order
				if ($payload["requestStatusCode"] == 176) {
					$note =  sprintf("Order #%s has been partially paid", strval($payload['accountNumber']));
				}

				if ($payload["requestStatusCode"] == 178) {
					$note =  sprintf("Order #%s has been paid in full", strval($payload['accountNumber']));
				}

				$order->add_order_note( $note );
				
				// send back a response to acknowledge the payment
				$response = array(
    		        "statusCode" => 183,
    		        "statusDescription" => "Payment accepted",
    		        "receiptNumber" =>  $payload['accountNumber'],
    		        "checkoutRequestID" => $payload["checkoutRequestID"],
    		        "merchantTransactionID" => $payload["merchantTransactionID"]
    		    );
    		    
    		    echo json_encode($response, true);
			}
            exit();
		}
 	}
}
