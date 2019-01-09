<?php

/*
 * Plugin Name: WooCommerce  Safepay Payment Gateway
 * Description: Take credit card payments on your store from Safepay payment gateway.
 * Author: PK SOL
 * Author URI: https://www.pksol.com
 * Version: 1.0.1
 * / 
 

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */

add_filter( 'woocommerce_payment_gateways', 'safepay_add_gateway_class' );
function safepay_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Safepay_Gateway'; // your class name is here
	return $gateways;
}
 
/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */

add_action( 'plugins_loaded', 'safepay_init_gateway_class' );
function safepay_init_gateway_class() {
 
	class WC_Safepay_Gateway extends WC_Payment_Gateway {
 
 		/**
 		 * Class constructor, more about it in Step 3
 		 */
 		public function __construct() {
 	
 			$this->id = 'safepay'; // payment gateway plugin ID
				$this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
				$this->has_fields = true; // in case you need a custom credit card form
				$this->method_title = 'Safepay Gateway';
				$this->method_description = 'Providing Security is our Passion Thank you for your interest in SAFEPay. This system makes it easy for you to manage your own payment account'; // will be displayed on the options page
 			 
 				// gateways can support subscriptions, refunds, saved payment methods,
 				$this->supports = array(
 					'products'
 				);
 			 
 				// Method with all the options fields
 				$this->init_form_fields();
 			 
 				// Load the settings.
 				$this->init_settings();
 				$this->title = $this->get_option( 'title' );
 				$this->description = $this->get_option( 'description' );
 				$this->enabled = $this->get_option( 'enabled' );
 				$this->testmode = 'yes' === $this->get_option( 'testmode' );
 				$this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
 				$this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );
 			 
 				// This action hook saves the settings
 				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
 			 
 				// We need custom JavaScript to obtain a token
 				add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
 			 
 				// You can also register a webhook here
 				// add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
		
 
 		}
 
		/**
 		 * Plugin options, we deal with it in Step 3 too
 		 */
 		public function init_form_fields(){
 			
 			$this->form_fields = array(
 					'enabled' => array(
 						'title'       => 'Enable/Disable',
 						'label'       => 'Enable Safepay Gateway',
 						'type'        => 'checkbox',
 						'description' => '',
 						'default'     => 'no'
 					),
 					'title' => array(
 						'title'       => 'Title',
 						'type'        => 'text',
 						'description' => 'This controls the title which the user sees during checkout.',
 						'default'     => 'Credit Card',
 						'desc_tip'    => true,
 					),
 					'description' => array(
 						'title'       => 'Description',
 						'type'        => 'textarea',
 						'description' => 'This controls the description which the user sees during checkout.',
 						'default'     => 'Pay with your credit card via our super-cool payment gateway.',
 					),
 					'testmode' => array(
 						'title'       => 'Test mode',
 						'label'       => 'Enable Test Mode',
 						'type'        => 'checkbox',
 						'description' => 'Place the payment gateway in test mode using test API keys.',
 						'default'     => 'yes',
 						'desc_tip'    => true,
 					),
 					'test_publishable_key' => array(
 						'title'       => 'Test Publishable Key',
 						'type'        => 'text'
 					),
 					'test_private_key' => array(
 						'title'       => 'Test Private Key',
 						'type'        => 'password',
 					),
 					'publishable_key' => array(
 						'title'       => 'Live Publishable Key',
 						'type'        => 'text'
 					),
 					'private_key' => array(
 						'title'       => 'Live Private Key',
 						'type'        => 'password'
 					)
 				);
	 	}
 
		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 */
		public function payment_fields() {

			echo "<a href='https://www.onlinebiller.com/safe/'>SafePay</a>";
 
		}
 
 	}
}