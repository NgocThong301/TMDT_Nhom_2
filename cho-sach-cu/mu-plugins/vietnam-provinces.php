<?php
/**
 * Plugin Name: Vietnam Provinces for WooCommerce
 * Description: Thêm dropdown chọn Tỉnh/Thành/Quận/Huyện/Phường/Xã Việt Nam thay vì nhập tay.
 * Version: 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Đăng ký danh sách tỉnh/thành phố Việt Nam
add_filter('woocommerce_states', 'vietnam_provinces_woocommerce_states', 1);

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

// Cấu hình country locale cho Việt Nam - bật state dưới dạng dropdown
add_filter('woocommerce_get_country_locale', 'vietnam_provinces_country_locale', 10, 1);

function vietnam_provinces_country_locale($locale) {
    $locale['VN'] = array(
        'postcode' => array(
            'required' => false,
            'hidden'   => true,
        ),
        'city' => array(
            'required' => false,
            'hidden'   => true,
        ),
        'state' => array(
            'required' => true,
            'hidden'   => false,
            'label'    => 'Tỉnh / Thành phố',
            'priority' => 40,
        ),
        'address_1' => array(
            'label'        => 'Địa chỉ',
            'placeholder'  => 'Số nhà, tên đường',
            'priority'     => 50,
        ),
        'address_2' => array(
            'label'       => 'Căn hộ, phòng, tầng (tùy chọn)',
            'placeholder' => 'Căn hộ, dãy phòng, đơn vị, v.v.',
            'priority'    => 60,
        ),
    );
    
    return $locale;
}

// Đặt VN làm quốc gia mặc định
add_filter('default_checkout_billing_country', 'vietnam_provinces_default_country');
add_filter('default_checkout_shipping_country', 'vietnam_provinces_default_country');

function vietnam_provinces_default_country($country) {
    return 'VN';
}

// Thêm CSS để cải thiện giao diện
add_action('wp_head', 'vietnam_provinces_custom_css');

function vietnam_provinces_custom_css() {
    if (is_checkout()) {
        echo '<style>
            #billing_postcode_field, #shipping_postcode_field { display: none !important; }
            #billing_city_field, #shipping_city_field { display: none !important; }
            .woocommerce-checkout #billing_state_field select,
            .woocommerce-checkout #shipping_state_field select,
            .woocommerce-checkout #calc_shipping_state_field select {
                width: 100%;
                padding: 12px;
                border-radius: 4px;
                border: 1px solid #ccc;
                background-color: #fff;
                font-size: 14px;
                cursor: pointer;
            }
            #billing_state_field, #shipping_state_field {
                margin-bottom: 15px;
            }
            #billing_state_field label,
            #shipping_state_field label {
                font-weight: 500;
                color: #333;
                display: block;
                margin-bottom: 5px;
            }
            .woocommerce-checkout #billing_state_field .woocommerce-input-wrapper,
            .woocommerce-checkout #shipping_state_field .woocommerce-input-wrapper {
                width: 100%;
                display: block;
            }
        </style>';
    }
}
