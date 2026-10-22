<?php
function csc_page($title, $content, $option_key = '') {
    $found = get_posts(array(
        'post_type'      => 'page',
        'title'          => $title,
        'post_status'    => 'any',
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ));
    $existing = $found ? get_post($found[0]) : null;
    if ($existing) {
        $id = (int) $existing->ID;
    } else {
        $id = wp_insert_post(array(
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_author'  => 1,
        ));
    }
    if ($option_key && $id) {
        update_option($option_key, $id);
    }
    return (int) $id;
}

$home_id = csc_page(
    'Trang chủ',
    "<!-- wp:heading {\"level\":1} --><h1>Chợ Sách Cũ</h1><!-- /wp:heading -->\n" .
    "<!-- wp:paragraph --><p>Sàn C2C để sinh viên và độc giả mua bán sách đã qua sử dụng: sách giáo khoa, giáo trình, văn học, kinh tế, kỹ năng, ngoại ngữ và truyện tranh.</p><!-- /wp:paragraph -->\n" .
    "<!-- wp:shortcode -->[products limit=\"8\" columns=\"4\" orderby=\"date\"]<!-- /wp:shortcode -->"
);

$shop = wc_get_page_id('shop');
update_option('show_on_front', 'page');
update_option('page_on_front', $home_id);
if ($shop > 0) {
    wp_update_post(array(
        'ID'         => $shop,
        'post_title' => 'Cửa hàng',
    ));
}

csc_page(
    'Giới thiệu',
    "<p><strong>Chợ Sách Cũ</strong> là website bài tập mô hình C2C: người bán đăng sách cũ, người mua tìm kiếm, lọc và đặt hàng.</p>" .
    "<p>Dữ liệu và tài khoản trên site này chỉ dùng để thử nghiệm local, không phải dữ liệu khách hàng thật.</p>"
);

WP_CLI::success('Đã cấu hình trang chủ / cửa hàng.');

csc_page(
    'Đăng Sách',
    "<!-- wp:shortcode -->[vendor_add_book]<!-- /wp:shortcode -->",
    'vendor_add_book_page_id'
);

WP_CLI::success('Đã tạo trang Đăng Sách cho vendor.');
