<?php
/**
 * Plugin Name: CSC Marketing Assets
 * Description: Tai lieu marketing va SEO cho Chợ Sách Cũ
 */

// Thêm meta tags SEO
add_action('wp_head', 'csc_seo_meta_tags');

function csc_seo_meta_tags() {
    if (is_front_page()) {
        echo '<meta name="description" content="Chợ Sách Cũ - Sàn C2C mua bán sách đã qua sử dụng: sách giáo khoa, giáo trình, văn học, kinh tế, kỹ năng, ngoại ngữ và truyện tranh. Mua bán dễ dàng, giá cả hợp lý.">' . "\n";
        echo '<meta name="keywords" content="sách cũ, mua bán sách, sách giáo khoa, sách secondhand, thương mại điện tử sách">' . "\n";
        echo '<meta property="og:title" content="Chợ Sách Cũ - Sàn C2C mua bán sách đã qua sử dụng">' . "\n";
        echo '<meta property="og:description" content="Sàn C2C để sinh viên và độc giả mua bán sách đã qua sử dụng với giá cả hợp lý.">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:url" content="' . home_url() . '">' . "\n";
        echo '<meta property="og:image" content="' . esc_url(wc_placeholder_img_src()) . '">' . "\n";
    }
}

// Schema.org Product markup
add_action('woocommerce_single_product_summary', 'csc_product_schema', 99);

function csc_product_schema() {
    global $product;
    
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->get_name(),
        'description' => $product->get_description(),
        'image' => wp_get_attachment_url($product->get_image_id()),
        'url' => get_permalink($product->get_id()),
        'offers' => array(
            '@type' => 'Offer',
            'priceCurrency' => 'VND',
            'price' => $product->get_price(),
            'availability' => $product->is_in_stock() 
                ? 'https://schema.org/InStock' 
                : 'https://schema.org/OutOfStock',
            'seller' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
            ),
        ),
    );
    
    if ($product->get_regular_price()) {
        $schema['offers']['priceValidUntil'] = date('Y-m-d', strtotime('+30 days'));
    }
    
    echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

// Tạo page analytics cho admin
add_action('init', 'csc_create_analytics_page');

function csc_create_analytics_page() {
    $page = get_page_by_path('analytics');
    
    if (!$page) {
        wp_insert_post(array(
            'post_title'    => 'Thống Kê',
            'post_name'     => 'analytics',
            'post_content'  => '[csc_analytics_dashboard]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
        ));
    }
}

// Backup Database
add_action('wp_ajax_csc_backup_database', 'csc_backup_database');
add_action('wp_ajax_nopriv_csc_backup_database', function() { wp_send_json_error(); });

function csc_backup_database() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }
    
    // Tạo backup metadata
    $backup_info = array(
        'created' => current_time('mysql'),
        'version' => get_bloginfo('version'),
        'plugins' => get_option('active_plugins'),
        'theme' => wp_get_theme()->get('Name'),
    );
    
    // Lưu vào file (trong thực tế nên dùng mysqldump)
    update_option('csc_last_backup', $backup_info);
    
    wp_send_json_success(array(
        'message' => 'Backup metadata đã được tạo',
        'info' => $backup_info
    ));
}

