<?php
/**
 * Chợ Sách Cũ Child Theme Functions
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Enqueue parent styles
add_action('wp_enqueue_scripts', 'chsachcu_child_enqueue_styles', 20);

function chsachcu_child_enqueue_styles() {
    wp_enqueue_style(
        'chsachcu-child-style',
        get_stylesheet_uri(),
        array('storefront-style', 'storefront-icons'),
        wp_get_theme()->get('Version')
    );
}

// Add inline CSS to style navigation menu
add_action('wp_head', function() {
    echo '<style id="csc-show-all-menu">
        /* Show the primary navigation menu (7 essential items) in one row */
        #site-navigation .primary-navigation {
            display: block !important;
            visibility: visible !important;
        }
        #site-navigation .primary-navigation ul.nav-menu {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            gap: 0 18px !important;
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
            visibility: visible !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        #site-navigation .primary-navigation ul.nav-menu > li {
            display: inline-flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            height: auto !important;
        }
        #site-navigation .primary-navigation ul.nav-menu > li > a {
            display: inline-flex !important;
            align-items: center !important;
            visibility: visible !important;
            opacity: 1 !important;
            height: auto !important;
            padding: 8px 0 !important;
        }
        /* Ensure the nav bar itself is not collapsed */
        #site-navigation {
            display: block !important;
            visibility: visible !important;
            height: auto !important;
            overflow: visible !important;
            margin: 0 !important;
        }
    </style>';
}, 1);

// Add Google Fonts
add_action('wp_enqueue_scripts', 'chsachcu_enqueue_fonts', 25);

function chsachcu_enqueue_fonts() {
    wp_enqueue_style(
        'chsachcu-google-fonts',
        'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Nunito:wght@400;500;600;700&display=swap',
        array(),
        null
    );
}

// Custom login logo
add_action('login_enqueue_scripts', 'chsachcu_login_logo');

function chsachcu_login_logo() {
    ?>
    <style type="text/css">
        #login h1 a {
            background-image: none;
            background-size: contain;
            width: 320px;
            height: 80px;
            font-size: 40px;
            font-weight: bold;
            color: #8B4513 !important;
            text-indent: 0;
            line-height: 80px;
        }
        #login h1 a::before {
            content: "📚 ";
        }
        body.login {
            background: linear-gradient(135deg, #F5F0E8 0%, #fff 100%);
        }
    </style>
    <?php
}

// ============================================
// CREATE CUSTOM MENU - Essential Pages Only
// ============================================
add_action('init', 'chsachcu_create_essential_menu');

function chsachcu_create_essential_menu() {
    // Get or create menu
    $menu_name = 'Essential Navigation';
    $menu_exists = wp_get_nav_menu_object($menu_name);
    
    if (!$menu_exists) {
        $menu_id = wp_create_nav_menu($menu_name);
        
        // Essential pages to include
        $essential_pages = array(
            'Cửa hàng' => 'cua-hang',
            'Đăng Sách' => 'dang-sach',
            'Khuyến mãi' => 'khuyen-mai',
            'My Orders' => 'my-orders',
            'So Sánh Sản Phẩm' => 'so-sanh-san-pham',
            'Thanh toán' => 'thanh-toan',
            'Tài khoản của tôi' => 'tai-khoan-cua-toi'
        );
        
        foreach ($essential_pages as $title => $slug) {
            $page = get_page_by_path($slug);
            if ($page) {
                wp_update_nav_menu_item($menu_id, 0, array(
                    'menu-item-title' => $title,
                    'menu-item-object-id' => $page->ID,
                    'menu-item-object' => 'page',
                    'menu-item-type' => 'post_type',
                    'menu-item-status' => 'publish'
                ));
            }
        }
        
        // Assign menu to theme location
        $locations = get_theme_mod('nav_menu_locations');
        $locations['primary'] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);
    }
}

// ============================================
// CUSTOMIZE CART ICON WITH BADGE
// ============================================
add_filter('woocommerce_cart_link', 'chsachcu_custom_cart_link', 10, 2);

