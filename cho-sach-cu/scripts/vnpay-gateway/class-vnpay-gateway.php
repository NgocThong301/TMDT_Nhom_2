<?php
/**
 * VNPay Gateway Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_VNPay_Gateway extends WC_Payment_Gateway {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'vnpay';
        $this->icon = apply_filters('woocommerce_vnpay_icon', VNPAY_PLUGIN_URL . 'assets/vnpay-logo.png');
        $this->has_fields = false;
        $this->method_title = 'VNPay';
        $this->method_description = 'Thanh toán qua cổng VNPay - Hỗ trợ QR Code, ATM, Visa';
        
        // Define supported features
        $this->supports = array(
            'products',
            'pay_for_order',
            'gateway_scheduled_time',
        );
        
        // Load settings
        $this->init_form_fields();
        $this->init_settings();
        
        // Define user set variables
        $this->title = $this->get_option('title', 'Thanh toán qua VNPay');
        $this->description = $this->get_option('description', 'Thanh toán an toàn qua cổng VNPay');
        $this->vnp_TmnCode = $this->get_option('vnp_TmnCode');
        $this->vnp_HashSecret = $this->get_option('vnp_HashSecret');
        $this->vnp_Url = $this->get_option('vnp_Url', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
        $this->vnp_ReturnUrl = $this->get_option('vnp_ReturnUrl', home_url('/?wc-api=wc_vnpay_return'));
        
        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        add_action('woocommerce_api_wc_vnpay_return', array($this, 'handle_vnpay_callback'));
    }

    /**
     * Initialize Gateway Settings Form Fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'type' => 'checkbox',
                'label' => 'Kích hoạt cổng thanh toán VNPay',
                'default' => 'yes'
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => 'Tiêu đề hiển thị cho khách hàng',
                'default' => 'Thanh toán qua VNPay',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'Mô tả phương thức thanh toán',
                'default' => 'Thanh toán an toàn qua cổng VNPay với QR Code, ATM, Visa',
                'desc_tip' => true,
            ),
            'vnp_TmnCode' => array(
                'title' => 'Terminal ID (vnp_TmnCode)',
                'type' => 'text',
                'description' => 'Mã Terminal ID từ VNPay Sandbox',
                'default' => getenv('VNPAY_TMN_CODE') ?: '',
                'desc_tip' => true,
            ),
            'vnp_HashSecret' => array(
                'title' => 'Secret Key (vnp_HashSecret)',
                'type' => 'password',
                'description' => 'Chuỗi bí mật tạo checksum từ VNPay Sandbox',
                'default' => getenv('VNPAY_HASH_SECRET') ?: '',
                'desc_tip' => true,
            ),
            'vnp_Url' => array(
                'title' => 'Payment URL (vnp_Url)',
                'type' => 'text',
                'description' => 'URL thanh toán môi trường TEST',
                'default' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
                'desc_tip' => true,
            ),
            'vnp_ReturnUrl' => array(
                'title' => 'Return URL (vnp_ReturnUrl)',
                'type' => 'text',
                'description' => 'URL callback sau khi thanh toán',
                'default' => '',
                'desc_tip' => true,
            ),
        );
    }

    /**
     * Admin Panel Options
     */
    public function admin_options() {
        echo '<h3>' . esc_html__('VNPay Payment Gateway', 'vnpay-gateway') . '</h3>';
        echo '<p>' . esc_html__('Cấu hình cổng thanh toán VNPay cho WooCommerce', 'vnpay-gateway') . '</p>';
        
        // Display test card info
        echo '<div style="background: #f0f0f1; padding: 15px; border-radius: 5px; margin: 15px 0;">';
        echo '<h4>Thông tin test:</h4>';
        echo '<p><strong>Ngân hàng:</strong> NCB</p>';
        echo '<p><strong>Số thẻ:</strong> 9704198526191432198</p>';
        echo '<p><strong>Tên chủ thẻ:</strong> NGUYEN VAN A</p>';
        echo '<p><strong>Ngày phát hành:</strong> 07/15</p>';
        echo '<p><strong>Mật khẩu OTP:</strong> 123456</p>';
        echo '</div>';
        
        parent::admin_options();
    }

    /**
     * Process the payment
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return array(
                'result' => 'fail',
                'redirect' => '',
            );
        }
        
        // Store order status
        $order->update_status('pending', __('Awaiting VNPay payment', 'vnpay-gateway'));
        
        // Build payment URL
        $payment_url = $this->build_payment_url($order);
        
        // Empty cart
        WC()->cart->empty_cart();
        
        // Return redirect
        return array(
            'result' => 'success',
            'redirect' => $payment_url,
        );
    }

    /**
     * Build VNPay payment URL
     */
    private function build_payment_url($order) {
        global $woocommerce;
        
        $vnp_Url = $this->vnp_Url;
        $vnp_Returnurl = $this->vnp_ReturnUrl ?: home_url('/?wc-api=wc_vnpay_return');
        
        // Build input data
        $vnp_TxnRef = $order->get_id() . '_' . time(); // Order ID + timestamp
        $vnp_OrderInfo = 'Thanh toan don hang #' . $order->get_id();
        $vnp_Amount = floatval($order->get_total()) * 100; // Convert to VND cents
        $vnp_IpAddr = $this->get_client_ip();
        $vnp_CreateDate = date('YmdHis');
        $vnp_Locale = 'vn';
        $vnp_CurrCode = 'VND';
        $vnp_BankCode = '';
        
        $inputData = array(
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $this->vnp_TmnCode,
            'vnp_Amount' => $vnp_Amount,
            'vnp_CurrCode' => $vnp_CurrCode,
            'vnp_TxnRef' => $vnp_TxnRef,
            'vnp_OrderInfo' => $vnp_OrderInfo,
            'vnp_OrderType' => 'other',
            'vnp_Locale' => $vnp_Locale,
            'vnp_CreateDate' => $vnp_CreateDate,
            'vnp_IpAddr' => $vnp_IpAddr,
            'vnp_ReturnUrl' => $vnp_Returnurl,
        );
        
        // Build secure hash
        ksort($inputData);
        $hashdata = '';
        $query = '';
        $i = 0;
        
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . '=' . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . '=' . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . '=' . urlencode($value) . '&';
        }
        
        $vnp_SecureHash = hash_hmac('sha512', $hashdata, $this->vnp_HashSecret);
        
        // Build final URL
        $payment_url = $vnp_Url . '?' . $query . 'vnp_SecureHash=' . $vnp_SecureHash;
        
        // Store order ID mapping for callback
        update_post_meta($order->get_id(), '_vnpay_txnref', $vnp_TxnRef);
        
        return $payment_url;
    }

    /**
     * Handle VNPay callback/return
     */
    public function handle_vnpay_callback() {
        // Get all GET parameters
        $inputData = $_GET;
        
        // Extract secure hash
        $vnp_SecureHash = isset($inputData['vnp_SecureHash']) ? $inputData['vnp_SecureHash'] : '';
        unset($inputData['vnp_SecureHash']);
        unset($inputData['wc-api']);
        unset($inputData['_wpnonce']);
        unset($inputData['_wp_http_referer']);
        
        // Sort and build hash data
        ksort($inputData);
        $hashdata = '';
        $i = 0;
        
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . '=' . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . '=' . urlencode($value);
                $i = 1;
            }
        }
        
        // Verify secure hash
        $secureHash = hash_hmac('sha512', $hashdata, $this->vnp_HashSecret);
        
        // Get response code
        $vnp_ResponseCode = isset($inputData['vnp_ResponseCode']) ? $inputData['vnp_ResponseCode'] : '';
        $vnp_TxnRef = isset($inputData['vnp_TxnRef']) ? $inputData['vnp_TxnRef'] : '';
        
        // Extract order ID from TxnRef
        $order_id = 0;
        if (!empty($vnp_TxnRef)) {
            $parts = explode('_', $vnp_TxnRef);
            $order_id = isset($parts[0]) ? intval($parts[0]) : 0;
        }
        
        if ($secureHash == $vnp_SecureHash) {
            if ($vnp_ResponseCode == '00') {
                // Success
                if ($order_id > 0) {
                    $order = wc_get_order($order_id);
                    if ($order) {
                        $order->update_status('completed', __('Payment completed via VNPay', 'vnpay-gateway'));
                        $order->payment_complete();
                        
                        // Store VNPay transaction info
                        update_post_meta($order_id, '_vnpay_transaction_id', isset($inputData['vnp_TransactionNo']) ? $inputData['vnp_TransactionNo'] : '');
                        update_post_meta($order_id, '_vnpay_response_code', $vnp_ResponseCode);
                    }
                }
                
                $redirect_url = $this->get_return_url($order);
            } else {
                // Failed
                if ($order_id > 0) {
                    $order = wc_get_order($order_id);
                    if ($order) {
                        $order->update_status('failed', sprintf(__('VNPay payment failed with code: %s', 'vnpay-gateway'), $vnp_ResponseCode));
                    }
                }
                
                $redirect_url = wc_get_checkout_url();
                wc_add_notice(__('Thanh toán VNPay không thành công. Mã lỗi: ', 'vnpay-gateway') . $vnp_ResponseCode, 'error');
            }
        } else {
            // Invalid signature
            if ($order_id > 0) {
                $order = wc_get_order($order_id);
                if ($order) {
                    $order->update_status('failed', __('Invalid VNPay signature', 'vnpay-gateway'));
                }
            }
            
            $redirect_url = wc_get_checkout_url();
            wc_add_notice(__('Xác thực thanh toán VNPay thất bại.', 'vnpay-gateway'), 'error');
        }
        
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Thankyou page
     */
    public function thankyou_page($order_id) {
        $order = wc_get_order($order_id);
        
        if ($order && $order->get_payment_method() == $this->id) {
            echo '<p style="color: green;">Cảm ơn bạn đã thanh toán qua VNPay. Đơn hàng đang được xử lý.</p>';
        }
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ipaddress = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP']) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED']) && $_SERVER['HTTP_X_FORWARDED']) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR']) && $_SERVER['HTTP_FORWARDED_FOR']) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED']) && $_SERVER['HTTP_FORWARDED']) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR']) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = '127.0.0.1';
        }
        
        return $ipaddress;
    }
}
