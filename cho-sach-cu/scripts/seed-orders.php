<?php
/**
 * Tạo dữ liệu test: đơn hàng, đánh giá, yêu thích
 * - Tạo đơn hàng ở nhiều trạng thái khác nhau
 * - Tạo đánh giá sản phẩm
 * - Tạo sản phẩm yêu thích
 */
if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

if (!class_exists('WooCommerce')) {
    WP_CLI::error('WooCommerce chưa kớ hoạt.');
}

// ============================================================
// Hàm helper
// ============================================================

function csc_seed_get_or_create_user($login, $email, $password, $role = 'customer') {
    $user = get_user_by('login', $login);
    if ($user) {
        return (int) $user->ID;
    }
    $id = wp_insert_user(array(
        'user_login'   => $login,
        'user_pass'    => $password,
        'user_email'   => $email,
        'display_name' => $login,
        'role'         => $role,
    ));
    if (is_wp_error($id)) {
        WP_CLI::warning($id->get_error_message());
        return 0;
    }
    return (int) $id;
}

function csc_seed_get_product_ids() {
    $products = wc_get_products(array(
        'limit'  => -1,
        'status' => 'publish',
        'return' => 'objects',
    ));
    return array_map(function($p) { return $p->get_id(); }, $products);
}

function csc_seed_add_order_note($order_id, $message, $type = 'customer') {
    $order = wc_get_order($order_id);
    if ($order) {
        $order->add_order_note($message, $type);
    }
}

function csc_seed_create_order($customer_id, $products, $status, $date_created, $payment_method = 'cod') {
    // Tạo address
    $address = array(
        'first_name' => 'Nguyễn',
        'last_name'  => 'Văn A',
        'company'    => '',
        'email'      => 'nguyenvana@email.local',
        'phone'      => '0901234567',
        'address_1'  => '123 Đường Nguyễn Trãi',
        'address_2'  => 'Phường 1',
        'city'       => 'Hà Nội',
        'state'      => 'Hà Nội',
        'postcode'   => '100000',
        'country'    => 'VN',
    );

    // Tạo order
    $order = wc_create_order(array(
        'customer_id' => $customer_id,
        'status'      => $status,
        'date_created'=> new DateTime($date_created),
    ));

    $order->set_address($address, 'billing');
    $order->set_address($address, 'shipping');

    // Thêm sản phẩm
    $subtotal = 0;
    foreach ($products as $prod_data) {
        $product_id = $prod_data['id'];
        $qty = $prod_data['qty'];
        $product = wc_get_product($product_id);
        if ($product) {
            $item_id = $order->add_product($product, $qty);
            $subtotal += $product->get_price() * $qty;
        }
    }

    // Tính shipping
    $shipping_cost = $subtotal >= 300000 ? 0 : 25000;
    
    // Set totals
    $order->set_subtotal($subtotal);
    $order->set_total($subtotal + $shipping_cost);
    $order->update_taxes();
    $order->calculate_totals();

    // Payment method
    $order->set_payment_method($payment_method);
    if ($payment_method === 'bacs') {
        $order->set_payment_method_title('Chuyển khoản ngân hàng (thử nghiệm)');
        $order->set_transaction_id('TEST-' . strtoupper(wp_generate_uuid4()));
    } else {
        $order->set_payment_method_title('Thanh toán khi nhận hàng');
    }

    $order->save();

    return $order->get_id();
}

function csc_seed_create_review($product_id, $customer_id, $rating, $comment, $date) {
    // Kiểm tra đã có review chưa
    $existing = get_comments(array(
        'post_id' => $product_id,
        'user_id' => $customer_id,
        'count' => true,
    ));
    if ($existing > 0) {
        return 0;
    }

    $user = get_userdata($customer_id);
    $author_name = $user ? $user->display_name : 'Khách hàng';

    $data = array(
        'comment_post_ID'      => $product_id,
        'comment_author'      => $author_name,
        'comment_author_email'=> $user ? $user->user_email : '',
        'comment_author_url'  => '',
        'comment_content'     => $comment,
        'comment_type'        => 'review',
        'user_id'             => $customer_id,
        'comment_date'        => $date,
        'comment_approved'    => 1,
    );

    $comment_id = wp_insert_comment($data);
    if ($comment_id) {
        update_comment_meta($comment_id, 'rating', $rating);
        // Cập nhật average rating cho sản phẩm
        $product = wc_get_product($product_id);
        if ($product) {
            $product->add_meta_data('_average_rating', $rating);
            $product->save();
        }
    }
    return $comment_id;
}

