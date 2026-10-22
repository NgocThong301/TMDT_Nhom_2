#!/usr/bin/env php
<?php
/**
 * Cấu hình phí ship theo 3 miền + freeship từ 699k cho Chợ Sách Cũ.
 */

if (!function_exists('WC')) {
    WP_CLI::error('WooCommerce chưa sẵn sàng.');
}

// Xóa zones cũ
$all_zones = WC_Shipping_Zones::get_zones();
foreach ($all_zones as $zone) {
    $zone_obj = new WC_Shipping_Zone($zone['id']);
    foreach ($zone_obj->get_shipping_methods() as $method) {
        $zone_obj->delete_shipping_method($method->instance_id);
    }
    $zone_obj->delete();
}
WP_CLI::log('Đã xóa các zone cũ.');

// Định nghĩa states theo miền
$north_states = array(
    'VN-HN', 'VN-HP', 'VN-HD', 'VN-HY', 'VN-BN', 'VN-VP',
    'VN-TN', 'VN-HB', 'VN-QN', 'VN-BG', 'VN-BK', 'VN-LS',
    'VN-CB', 'VN-TQ', 'VN-YB', 'VN-TB', 'VN-ND', 'VN-NBI',
    'VN-HM', 'VN-SL', 'VN-DB', 'VN-LC', 'VN-VC', 'VN-HG', 'VN-PT'
);

$central_states = array(
    'VN-HT', 'VN-NA', 'VN-TH', 'VN-QB', 'VN-QT', 'VN-TTH',
    'VN-DN', 'VN-QNA', 'VN-QNG', 'VN-BD', 'VN-PY', 'VN-KH',
    'VN-NT', 'VN-BTH', 'VN-GL', 'VN-DL', 'VN-DNO', 'VN-LD'
);

$south_states = array(
    'VN-SG', 'VN-CT', 'VN-AG', 'VN-BL', 'VN-BTE', 'VN-BDI',
    'VN-BP', 'VN-CM', 'VN-DNI', 'VN-DTH', 'VN-HGI', 'VN-KG',
    'VN-LA', 'VN-ST', 'VN-TNI', 'VN-TGI', 'VN-TV', 'VN-VL', 'VN-VT'
);

$freeship_threshold = 699000;

// Tạo Miền Bắc
$zone = new WC_Shipping_Zone();
$zone->set_zone_name('Miền Bắc');
$zone->save();
foreach ($north_states as $code) { $zone->add_location($code, 'state'); }
$zone->save();

$instance_id = $zone->add_shipping_method('flat_rate');
$method = WC_Shipping_Zones::get_shipping_method($instance_id);
if ($method) {
    $method->set_post_data(array(
        'title' => 'Phí vận chuyển Miền Bắc',
        'cost' => '30000',
        'type' => 'order',
        'tax_status' => 'none',
    ));
    $method->process_admin_options();
}

$free_id = $zone->add_shipping_method('free_shipping');
$free_method = WC_Shipping_Zones::get_shipping_method($free_id);
if ($free_method) {
    $free_method->set_post_data(array(
        'title' => 'Miễn phí vận chuyển Miền Bắc (đơn từ ' . number_format($freeship_threshold) . 'đ)',
        'requires' => 'min_amount',
        'min_amount' => (string) $freeship_threshold,
        'tax_status' => 'none',
    ));
    $free_method->process_admin_options();
}

WP_CLI::success('Đã tạo zone Miền Bắc với phí 30,000đ');

// Tạo Miền Trung
$zone = new WC_Shipping_Zone();
$zone->set_zone_name('Miền Trung');
$zone->save();
foreach ($central_states as $code) { $zone->add_location($code, 'state'); }
$zone->save();

$instance_id = $zone->add_shipping_method('flat_rate');
$method = WC_Shipping_Zones::get_shipping_method($instance_id);
if ($method) {
    $method->set_post_data(array(
        'title' => 'Phí vận chuyển Miền Trung',
        'cost' => '40000',
        'type' => 'order',
        'tax_status' => 'none',
    ));
    $method->process_admin_options();
}

$free_id = $zone->add_shipping_method('free_shipping');
$free_method = WC_Shipping_Zones::get_shipping_method($free_id);
if ($free_method) {
    $free_method->set_post_data(array(
        'title' => 'Miễn phí vận chuyển Miền Trung (đơn từ ' . number_format($freeship_threshold) . 'đ)',
        'requires' => 'min_amount',
        'min_amount' => (string) $freeship_threshold,
        'tax_status' => 'none',
    ));
    $free_method->process_admin_options();
}

WP_CLI::success('Đã tạo zone Miền Trung với phí 40,000đ');

// Tạo Miền Nam
$zone = new WC_Shipping_Zone();
$zone->set_zone_name('Miền Nam');
$zone->save();
foreach ($south_states as $code) { $zone->add_location($code, 'state'); }
$zone->save();

$instance_id = $zone->add_shipping_method('flat_rate');
$method = WC_Shipping_Zones::get_shipping_method($instance_id);
if ($method) {
    $method->set_post_data(array(
        'title' => 'Phí vận chuyển Miền Nam',
        'cost' => '45000',
        'type' => 'order',
        'tax_status' => 'none',
    ));
    $method->process_admin_options();
}

$free_id = $zone->add_shipping_method('free_shipping');
$free_method = WC_Shipping_Zones::get_shipping_method($free_id);
if ($free_method) {
    $free_method->set_post_data(array(
        'title' => 'Miễn phí vận chuyển Miền Nam (đơn từ ' . number_format($freeship_threshold) . 'đ)',
        'requires' => 'min_amount',
        'min_amount' => (string) $freeship_threshold,
        'tax_status' => 'none',
    ));
    $free_method->process_admin_options();
}

WP_CLI::success('Đã tạo zone Miền Nam với phí 45,000đ');

// Tạo zone Toàn Quốc cho freeship
$zone = new WC_Shipping_Zone();
$zone->set_zone_name('Toàn Quốc (Mặc định)');
$zone->save();

$free_id = $zone->add_shipping_method('free_shipping');
$free_method = WC_Shipping_Zones::get_shipping_method($free_id);
if ($free_method) {
    $free_method->set_post_data(array(
        'title' => 'Miễn phí vận chuyển toàn quốc (đơn từ ' . number_format($freeship_threshold) . 'đ)',
        'requires' => 'min_amount',
        'min_amount' => (string) $freeship_threshold,
        'tax_status' => 'none',
    ));
    $free_method->process_admin_options();
}

WP_CLI::success('Đã tạo zone Toàn Quốc với freeship từ ' . number_format($freeship_threshold) . 'đ');

// Hiển thị kết quả
echo "\n=== KẾT QUẢ CẤU HÌNH VẬN CHUYỂN ===\n";
echo "Phí ship theo miền:\n";
echo "- Miền Bắc: 30,000đ\n";
echo "- Miền Trung: 40,000đ\n";
echo "- Miền Nam: 45,000đ\n";
echo "- Freeship toàn quốc: đơn từ " . number_format($freeship_threshold) . "đ\n";
echo "\nCác zone đã tạo:\n";
$zones = WC_Shipping_Zones::get_zones();
foreach ($zones as $z) {
    $zone_obj = new WC_Shipping_Zone($z['id']);
    $methods = $zone_obj->get_shipping_methods();
    $locations = $zone_obj->get_zone_locations();
    echo "- {$z['zone_name']} (" . count($locations) . " tỉnh/thành, " . count($methods) . " phương thức)\n";
}
