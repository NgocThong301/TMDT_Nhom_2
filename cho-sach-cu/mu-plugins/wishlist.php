<?php
/**
 * Plugin Name: CSC Wishlist
 * Description: Tính năng danh sách yêu thích cho Chợ Sách Cũ
 * Version: 1.0
 * Author: Chợ Sách Cũ
 */

if (!defined('ABSPATH')) {
    exit;
}

class CSC_Wishlist {
    
    /**
     * Cookie name
     */
    const COOKIE_NAME = 'csc_wishlist';
    
    /**
     * Cookie expiry (30 days)
     */
    const COOKIE_EXPIRY = 30 * 24 * 60 * 60;
    
    /**
     * Constructor
     */
    public function __construct() {
        // AJAX handlers
        add_action('wp_ajax_csc_add_to_wishlist', array($this, 'ajax_add_to_wishlist'));
        add_action('wp_ajax_csc_remove_from_wishlist', array($this, 'ajax_remove_from_wishlist'));
        add_action('wp_ajax_csc_get_wishlist_count', array($this, 'ajax_get_count'));
        // Cho phép khách chưa đăng nhập cũng dùng được wishlist (lưu cookie)
        add_action('wp_ajax_nopriv_csc_add_to_wishlist', array($this, 'ajax_add_to_wishlist'));
        add_action('wp_ajax_nopriv_csc_remove_from_wishlist', array($this, 'ajax_remove_from_wishlist'));
        add_action('wp_ajax_nopriv_csc_get_wishlist_count', array($this, 'ajax_get_count'));
        
        // Shortcode
        add_shortcode('csc_wishlist', array($this, 'shortcode_wishlist'));
        
        // Add to wishlist button
        add_action('woocommerce_after_shop_loop_item', array($this, 'add_wishlist_button'), 20);
        add_action('woocommerce_single_product_summary', array($this, 'add_wishlist_button_single'), 35);
        
        // Enqueue script
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Wishlist count in header
        add_filter('woocommerce_add_to_cart_fragments', array($this, 'cart_fragments'));
    }
    
    /**
     * Get wishlist from cookie
     */
    public function get_wishlist() {
        if (isset($_COOKIE[self::COOKIE_NAME])) {
            $wishlist = json_decode($_COOKIE[self::COOKIE_NAME], true);
            return is_array($wishlist) ? $wishlist : array();
        }
        
        // Check if logged in user has stored wishlist
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $stored = get_user_meta($user_id, 'csc_wishlist', true);
            if ($stored) {
                return is_array($stored) ? $stored : array();
            }
        }
        
