<?php
/**
 * Plugin Name: CSC User Analytics
 * Description: Thong ke hanh vi nguoi dung cho Chợ Sách Cũ
 */

if (!defined('ABSPATH')) exit;

// Lưu log hành vi người dùng
add_action('wp_head', 'csc_track_user_behavior');

function csc_track_user_behavior() {
    if (!is_user_logged_in() && !isset($_COOKIE['csc_visitor_id'])) {
        $visitor_id = 'v_' . uniqid() . '_' . time();
        setcookie('csc_visitor_id', $visitor_id, time() + DAY_IN_SECONDS, '/');
        $_COOKIE['csc_visitor_id'] = $visitor_id;
    }
}

// AJAX: Track page view
add_action('wp_ajax_csc_track_pageview', 'csc_track_pageview');
add_action('wp_ajax_nopriv_csc_track_pageview', 'csc_track_pageview');

function csc_track_pageview() {
    $data = array(
        'visitor_id' => isset($_COOKIE['csc_visitor_id']) ? sanitize_text_field($_COOKIE['csc_visitor_id']) : '',
        'user_id'    => get_current_user_id(),
        'page_url'   => isset($_POST['page_url']) ? esc_url_raw($_POST['page_url']) : '',
        'page_title' => isset($_POST['page_title']) ? sanitize_text_field($_POST['page_title']) : '',
        'referrer'   => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '',
        'timestamp'  => current_time('mysql'),
    );
    
    // Lưu vào transient (cho đơn giản)
    $views = get_transient('csc_page_views') ?: array();
    $views[] = $data;
    
    // Giữ 1000 log gần nhất
    if (count($views) > 1000) {
        $views = array_slice($views, -1000);
    }
    
    set_transient('csc_page_views', $views, WEEK_IN_SECONDS);
    
    wp_send_json_success();
}

// AJAX: Track product view
add_action('wp_ajax_csc_track_product_view', 'csc_track_product_view');
add_action('wp_ajax_nopriv_csc_track_product_view', 'csc_track_product_view');

function csc_track_product_view() {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    if (!$product_id) {
        wp_send_json_error();
    }
    
    // Tăng view count
    $views = get_post_meta($product_id, '_csc_views', true) ?: 0;
    update_post_meta($product_id, '_csc_views', intval($views) + 1);
    
    // Lưu vào recent views
    $recent_views = isset($_COOKIE['csc_recent_views']) ? json_decode(stripslashes($_COOKIE['csc_recent_views']), true) : array();
    
    // Loại bỏ nếu đã có, thêm lên đầu
    $recent_views = array_filter($recent_views, function($id) use ($product_id) {
        return $id != $product_id;
    });
    array_unshift($recent_views, $product_id);
    $recent_views = array_slice($recent_views, 0, 10); // Giữ 10 sản phẩm gần nhất
    
    setcookie('csc_recent_views', json_encode($recent_views), time() + WEEK_IN_SECONDS, '/');
    
    wp_send_json_success(array('total_views' => intval($views) + 1));
}

// Shortcode: Trang thống kê cho admin
add_shortcode('csc_analytics_dashboard', 'csc_analytics_dashboard_shortcode');

function csc_analytics_dashboard_shortcode($atts) {
    // Chỉ admin mới thấy
    if (!current_user_can('manage_options')) {
        return '<p>Bạn không có quyền xem thống kê.</p>';
    }
    
    $page_views = get_transient('csc_page_views') ?: array();
    $top_products = csc_get_top_viewed_products(10);
    $stats = csc_get_analytics_stats();
    
    ob_start();
    ?>
    <div class="csc-analytics-dashboard">
        <h2>📊 Thống Kê Hành Vi Người Dùng</h2>
        
        <div class="stats-cards">
            <div class="stat-card">
                <h3><?php echo number_format($stats['total_views']); ?></h3>
                <p>Tổng lượt xem sản phẩm</p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format(count($page_views)); ?></h3>
                <p>Lượt truy cập trang</p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format($stats['total_products']); ?></h3>
                <p>Tổng sản phẩm</p>
            </div>
        </div>
        
        <h3>🏆 Top 10 Sản Phẩm Xem Nhiều Nhất</h3>
        <table class="analytics-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Sản phẩm</th>
                    <th>Lượt xem</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                foreach ($top_products as $item): 
                    $product = wc_get_product($item['product_id']);
                    if (!$product) continue;
                ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td>
                            <a href="<?php echo esc_url(get_permalink($item['product_id'])); ?>">
                                <?php echo esc_html($product->get_name()); ?>
                            </a>
                        </td>
                        <td><strong><?php echo number_format($item['views']); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <style>
        .csc-analytics-dashboard {
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
        }
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #0073aa 0%, #005177 100%);
            color: #fff;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
        }
        .stat-card h3 {
            font-size: 36px;
            margin: 0 0 10px;
        }
        .stat-card p {
            margin: 0;
            opacity: 0.9;
        }
        .analytics-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .analytics-table th,
        .analytics-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .analytics-table th {
            background: #f5f5f5;
            font-weight: bold;
        }
        .analytics-table tr:hover {
            background: #f9f9f9;
        }
    </style>
    <?php
    
    return ob_get_clean();
}

function csc_get_top_viewed_products($limit = 10) {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_csc_views',
                'compare' => 'EXISTS',
            ),
        ),
    );
    
    $products = get_posts($args);
    $views = array();
    
    foreach ($products as $product) {
        $v = get_post_meta($product->ID, '_csc_views', true) ?: 0;
        $views[] = array(
            'product_id' => $product->ID,
            'views' => intval($v),
        );
    }
    
    usort($views, function($a, $b) {
        return $b['views'] - $a['views'];
    });
    
    return array_slice($views, 0, $limit);
}

function csc_get_analytics_stats() {
    $products = wc_get_products(array('limit' => -1, 'return' => 'ids'));
    
    $total_views = 0;
    foreach ($products as $id) {
        $total_views += intval(get_post_meta($id, '_csc_views', true) ?: 0);
    }
    
    return array(
        'total_views' => $total_views,
        'total_products' => count($products),
    );
}

// Tracking script
add_action('wp_footer', 'csc_tracking_script');

function csc_tracking_script() {
    if (is_admin()) return;
    ?>
    <script>
    jQuery(document).ready(function() {
        // Track page view
        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'csc_track_pageview',
                page_url: window.location.href,
                page_title: document.title
            }
        });
        
        // Track product view
        <?php if (is_product()): 
            global $product;
        ?>
        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'csc_track_product_view',
                product_id: <?php echo $product->get_id(); ?>
            }
        });
        <?php endif; ?>
    });
    </script>
    <?php
}
