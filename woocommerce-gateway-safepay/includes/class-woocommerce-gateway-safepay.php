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
		$this->devmode = 'yes' === $this->get_option( 'devmode' );
		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ), 10, 1 );
		}
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
				$output .= "<div id='woocommerce-payment-option-safepay'></div>"; 
			}
			echo $output;
		}
	}

	public function process_payment( $order_id ) {
		global $woocommerce;
	 	$order = wc_get_order( $order_id );
		$tracker = get_post_meta( $order_id, '_tracker-order', true );
		$is_valid = $this->validateCallback($tracker);
		if($is_valid) {
			$order->payment_complete();
			$order->reduce_order_stock();
			$woocommerce->cart->empty_cart();		
			return array(
				'result' => 'success',
				'redirect' => $this->get_return_url( $order )
			);
		} else {
			return array(
			    'result'   => 'failure',
			    'messages' => __( 'There was an error procesing the payment', 'woocommerce-gateway-safepay' ),
			);
		}
	}

	protected function validateCallback($tracker = false) {
		if($tracker) {
			$safepay_settings = get_option('woocommerce_safepay_settings');
		    $payment_safepay_mode = $safepay_settings['devmode'] == 'yes' ? 'sandbox' : 'production';
			if($payment_safepay_mode == 'sandbox') {
				$url = "https://sandbox.api.getsafepay.com/order/v1/".$tracker;
			} else {
				$url = "https://api.getsafepay.com/order/v1/".$tracker;
			}
			$ch =  curl_init($url);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
		    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)');
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			$result = curl_exec($ch);
			if (curl_errno($ch)) { 
			   return curl_error($ch);
			}
			curl_close($ch);
			$result_array = json_decode($result);
			if(empty($result_array->status->errors)) {
				$state = $result_array->data->state;
				if($state === "TRACKER_ENDED") {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} 
	}

}