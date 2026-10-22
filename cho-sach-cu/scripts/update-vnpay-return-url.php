<?php
// Update VNPay Return URL — cấu hình qua biến môi trường VNPAY_RETURN_URL
// Ví dụ: docker compose run --rm -e VNPAY_RETURN_URL=https://your-domain.vn/?wc-api=wc_vnpay_return wpcli
require_once '/var/www/html/wp-load.php';

// Get current settings
$settings = get_option('woocommerce_vnpay_settings', array());

$return_url = getenv('VNPAY_RETURN_URL');
if (!$return_url) {
    $return_url = home_url('/?wc-api=wc_vnpay_return');
}

// Update Return URL
$settings['vnp_ReturnUrl'] = $return_url;
update_option('woocommerce_vnpay_settings', $settings);

echo "Return URL updated to:\n";
echo $settings['vnp_ReturnUrl'] . "\n";
