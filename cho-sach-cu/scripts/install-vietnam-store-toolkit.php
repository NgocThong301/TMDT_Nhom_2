#!/usr/bin/env php
<?php
/**
 * Cài đặt Vietnam Provinces for WooCommerce (custom mu-plugin).
 * Thêm dropdown chọn Tỉnh/Thành/Quận/Huyện/Phường/Xã Việt Nam.
 */

if (!function_exists('WC')) {
    WP_CLI::error('WooCommerce chưa sẵn sàng.');
}

$plugin_file = WP_CONTENT_DIR . '/mu-plugins/vietnam-provinces.php';

// Nội dung plugin
$plugin_content = <<<'PHP'
<?php
/**
 * Plugin Name: Vietnam Provinces for WooCommerce
 * Description: Thêm dropdown chọn Tỉnh/Thành/Quận/Huyện/Phường/Xã Việt Nam thay vì nhập tay.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Đăng ký danh sách tỉnh/thành phố Việt Nam
add_filter('woocommerce_states', 'vietnam_provinces_woocommerce_states');

function vietnam_provinces_woocommerce_states($states) {
    $states['VN'] = array(
        // Miền Bắc
        'VN-HN' => 'Hà Nội',
        'VN-HP' => 'Hải Phòng',
        'VN-HD' => 'Hải Dương',
        'VN-HY' => 'Hưng Yên',
        'VN-BN' => 'Bắc Ninh',
        'VN-VP' => 'Vĩnh Phúc',
        'VN-TN' => 'Thái Nguyên',
        'VN-HB' => 'Hòa Bình',
        'VN-QN' => 'Quảng Ninh',
        'VN-BG' => 'Bắc Giang',
        'VN-BK' => 'Bắc Kạn',
        'VN-LS' => 'Lạng Sơn',
        'VN-CB' => 'Cao Bằng',
        'VN-TQ' => 'Tuyên Quang',
        'VN-YB' => 'Yên Bái',
        'VN-TB' => 'Thái Bình',
        'VN-ND' => 'Nam Định',
        'VN-NBI' => 'Ninh Bình',
        'VN-HM' => 'Hà Nam',
        'VN-SL' => 'Sơn La',
        'VN-DB' => 'Điện Biên',
        'VN-LC' => 'Lai Châu',
        'VN-VC' => 'Lào Cai',
        'VN-HG' => 'Hà Giang',
        'VN-PT' => 'Phú Thọ',
        
        // Miền Trung
        'VN-HT' => 'Hà Tĩnh',
        'VN-NA' => 'Nghệ An',
        'VN-TH' => 'Thanh Hóa',
        'VN-QB' => 'Quảng Bình',
        'VN-QT' => 'Quảng Trị',
        'VN-TTH' => 'Thừa Thiên Huế',
        'VN-DN' => 'Đà Nẵng',
        'VN-QNA' => 'Quảng Nam',
        'VN-QNG' => 'Quảng Ngãi',
        'VN-BD' => 'Bình Định',
        'VN-PY' => 'Phú Yên',
        'VN-KH' => 'Khánh Hòa',
        'VN-NT' => 'Ninh Thuận',
        'VN-BTH' => 'Bình Thuận',
        'VN-GL' => 'Gia Lai',
        'VN-DL' => 'Đắk Lắk',
        'VN-DNO' => 'Đắk Nông',
        'VN-LD' => 'Lâm Đồng',
        
        // Miền Nam
        'VN-SG' => 'TP. Hồ Chí Minh',
        'VN-CT' => 'Cần Thơ',
        'VN-AG' => 'An Giang',
        'VN-BL' => 'Bạc Liêu',
        'VN-BTE' => 'Bến Tre',
        'VN-BDI' => 'Bình Dương',
        'VN-BP' => 'Bình Phước',
        'VN-CM' => 'Cà Mau',
        'VN-DNI' => 'Đồng Nai',
        'VN-DTH' => 'Đồng Tháp',
        'VN-HGI' => 'Hậu Giang',
        'VN-KG' => 'Kiên Giang',
        'VN-LA' => 'Long An',
        'VN-ST' => 'Sóc Trăng',
        'VN-TNI' => 'Tây Ninh',
        'VN-TGI' => 'Tiền Giang',
        'VN-TV' => 'Trà Vinh',
        'VN-VL' => 'Vĩnh Long',
        'VN-VT' => 'Bà Rịa - Vũng Tàu',
    );
    
    return $states;
}

// Đặt VN làm quốc gia mặc định
add_filter('default_checkout_billing_country', 'vietnam_provinces_default_country');
add_filter('default_checkout_shipping_country', 'vietnam_provinces_default_country');

function vietnam_provinces_default_country($country) {
    return 'VN';
}

// Ẩn các trường không cần thiết
add_action('wp_head', 'vietnam_provinces_custom_css');

function vietnam_provinces_custom_css() {
    if (is_checkout()) {
        echo '<style>
            #billing_postcode_field, #shipping_postcode_field { display: none; }
            .form-row-wide select { max-width: 100%; }
        </style>';
    }
}
PHP;

// Ghi file
if (file_put_contents($plugin_file, $plugin_content)) {
    WP_CLI::success("Đã cài Vietnam Provinces plugin.");
} else {
    WP_CLI::error("Không thể ghi file plugin.");
}

// Xóa script cũ
$old_script = WP_CONTENT_DIR . '/mu-plugins/install-vietnam-store-toolkit.php';
if (file_exists($old_script)) {
    unlink($old_script);
}

WP_CLI::success("Đã bật chế độ chọn địa chỉ Việt Nam (dropdown).");
