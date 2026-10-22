<?php
/**
 * Tạo khu vực vận chuyển Việt Nam bằng WooCommerce CRUD API.
 */
if (!function_exists('WC')) {
    WP_CLI::error('WooCommerce chưa sẵn sàng.');
}

$zones = WC_Shipping_Zones::get_zones();
$vietnam_id = 0;
foreach ($zones as $zone_data) {
    if (isset($zone_data['zone_name']) && $zone_data['zone_name'] === 'Việt Nam') {
        $vietnam_id = (int) $zone_data['id'];
        break;
    }
}

if (!$vietnam_id) {
    $zone = new WC_Shipping_Zone();
    $zone->set_zone_name('Việt Nam');
    $zone->save();
    $zone->add_location('VN', 'country');
    $vietnam_id = $zone->get_id();
} else {
    $zone = new WC_Shipping_Zone($vietnam_id);
}

$existing_methods = $zone->get_shipping_methods();
$has_flat = false;
$has_free = false;
foreach ($existing_methods as $method) {
    if ($method->id === 'flat_rate') {
        $has_flat = true;
    }
    if ($method->id === 'free_shipping') {
        $has_free = true;
    }
}

if (!$has_flat) {
    $instance_id = $zone->add_shipping_method('flat_rate');
    $method = WC_Shipping_Zones::get_shipping_method($instance_id);
    if ($method) {
        $method->set_post_data(array(
            'woocommerce_flat_rate_title'      => 'Phí ship cố định (thử nghiệm)',
            'woocommerce_flat_rate_cost'       => '25000',
            'woocommerce_flat_rate_tax_status' => 'none',
        ));
        $method->process_admin_options();
    }
}

if (!$has_free) {
    $instance_id = $zone->add_shipping_method('free_shipping');
    $method = WC_Shipping_Zones::get_shipping_method($instance_id);
    if ($method) {
        $method->set_post_data(array(
            'woocommerce_free_shipping_title'        => 'Miễn phí ship từ 300.000đ',
            'woocommerce_free_shipping_requires'     => 'min_amount',
            'woocommerce_free_shipping_min_amount'   => '300000',
        ));
        $method->process_admin_options();
    }
}

WP_CLI::success('Đã cấu hình vận chuyển Việt Nam.');
