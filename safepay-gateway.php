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
 				// add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
 			 
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
 					'devmode' => array(
 						'title'       => 'Development mode',
 						'label'       => 'Enable Development Mode',
 						'type'        => 'checkbox',
 						'description' => 'Place the payment gateway in Development mode using Dev API key.',
 						'default'     => 'yes',
 						'desc_tip'    => true,
 					),
 					'local_key' => array(
 						'title'       => 'Local Key',
 						'type'        => 'text'
 					),
 					'dev_key' => array(
 						'title'       => 'Dev Key',
 						'type'        => 'text'
 					),
 				);
	 	}


 
		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 */
		public function payment_fields() {

			$safepaySettings = get_option('woocommerce_safepay_settings');

			if ($safepaySettings != FALSE) {

				if ( $safepaySettings['description'] ) {
					echo wpautop( wp_kses_post( $safepaySettings['description'] ) );
					echo "<br>";
				}

				$env = '';

				if ($safepaySettings['devmode'] = 'yes') {
					$env = 'dev';
				} else {
					$env = 'local';
				}

				$devKey = $safepaySettings['dev_key'];
				$localKey = $safepaySettings['local_key'];
				$currency_safePay = get_woocommerce_currency();

				$totalPrice = WC()->cart->total;

				echo "<script src='https://storage.googleapis.com/safepayobjects/api/safepay-checkout.min.js'></script>
				
				<style>[id*='zoid-safepay-button'] {text-align: center;}</style>

				<script>

					function get_checked() {
						
						var inputMethod = jQuery('ul.wc_payment_methods.payment_methods').find('[name=payment_method]:checked');
						
						if(inputMethod.val() == 'safepay') {
							jQuery('#place_order').attr('disabled', 'disabled');
						} else {
							jQuery('#place_order').removeAttr('disabled');
						}

					}

					jQuery('ul.wc_payment_methods.payment_methods input').change(function() {
						get_checked();
					});

					get_checked();


					function isValid() {
					  	var validation_s = true;
					  	
					  	jQuery('.validate-required').each(function(index, el) {
					  		var ell = jQuery(el);
					  		var input = jQuery(el).find('input');
					  		var select = jQuery(el).find('select');
					  		var textarea = jQuery(el).find('textarea');

					  		if (input.length > 0 && input.val() == '') {
					  			validation_s = false;
					  		}

					  		if (select.length > 0 && select.val() == '') {
					  			validation_s = false;
					  		}

					  		if (textarea.length > 0 && textarea.val() == '') {
					  			validation_s = false;
					  		}

					  	});

					  	return validation_s;
					}

					safepay.Button.render({

			           	env: '".$env."',
			           	amount: '".$totalPrice."',

			           	client: {
			               'local': '".$localKey."',
			               'dev': '".$devKey."'
			           	},

			           	payment: function (data, actions) {
			           	
			            		if(isValid() == false) {
								jQuery('#place_order').removeAttr('disabled');
								jQuery('#place_order').trigger('click');
								jQuery('#place_order').attr('disabled', 'disabled');
			           		} else {
			           		   	return actions.payment.create({
				                   	transaction: {
				                    	amount: ".$totalPrice.",
				                    	currency: '".$currency_safePay."'
				                   	}
				               	});
			           		}

			           	},

			           	onCheckout: function(data, actions) {

			           		jQuery('#reference').val(data.reference);
			           		jQuery('#token').val(data.token);
			           		jQuery('#tracker').val(data.tracker);
							
							jQuery('#place_order').removeAttr('disabled');
			            	jQuery('#place_order').trigger('click');

			           	}

			       	}, '.payment_box.payment_method_safepay');

				</script>";

			}

		}



		public function process_payment( $order_id ) {
		 
			global $woocommerce;
		 
			// we need it to get any order detailes
			$order = wc_get_order( $order_id );

			// we received the payment
			$order->payment_complete();
			$order->reduce_order_stock();
			
			// Empty cart
			$woocommerce->cart->empty_cart();
			
			// Redirect to the thank you page
			return array(
				'result' => 'success',
				'redirect' => $this->get_return_url( $order )
			);


		}


 
 	}
}

add_action( 'woocommerce_after_order_notes', 'my_custom_checkout_hidden_field', 10, 1 );
function my_custom_checkout_hidden_field( $checkout ) {

    echo '<div id="user_link_hidden_checkout_field">
            <input type="hidden" class="input-hidden" name="reference" id="reference">
            <input type="hidden" class="input-hidden" name="token" id="token">
            <input type="hidden" class="input-hidden" name="tracker" id="tracker">
    </div>';

}

add_action( 'woocommerce_checkout_update_order_meta', 'save_custom_checkout_hidden_field', 10, 1 );
function save_custom_checkout_hidden_field( $order_id ) {

    if ( ! empty( $_POST['reference'] ) )
        update_post_meta( $order_id, '_reference', sanitize_text_field( $_POST['reference'] ) );

    if ( ! empty( $_POST['token'] ) )
        update_post_meta( $order_id, '_token', sanitize_text_field( $_POST['token'] ) );

    if ( ! empty( $_POST['tracker'] ) )
        update_post_meta( $order_id, '_tracker', sanitize_text_field( $_POST['tracker'] ) );

}