WP_CLI::line('=== Tạo dữ liệu test: Đơn hàng & Đánh giá ===');

// ============================================================
// 1. Đảm bảo có tài khoản customer để test
// ============================================================
WP_CLI::line('');
WP_CLI::line('1. Tạo tài khoản customer test...');

$customer1_id = csc_seed_get_or_create_user(
    'customer_hanoi',
    'customer.hanoi@cho-sach-cu.local',
    getenv('BUYER_PASSWORD') ?: 'change-me',
    'customer'
);
$customer2_id = csc_seed_get_or_create_user(
    'customer_hcm',
    'customer.hcm@cho-sach-cu.local',
    getenv('BUYER_PASSWORD') ?: 'change-me',
    'customer'
);
$customer3_id = csc_seed_get_or_create_user(
    'customer_danang',
    'customer.danang@cho-sach-cu.local',
    getenv('BUYER_PASSWORD') ?: 'change-me',
    'customer'
);

if ($customer1_id) update_user_meta($customer1_id, 'billing_phone', '0901234567');
if ($customer2_id) update_user_meta($customer2_id, 'billing_phone', '0902345678');
if ($customer3_id) update_user_meta($customer3_id, 'billing_phone', '0903456789');

WP_CLI::success("Đã tạo 3 tài khoản customer test");

// ============================================================
// 2. Lấy danh sách sản phẩm
// ============================================================
WP_CLI::line('');
WP_CLI::line('2. Lấy danh sách sản phẩm...');
$product_ids = csc_seed_get_product_ids();
WP_CLI::success('Tìm thấy ' . count($product_ids) . ' sản phẩm');

if (empty($product_ids)) {
    WP_CLI::error('Không có sản phẩm nào. Chạy seed-products.php trước.');
}

// ============================================================
// 3. Tạo đơn hàng mẫu
// ============================================================
WP_CLI::line('');
WP_CLI::line('3. Tạo đơn hàng mẫu...');

$orders_data = array(
    // Đơn hàng đã hoàn thành (đã thanh toán, đã giao)
    array(
        'customer' => $customer1_id,
        'products' => array(
            array('id' => $product_ids[0], 'qty' => 1),
            array('id' => $product_ids[3], 'qty' => 1),
        ),
        'status' => 'completed',
        'date' => '2026-10-15 10:30:00',
        'payment' => 'bacs',
        'note' => 'Cảm ơn bạn đã đặt hàng! Sách sẽ được gửi trong ngày.',
    ),
    array(
        'customer' => $customer2_id,
        'products' => array(
            array('id' => $product_ids[6], 'qty' => 1),
            array('id' => $product_ids[7], 'qty' => 1),
            array('id' => $product_ids[8], 'qty' => 1),
        ),
        'status' => 'completed',
        'date' => '2026-10-16 14:20:00',
        'payment' => 'cod',
        'note' => 'Đơn hàng đã giao thành công.',
    ),
    array(
        'customer' => $customer3_id,
        'products' => array(
            array('id' => $product_ids[12], 'qty' => 1),
            array('id' => $product_ids[13], 'qty' => 1),
        ),
        'status' => 'completed',
        'date' => '2026-10-17 09:15:00',
        'payment' => 'bacs',
        'note' => 'Thanh toán đã xác nhận. Đang chuẩn bị hàng.',
    ),

    // Đơn hàng đang xử lý
    array(
        'customer' => $customer1_id,
        'products' => array(
            array('id' => $product_ids[4], 'qty' => 2),
        ),
        'status' => 'processing',
        'date' => '2026-10-18 16:45:00',
        'payment' => 'cod',
        'note' => 'Đơn hàng đang được xử lý.',
    ),
    array(
        'customer' => $customer2_id,
        'products' => array(
            array('id' => $product_ids[10], 'qty' => 1),
            array('id' => $product_ids[11], 'qty' => 1),
        ),
        'status' => 'processing',
        'date' => '2026-10-19 11:30:00',
        'payment' => 'bacs',
        'note' => 'Chờ xác nhận thanh toán chuyển khoản.',
    ),

    // Đơn hàng đang vận chuyển
    array(
        'customer' => $customer3_id,
        'products' => array(
            array('id' => $product_ids[1], 'qty' => 1),
            array('id' => $product_ids[14], 'qty' => 1),
        ),
        'status' => 'shipped',
        'date' => '2026-10-18 08:00:00',
        'payment' => 'cod',
        'note' => 'Đơn hàng đã được giao cho đơn vị vận chuyển.',
    ),

    // Đơn hàng mới (chờ xử lý)
    array(
        'customer' => $customer1_id,
        'products' => array(
            array('id' => $product_ids[5], 'qty' => 1),
        ),
        'status' => 'on-hold',
        'date' => '2026-10-19 20:15:00',
        'payment' => 'bacs',
        'note' => 'Chờ thanh toán.',
    ),
    array(
        'customer' => $customer2_id,
        'products' => array(
            array('id' => $product_ids[15], 'qty' => 1),
            array('id' => $product_ids[16], 'qty' => 1),
        ),
        'status' => 'pending',
        'date' => '2026-10-20 07:30:00',
        'payment' => 'cod',
        'note' => 'Đơn hàng mới, đang chờ xác nhận.',
    ),

    // Đơn hàng đã hủy
    array(
        'customer' => $customer3_id,
        'products' => array(
            array('id' => $product_ids[2], 'qty' => 1),
        ),
        'status' => 'cancelled',
        'date' => '2026-10-17 15:00:00',
        'payment' => 'cod',
        'note' => 'Khách hủy đơn - không còn nhu cầu.',
    ),
);