function chsachcu_custom_cart_link($link, $cart_has_items) {
    $count = WC()->cart->get_cart_contents_count();
    $badge = $count > 0 ? '<span class="csc-cart-badge">' . $count . '</span>' : '';
    return str_replace('</a>', $badge . '</a>', $link);
}

// Add inline script for cart badge - always active
add_action('wp_footer', function() {
    echo '<script>
    (function() {
        function updateCartBadge() {
            // Find cart icon
            var cartLinks = document.querySelectorAll(".site-header-cart .cart-contents, .storefront-header-cart .cart-contents, a[href*=\"gio-hang\"], a.cart-contents");
            
            // Get count from WooCommerce fragments or calculate
            var count = 0;
            
            // Check mini cart items
            var miniCartItems = document.querySelectorAll(".woocommerce-mini-cart li");
            if (miniCartItems.length > 0) {
                count = miniCartItems.length;
            }
            
            // Check cart count from data attributes
            var countAttr = document.querySelector(".cart-contents[data-count]");
            if (countAttr) {
                count = parseInt(countAttr.dataset.count) || 0;
            }
            
            cartLinks.forEach(function(link) {
                // Remove existing badge
                var existing = link.querySelector(".csc-cart-badge");
                
                if (count > 0) {
                    if (!existing) {
                        existing = document.createElement("span");
                        existing.className = "csc-cart-badge";
                        link.style.position = "relative";
                        link.appendChild(existing);
                    }
                    existing.textContent = count;
                    existing.style.display = "flex";
                } else if (existing) {
                    existing.style.display = "none";
                }
            });
        }
        
        // Run on load and periodically
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", updateCartBadge);
        } else {
            updateCartBadge();
        }
        
        // Watch for cart updates
        var observer = new MutationObserver(function() {
            setTimeout(updateCartBadge, 500);
        });
        
        document.querySelectorAll(".site-header-cart, .storefront-header-cart").forEach(function(el) {
            observer.observe(el, { childList: true, subtree: true });
        });
        
        // Check every 2 seconds
        setInterval(updateCartBadge, 2000);
    })();
    </script>';
}, 100);

// Add JS to force grid layout - remove CSS class that sets columns
add_action('wp_footer', function() {
    if (is_front_page() || is_shop() || is_archive()) {
        echo '<script>
        (function() {
            function fixGrid() {
                var lists = document.querySelectorAll(".woocommerce ul.products");
                lists.forEach(function(list) {
                    // Remove columns-3, columns-4 classes that override our layout
                    list.className = list.className.replace(/columns-\d+/g, "columns-custom").trim();
                    // Force display and columns via inline style
                    list.setAttribute("style", "display: grid !important; grid-template-columns: repeat(4, 1fr) !important; gap: 20px !important; width: 100% !important;");
                    var items = list.querySelectorAll("li.product");
                    items.forEach(function(item) {
                        item.setAttribute("style", "flex: none !important; width: auto !important; max-width: none !important; float: none !important; margin: 0 !important;");
                    });
                });
            }
            // Run immediately and multiple times
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", fixGrid);
            } else {
                fixGrid();
            }
            // Run multiple times with interval
            for(var i = 1; i <= 10; i++) {
                setTimeout(fixGrid, i * 200);
            }
        })();
        </script>';
    }
}, 100);

// Customize WooCommerce product columns
add_filter('loop_shop_columns', 'chsachcu_loop_columns');

function chsachcu_loop_columns() {
    return 4;
}

// Remove default WooCommerce styles we don't need
add_filter('woocommerce_enqueue_styles', '__return_empty_array');

