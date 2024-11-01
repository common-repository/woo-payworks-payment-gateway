<?php
/* @wordpress-plugin
 * Plugin Name:       WooCommerce PayWorks Payment Gateway
 * Plugin URI:        https://www.payworks.bs
 * Description:       Pay using PayWorks. Add PayWorks as your payment processor.
 * Version:           5.1
 * Author:            PayWorks
 * Author URI:        https://www.83ideas.com
 * Text Domain:       woocommerce-payworks-payment-gateway
 * Domain Path: /languages
 */
$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
if(in_array('woocommerce/woocommerce.php', $active_plugins)){
	add_filter('woocommerce_payment_gateways', 'add_payworks_payment_gateway');
	function add_payworks_payment_gateway( $gateways ){
		$gateways[] = 'WC_PayWorks_Payment_Gateway';
		return $gateways; 
	}

	add_action('plugins_loaded', 'init_payworks_payment_gateway');
	function init_payworks_payment_gateway(){
		require 'class-woocommerce-payworks-payment-gateway.php';
	}
	
	add_action('template_redirect', 'wc_custom_redirect_after_purchase');
	function wc_custom_redirect_after_purchase() {
    	global $wp;
		$order_id =  intval( str_replace( 'checkout/order-received/', '', $wp->request ) );
		if($order_id == ''){
			$order_id =  intval( str_replace( 'checkout-2/order-received/', '', $wp->request ) );
		}
		
        // Get an instance of the WC_Order object
        $order = wc_get_order( $order_id );
        if($order->payment_method == 'payworks_payment'){
			$options = get_option('woocommerce_payworks_payment_settings');
			$my_secure_key = $options['payworkskey'];
			// DO NOT CHANGE THE KEY FOR EMAIL
			$emailkey = "P@yWorK$@KEy5941";
			$pro_feature = $options['pro_feature'];
	    	if (is_checkout() && !empty($wp->query_vars['order-received'])) {
				$order = new WC_Order($wp->query_vars['order-received']);
    	    	$quantity = 0;
        		if (count($order->get_items()) > 0) {
            		foreach ($order->get_items() as $item) {
                		if (!empty($item)) {
                    		$quantity+= $item['qty'];
	                	}
    	        	}
        		}
        		if($pro_feature == 'no'){
	        	    switch ($quantity) {
    	        	    case 1:
						    $totalamount = $order->total;
						    $ownerdata = get_bloginfo('admin_email');
						    $encrypted_amount=openssl_encrypt($totalamount,"AES-128-ECB",$my_secure_key);
						    $details = http_build_query($order);
						    $encrypted_owner=openssl_encrypt($ownerdata,"AES-128-ECB",$emailkey);
        	        	    wp_redirect('https://www.payworks.bs/processor/payviapayworks.html?&action=thirdparty&gateway='.$encrypted_amount.'&offset='.$encrypted_owner.'&details='.$details);
            	    	    break;
            		    default:
						    $totalamount = $order->total;
						    $ownerdata = get_bloginfo('admin_email');
						    $encrypted_amount=openssl_encrypt($totalamount,"AES-128-ECB",$my_secure_key);
						    $details = http_build_query($order);
						    $encrypted_owner=openssl_encrypt($ownerdata,"AES-128-ECB",$emailkey);
        	        	    wp_redirect('https://www.payworks.bs/processor/payviapayworks.html?&action=thirdparty&gateway='.$encrypted_amount.'&offset='.$encrypted_owner.'&details='.$details);
                		    break;
	        	    }
        		}
    	    	
    		}	
        }
	}

	add_action( 'plugins_loaded', 'payworks_payment_load_plugin_textdomain' );
	function payworks_payment_load_plugin_textdomain() {
	  load_plugin_textdomain( 'woocommerce-payworks-payment-gateway', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
	}
}