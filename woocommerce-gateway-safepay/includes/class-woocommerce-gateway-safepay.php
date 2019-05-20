<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Safepay_Gateway extends WC_Payment_Gateway {

	public function __construct() {
		$this->id = 'safepay';
		$this->icon = 'https://storage.googleapis.com/safepay-assets/safepay-logo.jpeg';
		$this->has_fields = true;
		$this->method_title = 'Safepay Checkout';
		$this->method_description = 'Configure our secure payments solution and easily start accepting credit and debit cards globally';
		$this->supports = array('products');
		$this->init_form_fields();	 
		$this->init_settings();
		$this->title = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled = $this->get_option( 'enabled' );
		$this->testmode = 'yes' === $this->get_option( 'testmode' );
		$this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
		$this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );
	 
		// Saves the settings on form submit
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		if ( ! $this->is_valid_for_use() ) {
			$this->enabled = 'no';
		}
	}

	public function is_valid_for_use() {
		return in_array(
			get_woocommerce_currency(),
			apply_filters(
				'woocommerce_paypal_supported_currencies',
				array( 'PKR', 'USD', 'GBP', 'EUR', 'AUD', 'CNY' )
			),
			true
		);
	}

	public function admin_options() {
		if ( $this->is_valid_for_use() ) {
			parent::admin_options();
		} else {
			?>
			<div class="inline error">
				<p>
					<strong><?php esc_html_e( 'Gateway disabled', 'woocommerce-gateway-safepay' ); ?></strong>: <?php esc_html_e( 'Safepay does not support your store currency.', 'woocommerce' ); ?>
				</p>
			</div>
			<?php
		}
	}

	public function init_form_fields(){	
		$this->form_fields = array(
			'enabled' => array(
				'title'       => __( 'Enable/Disable', 'woocommerce-gateway-safepay' ),
				'label'       => __( 'Enable Safepay Checkout', 'woocommerce-gateway-safepay' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-gateway-safepay' ),
				'type'        => 'text',
				'description' => __( 'This controls the title your user sees during checkout.', 'woocommerce-gateway-safepay' ),
				'default'     => __( 'Safepay Checkout', 'woocommerce-gateway-safepay' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-gateway-safepay' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description your user sees during checkout.', 'woocommerce-gateway-safepay' ),
				'default'     => __( 'Securely pay to an escrow with your credit or debit card.', 'woocommerce-gateway-safepay' ),
			),
			'devmode' => array(
				'title'       => __( 'Development mode', 'woocommerce-gateway-safepay' ),
				'label'       => __( 'Enable Development Mode', 'woocommerce-gateway-safepay' ),
				'type'        => 'checkbox',
				'description' => __( 'Place the payment gateway in Development mode using Sandbox API key.', 'woocommerce-gateway-safepay' ),
				'default'     => 'yes',
				'desc_tip'    => true,
			),
			'sandbox_key' => array(
				'title'       => __( 'Sandbox key', 'woocommerce-gateway-safepay' ),
				'type'        => 'text',
				'required'      => true,
			),
			'production_key' => array(
				'title'       => __( 'Production key', 'woocommerce-gateway-safepay' ),
				'type'        => 'text',
				'required'      => true,
			),
		);
 	}


	public function payment_fields() {
		$output = '';
		$safepay_settings = get_option('woocommerce_safepay_settings');
		if ($safepay_settings) {
			if ( $safepay_settings['description'] ) {
				$output .= wpautop( wp_kses_post( $safepay_settings['description'] ) );;
				$output .= "<br>"; 
			}
			echo $output;
		}
			/*
			$env = '';
			if ($safepay_settings['devmode'] === 'yes') {
				$env = 'sandbox';
			} else {
				$env = 'production';
			}
			$sandboxKey = $safepay_settings['sandbox_key'];
			$productionKey = $safepay_settings['production_key'];
			$currency_safePay = get_woocommerce_currency();
			$totalPrice = WC()->cart->total;
			$output .= "
				<script id='safepay-script'>  
					indexx++;
					if(indexx > 1) {

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

					function onClickPlaceOrder(handler) {
						document.querySelector('#place_order').addEventListener('click', handler);
					}
					function toggleButton(actions) {
						return isValid() ? actions.enable() : actions.disable();
					}

					safepay.Button.render({
						env: '".$env."',
						amount: '".$totalPrice."',  
						currency: '".$currency_safePay."',
						client: {
							'sandbox': '".$sandboxKey."',
							'production': '".$productionKey."'
						},
						validate: function(actions) {
							toggleButton(actions);
							onClickPlaceOrder(function() {
								toggleButton(actions);
							});
							jQuery('form[name=checkout] input').on('input', function (e) {
								toggleButton(actions);
							});
							jQuery('form[name=checkout] select').on('change', function (e) {
								toggleButton(actions);
							});
							jQuery('form[name=checkout] textarea').on('input', function (e) {
								toggleButton(actions);
							});
						},
						onClick: function() {
							if(isValid() == false) {
								jQuery('#place_order').removeAttr('disabled');
								jQuery('#place_order').trigger('click');
									jQuery('#place_order').attr('disabled', 'disabled');
								}
							},
							payment: function (data, actions) {
								return actions.payment.create({
									transaction: {
									amount: ".$totalPrice.",
									currency: '".$currency_safePay."'
									}
								});
							},
							onCheckout: function(data, actions) {
								jQuery('#reference').attr('value', data.reference);
								jQuery('#token').attr('value', data.token);
								jQuery('#tracker').attr('value', data.tracker);
								jQuery('#place_order').removeAttr('disabled');
								jQuery('#place_order').trigger('click');
							}
						}, '.payment_box.payment_method_safepay');
					}

				</script>
			";
			*/
	}

	public function process_payment( $order_id ) {
		global $woocommerce;
	 	$order = wc_get_order( $order_id );
		$order->payment_complete();
		$order->reduce_order_stock();
		$woocommerce->cart->empty_cart();		
		return array(
			'result' => 'success',
			'redirect' => $this->get_return_url( $order )
		);
	}



}