<?php
// Create a test order
require_once '/var/www/html/wp-load.php';

echo "Creating test order...\n";

// Get a product
$product_id = 50;
$product = wc_get_product($product_id);

if (!$product) {
    echo "Product not found\n";
    exit(1);
}

// Create order
$order = wc_create_order();
$order->add_product($product, 1);

// Set billing address
$order->set_billing_first_name('Test');
$order->set_billing_last_name('User');
$order->set_billing_email('test@example.com');
$order->set_billing_phone('0123456789');
$order->set_billing_address_1('123 Test Street');
$order->set_billing_city('Hanoi');

// Set payment method to VNPay
$order->set_payment_method('vnpay');
$order->set_payment_method_title('Thanh toán qua VNPay');

// Calculate totals
$order->calculate_totals();
$order->save();

echo "Order created successfully!\n";
echo "Order ID: " . $order->get_id() . "\n";
echo "Total: " . wc_price($order->get_total()) . "\n";
echo "Status: " . $order->get_status() . "\n";
echo "Payment Method: " . $order->get_payment_method_title() . "\n";

// Get checkout URL for VNPay
$checkout_url = $order->get_checkout_payment_url();
echo "\nCheckout URL: " . $checkout_url . "\n";

// Store order ID for reference
file_put_contents('/tmp/test_order_id.txt', $order->get_id());
echo "\nOrder ID saved to /tmp/test_order_id.txt\n";
