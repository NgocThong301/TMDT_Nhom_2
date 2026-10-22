<?php
/**
 * Plugin Name: CSC Admin Dashboard
 * Description: Dashboard quan tri cho Cho Sach Cu - Phuong an B: WooCommerce + An Dokan
 * Version: 2.0
 */

if (!defined('ABSPATH')) { exit; }

// An menu Dokan cho Admin - backend
add_action('admin_menu', 'csc_hide_dokan_menu', 99);

function csc_hide_dokan_menu() {
    if (current_user_can('manage_options')) {
        remove_menu_page('dokan');
        remove_menu_page('edit.php');
        remove_menu_page('edit-comments.php');
        remove_menu_page('tools.php');
    }
}

// An menu Vendor Onboarding, Store List, Kenh nguoi ban o frontend cho TAT CA users
add_filter('wp_nav_menu_items', 'csc_hide_vendor_menu_frontend', 10, 2);

function csc_hide_vendor_menu_frontend($items, $args) {
    if (empty($items)) {
        return $items;
    }

    // Danh sach slug can an
    $hide_slugs = array(
        'vendor-onboarding',
        'store-listing',
        'kenh-nguoi-ban',
    );

    // Dung DOM parsing thay vi regex de tranh xoa nham menu items
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8"><div id="csc-menu-wrapper">' . $items . '</div>');
    libxml_clear_errors();

    $wrapper = $dom->getElementById('csc-menu-wrapper');
    if (!$wrapper) {
        return $items;
    }

    foreach (iterator_to_array($wrapper->getElementsByTagName('li')) as $li) {
        $link = null;
        foreach ($li->getElementsByTagName('a') as $a) {
            $link = $a;
            break;
        }
        if (!$link) {
            continue;
        }

        $href = (string) $link->getAttribute('href');
        $text = trim(preg_replace('/\s+/u', ' ', $link->textContent));

        // An cac menu thua theo slug
        $should_hide = false;
        foreach ($hide_slugs as $slug) {
            if (strpos($href, $slug) !== false) {
                $should_hide = true;
                break;
            }
        }

        // An menu "Tai khoan" (trang thua) - giu lai "Tai khoan cua toi"
        if (!$should_hide && $text === 'Tài khoản') {
            $should_hide = true;
        }

        if ($should_hide) {
            $li->parentNode->removeChild($li);
        }
    }

    $result = '';
    foreach ($wrapper->childNodes as $child) {
        $result .= $dom->saveHTML($child);
    }

    return $result;
}

// An menu bang CSS cho TAT CA users
add_action('wp_head', 'csc_hide_vendor_menu_css');
add_action('wp_footer', 'csc_hide_vendor_menu_css_footer', 99);

function csc_hide_vendor_menu_css() {
    echo '<style id="csc-hide-vendor-menu">
        /* An menu Vendor Onboarding, Store List, Kenh nguoi ban, Tai khoan (trang thua) cho tat ca users */
        a[href*="vendor-onboarding"],
        a[href*="store-list"],
        a[href*="kenh-nguoi-ban"],
        a[href*="dokan-dashboard"],
        a[href*="/kenh-nguoi-ban/"],
        .menu-item a[href*="dokan"],
        #site-navigation a[href*="vendor-onboarding"],
        #site-navigation a[href*="store-list"],
        #site-navigation a[href*="kenh-nguoi-ban"],
        .nav.navbar-nav a[href*="vendor-onboarding"],
        .nav.navbar-nav a[href*="store-list"],
        .nav.navbar-nav a[href*="kenh-nguoi-ban"],
        a:has-text("Vendor Onboarding"),
        a:has-text("Store List"),
        li:has(> a:contains("Vendor Onboarding")),
        li:has(> a:contains("Store List")),
        li:has(> a:contains("Kênh người bán")) {
            display: none !important;
            visibility: hidden !important;
        }
        /* An menu "Tài khoản" (trang thừa) - chi an "Tài khoản" khong an "Tài khoản của tôi" */
        .nav.navbar-nav li:has(> a:contains("Tài khoản")):not(:has(> a:contains("của tôi"))),
        .menu li:has(> a:contains("Tài khoản")):not(:has(> a:contains("của tôi"))),
        #site-navigation li:has(> a:contains("Tài khoản")):not(:has(> a:contains("của tôi"))) {
            display: none !important;
            visibility: hidden !important;
        }
    </style>';
}

