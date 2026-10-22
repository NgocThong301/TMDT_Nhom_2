<?php
// Activate VNPay plugin
require_once '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

echo "Starting plugin activation...\n";

// Check if plugin file exists
if (file_exists(WP_PLUGIN_DIR . '/vnpay-gateway/vnpay-gateway.php')) {
    echo "Plugin file found\n";
    
    // Activate plugin
    $result = activate_plugin('vnpay-gateway/vnpay-gateway.php');
    
    if (is_wp_error($result)) {
        echo "Error: " . $result->get_error_message() . "\n";
        exit(1);
    } else {
        echo "Plugin activated successfully!\n";
    }
} else {
    echo "Plugin file not found\n";
    echo "Plugins directory:\n";
    $plugins = scandir(WP_PLUGIN_DIR);
    print_r($plugins);
    exit(1);
}
