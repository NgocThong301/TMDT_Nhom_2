<?php
/**
 * Cấu hình Dokan Lite qua API option của plugin.
 * Không giả định module Pro (duyệt sản phẩm nâng cao, subscription, v.v.).
 */
if (!function_exists('dokan')) {
    WP_CLI::warning('Dokan chưa được tải. Bỏ qua cấu hình Dokan.');
    return;
}

$general = get_option('dokan_general', array());
if (!is_array($general)) {
    $general = array();
}
$general['dashboard'] = isset($general['dashboard']) ? $general['dashboard'] : 0;
$general['my_orders'] = isset($general['my_orders']) ? $general['my_orders'] : 0;
update_option('dokan_general', $general);

$selling = get_option('dokan_selling', array());
if (!is_array($selling)) {
    $selling = array();
}
$selling['new_seller_enable_selling'] = 'on';
$selling['one_step_product_create'] = 'on';
$selling['product_status'] = 'pending';
$selling['disable_product_popup'] = 'off';
update_option('dokan_selling', $selling);

$appearance = get_option('dokan_appearance', array());
if (!is_array($appearance)) {
    $appearance = array();
}
$appearance['store_map'] = 'off';
$appearance['contact_seller'] = 'on';
$appearance['store_header_template'] = 'default';
update_option('dokan_appearance', $appearance);

update_option('dokan_pages_created', '1');

WP_CLI::success('Đã ghi tùy chọn Dokan Lite (đăng ký người bán bật; trạng thái sản phẩm mới: pending nếu Lite hỗ trợ).');
