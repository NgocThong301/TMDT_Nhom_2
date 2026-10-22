<?php
/**
 * Test vendor book submission directly
 */
require_once __DIR__ . '/../../../wp-load.php';

// Lấy vendor user
$vendor = get_user_by('login', 'vendor_sgk_demo');
if (!$vendor) {
    echo "❌ Vendor not found\n";
    exit(1);
}

echo "✅ Found vendor: {$vendor->display_name} (ID: {$vendor->ID})\n";

// Kiểm tra vendor có đúng role không
echo "Roles: " . implode(', ', $vendor->roles) . "\n";

// Tạo test data
$_POST = array(
    'vendor_add_book_nonce' => wp_create_nonce('vendor_add_book'),
    'book_title' => 'Test Book - PHP Submit ' . date('Y-m-d H:i:s'),
    'book_author' => 'Test Author',
    'book_price' => 50000,
    'book_condition' => 'good',
    'book_quantity' => 1,
    'book_category' => 17, // Sách giáo khoa
    'book_description' => 'Test description from PHP',
);
$_FILES = array(); // Không có ảnh

// Simulate logged in
wp_set_current_user($vendor->ID);

// Gọi function xử lý
$result = vendor_process_book_submission($_POST, $vendor->ID);

if (is_wp_error($result)) {
    echo "❌ Error: " . $result->get_error_message() . "\n";
} else {
    echo "✅ Book created successfully! Product ID: $result\n";
    
    // Lấy thông tin sản phẩm
    $product = wc_get_product($result);
    if ($product) {
        echo "Title: " . $product->get_name() . "\n";
        echo "Price: " . $product->get_price() . " VNĐ\n";
        echo "Status: " . $product->get_status() . "\n";
        echo "URL: " . get_permalink($result) . "\n";
    }
}