// Kịch bản quảng cáo Facebook
function csc_get_facebook_ad_template() {
    return '
    <div class="marketing-doc" style="max-width: 800px; margin: 30px auto; padding: 20px; background: #fff;">
        <h2 style="color: #0073aa;">📢 KỊCH BẢN QUẢNG CÁO FACEBOOK</h2>
        <h3>Chiến dịch: "Bán Sách Cũ - Thu Tiền Dễ Dàng"</h3>
        
        <h4>1. Mục tiêu chiến dịch</h4>
        <ul>
            <li>Tăng nhận diện thương hiệu Chợ Sách Cũ</li>
            <li>Thu hút người bán mới</li>
            <li>Tăng lượng đơn hàng</li>
        </ul>
        
        <h4>2. Đối tượng mục tiêu</h4>
        <table border="1" cellpadding="8" style="width: 100%;">
            <tr><th>Tiêu chí</th><th>Chi tiết</th></tr>
            <tr><td>Độ tuổi</td><td>18 - 35 tuổi</td></tr>
            <tr><td>Địa điểm</td><td>TP.HCM, Hà Nội, Đà Nẵng và các thành phố lớn</td></tr>
            <tr><td>Sở thích</td><td>Sách, văn học, giáo dục, sinh viên</td></tr>
            <tr><td>Hành vi</td><td>Đã mua sách online, thích tiết kiệm</td></tr>
        </table>
        
        <h4>3. Nội dung quảng cáo</h4>
        
        <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 15px 0;">
            <strong>📝 Copy Quảng Cáo 1 (Awareness):</strong>
            <p style="margin: 10px 0;">
                "📚 Sách cũ giá rẻ? Có ngay tại Chợ Sách Cũ!<br>
                🏷️ Sách giáo khoa, văn học, kỹ năng... giá chỉ từ 20K<br>
                🚚 Giao hàng toàn quốc - COD thanh toán khi nhận<br>
                <br>
                ✨ Bán sách cũ cũng dễ lắm! Đăng bán miễn phí, nhận tiền ngay!"
            </p>
            <strong>CTA:</strong> Tìm hiểu ngay
        </div>
        
        <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 15px 0;">
            <strong>📝 Copy Quảng Cáo 2 (Conversion):</strong>
            <p style="margin: 10px 0;">
                "🔓 ĐĂNG KÝ BÁN HÀNG NGAY - HOÀN TOÀN MIỄN PHÍ!<br>
                <br>
                ✅ Không tốn phí đăng bán<br>
                ✅ Thanh toán qua VNPAY - an toàn<br>
                ✅ Hỗ trợ 24/7<br>
                <br>
                📖 Sách cũ của bạn có thể là kho báu của người khác!"
            </p>
            <strong>CTA:</strong> Đăng ký ngay - 100% miễn phí
        </div>
        
        <h4>4. Ngân sách và lịch chạy</h4>
        <table border="1" cellpadding="8" style="width: 100%;">
            <tr><th>Giai đoạn</th><th>Thời gian</th><th>Ngân sách/ngày</th><th>Mục tiêu</th></tr>
            <tr><td>Awareness</td><td>Tuần 1</td><td>100.000đ</td><td>10.000 reach</td></tr>
            <tr><td>Consideration</td><td>Tuần 2-3</td><td>150.000đ</td><td>5.000 clicks</td></tr>
            <tr><td>Conversion</td><td>Tuần 4</td><td>200.000đ</td><td>100 đăng ký</td></tr>
        </table>
        
        <h4>5. A/B Test</h4>
        <ul>
            <li><strong>Test A:</strong> Hình ảnh sách stack cao → Test B: Hình ảnh sinh viên đọc sách</li>
            <li><strong>Test C:</strong> Copy tập trung vào "Mua" → Test D: Copy tập trung vào "Bán"</li>
        </ul>
        
        <h4>6. Theo dõi & Đo lường</h4>
        <ul>
            <li><strong>CPI (Cost Per Interest):</strong> Dưới 5.000đ</li>
            <li><strong>CPC (Cost Per Click):</strong> Dưới 3.000đ</li>
            <li><strong>CPA (Cost Per Acquisition):</strong> Dưới 30.000đ</li>
            <li><strong>ROAS (Return on Ad Spend):</strong> Target 300%</li>
        </ul>
    </div>
    ';
}

// Shortcode xem kịch bản marketing
add_shortcode('csc_marketing_doc', function() {
    return csc_get_facebook_ad_template();
});

// Tạo page kịch bản marketing
add_action('init', 'csc_create_marketing_page');

function csc_create_marketing_page() {
    $page = get_page_by_path('kien-bang-quang-cao');
    
    if (!$page) {
        wp_insert_post(array(
            'post_title'    => 'Kịch Bản Quảng Cáo',
            'post_name'     => 'kien-bang-quang-cao',
            'post_content'  => '[csc_marketing_doc]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
        ));
    }
}