// Remove Storefront default layout restrictions
add_action('wp_head', function() {
    echo '<style id="csc-force-grid">
        /* Force 4 columns for products */
        .woocommerce ul.products { 
            grid-template-columns: repeat(4, 1fr) !important; 
            display: grid !important;
            flex-wrap: wrap !important;
        }
        .woocommerce ul.products li.product { 
            flex: 1 1 calc(25% - 20px) !important;
            min-width: 0 !important;
            max-width: 100% !important;
        }
        @media (max-width: 1400px) { 
            .woocommerce ul.products { grid-template-columns: repeat(4, 1fr) !important; }
        }
        @media (max-width: 1000px) { 
            .woocommerce ul.products { grid-template-columns: repeat(3, 1fr) !important; }
        }
        @media (max-width: 600px) { 
            .woocommerce ul.products { grid-template-columns: repeat(2, 1fr) !important; }
        }
    </style>';
}, 100);

// Add JS to ensure the primary navigation menu is visible
add_action('wp_footer', function() {
    echo '<script>
    (function() {
        function ensureNavVisible() {
            var nav = document.getElementById("site-navigation");
            if (!nav) return;
            
            nav.style.display = "block";
            nav.style.visibility = "visible";
            nav.style.height = "auto";
            nav.style.overflow = "visible";
            
            var primary = nav.querySelector(".primary-navigation");
            if (primary) {
                primary.style.display = "block";
                primary.style.visibility = "visible";
            }
            
            var menu = nav.querySelector(".primary-navigation ul.nav-menu");
            if (menu) {
                menu.style.display = "flex";
                menu.style.flexWrap = "wrap";
                menu.style.height = "auto";
                menu.style.visibility = "visible";
                
                var items = menu.querySelectorAll(":scope > li");
                items.forEach(function(li) {
                    li.style.display = "inline-flex";
                    li.style.visibility = "visible";
                    li.style.opacity = "1";
                    li.style.height = "auto";
                });
            }
        }
        
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", ensureNavVisible);
        } else {
            ensureNavVisible();
        }
        setTimeout(ensureNavVisible, 500);
        setTimeout(ensureNavVisible, 1500);
    })();
    </script>';
}, 100);

// Add JS to force grid layout AND wrap buttons in container
add_action('wp_footer', function() {
    if (is_front_page() || is_shop() || is_archive()) {
        echo '<script>
        (function() {
            function fixAll() {
                // Fix grid layout
                var lists = document.querySelectorAll(".woocommerce ul.products");
                lists.forEach(function(list) {
                    list.className = list.className.replace(/columns-\d+/g, "columns-custom").trim();
                    list.setAttribute("style", "display: grid !important; grid-template-columns: repeat(4, 1fr) !important; gap: 20px !important; width: 100% !important;");
                    var items = list.querySelectorAll("li.product");
                    items.forEach(function(item) {
                        item.setAttribute("style", "flex: none !important; width: auto !important; max-width: none !important; float: none !important; margin: 0 !important;");
                    });
                });
                
                // Wrap compare + wishlist buttons in container
                var products = document.querySelectorAll("li.product");
                products.forEach(function(product) {
                    var compareBtn = product.querySelector("button.csc-add-compare");
                    var wishlistBtn = product.querySelector("a.csc-wishlist-btn");
                    
                    if (compareBtn && wishlistBtn) {
                        var container = product.querySelector(".csc-product-actions");
                        if (!container) {
                            container = document.createElement("div");
                            container.className = "csc-product-actions";
                            compareBtn.parentNode.insertBefore(container, compareBtn);
                            container.appendChild(compareBtn);
                            container.appendChild(wishlistBtn);
                        }
                    }
                });
            }
            
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", fixAll);
            } else {
                fixAll();
            }
            for(var i = 1; i <= 10; i++) {
                setTimeout(fixAll, i * 200);
            }
        })();
        </script>';
    }
}, 100);

// Add body classes
add_filter('body_class', 'chsachcu_body_classes');

function chsachcu_body_classes($classes) {
    $classes[] = 'chsachcu-theme';
    return $classes;
}

// Customize read more link
add_filter('the_content_more_link', 'chsachcu_read_more_link');

function chsachcu_read_more_link() {
    return '<a class="more-link" href="' . get_permalink() . '">Đọc tiếp →</a>';
}

// Remove default Storefront handheld footer bar
remove_action('storefront_footer', 'storefront_handheld_footer_bar', 999);
