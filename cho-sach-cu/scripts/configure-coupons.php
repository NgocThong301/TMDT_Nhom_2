<?php
/**
 * Cấu hình coupons và khuyến mãi mặc định cho Chợ Sách Cũ
 * 
 * Chạy: wp eval-file configure-coupons.php
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WooCommerce')) {
    if (class_exists('WP_CLI')) {
        WP_CLI::warning('WooCommerce chưa được kích hoạt.');
    }
    return;
}

// Helper function for logging
function csc_cli_log($message) {
    if (class_exists('WP_CLI')) {
        WP_CLI::log($message);
    } else {
        echo $message . "\n";
    }
}

function csc_cli_success($message) {
    if (class_exists('WP_CLI')) {
        WP_CLI::success($message);
    } else {
        echo "[OK] " . $message . "\n";
    }
}

// ============================================
// 1. CUPON GIẢM GIÁ CHO SINH VIÊN MỚI
// ============================================
$coupon_student = array(
    'code' => 'SINHVIENMOI',
    'discount_type' => 'percent',
    'discount_amount' => '10',
    'description' => 'Giảm 10% cho sinh viên mới đăng ký',
    'minimum_amount' => 0,
    'maximum_amount' => 50000,
    'usage_limit' => 1,
    'usage_limit_per_user' => 1,
    'expiry_days' => 30,
);

// ============================================
// 2. CUPON MIỄN PHÍ VẬN CHUYỂN
// ============================================
$coupon_shipping = array(
    'code' => 'FREESHIP',
    'discount_type' => 'fixed_cart',
    'discount_amount' => 0, // Miễn phí vận chuyển
    'description' => 'Miễn phí vận chuyển cho đơn từ 200k',
    'minimum_amount' => 200000,
    'maximum_amount' => 0,
    'usage_limit' => 100,
    'usage_limit_per_user' => 3,
    'expiry_days' => 60,
    'free_shipping' => true,
);

// ============================================
// 3. CUPON GIẢM 50K CHO ĐƠN TỪ 300K
// ============================================
$coupon_50k = array(
    'code' => 'GIAM50K',
    'discount_type' => 'fixed_cart',
    'discount_amount' => 50000,
    'description' => 'Giảm 50.000đ cho đơn hàng từ 300.000đ',
    'minimum_amount' => 300000,
    'maximum_amount' => 0,
    'usage_limit' => 200,
    'usage_limit_per_user' => 2,
    'expiry_days' => 45,
);

// ============================================
// 4. CUPON BLACK FRIDAY (có thể kích hoạt sau)
// ============================================
$coupon_blackfriday = array(
    'code' => 'BLACKFRIDAY20',
    'discount_type' => 'percent',
    'discount_amount' => '20',
    'description' => 'Giảm 20% - Black Friday Special (chưa kích hoạt)',
    'minimum_amount' => 500000,
    'maximum_amount' => 100000,
    'usage_limit' => 0, // 0 = không giới hạn
    'usage_limit_per_user' => 1,
    'expiry_days' => 0, // Sẽ được set khi kích hoạt
    'status' => 'draft', // Chưa kích hoạt
);

// ============================================
// HÀM TẠO CUPON
// ============================================
function csc_create_coupon($data) {
    // Kiểm tra xem coupon đã tồn tại chưa
    $existing = get_posts(array(
        'post_type' => 'shop_coupon',
        'title' => $data['code'],
        'posts_per_page' => 1,
    ));
    
    if (!empty($existing)) {
        csc_cli_log("Coupon '{$data['code']}' đã tồn tại, bỏ qua.");
        return $existing[0]->ID;
    }
    
    // Tạo coupon mới
    $coupon_id = wp_insert_post(array(
        'post_title'   => $data['code'],
        'post_status'  => isset($data['status']) && $data['status'] === 'draft' ? 'draft' : 'publish',
        'post_type'    => 'shop_coupon',
        'post_excerpt' => $data['description'],
    ));
    
    if (is_wp_error($coupon_id)) {
        csc_cli_log('Lỗi khi tạo coupon: ' . $coupon_id->get_error_message());
        return 0;
    }
    
    // Cập nhật các thuộc tính
    $coupon_data = array(
        'discount_type'              => $data['discount_type'],
        'coupon_amount'             => $data['discount_amount'],
        'minimum_amount'            => $data['minimum_amount'],
        'maximum_amount'            => $data['maximum_amount'],
        'usage_limit'               => $data['usage_limit'],
        'usage_limit_per_user'      => $data['usage_limit_per_user'],
        'date_expires'              => $data['expiry_days'] > 0 ? 
                                       date('Y-m-d', strtotime('+' . $data['expiry_days'] . ' days')) : 0,
        'individual_use'            => false,
        'exclude_sale_items'        => false,
    );
    
    // Xử lý free shipping
    if (isset($data['free_shipping']) && $data['free_shipping']) {
        $coupon_data['free_shipping'] = true;
    }
    
    // Áp dụng các thuộc tính
    foreach ($coupon_data as $key => $value) {
        update_post_meta($coupon_id, $key, $value);
    }
    
    csc_cli_success("Đã tạo coupon: {$data['code']}");
    
    return $coupon_id;
}

// ============================================
// TẠO CÁC CUPON
// ============================================
csc_cli_log('=== Bắt đầu cấu hình Coupons ===');

csc_create_coupon($coupon_student);
csc_create_coupon($coupon_shipping);
csc_create_coupon($coupon_50k);
csc_create_coupon($coupon_blackfriday);

// ============================================
// TẠO TRANG KHUYẾN MÃI
// ============================================
$promo_page_content = '
<div class="promotions-page">
    <h2>🎉 Khuyến mãi &amp; Mã giảm giá</h2>
    
    <p>Khám phá các ưu đãi hấp dẫn dành cho bạn!</p>
    
    <h3>📌 Mã giảm giá đang có</h3>
    
    <div class="coupon-card">
        <div class="coupon-header">
            <span class="coupon-code">SINHVIENMOI</span>
        </div>
        <div class="coupon-body">
            <h4>Giảm 10% cho sinh viên mới</h4>
            <p>Áp dụng cho tài khoản sinh viên mới đăng ký lần đầu.</p>
            <ul>
                <li>Giảm tối đa: 50.000đ</li>
                <li>Đơn tối thiểu: Không yêu cầu</li>
                <li>Sử dụng 1 lần duy nhất</li>
            </ul>
            <p class="coupon-expiry">Hết hạn: 30 ngày kể từ khi nhận</p>
        </div>
    </div>
    
    <div class="coupon-card">
        <div class="coupon-header">
            <span class="coupon-code">FREESHIP</span>
        </div>
        <div class="coupon-body">
            <h4>Miễn phí vận chuyển</h4>
            <p>Miễn phí giao hàng cho đơn hàng từ 200.000đ.</p>
            <ul>
                <li>Miễn phí 100% phí ship</li>
                <li>Đơn tối thiểu: 200.000đ</li>
                <li>Sử dụng tối đa 3 lần/ tài khoản</li>
            </ul>
            <p class="coupon-expiry">Hết hạn: 60 ngày kể từ khi nhận</p>
        </div>
    </div>
    
    <div class="coupon-card">
        <div class="coupon-header">
            <span class="coupon-code">GIAM50K</span>
        </div>
        <div class="coupon-body">
            <h4>Giảm 50.000đ cho đơn từ 300K</h4>
            <p>Giảm ngay 50.000đ cho mọi đơn hàng từ 300.000đ trở lên.</p>
            <ul>
                <li>Giảm cố định: 50.000đ</li>
                <li>Đơn tối thiểu: 300.000đ</li>
                <li>Sử dụng tối đa 2 lần/ tài khoản</li>
            </ul>
            <p class="coupon-expiry">Hết hạn: 45 ngày kể từ khi nhận</p>
        </div>
    </div>
    
    <style>
    .promotions-page { max-width: 800px; margin: 0 auto; }
    .coupon-card {
        border: 2px dashed #0073aa;
        border-radius: 8px;
        margin: 20px 0;
        overflow: hidden;
        background: #f0f7fa;
    }
    .coupon-header {
        background: #0073aa;
        color: white;
        padding: 15px;
        text-align: center;
    }
    .coupon-code {
        font-size: 1.5em;
        font-weight: bold;
        letter-spacing: 2px;
    }
    .coupon-body {
        padding: 20px;
    }
    .coupon-body h4 {
        margin-top: 0;
        color: #0073aa;
    }
    .coupon-body ul {
        margin-left: 20px;
    }
    .coupon-expiry {
        color: #666;
        font-size: 0.9em;
        font-style: italic;
    }
    </style>
</div>
';

// Tạo trang khuyến mãi
$found = get_posts(array(
    'post_type' => 'page',
    'title' => 'Khuyến mãi',
    'post_status' => 'any',
    'posts_per_page' => 1,
));

if (empty($found)) {
    $promo_page_id = wp_insert_post(array(
        'post_title'   => 'Khuyến mãi',
        'post_content' => $promo_page_content,
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_author'  => 1,
    ));
    update_option('csc_page_promotions', $promo_page_id);
    csc_cli_success('Đã tạo trang Khuyến mãi');
} else {
    csc_cli_log('Trang Khuyến mãi đã tồn tại.');
}

csc_cli_success('=== Hoàn tất cấu hình Coupons ===');
csc_cli_log('');
csc_cli_log('Các mã giảm giá đã tạo:');
csc_cli_log('  1. SINHVIENMOI - Giảm 10% cho sinh viên mới');
csc_cli_log('  2. FREESHIP - Miễn phí vận chuyển (đơn từ 200k)');
csc_cli_log('  3. GIAM50K - Giảm 50k cho đơn từ 300k');
csc_cli_log('  4. BLACKFRIDAY20 - (chế độ draft, chưa kích hoạt)');
csc_cli_log('');
csc_cli_log('Trang khuyến mãi đã được tạo.');
