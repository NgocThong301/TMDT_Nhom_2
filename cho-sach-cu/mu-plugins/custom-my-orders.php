<?php
/**
 * Plugin Name: Custom My Orders
 * Plugin URI: https://example.com/custom-my-orders
 * Description: Shortcode hiển thị đơn hàng riêng của mỗi tài khoản
 * Version: 1.0
 * Author: Developer
 * License: GPL v2 or later
 */

/**
 * Custom My Orders Shortcode
 * Chỉ hiển thị đơn hàng của tài khoản đang đăng nhập
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('custom-my-orders', 'custom_my_orders_shortcode');

function custom_my_orders_shortcode($atts = array(), $content = '') {
    // Kiểm tra đăng nhập
    if (!is_user_logged_in()) {
        return '<div class="woocommerce-message">Vui lòng <a href="' . esc_url(wp_login_url()) . '">đăng nhập</a> để xem đơn hàng của bạn.</div>';
    }

    // Kiểm tra WooCommerce có được load chưa
    if (!class_exists('WooCommerce')) {
        return '<div class="woocommerce-error">WooCommerce chưa được kích hoạt.</div>';
    }

    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;

    // Lấy tất cả đơn hàng của user hiện tại
    // Sử dụng wc_get_orders nếu có, nếu không dùng direct query
    if (function_exists('wc_get_orders')) {
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
    } else {
        // Fallback: Direct query nếu WooCommerce chưa load
        $orders = array();
    }

    ob_start();
    ?>
    <div class="woocommerce-my-orders-container">
        <h2><?php echo esc_html__('My Orders', 'woocommerce'); ?></h2>
        
        <?php if (empty($orders)) : ?>
            <p class="woocommerce-no-orders-found">
                <?php esc_html_e('Bạn chưa có đơn hàng nào.', 'woocommerce'); ?>
            </p>
        <?php else : ?>
            <table class="shop_table shop_table_responsive my_account_orders">
                <thead>
                    <tr>
                        <th class="order-number"><?php esc_html_e('Order', 'woocommerce'); ?></th>
                        <th class="order-date"><?php esc_html_e('Date', 'woocommerce'); ?></th>
                        <th class="order-status"><?php esc_html_e('Status', 'woocommerce'); ?></th>
                        <th class="order-total"><?php esc_html_e('Total', 'woocommerce'); ?></th>
                        <th class="order-actions"><?php esc_html_e('Actions', 'woocommerce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order) : 
                        $order_id = $order->get_id();
                        $order_data = $order->get_data();
                        $status = $order->get_status();
                        $status_name = $order->get_status();
                        if (function_exists('wc_get_order_status_name')) {
                            $status_name = wc_get_order_status_name($status);
                        }
                    ?>
                        <tr class="order">
                            <td class="order-number" data-title="<?php esc_attr_e('Order', 'woocommerce'); ?>">
                                <a href="<?php echo esc_url($order->get_view_order_url()); ?>">
                                    #<?php echo esc_html($order->get_order_number()); ?>
                                </a>
                            </td>
                            <td class="order-date" data-title="<?php esc_attr_e('Date', 'woocommerce'); ?>">
                                <time datetime="<?php echo esc_attr($order->get_date_created()->format('c')); ?>">
                                    <?php echo esc_html(wc_format_datetime($order->get_date_created())); ?>
                                </time>
                            </td>
                            <td class="order-status" data-title="<?php esc_attr_e('Status', 'woocommerce'); ?>">
                                <span class="woocommerce-label woocommerce-order-label <?php echo esc_attr(sanitize_html_class('status-' . $status)); ?>">
                                    <?php echo esc_html($status_name); ?>
                                </span>
                            </td>
                            <td class="order-total" data-title="<?php esc_attr_e('Total', 'woocommerce'); ?>">
                                <?php echo wp_kses_post($order->get_formatted_order_total()); ?>
                            </td>
                            <td class="order-actions" data-title="<?php esc_attr_e('Actions', 'woocommerce'); ?>">
                                <a href="<?php echo esc_url($order->get_view_order_url()); ?>" class="woocommerce-button button view">
                                    <?php esc_html_e('View', 'woocommerce'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <style>
        .woocommerce-my-orders-container {
            margin: 20px 0;
        }
        .woocommerce-my-orders-container h2 {
            margin-bottom: 20px;
            font-size: 24px;
        }
        .woocommerce-my-orders-container table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .woocommerce-my-orders-container th {
            background: #f8f8f8;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        .woocommerce-my-orders-container td {
            padding: 15px 12px;
            border-bottom: 1px solid #eee;
        }
        .woocommerce-my-orders-container tr:hover {
            background: #fafafa;
        }
        .woocommerce-my-orders-container .order-number a {
            font-weight: 600;
            color: #0073aa;
            text-decoration: none;
        }
        .woocommerce-my-orders-container .order-number a:hover {
            text-decoration: underline;
        }
        .woocommerce-my-orders-container .order-actions .button {
            display: inline-block;
            padding: 6px 12px;
            background: #0073aa;
            color: #fff !important;
            text-decoration: none;
            border-radius: 3px;
            font-size: 13px;
            margin-right: 5px;
        }
        .woocommerce-my-orders-container .order-actions .button:hover {
            background: #005177;
        }
        .woocommerce-order-label {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 500;
        }
        .woocommerce-order-label.status-completed { background: #d4edda; color: #155724; }
        .woocommerce-order-label.status-processing { background: #cce5ff; color: #004085; }
        .woocommerce-order-label.status-pending { background: #fff3cd; color: #856404; }
        .woocommerce-order-label.status-on-hold { background: #e2e3e5; color: #383d41; }
        .woocommerce-order-label.status-cancelled { background: #f8d7da; color: #721c24; }
        .woocommerce-order-label.status-refunded { background: #f8d7da; color: #721c24; }
        .woocommerce-order-label.status-failed { background: #f8d7da; color: #721c24; }

        @media screen and (max-width: 600px) {
            .woocommerce-my-orders-container table,
            .woocommerce-my-orders-container thead,
            .woocommerce-my-orders-container tbody,
            .woocommerce-my-orders-container th,
            .woocommerce-my-orders-container td,
            .woocommerce-my-orders-container tr {
                display: block;
            }
            .woocommerce-my-orders-container thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            .woocommerce-my-orders-container tr {
                border: 1px solid #ccc;
                margin-bottom: 15px;
            }
            .woocommerce-my-orders-container td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 50%;
            }
            .woocommerce-my-orders-container td:before {
                position: absolute;
                top: 15px;
                left: 10px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: 600;
                content: attr(data-title);
            }
        }
    </style>
    <?php
    
    return ob_get_clean();
}