$created_orders = 0;
foreach ($orders_data as $order_data) {
    $order_id = csc_seed_create_order(
        $order_data['customer'],
        $order_data['products'],
        $order_data['status'],
        $order_data['date'],
        $order_data['payment']
    );
    if ($order_id) {
        csc_seed_add_order_note($order_id, $order_data['note'], 'system');
        $created_orders++;
        WP_CLI::log("  Đơn hàng #$order_id - {$order_data['status']}");
    }
}

WP_CLI::success("Đã tạo $created_orders đơn hàng mẫu");

// ============================================================
// 4. Tạo đánh giá sản phẩm
// ============================================================
WP_CLI::line('');
WP_CLI::line('4. Tạo đánh giá sản phẩm...');

$reviews_data = array(
    // Sách giáo khoa
    array(
        'product' => $product_ids[0], // Toán 12
        'customer' => $customer1_id,
        'rating' => 5,
        'comment' => 'Sách còn rất tốt, có vài ghi chú nhỏ bằng bút chì. Giao hàng nhanh, đóng gói cẩn thận. Sẽ ủng hộ tiếp!',
        'date' => '2026-10-16 12:00:00',
    ),
    array(
        'product' => $product_ids[1], // Ngữ văn 11
        'customer' => $customer2_id,
        'rating' => 4,
        'comment' => 'Bìa đẹp, ruột sạch. Ship hơi lâu 1 ngày nhưng tổng quan ok.',
        'date' => '2026-10-17 09:30:00',
    ),

    // Văn học
    array(
        'product' => $product_ids[6], // Số đỏ
        'customer' => $customer3_id,
        'rating' => 5,
        'comment' => 'Sách văn học hay, đọc như mới. Giá rẻ hơn nhiều so với nhà sách. Recommend!',
        'date' => '2026-10-17 14:00:00',
    ),
    array(
        'product' => $product_ids[7], // Chí Phèo
        'customer' => $customer1_id,
        'rating' => 4,
        'comment' => 'Truyện ngắn Nam Cao kinh điển. Sách giao đúng như mô tả, có đánh dấu nhẹ.',
        'date' => '2026-10-18 10:00:00',
    ),
    array(
        'product' => $product_ids[8], // Tôi thấy hoa vàng
        'customer' => $customer2_id,
        'rating' => 5,
        'comment' => 'Sách gần như mới, chưa đọc. Nguyễn Nhật Ánh viết hay như thường. Mua lần 2 rồi.',
        'date' => '2026-10-19 11:00:00',
    ),

    // Kỹ năng
    array(
        'product' => $product_ids[11], // Đắc nhân tâm
        'customer' => $customer3_id,
        'rating' => 4,
        'comment' => 'Sách kinh điển. Bản cũ đọc vẫn thích, có vài chỗ underline nhưng không ảnh hưởng.',
        'date' => '2026-10-16 16:00:00',
    ),
    array(
        'product' => $product_ids[12], // Nhà giả kim
        'customer' => $customer1_id,
        'rating' => 5,
        'comment' => 'Sách hay, giao nhanh. Sách cũ mà tình trạng rất tốt, không ngờ.',
        'date' => '2026-10-18 14:30:00',
    ),

    // Ngoại ngữ
    array(
        'product' => $product_ids[13], // English Grammar
        'customer' => $customer2_id,
        'rating' => 4,
        'comment' => 'Sách ngữ pháp hay, có làm bài tập bằng bút chì nên dễ xóa. Phù hợp để ôn luyện.',
        'date' => '2026-10-15 15:00:00',
    ),
    array(
        'product' => $product_ids[14], // IELTS 17
        'customer' => $customer3_id,
        'rating' => 5,
        'comment' => 'Sách IELTS chất lượng, không có vết bẩn. Mua cho em học, rất hài lòng.',
        'date' => '2026-10-17 10:00:00',
    ),

    // Kinh tế
    array(
        'product' => $product_ids[9], // Dạy con làm giàu
        'customer' => $customer1_id,
        'rating' => 4,
        'comment' => 'Nội dung quý giá, có gạch chân các ý quan trọng. Cảm ơn người bán đã giữ gìn tốt.',
        'date' => '2026-10-19 08:00:00',
    ),
);

