<?php
/**
 * Plugin Name: Create Vendor Book Page
 * Description: Tự động tạo page Đăng Sách nếu chưa có
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'ensure_vendor_book_page');

function ensure_vendor_book_page() {
    // Chỉ chạy một lần
    if (get_transient('vendor_book_page_created')) {
        return;
    }
    
    $existing = get_posts(array(
        'post_type'      => 'page',
        'title'          => 'Đăng Sách',
        'post_status'    => 'any',
        'posts_per_page' => 1,
    ));
    
    if (empty($existing)) {
        wp_insert_post(array(
            'post_title'   => 'Đăng Sách',
            'post_content' => '<!-- wp:shortcode -->[vendor_add_book]<!-- /wp:shortcode -->',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_author'  => 1,
        ));
    }
    
    // Đánh dấu đã chạy
    set_transient('vendor_book_page_created', true, MONTH_IN_SECONDS);
}
