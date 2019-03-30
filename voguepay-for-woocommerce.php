<?php
/*
Plugin Name: WooCommerce Voguepay Payment Gateway
Plugin URI: http://codemypain.com
Description: Voguepay payment gateway plugin for woocommerce
Version: 1.0.1
Author: Isaac Oyelowo
Author URI: http://www.isaacoyelowo.com
*/

/*
#begin plugin
## CodeMyPain
### Solving real life issues one code at a time.
*/

// define plugin directory
define( 'VPG_URL', plugin_dir_url( __FILE__ ) );
require_once dirname(__FILE__) . '/functions.php';
function voguepay_woocommerce() 
{
	require_once dirname(__FILE__) . '/voguepay.class.php';
	if( !is_admin() )
	{
		new WC_Voguepay_Woocommerce();
	}
}
function add_voguepay_gateway( $methods )
{
	$methods[] = 'WC_Voguepay_Woocommerce';
	return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_voguepay_gateway' );
add_action( 'plugins_loaded', 'voguepay_woocommerce', 0 );

?>