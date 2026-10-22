<?php
/**
 * Plugin Name: CSC Seller Registration
 * Description: Trang đăng ký bán hàng cho customer
 * Version: 1.0
 */

if (!defined('ABSPATH')) exit;

// Shortcode đăng ký bán hàng
add_shortcode('csc_seller_register', 'csc_seller_register_shortcode');

function csc_seller_register_shortcode($atts) {
    // Chỉ cho phép user đã đăng nhập
    if (!is_user_logged_in()) {
        return '<div class="woocommerce-info">
            Vui lòng <a href="' . esc_url(wp_login_url(home_url('/seller-registration'))) . '">đăng nhập</a> để đăng ký bán hàng.
        </div>';
    }

    $user_id = get_current_user_id();
    $user = get_userdata($user_id);

    // Kiểm tra đã là seller chưa
    if (dokan_is_user_seller($user_id)) {
        return '<div class="woocommerce-message">
            ✅ Bạn đã là người bán! <a href="' . esc_url(home_url('/dang-sach')) . '">Đăng sách ngay</a>
        </div>';
    }

    $message = '';
    $error = '';

    // Xử lý form submit
    if (isset($_POST['csc_seller_register_nonce']) && wp_verify_nonce($_POST['csc_seller_register_nonce'], 'csc_seller_register')) {
        $result = csc_process_seller_registration($user_id, $_POST);

        if (is_wp_error($result)) {
            $error = $result->get_error_message();
        } else {
            $message = '🎉 Chúc mừng! Bạn đã đăng ký bán hàng thành công!';
            // Redirect về trang đăng sách
            echo '<script>window.location.href = "' . esc_url(home_url('/dang-sach')) . '";</script>';
        }
    }

    ob_start();
    ?>
    <div class="csc-seller-register-container">
        <div class="seller-register-header">
            <h2>📚 Đăng Ký Bán Hàng</h2>
            <p>Điền thông tin bên dưới để bắt đầu bán sách trên Chợ Sách Cũ</p>
        </div>

        <?php if ($message): ?>
            <div class="woocommerce-message"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="woocommerce-error"><?php echo esc_html($error); ?></div>
        <?php endif; ?>

        <form method="post" class="seller-register-form">
            <?php wp_nonce_field('csc_seller_register', 'csc_seller_register_nonce'); ?>

            <div class="form-section">
                <h3>📋 Thông Tin Cửa Hàng</h3>

                <div class="form-row">
                    <label for="store_name">Tên cửa hàng <span class="required">*</span></label>
                    <input type="text" id="store_name" name="store_name" required
                           placeholder="Ví dụ: Shop Sách Cũ ABC"
                           value="<?php echo esc_attr($user->display_name); ?> Bookstore">
                </div>

                <div class="form-row">
                    <label for="store_url">Địa chỉ cửa hàng</label>
                    <input type="text" id="store_url" name="store_url"
                           placeholder=" Ví dụ: shop-sach-cu-abc (không dấu, không khoảng trắng)">
                    <p class="form-hint"><?php echo esc_html(home_url('/')); ?>/<span id="store-url-preview">shop-sach-cu-abc</span></p>
                </div>

                <div class="form-row">
                    <label for="store_description">Giới thiệu cửa hàng</label>
                    <textarea id="store_description" name="store_description" rows="3"
                              placeholder="Giới thiệu ngắn về cửa hàng của bạn..."></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3>📍 Thông Tin Liên Hệ</h3>

                <div class="form-row-group">
                    <div class="form-row">
                        <label for="phone">Số điện thoại <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" required
                               placeholder="0xxx xxx xxx" pattern="[0-9]{10,11}">
                    </div>
                    <div class="form-row">
                        <label for="city">Thành phố/Tỉnh</label>
                        <select id="city" name="city">
                            <option value="">-- Chọn thành phố --</option>
                            <option value="Hồ Chí Minh">TP. Hồ Chí Minh</option>
                            <option value="Hà Nội">Hà Nội</option>
                            <option value="Đà Nẵng">Đà Nẵng</option>
                            <option value="Hải Phòng">Hải Phòng</option>
                            <option value="Cần Thơ">Cần Thơ</option>
                            <option value="An Giang">An Giang</option>
                            <option value="Bà Rịa - Vũng Tàu">Bà Rịa - Vũng Tàu</option>
                            <option value="Bắc Giang">Bắc Giang</option>
                            <option value="Bắc Kạn">Bắc Kạn</option>
                            <option value="Bạc Liêu">Bạc Liêu</option>
                            <option value="Bắc Ninh">Bắc Ninh</option>
                            <option value="Bến Tre">Bến Tre</option>
                            <option value="Bình Định">Bình Định</option>
                            <option value="Bình Dương">Bình Dương</option>
                            <option value="Bình Phước">Bình Phước</option>
                            <option value="Bình Thuận">Bình Thuận</option>
                            <option value="Cà Mau">Cà Mau</option>
                            <option value="Cao Bằng">Cao Bằng</option>
                            <option value="Đắk Lắk">Đắk Lắk</option>
                            <option value="Đắk Nông">Đắk Nông</option>
                            <option value="Điện Biên">Điện Biên</option>
                            <option value="Đồng Nai">Đồng Nai</option>
                            <option value="Đồng Tháp">Đồng Tháp</option>
                            <option value="Gia Lai">Gia Lai</option>
                            <option value="Hà Giang">Hà Giang</option>
                            <option value="Hà Nam">Hà Nam</option>
                            <option value="Hà Tĩnh">Hà Tĩnh</option>
                            <option value="Hải Dương">Hải Dương</option>
                            <option value="Hậu Giang">Hậu Giang</option>
                            <option value="Hòa Bình">Hòa Bình</option>
                            <option value="Hưng Yên">Hưng Yên</option>
                            <option value="Khánh Hòa">Khánh Hòa</option>
                            <option value="Kiên Giang">Kiên Giang</option>
                            <option value="Kon Tum">Kon Tum</option>
                            <option value="Lai Châu">Lai Châu</option>
                            <option value="Lâm Đồng">Lâm Đồng</option>
                            <option value="Lạng Sơn">Lạng Sơn</option>
                            <option value="Lào Cai">Lào Cai</option>
                            <option value="Long An">Long An</option>
                            <option value="Nam Định">Nam Định</option>
                            <option value="Nghệ An">Nghệ An</option>
                            <option value="Ninh Bình">Ninh Bình</option>
                            <option value="Ninh Thuận">Ninh Thuận</option>
                            <option value="Phú Thọ">Phú Thọ</option>
                            <option value="Phú Yên">Phú Yên</option>
                            <option value="Quảng Bình">Quảng Bình</option>
                            <option value="Quảng Nam">Quảng Nam</option>
                            <option value="Quảng Ngãi">Quảng Ngãi</option>
                            <option value="Quảng Ninh">Quảng Ninh</option>
                            <option value="Quảng Trị">Quảng Trị</option>
                            <option value="Sóc Trăng">Sóc Trăng</option>
                            <option value="Sơn La">Sơn La</option>
                            <option value="Tây Ninh">Tây Ninh</option>
                            <option value="Thái Bình">Thái Bình</option>
                            <option value="Thái Nguyên">Thái Nguyên</option>
                            <option value="Thanh Hóa">Thanh Hóa</option>
                            <option value="Thừa Thiên Huế">Thừa Thiên Huế</option>
                            <option value="Tiền Giang">Tiền Giang</option>
                            <option value="Trà Vinh">Trà Vinh</option>
                            <option value="Tuyên Quang">Tuyên Quang</option>
                            <option value="Vĩnh Long">Vĩnh Long</option>
                            <option value="Vĩnh Phúc">Vĩnh Phúc</option>
                            <option value="Yên Bái">Yên Bái</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <label for="address">Địa chỉ cụ thể</label>
                    <input type="text" id="address" name="address"
                           placeholder="Số nhà, đường, phường/xã...">
                </div>
            </div>

            <div class="form-section terms-section">
                <label class="checkbox-label">
                    <input type="checkbox" name="agree_terms" required>
                    Tôi đồng ý với <a href="<?php echo esc_url(home_url('/dieu-khoan-giao-dich')); ?>" target="_blank">Điều khoản giao dịch</a> và cam kết bán hàng chân thật
                </label>
            </div>

            <div class="form-row submit-row">
                <button type="submit" class="woocommerce-Button button">
                    🚀 Đăng Ký Ngay
                </button>
            </div>
        </form>
    </div>

    <style>
        .csc-seller-register-container {
            max-width: 700px;
            margin: 30px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .seller-register-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .seller-register-header h2 {
            color: #0073aa;
            margin-bottom: 10px;
        }
        .seller-register-header p {
            color: #666;
            margin: 0;
        }
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .form-section h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
            font-size: 16px;
        }
        .form-row {
            margin-bottom: 20px;
        }
        .form-row-group {
            display: flex;
            gap: 20px;
        }
        .form-row-group .form-row {
            flex: 1;
        }
        .form-row label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-row .required {
            color: #e2401c;
        }
        .form-row input[type="text"],
        .form-row input[type="tel"],
        .form-row input[type="email"],
        .form-row select,
        .form-row textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-row input:focus,
        .form-row select:focus,
        .form-row textarea:focus {
            border-color: #0073aa;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,115,170,0.1);
        }
        .form-hint {
            font-size: 12px;
            color: #666;
            margin: 5px 0 0;
        }
        .terms-section {
            background: #fff;
            border: 1px solid #ddd;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-weight: normal;
        }
        .checkbox-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        .submit-row {
            text-align: center;
            margin-top: 30px;
        }
        .submit-row button {
            background: linear-gradient(135deg, #0073aa 0%, #005177 100%) !important;
            color: #fff !important;
            border: none !important;
            padding: 15px 50px !important;
            font-size: 16px !important;
            font-weight: 600 !important;
            cursor: pointer;
            border-radius: 30px !important;
            transition: transform 0.2s, box-shadow 0.2s !important;
        }
        .submit-row button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,115,170,0.4) !important;
        }
        @media screen and (max-width: 600px) {
            .form-row-group {
                flex-direction: column;
                gap: 0;
            }
            .csc-seller-register-container {
                padding: 20px;
                margin: 15px;
            }
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Preview store URL
        $('#store_url').on('input', function() {
            var val = $(this).val().toLowerCase()
                .replace(/[^a-z0-9]/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
            $('#store-url-preview').text(val || 'shop-cua-ban');
        });

        $('#store_name').on('input', function() {
            if (!$('#store_url').data('manual')) {
                var val = $(this).val().toLowerCase()
                    .replace(/[^a-z0-9\u00c0-\u024f\s]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
                $('#store_url').val(val).trigger('input');
            }
        });

        $('#store_url').on('focus', function() {
            $(this).data('manual', true);
        });
    });
    </script>
    <?php

    return ob_get_clean();
}

