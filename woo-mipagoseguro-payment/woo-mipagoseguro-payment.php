<?php
    /*
    Plugin Name: woo-mipagoseguro-payment
    Plugin URI: https://mipagoseguro.co
    Description: Este plugin permite mostrar mipagoseguro en las tiendas woocommerce.
    Version:1.0
    Author:Leonardo Espinosa
    Author URI:https://mipagoseguro.co
    License:GPLv2 o versión más reciente
*/
if (! defined('ABSPATH')) {
    exit;
}

function woocommerce_mps_activation()
{
    $all_active_plugins = get_option('active_plugins');
    if (is_multisite()) {
        $all_active_plugins = array_merge($all_active_plugins, wp_get_active_network_plugins());
    }

    $all_active_plugins = apply_filters('active_plugins', $all_active_plugins);

    if (! stripos(implode($all_active_plugins), '/woocommerce.php')) {
        deactivate_plugins(plugin_basename(__FILE__)); // Deactivate ourself.

        $message = sprintf(__('Disculpa ! Para poder utilizar el plugin %s, necesita instalar y activar woocommerce.', 'woo-mipagoseguro-payment'), 'Mipagoseguro');
        wp_die($message, 'WooCommerce Mipagoseguro Payment', array('back_link' => true));
    }
}

register_activation_hook(__FILE__, 'woocommerce_mps_activation');

function init_your_gateway_class() {
    require_once 'mps-class-gateway.php';
}

add_action( 'plugins_loaded', 'init_your_gateway_class' );


function add_mps_gateway_class( $methods ) {
    $methods[] = 'MPS_Gateway'; 
    return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_mps_gateway_class' );
?>