<?php
// Simulate successful VNPay callback
require_once '/var/www/html/wp-load.php';

echo "Simulating VNPay callback...\n\n";

// Order ID
$order_id = 66;
$order = wc_get_order($order_id);

if (!$order) {
    echo "Order not found!\n";
    exit(1);
}

echo "Order #{$order_id}:\n";
echo "- Status: " . $order->get_status() . "\n";
echo "- Total: " . wc_price($order->get_total()) . "\n\n";

// Simulate successful payment
echo "Processing successful payment...\n";

// Update order status
$order->update_status('completed', __('Payment completed via VNPay (simulated)', 'vnpay-gateway'));
$order->payment_complete();

// Store transaction info
update_post_meta($order_id, '_vnpay_response_code', '00');
update_post_meta($order_id, '_vnpay_transaction_id', '12345678');
update_post_meta($order_id, '_vnpay_txnref', '66_1792511649');

echo "✅ Payment simulation successful!\n\n";

echo "Updated Order #{$order_id}:\n";
echo "- Status: " . $order->get_status() . "\n";
echo "- Transaction ID: " . get_post_meta($order_id, '_vnpay_transaction_id', true) . "\n";
echo "- Response Code: " . get_post_meta($order_id, '_vnpay_response_code', true) . "\n";
