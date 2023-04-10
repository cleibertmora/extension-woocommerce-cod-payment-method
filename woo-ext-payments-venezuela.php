<?php
/**
 * Plugin Name: Extensión pagos Venezuela WooCommerce
 * Description: Métodos de pago para Venezuela en tienda de WooCommerce
 * Version: 1.0.0
 * Author: Cleibert Mora
 * Author URI: https://cleibertmora.com/
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// asegurarse de que WooCommerce esté activo
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    // cargar la clase de la extension

    add_action( 'plugins_loaded', 'cargar_clase_de_extension_formas_de_pago_venezuela' );

    function cargar_clase_de_extension_formas_de_pago_venezuela() {
        require_once plugin_dir_path( __FILE__ ) . 'ExtensionWoo.php';

        WC_Ext_Payment_For_Vzla_Payments::get_instance();
    }
}

// Check if woocommerce is activated
add_action( 'admin_init', 'wc_extention_pago_zelle_gateway_check_dependencies' );

function wc_extention_pago_zelle_gateway_check_dependencies() {
    if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        // WooCommerce is not installed or active, show error message
        deactivate_plugins( plugin_basename( __FILE__ ) ); // desactivar el plugin actual
        wp_die( 'La extensión My WooCommerce requiere WooCommerce para funcionar. Por favor, instala y activa WooCommerce antes de activar esta extensión.' );
    }
}
