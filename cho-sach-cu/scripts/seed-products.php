<?php
/**
 * Tạo tài khoản thử nghiệm và sản phẩm sách bằng WooCommerce / WordPress API.
 * Không INSERT trực tiếp vào bảng wp_posts / wp_postmeta.
 */

if (!class_exists('WooCommerce')) {
    WP_CLI::error('WooCommerce chưa kích hoạt.');
}

function csc_user($login, $email, $password, $role, $display, $store_name = '') {
    $user = get_user_by('login', $login);
    if ($user) {
        $id = (int) $user->ID;
        $user->set_role($role);
    } else {
        $id = wp_insert_user(array(
            'user_login'   => $login,
            'user_pass'    => $password,
            'user_email'   => $email,
            'display_name' => $display,
            'nickname'     => $display,
            'role'         => $role,
        ));
        if (is_wp_error($id)) {
            WP_CLI::warning($id->get_error_message());
            return 0;
        }
        $id = (int) $id;
    }

    if ($role === 'seller') {
        update_user_meta($id, 'dokan_enable_selling', 'yes');
        update_user_meta($id, 'dokan_publishing', 'no');
        $profile = get_user_meta($id, 'dokan_profile_settings', true);
        if (!is_array($profile)) {
            $profile = array();
        }
        $profile['store_name'] = $store_name;
        $profile['phone'] = '090000000' . substr((string) $id, -1);
        $profile['address'] = array(
            'street_1' => 'Số 1 Nguyễn Trãi',
            'city'     => 'Hà Nội',
            'zip'      => '100000',
            'country'  => 'VN',
            'state'    => '',
        );
        update_user_meta($id, 'dokan_profile_settings', $profile);
        update_user_meta($id, 'dokan_store_name', $store_name);
    }

    return $id;
}

function csc_term($taxonomy, $name, $slug) {
    $by_slug = get_term_by('slug', $slug, $taxonomy);
    if ($by_slug && !is_wp_error($by_slug)) {
        return (int) $by_slug->term_id;
    }
    $by_name = get_term_by('name', $name, $taxonomy);
    if ($by_name && !is_wp_error($by_name)) {
        return (int) $by_name->term_id;
    }
    $result = wp_insert_term($name, $taxonomy, array('slug' => $slug));
    if (is_wp_error($result)) {
        WP_CLI::warning($taxonomy . ': ' . $result->get_error_message());
        return 0;
    }
    return (int) $result['term_id'];
}

function csc_ensure_attribute($label, $slug) {
    $taxonomy = wc_attribute_taxonomy_name($slug);
    $id = wc_attribute_taxonomy_id_by_name($slug);
    if (!$id) {
        $id = wc_create_attribute(array(
            'name'         => $label,
            'slug'         => $slug,
            'type'         => 'select',
            'order_by'     => 'menu_order',
            'has_archives' => false,
        ));
        if (is_wp_error($id)) {
            WP_CLI::warning($id->get_error_message());
            return $taxonomy;
        }
        delete_transient('wc_attribute_taxonomies');
        WC_Cache_Helper::invalidate_cache_group('woocommerce-attributes');
    }
    if (!taxonomy_exists($taxonomy)) {
        register_taxonomy(
            $taxonomy,
            array('product'),
            array(
                'labels'       => array('name' => $label),
                'hierarchical' => false,
                'show_ui'      => false,
                'query_var'    => true,
                'rewrite'      => false,
            )
        );
    }
    return $taxonomy;
}

