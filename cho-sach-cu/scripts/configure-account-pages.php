<?php
/**
 * Cấu hình trang Đăng nhập / Tài khoản cho WooCommerce + Dokan
 * - Thiết lập My Account page cho WooCommerce
 * - Cấu hình endpoints cho Dokan dashboard
 * - Bật tính năng đăng ký
 */
if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

if (!class_exists('WooCommerce')) {
    WP_CLI::error('WooCommerce chưa kích hoạt.');
}

WP_CLI::line('=== Cấu hình trang Đăng nhập / Tài khoản ===');

// ============================================================
// 1. Tạo/cập nhật trang "Tài khoản của tôi" (My Account)
// ============================================================
$myaccount_page = get_page_by_title('Tài khoản của tôi');
if (!$myaccount_page) {
    $myaccount_page = get_page_by_title('My Account');
}

if (!$myaccount_page) {
    $page_id = wp_insert_post(array(
        'post_title'   => 'Tài khoản của tôi',
        'post_content' => '[woocommerce_my_account]',
        'post_type'    => 'page',
        'post_status'  => 'publish',
        'post_author'  => 1,
    ));
    WP_CLI::success("Đã tạo trang Tài khoản (ID: $page_id)");
} else {
    $page_id = $myaccount_page->ID;
    wp_update_post(array(
        'ID'          => $page_id,
        'post_title'   => 'Tài khoản của tôi',
        'post_content' => '[woocommerce_my_account]',
        'post_status'  => 'publish',
    ));
    WP_CLI::success("Đã cập nhật trang Tài khoản (ID: $page_id)");
}

update_option('woocommerce_myaccount_page_id', $page_id);
update_option('woocommerce_myaccount_menu_order', 1);

// ============================================================
// 2. Cấu hình endpoint cho WooCommerce My Account
// ============================================================
$endpoints = array(
    'orders'                      => 'don-hang',
    'view-order'                  => 'chi-tiet-don-hang',
    'downloads'                   => 'tai-xuong',
    'edit-account'                => 'thong-tin-tai-khoan',
    'edit-address'                => 'dia-chi',
    'payment-methods'             => 'phuong-thuc-thanh-toan',
    'add-payment-method'          => 'them-phuong-thuc-thanh-toan',
    'remove-payment-method'       => 'xoa-phuong-thuc-thanh-toan',
    'customer-logout'             => 'dang-xuat',
    'lost-password'               => 'quen-mat-khau',
);

// Cập nhật endpoint nếu chưa đúng
foreach ($endpoints as $key => $slug) {
    $current = get_option('woocommerce_' . $key . '_endpoint', '');
    if ($current !== $slug) {
        update_option('woocommerce_' . $key . '_endpoint', $slug);
        WP_CLI::log("  Endpoint: $key -> $slug");
    }
}

WP_CLI::success('Đã cấu hình WooCommerce endpoints');

// ============================================================
// 3. Cấu hình Dokan Dashboard
// ============================================================
WP_CLI::line('Cấu hình Dokan Dashboard...');

// Tìm hoặc tạo trang Vendor Dashboard
$dokan_dashboard = get_page_by_title('Dashboard');
$dokan_exists = false;
if ($dokan_dashboard) {
    foreach (array('Cửa hàng của tôi', 'Vendor Dashboard', 'Kênh người bán') as $alt_title) {
        $alt = get_page_by_title($alt_title);
        if ($alt) {
            $dokan_dashboard = $alt;
            $dokan_exists = true;
            break;
        }
    }
}

