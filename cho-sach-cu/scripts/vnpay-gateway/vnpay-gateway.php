<?php
/**
 * Plugin Name: WooCommerce VNPay Payment Gateway
 * Plugin URI: https://github.com/example/vnpay-woocommerce
 * Description: Tích hợp cổng thanh toán VNPay cho WooCommerce
 * Version: 1.0.0
 * Author: Developer
 * Author URI: https://example.com
 * Text Domain: vnpay-gateway
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constants
define('VNPAY_PLUGIN_VERSION', '1.0.0');
define('VNPAY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VNPAY_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Add VNPay Gateway to WooCommerce
 */
add_filter('woocommerce_payment_gateways', 'vnpay_add_gateway');

function vnpay_add_gateway($gateways) {
    $gateways[] = 'WC_VNPay_Gateway';
    return $gateways;
}

/**
 * Initialize the gateway
 */
add_action('plugins_loaded', 'vnpay_gateway_init', 0);

function vnpay_gateway_init() {
    
    if (!class_exists('WooCommerce')) {
        return;
    }
    
    require_once VNPAY_PLUGIN_PATH . 'class-vnpay-gateway.php';
    
}

/**
 * Check if we're on the VNPay return/callback page
 */
add_action('woocommerce_api_wc_vnpay_return', 'vnpay_handle_callback');

function vnpay_handle_callback() {
    require_once VNPAY_PLUGIN_PATH . 'class-vnpay-gateway.php';
    
    $gateway = new WC_VNPay_Gateway();
    $gateway->handle_vnpay_callback();
}