function csc_cover_png($path, $title, $author, $hex) {
    $w = 600;
    $h = 800;
    if (!function_exists('imagecreatetruecolor')) {
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
        return;
    }
    $im = imagecreatetruecolor($w, $h);
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $bg = imagecolorallocate($im, $r, $g, $b);
    $fg = imagecolorallocate($im, 255, 255, 255);
    $band = imagecolorallocate($im, 20, 20, 20);
    imagefilledrectangle($im, 0, 0, $w, $h, $bg);
    imagefilledrectangle($im, 0, 0, 28, $h, $band);
    imagefilledrectangle($im, 40, 120, $w - 40, 126, $fg);
    $wrapped = wordwrap($title, 18, "\n");
    $y = 180;
    foreach (explode("\n", $wrapped) as $line) {
        imagestring($im, 5, 60, $y, $line, $fg);
        $y += 28;
    }
    imagestring($im, 4, 60, 620, $author, $fg);
    imagestring($im, 3, 60, 680, 'Cho Sach Cu - minh hoa local', $fg);
    imagepng($im, $path);
    imagedestroy($im);
}

function csc_import_cover($title, $author, $hex) {
    $dir = wp_upload_dir();
    $file = trailingslashit($dir['path']) . sanitize_title($title) . '-cover.png';
    csc_cover_png($file, $title, $author, $hex);
    $filetype = wp_check_filetype(basename($file), null);
    $attachment = array(
        'post_mime_type' => $filetype['type'] ? $filetype['type'] : 'image/png',
        'post_title'     => $title,
        'post_content'   => '',
        'post_status'    => 'inherit',
    );
    $attach_id = wp_insert_attachment($attachment, $file);
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $meta = wp_generate_attachment_metadata($attach_id, $file);
    wp_update_attachment_metadata($attach_id, $meta);
    return (int) $attach_id;
}

function csc_find_product($sku) {
    $id = wc_get_product_id_by_sku($sku);
    return $id ? (int) $id : 0;
}

$vendor1 = csc_user(
    'vendor_sgk_demo',
    'vendor.sgk@cho-sach-cu.local',
    getenv('VENDOR1_PASSWORD') ?: 'change-me',
    'seller',
    'Người bán SGK (demo)',
    'Tủ sách lớp 12'
);
$vendor2 = csc_user(
    'vendor_vanhoc_demo',
    'vendor.vanhoc@cho-sach-cu.local',
    getenv('VENDOR2_PASSWORD') ?: 'change-me',
    'seller',
    'Người bán văn học (demo)',
    'Sách cũ Nguyễn Du'
);
$vendor3 = csc_user(
    'vendor_ngoaingu_demo',
    'vendor.ngoaingu@cho-sach-cu.local',
    getenv('VENDOR3_PASSWORD') ?: 'change-me',
    'seller',
    'Người bán ngoại ngữ (demo)',
    'Góc IELTS secondhand'
);
csc_user(
    'buyer_demo',
    'buyer@cho-sach-cu.local',
    getenv('BUYER_PASSWORD') ?: 'change-me',
    'customer',
    'Người mua (demo)'
);

$categories = array(
    'sach-giao-khoa'     => 'Sách giáo khoa',
    'giao-trinh-dai-hoc' => 'Giáo trình đại học',
    'van-hoc'            => 'Văn học',
    'kinh-te'            => 'Kinh tế',
    'ky-nang'            => 'Kỹ năng',
    'ngoai-ngu'          => 'Ngoại ngữ',
    'truyen-tranh'       => 'Truyện tranh',
);
$cat_ids = array();
foreach ($categories as $slug => $name) {
    $cat_ids[$slug] = csc_term('product_cat', $name, $slug);
}

$tax_author = csc_ensure_attribute('Tác giả', 'tac-gia');
$tax_condition = csc_ensure_attribute('Tình trạng', 'tinh-trang');