function csc_hide_vendor_menu_css_footer() {
    echo '<script id="csc-hide-vendor-menu-js">
        document.addEventListener("DOMContentLoaded", function() {
            // An menu bang JS (backup cho tat ca)
            var selectors = [
                "a[href*=\'vendor-onboarding\']",
                "a[href*=\'store-list\']",
                "a[href*=\'kenh-nguoi-ban\']",
                "a[href*=\'dokan-dashboard\']"
            ];

            // An menu Vendor/Store
            selectors.forEach(function(sel) {
                document.querySelectorAll(sel).forEach(function(el) {
                    var li = el.closest("li") || el.closest(".menu-item");
                    if (li) li.style.display = "none";
                });
            });

            // An menu "Tài khoản" nhung giu "Tài khoản của tôi"
            document.querySelectorAll("li.menu-item, li.page_item, nav li").forEach(function(li) {
                var link = li.querySelector("a");
                if (link) {
                    var text = link.textContent.trim();
                    // An neu la "Tài khoản" nhung KHONG phai "Tài khoản của tôi"
                    if (text === "Tài khoản") {
                        li.style.display = "none";
                    }
                }
            });

            // An menu "Dashboard" cho customer (non-admin)
            var isAdmin = document.body.classList.contains("logged-in") && document.body.classList.contains("admin-bar");
            if (!isAdmin) {
                document.querySelectorAll("li.menu-item, li.page_item, nav li").forEach(function(li) {
                    var link = li.querySelector("a");
                    if (link && link.textContent.trim() === "Dashboard") {
                        li.style.display = "none";
                    }
                });
            }
        });
    </script>';
}

// Chuyen huong trang vendor-onboarding ve trang chu
add_action('template_redirect', 'csc_redirect_vendor_onboarding');

function csc_redirect_vendor_onboarding() {
    if (is_page('vendor-onboarding')) {
        wp_redirect(home_url());
        exit;
    }
}

// Loc Dokan nav menu cho admin
add_filter('dokan_get_dashboard_nav', 'csc_hide_dokan_nav_for_admin', 10);

function csc_hide_dokan_nav_for_admin($nav) {
    if (current_user_can('manage_options')) {
        return array(); // Tra ve mang rong cho admin
    }
    return $nav;
}

// An sidebar menu Dokan cho admin
add_action('wp', 'csc_disable_dokan_sidebar', 1);

function csc_disable_dokan_sidebar() {
    if (current_user_can('manage_options') && is_page('kenh-nguoi-ban')) {
        // Chuyen huong admin ve trang dashboard cua minh
        // wp_redirect(admin_url('admin.php?page=csc-dashboard'));
        // exit;
    }
}

// Menu chinh
add_action('admin_menu', 'csc_admin_menu');

function csc_admin_menu() {
    add_menu_page('TD Tong quan', 'C2C Admin', 'manage_options', 'csc-dashboard', 'csc_dashboard_page', 'dashicons-dashboard', 1);
    add_submenu_page('csc-dashboard', 'Dashboard', 'Tong quan', 'manage_options', 'csc-dashboard', 'csc_dashboard_page');
    add_submenu_page('csc-dashboard', 'QL Sach', 'QL Sach', 'manage_options', 'csc-books', 'csc_books_page');
    add_submenu_page('csc-dashboard', 'QL Tai khoan', 'QL Tai khoan', 'manage_options', 'csc-users', 'csc_users_page');
    add_submenu_page('csc-dashboard', 'QL Don hang', 'QL Don hang', 'manage_options', 'csc-orders', 'csc_orders_page');
}

