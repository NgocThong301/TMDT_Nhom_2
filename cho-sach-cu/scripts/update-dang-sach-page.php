<?php
/**
 * Quick script to update Đăng Sách page content
 */
require_once __DIR__ . '/../../../wp-load.php';

$page = get_page_by_title('Đăng Sách');
if ($page) {
    wp_update_post(array(
        'ID' => $page->ID,
        'post_content' => '[vendor_add_book]'
    ));
    echo "Updated page ID: " . $page->ID . "\n";
} else {
    echo "Page not found\n";
}