        return array();
    }
    
    /**
     * Save wishlist to cookie and user meta
     */
    private function save_wishlist($wishlist) {
        // Save to cookie
        setcookie(self::COOKIE_NAME, json_encode($wishlist), time() + self::COOKIE_EXPIRY, '/');
        $_COOKIE[self::COOKIE_NAME] = json_encode($wishlist);
        
        // Save to user meta if logged in
        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'csc_wishlist', $wishlist);
        }
    }
    
    /**
     * Add product to wishlist
     */
    public function ajax_add_to_wishlist() {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if (!$product_id) {
            wp_send_json_error('Invalid product');
        }
        
        $wishlist = $this->get_wishlist();
        
        if (!in_array($product_id, $wishlist)) {
            $wishlist[] = $product_id;
            $this->save_wishlist($wishlist);
            wp_send_json_success(array(
                'message' => 'Đã thêm vào yêu thích',
                'count' => count($wishlist),
                'added' => true
            ));
        } else {
            wp_send_json_success(array(
                'message' => 'Đã có trong danh sách yêu thích',
                'count' => count($wishlist),
                'added' => false
            ));
        }
    }
    
    /**
     * Remove product from wishlist
     */
    public function ajax_remove_from_wishlist() {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if (!$product_id) {
            wp_send_json_error('Invalid product');
        }
        
        $wishlist = $this->get_wishlist();
        $wishlist = array_diff($wishlist, array($product_id));
        $wishlist = array_values($wishlist); // Re-index
        $this->save_wishlist($wishlist);
        
        wp_send_json_success(array(
            'message' => 'Đã xóa khỏi yêu thích',
            'count' => count($wishlist)
        ));
    }
    
    /**
     * Get wishlist count
     */
    public function ajax_get_count() {
        $wishlist = $this->get_wishlist();
        wp_send_json_success(array('count' => count($wishlist)));
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'csc-wishlist',
            plugins_url('assets/wishlist.css', __FILE__),
            array(),
            '1.0'
        );
        
        wp_enqueue_script(
            'csc-wishlist',
            plugins_url('assets/wishlist.js', __FILE__),
            array('jquery'),
            '1.2',
            true
        );
        
        wp_localize_script('csc-wishlist', 'cscWishlist', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('csc_wishlist_nonce'),
            'textAdded' => 'Đã thêm vào yêu thích',
            'textRemoved' => 'Đã xóa khỏi yêu thích',
            'textViewWishlist' => 'Xem danh sách',
        ));
    }
    
    /**
     * Add wishlist button to product loop
     */
    public function add_wishlist_button() {
        global $product;
        
        $product_id = $product->get_id();
        $wishlist = $this->get_wishlist();
        $is_in_wishlist = in_array($product_id, $wishlist);
        
        echo sprintf(
            '<a href="#" class="csc-wishlist-btn %s" data-product-id="%d" title="%s" aria-label="%s">
                <i class="%s fa-heart"></i>
            </a>',
            $is_in_wishlist ? 'active' : '',
            $product_id,
            $is_in_wishlist ? 'Xóa khỏi yêu thích' : 'Thêm vào yêu thích',
            $is_in_wishlist ? 'Xóa khỏi yêu thích' : 'Thêm vào yêu thích',
            $is_in_wishlist ? 'fas' : 'far'
        );
    }
    
    /**
     * Add wishlist button to single product
     */
    public function add_wishlist_button_single() {
        global $product;
        
        $product_id = $product->get_id();
        $wishlist = $this->get_wishlist();
        $is_in_wishlist = in_array($product_id, $wishlist);
        
        echo sprintf(
            '<button class="csc-wishlist-btn-single csc-wishlist-btn %s" data-product-id="%d" title="%s">
                <i class="%s fa-heart"></i>
                <span>%s</span>
            </button>',
            $is_in_wishlist ? 'active' : '',
            $product_id,
            $is_in_wishlist ? 'Xóa khỏi yêu thích' : 'Thêm vào yêu thích',
            $is_in_wishlist ? 'fas' : 'far',
            $is_in_wishlist ? 'Xóa khỏi yêu thích' : 'Thêm vào yêu thích'
        );
    }
    
    /**
     * Shortcode for wishlist page
     */
    public function shortcode_wishlist($atts) {
        $wishlist = $this->get_wishlist();
        
        if (empty($wishlist)) {
            return '
                <div class="csc-wishlist-empty">
                    <i class="fa fa-heart-o" style="font-size: 48px; color: #ccc;"></i>
                    <h3>Danh sách yêu thích trống</h3>
                    <p>Bạn chưa có sản phẩm nào trong danh sách yêu thích.</p>
                    <a href="' . get_permalink(wc_get_page_id('shop')) . '" class="button">
                        Tiếp tục mua sắm
                    </a>
                </div>
            ';
        }
        
        // Get products
        $args = array(
            'post_type' => 'product',
            'post__in' => $wishlist,
            'posts_per_page' => -1,
        );
        
        $products = new WP_Query($args);
        
        ob_start();
        
        if ($products->have_posts()) {
            echo '<div class="csc-wishlist-container">';
            echo '<h2>Danh sách yêu thích của bạn</h2>';
            echo '<div class="csc-wishlist-products">';
            
            woocommerce_product_loop_start();
            
            while ($products->have_posts()) {
                $products->the_post();
                global $product;
                
                echo '<div class="csc-wishlist-item">';
                woocommerce_template_single_add_to_cart();
                
                echo sprintf(
                    '<button class="csc-wishlist-remove" data-product-id="%d">
                        <i class="fa fa-trash"></i> Xóa
                    </button>',
                    $product->get_id()
                );
                
                woocommerce_template_single_price();
                woocommerce_template_single_title();
                woocommerce_template_single_excerpt();
                
                echo '</div>';
            }
            
            woocommerce_product_loop_end();
            
            echo '</div></div>';
        }
        
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    /**
     * Add count to header fragment
     */
    public function cart_fragments($fragments) {
        $count = count($this->get_wishlist());
        $fragments['.csc-wishlist-count'] = '<span class="csc-wishlist-count">' . $count . '</span>';
        return $fragments;
    }
}

// Initialize
new CSC_Wishlist();