// Dashboard
function csc_dashboard_page() {
    $total_books = wp_count_posts('product')->publish;
    $pending_books = wp_count_posts('product')->pending;
    $total_orders = wp_count_posts('shop_order')->publish;
    $total_vendors = count(get_users(array('role__in' => array('seller', 'vendor'))));
    $total_revenue = 0;
    $orders = wc_get_orders(array('limit' => -1, 'status' => array('completed', 'processing')));
    foreach ($orders as $o) { $total_revenue += $o->get_total(); }
    $recent_books = get_posts(array('post_type' => 'product', 'posts_per_page' => 5, 'orderby' => 'date', 'order' => 'DESC'));
    $recent_orders = wc_get_orders(array('limit' => 5, 'orderby' => 'date', 'order' => 'DESC'));
?>
<style>
.csc-admin { max-width:1400px; }
.csc-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
.stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:30px; }
.stat-card { background:#fff; padding:25px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.08); text-align:center; }
.stat-card:hover { transform:translateY(-3px); }
.stat-icon { font-size:40px; margin-bottom:10px; }
.stat-number { font-size:36px; font-weight:bold; display:block; }
.stat-label { color:#666; font-size:14px; margin-top:5px; }
.stat-card.blue .stat-number { color:#0073aa; }
.stat-card.green .stat-number { color:#2ecc71; }
.stat-card.orange .stat-number { color:#f39c12; }
.stat-card.purple .stat-number { color:#9b59b6; }
.stat-card.teal .stat-number { color:#1abc9c; }
.dashboard-section { background:#fff; border-radius:12px; padding:25px; margin-bottom:25px; box-shadow:0 2px 10px rgba(0,0,0,0.08); }
.dashboard-section h2 { margin-top:0; padding-bottom:15px; border-bottom:2px solid #f1f1f1; }
.section-grid { display:grid; grid-template-columns:1fr 1fr; gap:25px; }
.mini-table { width:100%; border-collapse:collapse; }
.mini-table th, .mini-table td { padding:12px; text-align:left; border-bottom:1px solid #eee; }
.mini-table th { background:#f8f9fa; font-weight:600; }
.mini-table tr:hover { background:#f8f9fa; }
.badge { display:inline-block; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:500; }
.badge-success { background:#d4edda; color:#155724; }
.badge-warning { background:#fff3cd; color:#856404; }
.badge-info { background:#cce5ff; color:#004085; }
.quick-links { display:grid; grid-template-columns:repeat(4, 1fr); gap:15px; margin-top:20px; }
.quick-link { display:block; padding:20px; background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:#fff; text-decoration:none; border-radius:10px; text-align:center; transition:transform 0.2s; }
.quick-link:hover { transform:translateY(-3px); }
.quick-link .icon { font-size:30px; margin-bottom:10px; }
.quick-link .text { font-size:14px; font-weight:500; }
</style>

<div class="wrap csc-admin">
    <div class="csc-header">
        <h1>TD Tong quan Cua hang</h1>
        <p style="color:#666;">Cho Sach Cu - He thong quan tri</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card blue"><div class="stat-icon">B</div><span class="stat-number"><?php echo number_format($total_books); ?></span><span class="stat-label">Sach dang ban</span></div>
        <div class="stat-card orange"><div class="stat-icon">C</div><span class="stat-number"><?php echo number_format($pending_books); ?></span><span class="stat-label">Cho duyet</span></div>
        <div class="stat-card green"><div class="stat-icon">D</div><span class="stat-number"><?php echo number_format($total_orders); ?></span><span class="stat-label">Don hang</span></div>
        <div class="stat-card teal"><div class="stat-icon">N</div><span class="stat-number"><?php echo number_format($total_vendors); ?></span><span class="stat-label">Nguoi ban</span></div>
        <div class="stat-card purple"><div class="stat-icon">$</div><span class="stat-number"><?php echo number_format($total_revenue, 0); ?>d</span><span class="stat-label">Doanh thu</span></div>
    </div>
    
    <div class="dashboard-section">
        <h2>Thao tac nhanh</h2>
        <div class="quick-links">
            <a href="<?php echo admin_url('admin.php?page=csc-books'); ?>" class="quick-link"><div class="icon">+</div><div class="text">Tat ca sach</div></a>
            <a href="<?php echo admin_url('admin.php?page=csc-users'); ?>" class="quick-link"><div class="icon">U</div><div class="text">Tai khoan</div></a>
            <a href="<?php echo admin_url('admin.php?page=csc-orders'); ?>" class="quick-link"><div class="icon">O</div><div class="text">Don hang</div></a>
            <a href="<?php echo admin_url('admin.php?page=csc-books&status=pending'); ?>" class="quick-link"><div class="icon">!</div><div class="text">Duyet sach</div></a>
        </div>
    </div>
    
    <div class="dashboard-section">
        <h2>Hoat dong gan day</h2>
        <div class="section-grid">
            <div>
                <h3 style="font-size:16px; color:#666;">Sach moi nhat</h3>
                <table class="mini-table">
                    <thead><tr><th>Ten sach</th><th>Gia</th><th>Trang thai</th></tr></thead>
                    <tbody>
                        <?php if(empty($recent_books)): ?>
                            <tr><td colspan="3" style="text-align:center;">Chua co sach nao</td></tr>
                        <?php else: foreach($recent_books as $book): $p = wc_get_product($book->ID); ?>
                            <tr>
                                <td><a href="<?php echo get_edit_post_link($book->ID); ?>"><?php echo esc_html($book->post_title); ?></a></td>
                                <td><?php echo $p ? number_format($p->get_price()).'d' : '-'; ?></td>
                                <td><span class="badge <?php echo $book->post_status==='publish'?'badge-success':'badge-warning'; ?>"><?php echo $book->post_status==='publish'?'Da dang':'Cho duyet'; ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div>
                <h3 style="font-size:16px; color:#666;">Don hang gan nhat</h3>
                <table class="mini-table">
                    <thead><tr><th>Ma don</th><th>Khach hang</th><th>Tong</th><th>Trang thai</th></tr></thead>
                    <tbody>
                        <?php if(empty($recent_orders)): ?>
                            <tr><td colspan="4" style="text-align:center;">Chua co don hang nao</td></tr>
                        <?php else: foreach($recent_orders as $order): $c = $order->get_user(); ?>
                            <tr>
                                <td><a href="<?php echo $order->get_edit_order_url(); ?>">#<?php echo $order->get_order_number(); ?></a></td>
                                <td><?php echo $c ? esc_html($c->display_name) : $order->get_billing_first_name(); ?></td>
                                <td><?php echo number_format($order->get_total()); ?>d</td>
                                <td><span class="badge badge-<?php echo $order->get_status()==='completed'?'success':'info'; ?>"><?php echo wc_get_order_status_name($order->get_status()); ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php }

// Books Page
function csc_books_page() {
    if(isset($_POST['csc_action']) && wp_verify_nonce($_POST['csc_nonce'], 'csc_books')) {
        $book_id = intval($_POST['book_id']);
        if($_POST['csc_action'] === 'delete') { wp_delete_post($book_id); echo '<div class="notice notice-success"><p>Da xoa sach!</p></div>'; }
        elseif($_POST['csc_action'] === 'publish') { wp_update_post(array('ID'=>$book_id, 'post_status'=>'publish')); echo '<div class="notice notice-success"><p>Da xuat ban sach!</p></div>'; }
        elseif($_POST['csc_action'] === 'pending') { wp_update_post(array('ID'=>$book_id, 'post_status'=>'pending')); echo '<div class="notice notice-success"><p>Da chuyen sang cho duyet!</p></div>'; }
    }
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $args = array('post_type'=>'product', 'posts_per_page'=>20, 'paged'=>$paged, 'orderby'=>'date', 'order'=>'DESC');
    if($status_filter) $args['post_status'] = $status_filter;
    if($search) $args['s'] = $search;
    $books = new WP_Query($args);
    $stats = array('total'=>wp_count_posts('product')->publish + wp_count_posts('product')->pending, 'publish'=>wp_count_posts('product')->publish, 'pending'=>wp_count_posts('product')->pending);
?>
<style>
.csc-admin { max-width:1400px; }
.page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; }
.stats-bar { display:flex; gap:10px; margin-bottom:20px; }
.stat-pill { padding:8px 16px; background:#fff; border-radius:20px; font-size:14px; text-decoration:none; color:#333; }
.stat-pill.active { background:#0073aa; color:#fff; }
.stat-pill span { font-weight:bold; margin-left:5px; }
.filter-bar { background:#fff; padding:20px; border-radius:10px; margin-bottom:20px; display:flex; gap:15px; align-items:center; flex-wrap:wrap; }
.filter-bar input, .filter-bar select { padding:8px 12px; border:1px solid #ddd; border-radius:5px; }
.filter-bar input[type="text"] { min-width:200px; }
.books-table { background:#fff; border-radius:10px; overflow:hidden; }
.books-table table { width:100%; border-collapse:collapse; }
.books-table th { background:#f8f9fa; padding:15px; text-align:left; font-weight:600; }
.books-table td { padding:15px; border-bottom:1px solid #eee; vertical-align:middle; }
.books-table tr:hover { background:#f8f9fa; }
.product-thumb { width:60px; height:60px; object-fit:cover; border-radius:8px; }
.product-thumb-placeholder { width:60px; height:60px; background:#f1f1f1; display:flex; align-items:center; justify-content:center; border-radius:8px; font-size:24px; }
.action-btns { display:flex; gap:5px; }
.action-btns .button { padding:5px 10px; font-size:12px; }
.badge { display:inline-block; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:500; }
.badge-success { background:#d4edda; color:#155724; }
.badge-warning { background:#fff3cd; color:#856404; }
.badge-secondary { background:#e2e3e5; color:#383d41; }
.pagination { margin-top:20px; display:flex; justify-content:center; gap:5px; }
.pagination a, .pagination span { padding:8px 12px; background:#fff; border:1px solid #ddd; border-radius:5px; text-decoration:none; color:#333; }
.pagination a:hover { background:#0073aa; color:#fff; border-color:#0073aa; }
.pagination .current { background:#0073aa; color:#fff; border-color:#0073aa; }
</style>

<div class="wrap csc-admin">
    <div class="page-header"><h1>QL Sach</h1><a href="<?php echo admin_url('post-new.php?post_type=product'); ?>" class="button button-primary">+ Them sach moi</a></div>
    
    <div class="stats-bar">
        <a href="<?php echo admin_url('admin.php?page=csc-books'); ?>" class="stat-pill <?php echo !$status_filter?'active':''; ?>">Tat ca <span><?php echo $stats['total']; ?></span></a>
        <a href="<?php echo admin_url('admin.php?page=csc-books&status=publish'); ?>" class="stat-pill <?php echo $status_filter==='publish'?'active':''; ?>">Da xuat ban <span><?php echo $stats['publish']; ?></span></a>
        <a href="<?php echo admin_url('admin.php?page=csc-books&status=pending'); ?>" class="stat-pill <?php echo $status_filter==='pending'?'active':''; ?>">Cho duyet <span><?php echo $stats['pending']; ?></span></a>
    </div>
    
    <form method="get" class="filter-bar">
        <input type="hidden" name="page" value="csc-books">
        <input type="text" name="s" placeholder="Tim kiem sach..." value="<?php echo esc_attr($search); ?>">
        <select name="status"><option value="">Tat ca trang thai</option><option value="publish" <?php selected($status_filter,'publish'); ?>>Da xuat ban</option><option value="pending" <?php selected($status_filter,'pending'); ?>>Cho duyet</option></select>
        <button type="submit" class="button">Loc</button>
    </form>
    
    <div class="books-table">
        <table>
            <thead><tr><th width="80">Anh</th><th>Ten sach</th><th>Tac gia</th><th>Gia</th><th>Nguoi ban</th><th>Trang thai</th><th>Ngay tao</th><th width="180">Hanh dong</th></tr></thead>
            <tbody>
                <?php if(!$books->have_posts()): ?>
                    <tr><td colspan="8" style="text-align:center; padding:40px;">Chua co sach nao!</td></tr>
                <?php else: while($books->have_posts()): $books->the_post(); global $post; $product = wc_get_product($post->ID); $author = get_post_meta($post->ID, '_book_author', true); $vendor = get_user_by('id', $post->post_author); $thumb = get_the_post_thumbnail_url($post->ID, array(60,60)); ?>
                    <tr>
                        <td><?php if($thumb): ?><img src="<?php echo esc_url($thumb); ?>" class="product-thumb"><?php else: ?><div class="product-thumb-placeholder">B</div><?php endif; ?></td>
                        <td><strong><a href="<?php echo get_edit_post_link($post->ID); ?>"><?php echo esc_html($post->post_title); ?></a></strong><br><small style="color:#666;">ID: <?php echo $post->ID; ?></small></td>
                        <td><?php echo esc_html($author?:'-'); ?></td>
                        <td><?php if($product): ?><strong style="color:#0073aa;"><?php echo number_format($product->get_price()); ?>d</strong><?php else: ?>-<?php endif; ?></td>
                        <td><?php if($vendor): ?><a href="<?php echo get_edit_user_link($vendor->ID); ?>"><?php echo esc_html($vendor->display_name); ?></a><?php else: ?><span style="color:#999;">-</span><?php endif; ?></td>
                        <td><span class="badge badge-<?php $m=array('publish'=>'success','pending'=>'warning','draft'=>'secondary'); echo $m[$post->post_status]??'secondary'; ?>"><?php $sm=array('publish'=>'Da xuat ban','pending'=>'Cho duyet','draft'=>'Ban nhap'); echo $sm[$post->post_status]??$post->post_status; ?></span></td>
                        <td><?php echo date('d/m/Y', strtotime($post->post_date)); ?></td>
                        <td>
                            <div class="action-btns">
                                <a href="<?php echo get_permalink($post->ID); ?>" class="button" target="_blank">Xem</a>
                                <a href="<?php echo get_edit_post_link($post->ID); ?>" class="button">Sua</a>
                                <?php if($post->post_status !== 'publish'): ?>
                                    <form method="post" style="display:inline;"><input type="hidden" name="book_id" value="<?php echo $post->ID; ?>"><input type="hidden" name="csc_action" value="publish"><input type="hidden" name="csc_nonce" value="<?php echo wp_create_nonce('csc_books'); ?>"><button type="submit" class="button button-primary">Duyet</button></form>
                                <?php endif; ?>
                                <form method="post" style="display:inline;"><input type="hidden" name="book_id" value="<?php echo $post->ID; ?>"><input type="hidden" name="csc_action" value="delete"><input type="hidden" name="csc_nonce" value="<?php echo wp_create_nonce('csc_books'); ?>"><button type="submit" class="button" onclick="return confirm('Xoa sach nay?')">Xoa</button></form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($books->max_num_pages > 1): ?>
        <div class="pagination">
            <?php for($i=1; $i<=$books->max_num_pages; $i++): ?>
                <?php if($i == $paged): ?><span class="current"><?php echo $i; ?></span>
                <?php else: ?><a href="<?php echo add_query_arg(array('page'=>'csc-books','paged'=>$i)); ?>"><?php echo $i; ?></a><?php endif; ?>
            <?php endfor; ?>
        </div>
    <?php endif; wp_reset_postdata(); ?>
</div>
<?php }

// Users Page
function csc_users_page() {
    if(isset($_POST['csc_user_action']) && wp_verify_nonce($_POST['csc_user_nonce'], 'csc_users')) {
        $user_id = intval($_POST['user_id']);
        $action = sanitize_text_field($_POST['csc_user_action']);
        if($action === 'activate') { update_user_meta($user_id, 'dokan_enabled', 'on'); echo '<div class="notice notice-success"><p>Da kich hoat tai khoan!</p></div>'; }
        elseif($action === 'deactivate') { update_user_meta($user_id, 'dokan_enabled', 'off'); echo '<div class="notice notice-success"><p>Da tam ngung tai khoan!</p></div>'; }
        elseif($action === 'delete') { require_once(ABSPATH.'wp-admin/includes/user.php'); wp_delete_user($user_id, 1); echo '<div class="notice notice-success"><p>Da xoa tai khoan!</p></div>'; }
    }
    if(isset($_POST['update_user']) && wp_verify_nonce($_POST['update_user_nonce'], 'update_user')) {
        $user_id = intval($_POST['user_id']);
        wp_update_user(array('ID'=>$user_id, 'first_name'=>sanitize_text_field($_POST['first_name']), 'last_name'=>sanitize_text_field($_POST['last_name']), 'display_name'=>sanitize_text_field($_POST['display_name']), 'user_email'=>sanitize_email($_POST['email'])));
        update_user_meta($user_id, 'billing_phone', sanitize_text_field($_POST['phone']));
        echo '<div class="notice notice-success"><p>Da cap nhat thong tin!</p></div>';
    }
    $role_filter = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $args = array('orderby'=>'registered', 'order'=>'DESC');
    if($role_filter) $args['role'] = $role_filter; else $args['role__in'] = array('administrator','seller','vendor','customer','subscriber');
    if($search) $args['search'] = '*'.$search.'*';
    $users = get_users($args);
    $stats = array('all'=>count(get_users(array('fields'=>array('ID')))), 'vendors'=>count(get_users(array('role__in'=>array('seller','vendor')))), 'customers'=>count(get_users(array('role'=>'customer'))), 'admins'=>count(get_users(array('role'=>'administrator'))));
?>
<style>
.csc-admin { max-width:1400px; }
.page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; }
.users-table { background:#fff; border-radius:10px; overflow:hidden; }
.users-table table { width:100%; border-collapse:collapse; }
.users-table th { background:#f8f9fa; padding:15px; text-align:left; font-weight:600; }
.users-table td { padding:15px; border-bottom:1px solid #eee; vertical-align:middle; }
.users-table tr:hover { background:#f8f9fa; }
.role-badge { display:inline-block; padding:4px 10px; border-radius:15px; font-size:12px; font-weight:500; }
.role-badge.administrator { background:#f8d7da; color:#721c24; }
.role-badge.seller, .role-badge.vendor { background:#cce5ff; color:#004085; }
.role-badge.customer { background:#d4edda; color:#155724; }
.action-btns { display:flex; gap:5px; }
.action-btns .button { padding:5px 10px; font-size:12px; }
.edit-modal { display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center; }
.edit-modal.show { display:flex; }
.edit-modal-content { background:#fff; padding:30px; border-radius:10px; max-width:500px; width:100%; max-height:90vh; overflow-y:auto; }
.edit-modal-content h2 { margin-top:0; }
.edit-modal-content label { display:block; margin-bottom:5px; font-weight:500; }
.edit-modal-content input, .edit-modal-content select { width:100%; padding:10px; margin-bottom:15px; border:1px solid #ddd; border-radius:5px; }
.edit-modal-content .button-group { display:flex; gap:10px; justify-content:flex-end; }
</style>

<div class="wrap csc-admin">
    <div class="page-header"><h1>QL Tai khoan</h1></div>
    
    <div class="stats-bar">
        <a href="<?php echo admin_url('admin.php?page=csc-users'); ?>" class="stat-pill <?php echo !$role_filter?'active':''; ?>">Tat ca <span><?php echo $stats['all']; ?></span></a>
        <a href="<?php echo admin_url('admin.php?page=csc-users&role=seller'); ?>" class="stat-pill <?php echo $role_filter==='seller'?'active':''; ?>">Vendor <span><?php echo $stats['vendors']; ?></span></a>
        <a href="<?php echo admin_url('admin.php?page=csc-users&role=customer'); ?>" class="stat-pill <?php echo $role_filter==='customer'?'active':''; ?>">Khach hang <span><?php echo $stats['customers']; ?></span></a>
        <a href="<?php echo admin_url('admin.php?page=csc-users&role=administrator'); ?>" class="stat-pill <?php echo $role_filter==='administrator'?'active':''; ?>">Admin <span><?php echo $stats['admins']; ?></span></a>
    </div>
    
    <form method="get" class="filter-bar">
        <input type="hidden" name="page" value="csc-users">
        <input type="text" name="s" placeholder="Tim kiem..." value="<?php echo esc_attr($search); ?>">
        <select name="role"><option value="">Tat ca vai tro</option><option value="administrator" <?php selected($role_filter,'administrator'); ?>>Admin</option><option value="seller" <?php selected($role_filter,'seller'); ?>>Vendor</option><option value="customer" <?php selected($role_filter,'customer'); ?>>Customer</option></select>
        <button type="submit" class="button">Loc</button>
    </form>
    
    <div class="users-table">
        <table>
            <thead><tr><th width="60">Avatar</th><th>Ten</th><th>Email</th><th>DT</th><th>Vai tro</th><th>Ngay DK</th><th width="200">Hanh dong</th></tr></thead>
            <tbody>
                <?php if(empty($users)): ?>
                    <tr><td colspan="7" style="text-align:center; padding:40px;">Khong tim thay!</td></tr>
                <?php else: foreach($users as $user): $phone = get_user_meta($user->ID, 'billing_phone', true); $is_active = get_user_meta($user->ID, 'dokan_enabled', true) !== 'off'; ?>
                    <tr>
                        <td><?php echo get_avatar($user->ID, 40); ?></td>
                        <td><strong><?php echo esc_html($user->display_name); ?></strong><br><small style="color:#666;">@<?php echo esc_html($user->user_login); ?></small></td>
                        <td><a href="mailto:<?php echo esc_attr($user->user_email); ?>"><?php echo esc_html($user->user_email); ?></a></td>
                        <td><?php echo esc_html($phone?:'-'); ?></td>
                        <td><span class="role-badge <?php echo esc_attr($user->roles[0]); ?>"><?php $rm=array('administrator'=>'Admin','seller'=>'Vendor','vendor'=>'Vendor','customer'=>'Khach hang','subscriber'=>'Subscriber'); echo $rm[$user->roles[0]]??$user->roles[0]; ?></span></td>
                        <td><?php echo date('d/m/Y', strtotime($user->user_registered)); ?></td>
                        <td>
                            <div class="action-btns">
                                <button type="button" class="button" onclick="showEditModal(<?php echo $user->ID; ?>)">Sua</button>
                                <?php if(in_array($user->roles[0], array('seller','vendor'))): ?>
                                    <form method="post" style="display:inline;"><input type="hidden" name="user_id" value="<?php echo $user->ID; ?>"><input type="hidden" name="csc_user_action" value="<?php echo $is_active?'deactivate':'activate'; ?>"><input type="hidden" name="csc_user_nonce" value="<?php echo wp_create_nonce('csc_users'); ?>"><button type="submit" class="button"><?php echo $is_active?'Ngung':'Kich hoat'; ?></button></form>
                                <?php endif; ?>
                                <?php if($user->ID !== get_current_user_id() && $user->roles[0] !== 'administrator'): ?>
                                    <form method="post" style="display:inline;"><input type="hidden" name="user_id" value="<?php echo $user->ID; ?>"><input type="hidden" name="csc_user_action" value="delete"><input type="hidden" name="csc_user_nonce" value="<?php echo wp_create_nonce('csc_users'); ?>"><button type="submit" class="button" onclick="return confirm('Xoa tai khoan nay?')">Xoa</button></form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    
    <div id="editModal" class="edit-modal">
        <div class="edit-modal-content">
            <h2>Sua thong tin tai khoan</h2>
            <form method="post">
                <input type="hidden" name="user_id" id="edit_user_id">
                <input type="hidden" name="update_user_nonce" value="<?php echo wp_create_nonce('update_user'); ?>">
                <label>Ho</label><input type="text" name="first_name" id="edit_first_name">
                <label>Ten</label><input type="text" name="last_name" id="edit_last_name">
                <label>Ten hien thi</label><input type="text" name="display_name" id="edit_display_name">
                <label>Email</label><input type="email" name="email" id="edit_email">
                <label>Dien thoai</label><input type="text" name="phone" id="edit_phone">
                <div class="button-group"><button type="button" class="button" onclick="hideEditModal()">Huy</button><button type="submit" name="update_user" class="button button-primary">Luu</button></div>
            </form>
        </div>
    </div>
    
    <script>
    function showEditModal(userId) { jQuery.ajax({url:ajaxurl,type:'POST',data:{action:'csc_get_user_data',user_id:userId,nonce:'<?php echo wp_create_nonce('csc_user_data'); ?>'},success:function(r){if(r.success){var d=r.data;jQuery('#edit_user_id').val(userId);jQuery('#edit_first_name').val(d.first_name||'');jQuery('#edit_last_name').val(d.last_name||'');jQuery('#edit_display_name').val(d.display_name||'');jQuery('#edit_email').val(d.email||'');jQuery('#edit_phone').val(d.phone||'');jQuery('#editModal').addClass('show');}}}); }
    function hideEditModal() { jQuery('#editModal').removeClass('show'); }
    jQuery('#editModal').on('click',function(e){if(e.target===this)hideEditModal();});
    </script>
</div>
<?php }

add_action('wp_ajax_csc_get_user_data','csc_ajax_get_user_data');
function csc_ajax_get_user_data() {
    if(!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'],'csc_user_data')) wp_send_json_error();
    $user_id = intval($_POST['user_id']);
    $user = get_user_by('id', $user_id);
    if(!$user) wp_send_json_error();
    wp_send_json_success(array('first_name'=>$user->first_name,'last_name'=>$user->last_name,'display_name'=>$user->display_name,'email'=>$user->user_email,'phone'=>get_user_meta($user_id,'billing_phone',true)));
}

// Orders Page
function csc_orders_page() {
    if(isset($_POST['csc_order_action']) && wp_verify_nonce($_POST['csc_order_nonce'],'csc_orders')) {
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        if($_POST['csc_order_action']==='processing') { $order->update_status('processing'); echo '<div class="notice notice-success"><p>Da cap nhat!</p></div>'; }
        elseif($_POST['csc_order_action']==='completed') { $order->update_status('completed'); echo '<div class="notice notice-success"><p>Da hoan thanh!</p></div>'; }
        elseif($_POST['csc_order_action']==='cancelled') { $order->update_status('cancelled'); echo '<div class="notice notice-success"><p>Da huy!</p></div>'; }
    }
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $args = array('limit'=>20,'orderby'=>'date','order'=>'DESC');
    if($status_filter) $args['status'] = $status_filter;
    $orders = wc_get_orders($args);
    $stats = array('total'=>wp_count_posts('shop_order')->publish,'completed'=>function_exists('wc_orders_count')?wc_orders_count('wc-completed'):0,'processing'=>function_exists('wc_orders_count')?wc_orders_count('wc-processing'):0,'pending'=>function_exists('wc_orders_count')?wc_orders_count('wc-pending'):0);
?>
<style>
.csc-admin { max-width:1400px; }
.page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; }
.orders-table { background:#fff; border-radius:10px; overflow:hidden; }
.orders-table table { width:100%; border-collapse:collapse; }
.orders-table th { background:#f8f9fa; padding:15px; text-align:left; font-weight:600; }
.orders-table td { padding:15px; border-bottom:1px solid #eee; vertical-align:middle; }
.orders-table tr:hover { background:#f8f9fa; }
.customer-info strong { display:block; }
.customer-info small { color:#666; }
.action-btns { display:flex; gap:5px; }
.action-btns .button { padding:5px 10px; font-size:12px; }
.badge { display:inline-block; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:500; }
.badge-success { background:#d4edda; color:#155724; }
.badge-info { background:#cce5ff; color:#004085; }
.badge-warning { background:#fff3cd; color:#856404; }
.badge-danger { background:#f8d7da; color:#721c24; }
</style>

<div class="wrap csc-admin">
    <div class="page-header"><h1>QL Don hang</h1></div>
    
    <div class="stats-bar">
        <a href="<?php echo admin_url('admin.php?page=csc-orders'); ?>" class="stat-pill <?php echo !$status_filter?'active':''; ?>">Tat ca <span><?php echo $stats['total']; ?></span></a>
        <a href="<?php echo admin_url('admin.php?page=csc-orders&status=completed'); ?>" class="stat-pill <?php echo $status_filter==='completed'?'active':''; ?>">Hoan thanh <span><?php echo $stats['completed']; ?></span></a>
        <a href="<?php echo admin_url('admin.php?page=csc-orders&status=processing'); ?>" class="stat-pill <?php echo $status_filter==='processing'?'active':''; ?>">Dang xu ly <span><?php echo $stats['processing']; ?></span></a>
        <a href="<?php echo admin_url('admin.php?page=csc-orders&status=pending'); ?>" class="stat-pill <?php echo $status_filter==='pending'?'active':''; ?>">Cho thanh toan <span><?php echo $stats['pending']; ?></span></a>
    </div>
    
    <div class="orders-table">
        <table>
            <thead><tr><th>Ma don</th><th>Khach hang</th><th>San pham</th><th>Tong tien</th><th>TT</th><th>Ngay dat</th><th width="150">Hanh dong</th></tr></thead>
            <tbody>
                <?php if(empty($orders)): ?>
                    <tr><td colspan="7" style="text-align:center; padding:40px;">Chua co don hang nao!</td></tr>
                <?php else: foreach($orders as $order): $customer = $order->get_user(); $items = $order->get_items(); ?>
                    <tr>
                        <td><strong><a href="<?php echo $order->get_edit_order_url(); ?>">#<?php echo $order->get_order_number(); ?></a></strong></td>
                        <td class="customer-info"><strong><?php echo esc_html($order->get_billing_first_name().' '.$order->get_billing_last_name()); ?></strong><small><?php echo esc_html($order->get_billing_phone()); ?></small></td>
                        <td><?php foreach(array_slice($items,0,2) as $item): echo esc_html($item->get_name()).' x'.$item->get_quantity().'<br>'; endforeach; ?></td>
                        <td><strong style="color:#e74c3c;"><?php echo number_format($order->get_total()); ?>d</strong></td>
                        <td><span class="badge badge-<?php $bm=array('completed'=>'success','processing'=>'info','pending'=>'warning','cancelled'=>'danger'); echo $bm[$order->get_status()]??'secondary'; ?>"><?php echo wc_get_order_status_name($order->get_status()); ?></span></td>
                        <td><?php echo date('d/m/Y', strtotime($order->get_date_created())); ?></td>
                        <td>
                            <div class="action-btns">
                                <a href="<?php echo $order->get_edit_order_url(); ?>" class="button">Sua</a>
                                <form method="post" style="display:inline;"><input type="hidden" name="order_id" value="<?php echo $order->get_id(); ?>"><input type="hidden" name="csc_order_nonce" value="<?php echo wp_create_nonce('csc_orders'); ?>"><select name="csc_order_action" style="padding:5px;border:1px solid #ddd;border-radius:5px;" onchange="this.form.submit()"><option value="">-</option><option value="processing">Dang xu ly</option><option value="completed">Hoan thanh</option><option value="cancelled">Huy</option></select></form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php }