if (!$dokan_dashboard || (isset($dokan_dashboard->post_content) && strpos($dokan_dashboard->post_content, '[dokan-dashboard]') === false)) {
    // Tạo trang mới
    $dashboard_page_id = wp_insert_post(array(
        'post_title'   => 'Kênh người bán',
        'post_content' => '[dokan-dashboard]',
        'post_type'    => 'page',
        'post_status'  => 'publish',
        'post_author'  => 1,
    ));
    
    // Cập nhật Dokan settings
    $dokan_pages = get_option('dokan_pages', array());
    $dokan_pages['dashboard'] = $dashboard_page_id;
    update_option('dokan_pages', $dokan_pages);
    
    // Cập nhật rewrite rules
    update_option('dokan_route_base', 'cua-hang');
    
    WP_CLI::success("Đã tạo trang Kênh người bán (ID: $dashboard_page_id)");
} else {
    $dashboard_page_id = $dokan_dashboard->ID;
    wp_update_post(array(
        'ID'          => $dashboard_page_id,
        'post_content' => '[dokan-dashboard]',
        'post_status'  => 'publish',
    ));
    
    $dokan_pages = get_option('dokan_pages', array());
    $dokan_pages['dashboard'] = $dashboard_page_id;
    update_option('dokan_pages', $dokan_pages);
    
    WP_CLI::success("Đã cập nhật trang Kênh người bán (ID: $dashboard_page_id)");
}

// Cấu hình Dokan general settings
$dokan_general = get_option('dokan_general', array());
if (!is_array($dokan_general)) {
    $dokan_general = array();
}
$dokan_general['dashboard'] = $dashboard_page_id;
$dokan_general['enable_pricing'] = 'on';
$dokan_general['enable_report'] = 'on';
update_option('dokan_general', $dokan_general);

// Cấu hình selling settings
$dokan_selling = get_option('dokan_selling', array());
if (!is_array($dokan_selling)) {
    $dokan_selling = array();
}
$dokan_selling['new_seller_enable_selling'] = 'on';
$dokan_selling['order_status_change'] = 'on';
$dokan_selling['enable_tos'] = 'on';
$dokan_selling['store_terms_and_conditions'] = 'Khi đăng bán sách trên Chợ Sách Cũ, bạn đồng ý với các điều khoản sau:
1. Sách đăng bán phải là sách đã qua sử dụng, không phải sách lậu.
2. Mô tả tình trạng sách phải trung thực.
3. Giao hàng trong vòng 3 ngày làm việc sau khi xác nhận đơn.
4. Chịu trách nhiệm về chất lượng sách đăng bán.';
update_option('dokan_selling', $dokan_selling);

// Cấu hình withdraw settings
$dokan_withdraw = get_option('dokan_withdraw', array());
if (!is_array($dokan_withdraw)) {
    $dokan_withdraw = array();
}
$dokan_withdraw['withdraw_order_status'] = array('completed', 'processing');
$dokan_withdraw['withdraw_limit'] = 50000;
$dokan_withdraw['withdraw_threshold'] = 0;
$dokan_withdraw['payment_methods'] = array('bank_transfer');
update_option('dokan_withdraw', $dokan_withdraw);

WP_CLI::success('Đã cấu hình Dokan');

// ============================================================
// 4. Cấu hình đăng ký / đăng nhập
// ============================================================
WP_CLI::line('Cấu hình đăng ký / đăng nhập...');

update_option('woocommerce_enable_myaccount_registration', 'yes');
update_option('woocommerce_registration_generate_username', 'yes');
update_option('woocommerce_registration_generate_password', 'yes');
update_option('woocommerce_enable_signup_and_login_from_checkout', 'yes');
update_option('woocommerce_enable_customer_creation_on_order_created', 'yes');

WP_CLI::success('Đã bật đăng ký / đăng nhập');

// ============================================================
// 5. Thêm menu link vào theme
// ============================================================
WP_CLI::line('Cấu hình navigation links...');

// Flush rewrite rules
WP_CLI::line('Flush rewrite rules...');
flush_rewrite_rules();

WP_CLI::success('=== Hoàn tất cấu hình Đăng nhập / Tài khoản ===');
WP_CLI::line('');
WP_CLI::line('Trang đã tạo:');
WP_CLI::line('  - Tài khoản của tôi: ' . get_permalink($page_id));
WP_CLI::line('  - Kênh người bán: ' . get_permalink($dashboard_page_id));
WP_CLI::line('');
WP_CLI::line('Endpoints đã cấu hình:');
foreach ($endpoints as $key => $slug) {
    WP_CLI::line("  - $key: /$slug/");
}