$books = array(
    array('CSC-SGK-001', 'Toán 12 tập 1 (sách giáo khoa cũ)', 'Bộ Giáo dục và Đào tạo', 'sach-giao-khoa', 55000, 'Tốt', 4, $vendor1, '3A7A44', 'Sách giáo khoa Toán 12 tập 1, có ghi chú nhẹ bằng bút chì ở một số bài luyện tập.'),
    array('CSC-SGK-002', 'Ngữ văn 11 tập 1 (sách giáo khoa cũ)', 'Bộ Giáo dục và Đào tạo', 'sach-giao-khoa', 45000, 'Rất tốt', 3, $vendor1, '2E6F40', 'Bìa còn đẹp, ruột sách sạch, phù hợp ôn thi học kỳ.'),
    array('CSC-SGK-003', 'Hóa học 10 (sách giáo khoa cũ)', 'Bộ Giáo dục và Đào tạo', 'sach-giao-khoa', 40000, 'Có dấu hiệu sử dụng', 2, $vendor1, '1F4E3A', 'Gáy sách hơi mòn, có đánh dấu công thức. Đọc tốt.'),
    array('CSC-GT-001', 'Giáo trình Kinh tế vi mô', 'PGS.TS. Nguyễn Văn Ngọc', 'giao-trinh-dai-hoc', 120000, 'Tốt', 5, $vendor1, '1D4E89', 'Giáo trình đại học dùng cho khối kinh tế. Có gạch chân các định nghĩa quan trọng.'),
    array('CSC-GT-002', 'Giáo trình Cấu trúc dữ liệu và giải thuật', 'Đỗ Xuân Lôi', 'giao-trinh-dai-hoc', 150000, 'Rất tốt', 3, $vendor1, '163A66', 'Sách chuyên ngành CNTT, ít viết, phù hợp sinh viên năm 2-3.'),
    array('CSC-GT-003', 'Giáo trình Luật Hiến pháp Việt Nam', 'Trường ĐH Luật Hà Nội', 'giao-trinh-dai-hoc', 90000, 'Tốt', 2, $vendor2, '0F2C4C', 'Giáo trình luật, còn đủ phụ lục. Không rách trang.'),
    array('CSC-VH-001', 'Số đỏ', 'Vũ Trọng Phụng', 'van-hoc', 65000, 'Rất tốt', 4, $vendor2, '8C2F39', 'Tiểu thuyết hiện thực phê phán. Ấn bản phổ thông, giấy ngả nhẹ.'),
    array('CSC-VH-002', 'Chí Phèo', 'Nam Cao', 'van-hoc', 35000, 'Tốt', 6, $vendor2, '6B2D35', 'Tập truyện ngắn Nam Cao, phù hợp ôn văn học nhà trường.'),
    array('CSC-VH-003', 'Tôi thấy hoa vàng trên cỏ xanh', 'Nguyễn Nhật Ánh', 'van-hoc', 75000, 'Mới', 3, $vendor2, 'A45C5C', 'Gần như sách mới, chưa viết, bìa còn bóng.'),
    array('CSC-KT-001', 'Dạy con làm giàu - tập 1', 'Robert T. Kiyosaki', 'kinh-te', 85000, 'Tốt', 4, $vendor2, 'C47B17', 'Sách kinh tế phổ thông, bản dịch tiếng Việt, có underline một số ý.'),
    array('CSC-KT-002', 'Think and Grow Rich (bản tiếng Việt)', 'Napoleon Hill', 'kinh-te', 95000, 'Rất tốt', 2, $vendor2, 'A65B12', 'Sách kinh điển về tư duy làm giàu. Tình trạng rất tốt.'),
    array('CSC-KN-001', 'Đắc nhân tâm', 'Dale Carnegie', 'ky-nang', 80000, 'Tốt', 5, $vendor2, '5C4D7A', 'Sách kỹ năng giao tiếp, bản phổ thông, đọc nhiều nhưng còn chắc tay.'),
    array('CSC-KN-002', 'Nhà giả kim', 'Paulo Coelho', 'ky-nang', 70000, 'Rất tốt', 4, $vendor2, '3F6B5A', 'Tiểu thuyết giàu ý nghĩa, thường được xếp kệ kỹ năng/văn học truyền cảm hứng.'),
    array('CSC-NN-001', 'English Grammar in Use (sách cũ)', 'Raymond Murphy', 'ngoai-ngu', 180000, 'Tốt', 3, $vendor3, '2C6E9E', 'Sách ngữ pháp tiếng Anh, có làm một số bài tập bằng bút chì.'),
    array('CSC-NN-002', 'Cambridge IELTS 17 Academic (sách cũ)', 'Cambridge University Press', 'ngoai-ngu', 160000, 'Rất tốt', 2, $vendor3, '1B4F72', 'Đề thi IELTS Academic, không kèm đĩa, sách sạch.'),
    array('CSC-TT-001', 'Doraemon tập 1 (sách cũ)', 'Fujiko F. Fujio', 'truyen-tranh', 25000, 'Có dấu hiệu sử dụng', 8, $vendor3, 'E25C2A', 'Truyện tranh thiếu nhi, góc bìa hơi mòn, đủ trang.'),
    array('CSC-TT-002', 'One Piece tập 1 (sách cũ)', 'Eiichiro Oda', 'truyen-tranh', 30000, 'Tốt', 5, $vendor3, '1F6AA5', 'Tập mở đầu One Piece, bản tiếng Việt, còn đẹp.'),
);

