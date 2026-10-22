<?php
/**
 * Plugin Name: CSC Product Suggestions
 * Description: Gợi ý sản phẩm cho Chợ Sách Cũ
 */

if (!defined('ABSPATH')) exit;

// Shortcode gợi ý sản phẩm liên quan
add_shortcode('csc_product_suggestions', 'csc_product_suggestions_shortcode');

function csc_product_suggestions_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 8,
        'title' => '📚 Sách bạn có thể thích',
    ), $atts);
    
    // Lấy sản phẩm đang xem
    global $product;
    
    if (!is_a($product, 'WC_Product')) {
        return '';
    }
    
    $product_id = $product->get_id();
    $categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
    $tags = wp_get_post_terms($product_id, 'product_tag', array('fields' => 'ids'));
    
    // Query sản phẩm liên quan
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => intval($atts['limit']),
        'post__not_in' => array($product_id),
        'post_status' => 'publish',
        'orderby' => 'rand',
    );
    
    // Ưu tiên cùng danh mục
    if (!empty($categories)) {
        $args['tax_query'] = array(
            'relation' => 'OR',
            array(
                'taxonomy' => 'product_cat',
                'field' => 'id',
                'terms' => $categories,
            ),
        );
    }
    
    $products = new WP_Query($args);
    
    if (empty($products->posts)) {
        return '';
    }
    
    ob_start();
    ?>
    <section class="csc-product-suggestions">
        <h3 class="suggestions-title"><?php echo esc_html($atts['title']); ?></h3>
        <div class="suggestions-grid">
            <?php foreach ($products->posts as $post): 
                $product = wc_get_product($post->ID);
                if (!$product) continue;
            ?>
                <div class="suggestion-item">
                    <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="suggestion-link">
                        <div class="suggestion-image">
                            <?php echo $product->get_image('woocommerce_thumbnail'); ?>
                        </div>
                        <h4 class="suggestion-name"><?php echo esc_html($product->get_name()); ?></h4>
                        <div class="suggestion-price">
                            <?php echo $product->get_price_html(); ?>
                        </div>
                    </a>
                    <button class="add-to-cart-btn" data-product-id="<?php echo esc_attr($post->ID); ?>">
                        Thêm vào giỏ
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <style>
        .csc-product-suggestions {
            margin: 40px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 12px;
        }
        .suggestions-title {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0073aa;
        }
        .suggestions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
        }
        .suggestion-item {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .suggestion-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .suggestion-link {
            display: block;
            text-decoration: none;
            color: inherit;
        }
        .suggestion-image {
            aspect-ratio: 1;
            overflow: hidden;
        }
        .suggestion-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .suggestion-name {
            font-size: 14px;
            color: #333;
            padding: 10px 10px 5px;
            margin: 0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .suggestion-price {
            padding: 5px 10px 10px;
            color: #e2401c;
            font-weight: bold;
        }
        .add-to-cart-btn {
            width: 100%;
            padding: 10px;
            background: #0073aa;
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.2s;
        }
        .add-to-cart-btn:hover {
            background: #005177;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        $('.add-to-cart-btn').click(function(e) {
            e.preventDefault();
            var productId = $(this).data('product-id');
            var $btn = $(this);
            
            $btn.text('Đang thêm...').prop('disabled', true);
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'csc_add_to_cart',
                    product_id: productId
                },
                success: function(response) {
                    if (response.success) {
                        $btn.text('Đã thêm ✓');
                        $(document.body).trigger('added_to_cart');
                    } else {
                        $btn.text('Thử lại').prop('disabled', false);
                    }
                },
                error: function() {
                    $btn.text('Thử lại').prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
    
    return ob_get_clean();
}

// AJAX handler
add_action('wp_ajax_csc_add_to_cart', 'csc_ajax_add_to_cart');
add_action('wp_ajax_nopriv_csc_add_to_cart', 'csc_ajax_add_to_cart');

function csc_ajax_add_to_cart() {
    if (!isset($_POST['product_id'])) {
        wp_send_json_error();
    }
    
    $product_id = intval($_POST['product_id']);
    WC()->cart->add_to_cart($product_id);
    
    wp_send_json_success();
}

// Hook vào single product
add_action('woocommerce_after_main_content', 'csc_add_product_suggestions', 20);

function csc_add_product_suggestions() {
    if (!is_product()) return;
    echo do_shortcode('[csc_product_suggestions limit=8]');
}
