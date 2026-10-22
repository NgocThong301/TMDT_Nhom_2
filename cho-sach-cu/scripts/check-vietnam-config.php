#!/usr/bin/env php
<?php
/**
 * Kiểm tra cấu hình Vietnam Provinces đã được áp dụng.
 */

if (!function_exists('WC')) {
    WP_CLI::error('WooCommerce chưa sẵn sàng.');
}

echo "\n=== KIỂM TRA CẤU HÌNH VIETNAM PROVINCES ===\n\n";

// 1. Kiểm tra mu-plugin
$mu_plugin_path = WP_CONTENT_DIR . '/mu-plugins/vietnam-provinces.php';
if (file_exists($mu_plugin_path)) {
    WP_CLI::success("✓ Vietnam Provinces plugin: Đã cài");
} else {
    WP_CLI::error("✗ Vietnam Provinces plugin: Chưa cài");
}

// 2. Kiểm tra states
$states = apply_filters('woocommerce_states', array());
if (isset($states['VN']) && count($states['VN']) > 0) {
    WP_CLI::success("✓ Tỉnh/Thành VN: " . count($states['VN']) . " tỉnh/thành");
} else {
    WP_CLI::warning("✗ Tỉnh/Thành VN: Chưa có (plugin chưa load)");
}

// 3. Kiểm tra shipping zones
echo "\n--- Shipping Zones ---\n";
$zones = WC_Shipping_Zones::get_zones();
if (count($zones) > 0) {
    foreach ($zones as $zone) {
        $zone_obj = new WC_Shipping_Zone($zone['id']);
        $methods = $zone_obj->get_shipping_methods();
        $locations = $zone_obj->get_zone_locations();
        
        $location_str = '';
        foreach ($locations as $loc) {
            if ($loc->type === 'state') {
                $location_str .= $loc->code . ' ';
            }
        }
        
        echo "Zone: {$zone['zone_name']}\n";
        echo "  Locations: " . ($location_str ?: 'Toàn quốc (default)') . "\n";
        
        foreach ($methods as $method) {
            $settings = $method->get_instance_settings();
            $title = isset($settings['title']) ? $settings['title'] : $method->id;
            $cost = isset($settings['cost']) ? number_format((int)$settings['cost']) . 'đ' : '-';
            $min_amount = isset($settings['min_amount']) ? number_format((int)$settings['min_amount']) . 'đ' : '-';
            
            echo "  Method: {$title}\n";
            if ($method->id === 'flat_rate') {
                echo "    Cost: {$cost}\n";
            } elseif ($method->id === 'free_shipping') {
                echo "    Min Amount: {$min_amount}\n";
            }
        }
        echo "\n";
    }
} else {
    WP_CLI::warning("Chưa có shipping zone nào");
}

// 4. Tóm tắt
echo "\n=== TÓM TẮT ===\n";
echo "- Miền Bắc: 30,000đ\n";
echo "- Miền Trung: 40,000đ\n";
echo "- Miền Nam: 45,000đ\n";
echo "- Freeship: đơn từ 699,000đ\n";
echo "- Chọn địa chỉ: dropdown thay vì nhập tay\n";