foreach ($books as $book) {
    list($sku, $title, $author, $cat_slug, $price, $condition, $qty, $vendor_id, $hex, $desc) = $book;
    if (!$vendor_id) {
        WP_CLI::warning("Bỏ qua $sku vì thiếu vendor.");
        continue;
    }

    csc_term($tax_author, $author, sanitize_title($author));
    csc_term($tax_condition, $condition, sanitize_title($condition));

    $product_id = csc_find_product($sku);
    $product = $product_id ? wc_get_product($product_id) : new WC_Product_Simple();
    if (!$product) {
        $product = new WC_Product_Simple();
    }

    $product->set_name($title);
    $product->set_sku($sku);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_regular_price((string) $price);
    $product->set_short_description("Tác giả: $author. Tình trạng: $condition. Sách vật lý đã qua sử dụng.");
    $product->set_description(
        "<p>$desc</p>" .
        "<ul>" .
        "<li>Tác giả / biên soạn: $author</li>" .
        "<li>Tình trạng: $condition</li>" .
        "<li>Loại: sách vật lý (không phải ebook)</li>" .
        "<li>Giao dịch C2C local — không phải hàng từ nhà sách chính hãng</li>" .
        "</ul>"
    );
    $product->set_manage_stock(true);
    $product->set_stock_quantity((int) $qty);
    $product->set_stock_status('instock');
    $product->set_sold_individually(false);
    $product->set_virtual(false);
    $product->set_downloadable(false);
    $product->set_category_ids(array($cat_ids[$cat_slug]));
    $product->set_weight('0.4');
    $product->update_meta_data('_visibility', 'visible');

    $attr_author = new WC_Product_Attribute();
    $attr_author->set_id((int) wc_attribute_taxonomy_id_by_name('tac-gia'));
    $attr_author->set_name($tax_author);
    $attr_author->set_options(array($author));
    $attr_author->set_visible(true);
    $attr_author->set_variation(false);

    $attr_cond = new WC_Product_Attribute();
    $attr_cond->set_id((int) wc_attribute_taxonomy_id_by_name('tinh-trang'));
    $attr_cond->set_name($tax_condition);
    $attr_cond->set_options(array($condition));
    $attr_cond->set_visible(true);
    $attr_cond->set_variation(false);

    $product->set_attributes(array($attr_author, $attr_cond));

    if (!$product->get_image_id()) {
        $product->set_image_id(csc_import_cover($title, $author, $hex));
    }

    $saved_id = $product->save();
    wp_set_object_terms($saved_id, array($author), $tax_author, false);
    wp_set_object_terms($saved_id, array($condition), $tax_condition, false);
    wp_update_post(array(
        'ID'          => $saved_id,
        'post_author' => (int) $vendor_id,
    ));
    WP_CLI::log("Sản phẩm $sku #$saved_id");
}

WP_CLI::success('Đã tạo/cập nhật sách mẫu và tài khoản thử nghiệm.');
