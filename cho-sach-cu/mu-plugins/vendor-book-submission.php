<?php
/**
 * Plugin Name: Vendor Book Submission
 * Description: Cho phép người bán (vendor) đăng sách với ảnh bìa
 * Version: 1.0
 * Author: C2C
 */

if (!defined('ABSPATH')) {
    exit;
}

// Đăng ký shortcode đăng sách
add_shortcode('vendor_add_book', 'vendor_add_book_shortcode');

function vendor_add_book_shortcode($atts = array()) {
    // Kiểm tra đăng nhập
    if (!is_user_logged_in()) {
        return '<div class="woocommerce-message">Vui lòng <a href="' . esc_url(wp_login_url()) . '">đăng nhập</a> để đăng sách.</div>';
    }
    
    // Kiểm tra vendor (có thể dùng Dokan hoặc kiểm tra role)
    $user = wp_get_current_user();
    $is_vendor = in_array('seller', (array)$user->roles) || 
                 in_array('vendor', (array)$user->roles) ||
                 in_array('administrator', (array)$user->roles) ||
                 dokan_is_user_seller($user->ID);
    
    if (!$is_vendor) {
        return '<div class="woocommerce-error">Bạn cần là người bán để đăng sách. <a href="' . esc_url(home_url('/seller-registration')) . '">Đăng ký bán hàng ngay</a></div>';
    }
    
    // Xử lý form submit
    $message = '';
    $error = '';
    
    if (isset($_POST['vendor_add_book_nonce']) && wp_verify_nonce($_POST['vendor_add_book_nonce'], 'vendor_add_book')) {
        $result = vendor_process_book_submission($_POST, $user->ID);
        if (is_wp_error($result)) {
            $error = $result->get_error_message();
        } else {
            $message = 'Sách đã được đăng thành công! ' . 
                       '<a href="' . esc_url(get_edit_post_link($result)) . '">Chỉnh sửa</a> | ' .
                       '<a href="' . esc_url(get_permalink($result)) . '">Xem</a>';
        }
    }
    
    ob_start();
    ?>
    <div class="vendor-add-book-container">
        <h2>📚 Đăng Sách Mới</h2>
        
        <?php if ($message): ?>
            <div class="woocommerce-message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="woocommerce-error"><?php echo esc_html($error); ?></div>
        <?php endif; ?>
        
        <form id="vendor-add-book-form" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('vendor_add_book', 'vendor_add_book_nonce'); ?>
            
            <div class="form-row">
                <label for="book_title">Tiêu đề sách <span class="required">*</span></label>
                <input type="text" id="book_title" name="book_title" required placeholder="Ví dụ: Truyện Kiều - Nguyễn Du">
            </div>
            
            <div class="form-row">
                <label for="book_author">Tác giả <span class="required">*</span></label>
                <input type="text" id="book_author" name="book_author" required placeholder="Ví dụ: Nguyễn Du">
            </div>
            
            <div class="form-row-group">
                <div class="form-row">
                    <label for="book_price">Giá bán (VNĐ) <span class="required">*</span></label>
                    <input type="number" id="book_price" name="book_price" required min="1000" step="1000" placeholder="25000">
                </div>
                <div class="form-row">
                    <label for="book_regular_price">Giá gốc (VNĐ)</label>
                    <input type="number" id="book_regular_price" name="book_regular_price" min="1000" step="1000" placeholder="50000">
                </div>
            </div>
            
            <div class="form-row-group">
                <div class="form-row">
                    <label for="book_condition">Tình trạng</label>
                    <select id="book_condition" name="book_condition">
                        <option value="new">Mới</option>
                        <option value="like_new">Như mới</option>
                        <option value="good">Tốt</option>
                        <option value="acceptable">Chấp nhận được</option>
                    </select>
                </div>
                <div class="form-row">
                    <label for="book_quantity">Số lượng</label>
                    <input type="number" id="book_quantity" name="book_quantity" min="1" value="1">
                </div>
            </div>
            
            <div class="form-row">
                <label for="book_category">Danh mục <span class="required">*</span></label>
                <?php
                wp_dropdown_categories(array(
                    'taxonomy' => 'product_cat',
                    'name' => 'book_category',
                    'id' => 'book_category',
                    'class' => 'select short',
                    'show_option_none' => '— Chọn danh mục —',
                    'required' => true,
                ));
                ?>
            </div>
            
            <div class="form-row">
                <label for="book_publisher">Nhà xuất bản</label>
                <input type="text" id="book_publisher" name="book_publisher" placeholder="Ví dụ: NXB Văn học">
            </div>
            
            <div class="form-row">
                <label for="book_year">Năm xuất bản</label>
                <input type="number" id="book_year" name="book_year" min="1900" max="<?php echo date('Y'); ?>" placeholder="<?php echo date('Y'); ?>">
            </div>
            
            <div class="form-row">
                <label for="book_description">Mô tả sách</label>
                <textarea id="book_description" name="book_description" rows="5" placeholder="Mô tả chi tiết về tình trạng, nội dung sách..."></textarea>
            </div>
            
            <div class="form-row">
                <label>Ảnh bìa sách <span class="required">*</span></label>
                <div class="image-upload-wrapper">
                    <input type="file" id="book_image" name="book_image" accept="image/*" required>
                    <p class="upload-hint">Hỗ trợ: JPG, PNG, WebP. Tối đa 5MB.</p>
                    <div id="image-preview" class="image-preview"></div>
                </div>
            </div>
            
            <div class="form-row">
                <label>Ảnh chi tiết (tùy chọn)</label>
                <div class="image-upload-wrapper">
                    <input type="file" id="book_gallery_1" name="book_gallery_1" accept="image/*">
                    <input type="file" id="book_gallery_2" name="book_gallery_2" accept="image/*">
                    <input type="file" id="book_gallery_3" name="book_gallery_3" accept="image/*">
                    <p class="upload-hint">Có thể upload tối đa 3 ảnh chi tiết</p>
                </div>
            </div>
            
            <div class="form-row submit-row">
                <button type="submit" class="woocommerce-Button button" name="vendor_add_book_submit">
                    📖 Đăng Sách
                </button>
            </div>
        </form>
    </div>
    
    <style>
        .vendor-add-book-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .vendor-add-book-container h2 {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #0073aa;
            color: #333;
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
        .form-row input[type="number"],
        .form-row select,
        .form-row textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-row input:focus,
        .form-row select:focus,
        .form-row textarea:focus {
            border-color: #0073aa;
            outline: none;
        }
        .image-upload-wrapper input[type="file"] {
            margin-bottom: 10px;
        }
        .upload-hint {
            font-size: 12px;
            color: #666;
            margin: 5px 0;
        }
        .image-preview {
            max-width: 200px;
            margin-top: 10px;
        }
        .image-preview img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .submit-row {
            margin-top: 30px;
        }
        .submit-row button {
            background: #0073aa !important;
            color: #fff !important;
            border: none !important;
            padding: 15px 40px !important;
            font-size: 16px !important;
            cursor: pointer;
            border-radius: 4px;
        }
        .submit-row button:hover {
            background: #005177 !important;
        }
        @media screen and (max-width: 600px) {
            .form-row-group {
                flex-direction: column;
                gap: 0;
            }
            .vendor-add-book-container {
                padding: 20px;
                margin: 15px;
            }
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Preview ảnh chính
        $('#book_image').change(function() {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#image-preview').html('<img src="' + e.target.result + '" alt="Preview">');
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Validate form
        $('#vendor-add-book-form').submit(function() {
            var price = $('#book_price').val();
            if (price && price < 1000) {
                alert('Giá sách phải từ 1,000 VNĐ trở lên');
                return false;
            }
            
            var fileInput = $('#book_image')[0];
            if (fileInput.files[0]) {
                var fileSize = fileInput.files[0].size / 1024 / 1024;
                if (fileSize > 5) {
                    alert('Ảnh bìa không được vượt quá 5MB');
                    return false;
                }
            }
        });
    });
    </script>
    <?php
    
    return ob_get_clean();
}

// Xử lý submit form
function vendor_process_book_submission($data, $vendor_id) {
    // Validate required fields
    if (empty($data['book_title']) || empty($data['book_price']) || empty($data['book_category'])) {
        return new WP_Error('missing_fields', 'Vui lòng điền đầy đủ thông tin bắt buộc');
    }
    
    // Tạo sản phẩm
    $product = new WC_Product_Simple();
    $product->set_name(sanitize_text_field($data['book_title']));
    $product->set_status('publish'); // Xuất bản ngay
    $product->set_regular_price((string) floatval($data['book_price']));
    $product->set_price((string) floatval($data['book_price']));
    
    if (!empty($data['book_regular_price'])) {
        $product->set_regular_price(floatval($data['book_regular_price']));
    }
    
    if (!empty($data['book_quantity'])) {
        $product->set_stock_quantity(intval($data['book_quantity']));
        $product->set_manage_stock(true);
    }
    
    if (!empty($data['book_description'])) {
        $product->set_description(wp_kses_post($data['book_description']));
    }
    
    $product->set_short_description(sanitize_text_field($data['book_author']));
    
    $product_id = $product->save();
    
    if (!$product_id) {
        return new WP_Error('save_error', 'Không thể lưu sản phẩm');
    }
    
    // Set vendor as post author
    wp_update_post(array(
        'ID' => $product_id,
        'post_author' => $vendor_id
    ));
    
    // Set category
    if (!empty($data['book_category'])) {
        wp_set_object_terms($product_id, intval($data['book_category']), 'product_cat');
    }
    
    // Upload ảnh chính
    if (!empty($_FILES['book_image']['name'])) {
        $upload_result = vendor_upload_product_image($product_id, 'book_image');
        if (!is_wp_error($upload_result)) {
            set_post_thumbnail($product_id, $upload_result);
        }
    }
    
    // Upload ảnh gallery
    $gallery_ids = array();
    for ($i = 1; $i <= 3; $i++) {
        $field_name = 'book_gallery_' . $i;
        if (!empty($_FILES[$field_name]['name'])) {
            $gallery_result = vendor_upload_product_image($product_id, $field_name);
            if (!is_wp_error($gallery_result)) {
                $gallery_ids[] = $gallery_result;
            }
        }
    }
    
    if (!empty($gallery_ids)) {
        update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
    }
    
    // Lưu metadata sách
    update_post_meta($product_id, '_book_author', sanitize_text_field($data['book_author']));
    update_post_meta($product_id, '_book_condition', sanitize_text_field($data['book_condition']));
    if (!empty($data['book_publisher'])) {
        update_post_meta($product_id, '_book_publisher', sanitize_text_field($data['book_publisher']));
    }
    if (!empty($data['book_year'])) {
        update_post_meta($product_id, '_book_year', intval($data['book_year']));
    }
    
    return $product_id;
}

// Upload ảnh sản phẩm
function vendor_upload_product_image($product_id, $file_key) {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    // Check for errors
    if ($_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
        return new WP_Error('upload_error', 'Lỗi upload ảnh');
    }
    
    // Verify it's an image
    $allowed_mime_types = array('image/jpeg', 'image/png', 'image/webp', 'image/gif');
    $file_type = mime_content_type($_FILES[$file_key]['tmp_name']);
    if (!in_array($file_type, $allowed_mime_types)) {
        return new WP_Error('invalid_file', 'Chỉ chấp nhận file ảnh (JPG, PNG, WebP, GIF)');
    }
    
    // Upload
    $attachment_id = media_handle_upload($file_key, $product_id);
    
    if (is_wp_error($attachment_id)) {
        return $attachment_id;
    }
    
    return $attachment_id;
}

// Thêm menu vào Dokan dashboard
add_action('dokan_get_endpoints', 'vendor_add_dokan_endpoint');
function vendor_add_dokan_endpoint($endpoints) {
    $endpoints['add-book'] = 'add-book';
    return $endpoints;
}

// Tạo page cho endpoint
add_action('dokan_load_custom_template', 'vendor_load_add_book_template');
function vendor_load_add_book_template($query_vars) {
    if (isset($query_vars['add-book'])) {
        // Enqueue media uploader
        wp_enqueue_media();
        ?>
        <div class="dokan-dashboard-wrap">
            <?php do_action('dokan_dashboard_content_start'); ?>
            <div class="dokan-dashboard-content">
                <?php echo do_shortcode('[vendor_add_book]'); ?>
            </div>
            <?php do_action('dokan_dashboard_content_end'); ?>
        </div>
        <?php
        exit;
    }
}

// Thêm link vào menu vendor
add_filter('dokan_get_dashboard_nav', 'vendor_add_book_nav');
function vendor_add_book_nav($nav) {
    $nav['add-book'] = array(
        'title' => '📖 Đăng Sách',
        'icon' => '<i class="fa fa-plus"></i>',
        'url' => dokan_get_navigation_url('add-book'),
        'pos' => 50,
    );
    return $nav;
}