// Xử lý đăng ký seller
function csc_process_seller_registration($user_id, $data) {
    // Validate
    if (empty($data['store_name'])) {
        return new WP_Error('missing_store_name', 'Vui lòng nhập tên cửa hàng');
    }

    if (empty($data['phone'])) {
        return new WP_Error('missing_phone', 'Vui lòng nhập số điện thoại');
    }

    if (empty($data['agree_terms'])) {
        return new WP_Error('missing_terms', 'Bạn cần đồng ý với điều khoản');
    }

    // Tạo vendor với Dokan
    $store_name = sanitize_text_field($data['store_name']);
    $store_url = !empty($data['store_url']) ? sanitize_title($data['store_url']) : sanitize_title($store_name);
    $store_description = sanitize_textarea_field($data['store_description']);
    $phone = sanitize_text_field($data['phone']);
    $city = sanitize_text_field($data['city']);
    $address = sanitize_text_field($data['address']);

    // Đăng ký seller với Dokan
    $dokan_settings = array(
        'store_name'      => $store_name,
        'social'          => array(),
        'payment'          => array(),
        'phone'           => $phone,
        'show_email'      => 'no',
        'address'         => array(
            'street_1' => $address,
            'city'    => $city,
            'country' => 'VN',
        ),
        'dokan_category' => array(),
    );

    // Cập nhật Dokan settings
    update_user_meta($user_id, 'dokan_profile_settings', $dokan_settings);

    // Cập nhật store name trực tiếp
    update_user_meta($user_id, 'dokan_store_name', $store_name);
    update_user_meta($user_id, 'store_name', $store_name);

    // Đánh dấu vendor status là approved (tự động duyệt)
    update_user_meta($user_id, 'dokan_enabled', '1');
    update_user_meta($user_id, 'dokan_registration_method', 'web');

    // Gán role seller
    $user = new WP_User($user_id);
    $user->add_role('seller');

    return true;
}

// Tạo page đăng ký seller
add_action('init', 'csc_create_seller_register_page');

function csc_create_seller_register_page() {
    // Kiểm tra page đã tồn tại chưa
    $page = get_page_by_path('seller-registration');

    if (!$page) {
        // Tạo page mới
        wp_insert_post(array(
            'post_title'    => 'Đăng Ký Bán Hàng',
            'post_name'     => 'seller-registration',
            'post_content'  => '[csc_seller_register]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
        ));
    }
}
