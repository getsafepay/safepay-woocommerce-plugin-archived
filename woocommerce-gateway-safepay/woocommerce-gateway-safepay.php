<?php
/**
 * Plugin Name: WooCommerce Safepay Gateway
 * Plugin URI: https://www.getsafepay.com
 * Description: Accept Credit and Debit card payments on your store with our escrow solution.
 * Author: Ziyad Parekh
 * Author URI: https://www.getsafepay.com
 * Version: 1.1.0
 * Requires at least: 4.4
 * Tested up to: 5.2
 * WC requires at least: 2.6
 * WC tested up to: 3.6
 * Text Domain: woocommerce-gateway-safepay
 *
 */

if ( !defined('ABSPATH') ) { exit; }

if ( !class_exists( 'WoocommerceGatewaySafepay' ) ) {

	define( 'WC_SAFEPAY_VERSION', '1.1.0' );

	class WoocommerceGatewaySafepay {

		private static $instance;

		public static function getInstance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		private function __clone() {}

		private function __wakeup() {}
		
		private function __construct() {
			if ( ! class_exists( 'WooCommerce' ) ) {
				add_action( 'admin_notices', array($this, 'woocommerce_safepay_missing_wc_notice' ));
				return;
			} else {
				$this->init();
			}
		}

	    public function init() {
			add_action( 'admin_init', array( $this, 'install' ) );
			require_once dirname( __FILE__ ) . '/includes/class-woocommerce-gateway-safepay.php';
			add_filter( 'woocommerce_payment_gateways', array( $this, 'safepay_add_gateway') );
			add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'safepay_display_admin_order_meta'), 10, 1 );
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_safepay_checkout_hidden_fields'), 10, 1 );
			add_action( 'woocommerce_after_order_notes', array( $this, 'safepay_checkout_hidden_fields'), 10, 1 );
			if ( !is_admin() ) {
				add_action('wp_enqueue_scripts', array( $this, 'add_static_safepay_files'), 1);
			}
			add_action( 'wp_ajax_get_cart_total', array( $this, 'ajax_get_cart_total' ) );
			add_action( 'wp_ajax_nopriv_get_cart_total', array( $this, 'ajax_get_cart_total' ) );
	    }

	    public function ajax_get_cart_total() {
	    	echo WC()->cart->total;
	    	wp_die();
	    }

		public function safepay_add_gateway( $methods ) {
			$methods[] = 'WC_Safepay_Gateway';
			return $methods;
		}

	    public function woocommerce_safepay_missing_wc_notice() {
			$output = '';
			$output .= '<div class="error">';
			$output .= '    <p>';
			$output .= '    	<strong>';
			$output .= sprintf( esc_html__( 'Safepay requires WooCommerce to be installed and active. You can download %s here.', 'woocommerce-gateway-safepay' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' );
			$output .= '    	</strong>';
			$output .= '    </p>';
			$output .= '</div>';
			echo $output;
		}

		public function woocommerce_gateway_safepay_init() {
			require_once dirname( __FILE__ ) . '/includes/class-woocommerce-gateway-safepay.php';
		}

		public function install() {
			if ( ! is_plugin_active( plugin_basename( __FILE__ ) ) ) { return; }
			$this->update_plugin_version();
		}

		public function update_plugin_version() {
			delete_option( 'woocommerce_safepay_version' );
			update_option( 'woocommerce_safepay_version', WC_SAFEPAY_VERSION );
		}	

		function add_static_safepay_files() {
	        if(is_checkout()) {
				wp_register_script('safepayobjects-scripts', 'https://storage.googleapis.com/safepayobjects/api/safepay-checkout.min.js', array(), '1.0.0');
		        wp_enqueue_script('safepayobjects-scripts');
		        $safepay_settings = get_option('woocommerce_safepay_settings');
		        $required_values = array();
		        if($safepay_settings) {
		        	$required_values = array(
		        		'enviroment' => $safepay_settings['devmode'] == 'yes' ? 'sandbox' : 'production',
		        		'sandboxKey' => $safepay_settings['sandbox_key'],
		        		'productionKey' => $safepay_settings['production_key'],
		        		'passthrough' => $safepay_settings['passthrough'] === 'yes' ? true : false,
		        		'currencySafePay' => get_woocommerce_currency(),
		        		'ajax_url' => admin_url('admin-ajax.php'),
		        	);
		        }		        
	        	wp_register_script('safepay-scripts', plugins_url('assets/js/safepay_script.js', __FILE__ ), array('jquery'), '1.0.0');
	        	wp_localize_script( 'safepay-scripts', 'required_values', $required_values );
	        	wp_enqueue_script('safepay-scripts');
	        	wp_register_style('safepay-style', plugins_url('assets/css/safepay_style.css', __FILE__ ));
	        	wp_enqueue_style('safepay-style');
			}
		}

		public function safepay_display_admin_order_meta($order) {		    
			$reference = get_post_meta( $order->id, '_reference-order', true );
			$token = get_post_meta( $order->id, '_token-order', true );
			$tracker = get_post_meta( $order->id, '_tracker-order', true );
			if($reference) {
				echo '<p><strong>'.__('Reference', 'woocommerce-gateway-safepay').':</strong> ' . get_post_meta( $order->id, '_reference-order', true ) . '</p>';
			}
			if($token) {
			    echo '<p><strong>'.__('Token', 'woocommerce-gateway-safepay').':</strong> ' . get_post_meta( $order->id, '_token-order', true ) . '</p>';	
			}
			if($tracker) {
			    echo '<p><strong>'.__('Tracker', 'woocommerce-gateway-safepay').':</strong> ' . get_post_meta( $order->id, '_tracker-order', true ) . '</p>';
			}
		}

		public function save_safepay_checkout_hidden_fields( $order_id ) {
		    if ( ! empty( $_POST['reference'] ) ) {
		        update_post_meta( $order_id, '_reference-order', sanitize_text_field( $_POST['reference'] ) );
		    }
		    if ( ! empty( $_POST['token'] ) ) {
		        update_post_meta( $order_id, '_token-order', sanitize_text_field( $_POST['token'] ) );
		    }
		    if ( ! empty( $_POST['tracker'] ) ) {
		        update_post_meta( $order_id, '_tracker-order', sanitize_text_field( $_POST['tracker'] ) );
		    }
		}

		public function safepay_checkout_hidden_fields( $checkout ) {
		    $output  = '';
		    $output .= '<div id="user_link_hidden_checkout_field">';
		    $output .= '	<input type="hidden" class="input-hidden" name="reference" id="reference">';
		    $output .= '	<input type="hidden" class="input-hidden" name="token" id="token">';
		    $output .= '	<input type="hidden" class="input-hidden" name="tracker" id="tracker">';
		    $output .= '</div>';
			echo $output;
		}

	}
	
}


function init_object_woocommerce_gateway_safepay () {
    $woocommerce_gateway_safepay = WoocommerceGatewaySafepay::getInstance();
}

add_action( 'plugins_loaded', 'init_object_woocommerce_gateway_safepay');