$created_reviews = 0;
foreach ($reviews_data as $review) {
    $comment_id = csc_seed_create_review(
        $review['product'],
        $review['customer'],
        $review['rating'],
        $review['comment'],
        $review['date']
    );
    if ($comment_id) {
        $created_reviews++;
        WP_CLI::log("  Đánh giá sản phẩm #$comment_id - {$review['rating']} sao");
    }
}

WP_CLI::success("Đã tạo $created_reviews đánh giá");

// ============================================================
// 5. Thêm wishlist (yêu thích) cho customer
// ============================================================
WP_CLI::line('');
WP_CLI::line('5. Tạo danh sách yêu thích...');

// WooCommerce không có bảng wishlist mặc định, dùng user_meta đơn giản
$wishlist_data = array(
    $customer1_id => array($product_ids[2], $product_ids[5], $product_ids[15]),
    $customer2_id => array($product_ids[4], $product_ids[9], $product_ids[14]),
    $customer3_id => array($product_ids[0], $product_ids[10], $product_ids[16]),
);

$wishlist_count = 0;
foreach ($wishlist_data as $cust_id => $product_id_list) {
    $existing = get_user_meta($cust_id, '_wishlist_products', true);
    if (empty($existing)) {
        $existing = array();
    }
    foreach ($product_id_list as $pid) {
        if (!in_array($pid, $existing)) {
            $existing[] = $pid;
            $wishlist_count++;
        }
    }
    update_user_meta($cust_id, '_wishlist_products', $existing);
}

WP_CLI::success("Đã thêm $wishlist_count sản phẩm vào danh sách yêu thích");

// ============================================================
// 6. Tạo thông báo cho vendor (optional)
// ============================================================
WP_CLI::line('');
WP_CLI::success('=== Hoàn tất tạo dữ liệu test ===');
WP_CLI::line('');
WP_CLI::line('Tổng kết:');
WP_CLI::line("  - 3 tài khoản customer test");
WP_CLI::line("  - $created_orders đơn hàng (các trạng thái: completed, processing, shipped, on-hold, pending, cancelled)");
WP_CLI::line("  - $created_reviews đánh giá sản phẩm");
WP_CLI::line("  - $wishlist_count sản phẩm yêu thích");
WP_CLI::line('');
WP_CLI::line('Tài khoản customer test:');
WP_CLI::line('  customer_hanoi / (mật khẩu trong .env → BUYER_PASSWORD)');
WP_CLI::line('  customer_hcm / (mật khẩu trong .env → BUYER_PASSWORD)');
WP_CLI::line('  customer_danang / (mật khẩu trong .env → BUYER_PASSWORD)');
WP_CLI::line('');
WP_CLI::line('Xem đơn hàng: wp-admin > WooCommerce > Đơn hàng');
WP_CLI::line('Xem đánh giá: wp-admin > Comments hoặc trang sản phẩm');
