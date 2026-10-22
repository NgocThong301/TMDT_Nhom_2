<?php
// Configure VNPay gateway settings
require_once '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// VNPay settings
$vnpay_settings = array(
    'enabled' => 'yes',
    'title' => 'Thanh toán qua VNPay',
    'description' => 'Thanh toán an toàn qua cổng VNPay với QR Code, ATM, Visa',
    'vnp_TmnCode' => getenv('VNPAY_TMN_CODE') ?: 'DEMO_TMN_CODE',
    'vnp_HashSecret' => getenv('VNPAY_HASH_SECRET') ?: 'DEMO_HASH_SECRET',
    'vnp_Url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
    'vnp_ReturnUrl' => home_url('/?wc-api=wc_vnpay_return'),
);

echo "Configuring VNPay gateway settings...\n";

// Get WooCommerce payment settings
$gateways = get_option('woocommerce_gateways', array());
if (!is_array($gateways)) {
    $gateways = array();
}

// Update VNPay gateway settings
$gateways['vnpay'] = $vnpay_settings;
update_option('woocommerce_vnpay_settings', $vnpay_settings);

echo "VNPay gateway settings configured:\n";
print_r($vnpay_settings);

// Enable the gateway
$enabled_gateways = get_option('woocommerce_enabled_payment_gateways', array());
if (!is_array($enabled_gateways)) {
    $enabled_gateways = array();
}
$enabled_gateways['vnpay'] = 'yes';
update_option('woocommerce_enabled_payment_gateways', $enabled_gateways);

echo "\nVNPay gateway enabled!\n";
echo "Return URL: " . home_url('/?wc-api=wc_vnpay_return') . "\n";
