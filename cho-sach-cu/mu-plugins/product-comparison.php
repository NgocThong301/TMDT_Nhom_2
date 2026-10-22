<?php
/**
 * Plugin Name: CSC Product Comparison
 * Description: So sanh san pham cho Chợ Sách Cũ
 */

if (!defined('ABSPATH')) exit;

// Shortcode so sánh sản phẩm
add_shortcode('csc_compare', 'csc_compare_shortcode');

function csc_compare_shortcode($atts) {
    $atts = shortcode_atts(array(
        'max' => 4,
    ), $atts);

    ob_start();
    ?>
    <div class="csc-compare-container" id="csc-compare-app" data-max="<?php echo esc_attr($atts['max']); ?>">
        <h3>📊 So Sánh Sản Phẩm</h3>

        <div class="csc-compare-loading">
            <p>Đang tải danh sách so sánh...</p>
        </div>

        <div class="csc-compare-body" style="display:none;">
            <div class="compare-table-wrapper">
                <table class="compare-table" id="csc-compare-table"></table>
            </div>

            <div class="compare-clear-row">
                <button type="button" class="compare-clear-all" id="csc-compare-clear">🗑️ Xóa tất cả</button>
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="compare-continue">← Tiếp tục mua sắm</a>
            </div>
        </div>

        <div class="csc-compare-empty" style="display:none;">
            <p>📋 Chưa có sản phẩm nào để so sánh.</p>
            <p>Hãy thêm sản phẩm vào danh sách so sánh từ trang chi tiết sản phẩm.</p>
        </div>
    </div>

    <style>
        .csc-compare-container {
            margin: 30px 0;
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .csc-compare-container h3 {
            margin-bottom: 20px;
            color: #333;
        }
        .compare-table-wrapper {
            overflow-x: auto;
        }
        .compare-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        .compare-table th,
        .compare-table td {
            padding: 15px;
            text-align: center;
            border: 1px solid #eee;
            vertical-align: middle;
        }
        .compare-table th.compare-feature-header {
            background: #f5f5f5;
            text-align: left;
            font-weight: bold;
            width: 150px;
        }
        .compare-product-header {
            position: relative;
            background: #f9f9f9;
        }
        .compare-product-header a {
            display: block;
            text-decoration: none;
            color: #333;
        }
        .compare-product-header img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            margin-bottom: 10px;
        }
        .compare-product-header span {
            display: block;
            font-weight: bold;
        }
        .remove-compare {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #e2401c;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
        }
        .compare-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .compare-table td:first-child {
            font-weight: bold;
            text-align: left;
            background: #f5f5f5;
        }
        .compare-add-cart {
            background: #0073aa;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        .compare-add-cart:hover {
            background: #005177;
        }
        .compare-clear-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .compare-clear-all {
            background: #e2401c;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        .compare-clear-all:hover {
            background: #c0341a;
        }
        .compare-continue {
            color: #0073aa;
            text-decoration: none;
        }
        .csc-compare-empty {
            text-align: center;
            padding: 40px;
            background: #f9f9f9;
            border-radius: 8px;
            color: #666;
        }
        .csc-compare-loading {
            text-align: center;
            padding: 30px;
            color: #666;
        }

        .csc-add-compare,
        .csc-add-compare-product {
            background: #f0f0f0;
            border: 1px solid #ddd;
            padding: 5px 10px;
            margin: 5px;
            cursor: pointer;
            font-size: 12px;
            border-radius: 4px;
        }
        .csc-add-compare:hover,
        .csc-add-compare-product:hover {
            background: #0073aa;
            color: #fff;
            border-color: #0073aa;
        }
        .csc-add-compare.is-added,
        .csc-add-compare-product.is-added {
            background: #0073aa;
            color: #fff;
            border-color: #0073aa;
        }

        /* Badge số lượng trên icon so sánh */
        .csc-compare-count {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #0073aa;
            color: #fff;
            padding: 10px 18px;
            border-radius: 30px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.25);
            z-index: 999;
            cursor: pointer;
            font-size: 14px;
            display: none;
        }
        .csc-compare-count:hover {
            background: #005177;
        }
    </style>

    <script>
    (function($) {
        function loadCompareTable() {
            var $app = $('#csc-compare-app');
            if (!$app.length) return;

            var ids = window.cscCompare ? window.cscCompare.get() : [];

            if (ids.length < 2) {
                $app.find('.csc-compare-loading, .csc-compare-body').hide();
                $app.find('.csc-compare-empty').show();
                return;
            }

            $app.find('.csc-compare-loading').show();
            $app.find('.csc-compare-body, .csc-compare-empty').hide();

            $.ajax({
                url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                type: 'POST',
                data: { action: 'csc_compare_data', product_ids: ids.join(',') },
                success: function(response) {
                    $app.find('.csc-compare-loading').hide();

                    if (!response.success || !response.data.products || response.data.products.length < 2) {
                        $app.find('.csc-compare-empty').show();
                        return;
                    }

                    renderCompareTable(response.data);
                    $app.find('.csc-compare-body').show();
                },
                error: function() {
                    $app.find('.csc-compare-loading').hide();
                    $app.find('.csc-compare-empty').show();
                }
            });
        }

        function renderCompareTable(data) {
            var products = data.products;
            var rows = data.rows;
            var html = '<thead><tr><th class="compare-feature-header">Tiêu chí</th>';

            products.forEach(function(p) {
                html += '<th class="compare-product-header">' +
                    '<a href="' + p.permalink + '">' +
                    (p.image ? '<img src="' + p.image + '" alt="' + p.name + '">' : '') +
                    '<span>' + p.name + '</span></a>' +
                    '<button type="button" class="remove-compare" data-id="' + p.id + '" title="Xóa">&times;</button>' +
                    '</th>';
            });

            html += '</tr></thead><tbody>';

            rows.forEach(function(row) {
                html += '<tr><td>' + row.label + '</td>';
                row.values.forEach(function(v) {
                    html += '<td>' + v + '</td>';
                });
                html += '</tr>';
            });

            html += '<tr class="compare-action"><td></td>';
            products.forEach(function(p) {
                html += '<td><button type="button" class="compare-add-cart" data-id="' + p.id + '">Thêm vào giỏ</button></td>';
            });
            html += '</tr></tbody>';

            $('#csc-compare-table').html(html);
        }

        $(document).ready(function() {
            loadCompareTable();

            // Xóa 1 sản phẩm khỏi bảng so sánh
            $(document).on('click', '.remove-compare', function() {
                var id = parseInt($(this).data('id'), 10);
                var ids = window.cscCompare.get().filter(function(i) { return i !== id; });
                window.cscCompare.set(ids);
                loadCompareTable();
            });

            // Xóa tất cả
            $(document).on('click', '#csc-compare-clear', function() {
                if (confirm('Xóa tất cả sản phẩm khỏi danh sách so sánh?')) {
                    window.cscCompare.set([]);
                    loadCompareTable();
                }
            });

            // Thêm vào giỏ từ bảng so sánh
            $(document).on('click', '.compare-add-cart', function() {
                var id = $(this).data('id');
                var $btn = $(this);
                $btn.text('Đang thêm...').prop('disabled', true);

                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: { action: 'csc_add_to_cart', product_id: id },
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
    })(jQuery);
    </script>
    <?php

    return ob_get_clean();
}

// AJAX: trả về dữ liệu so sánh
add_action('wp_ajax_csc_compare_data', 'csc_ajax_compare_data');
add_action('wp_ajax_nopriv_csc_compare_data', 'csc_ajax_compare_data');

function csc_ajax_compare_data() {
    $ids = isset($_POST['product_ids']) ? sanitize_text_field($_POST['product_ids']) : '';
    $id_array = array_filter(array_map('intval', explode(',', $ids)));

    if (empty($id_array)) {
        wp_send_json_error();
    }

    $products = array();
    foreach ($id_array as $id) {
        $p = wc_get_product($id);
        if ($p) {
            $products[] = $p;
        }
    }

    if (count($products) < 2) {
        wp_send_json_error();
    }

    $product_data = array();
    foreach ($products as $p) {
        $image_id = $p->get_image_id();
        $cats = wp_get_post_terms($p->get_id(), 'product_cat');
        $author = get_post_meta($p->get_id(), '_book_author', true);
        $condition = get_post_meta($p->get_id(), '_book_condition', true);
        $publisher = get_post_meta($p->get_id(), '_book_publisher', true);
        $year = get_post_meta($p->get_id(), '_book_year', true);

        $condition_labels = array(
            'new'         => 'Mới',
            'like_new'    => 'Như mới',
            'good'        => 'Tốt',
            'acceptable'  => 'Chấp nhận được',
        );

        $product_data[] = array(
            'id'        => $p->get_id(),
            'name'      => $p->get_name(),
            'permalink' => get_permalink($p->get_id()),
            'image'     => $image_id ? wp_get_attachment_url($image_id) : wc_placeholder_img_src(),
        );

        $rows[] = array(
            'label'  => 'Giá',
            'values' => array_map(function($x) { return $x->get_price_html(); }, $products),
        );
        $rows[] = array(
            'label'  => 'Tình trạng',
            'values' => array_map(function($x) {
                return $x->is_in_stock() ? '✓ Còn hàng' : '✗ Hết hàng';
            }, $products),
        );
        $rows[] = array(
            'label'  => 'Danh mục',
            'values' => array_map(function($x) {
                $c = wp_get_post_terms($x->get_id(), 'product_cat');
                return $c && !is_wp_error($c) ? implode(', ', wp_list_pluck($c, 'name')) : '-';
            }, $products),
        );
        $rows[] = array(
            'label'  => 'Tác giả',
            'values' => array_map(function($x) {
                $a = get_post_meta($x->get_id(), '_book_author', true);
                return $a ? esc_html($a) : '-';
            }, $products),
        );
        $rows[] = array(
            'label'  => 'Tình trạng sách',
            'values' => array_map(function($x) {
                $cond = get_post_meta($x->get_id(), '_book_condition', true);
                $labels = array('new' => 'Mới', 'like_new' => 'Như mới', 'good' => 'Tốt', 'acceptable' => 'Chấp nhận được');
                return isset($labels[$cond]) ? $labels[$cond] : '-';
            }, $products),
        );
        $rows[] = array(
            'label'  => 'Nhà xuất bản',
            'values' => array_map(function($x) {
                $p = get_post_meta($x->get_id(), '_book_publisher', true);
                return $p ? esc_html($p) : '-';
            }, $products),
        );
        $rows[] = array(
            'label'  => 'Năm xuất bản',
            'values' => array_map(function($x) {
                $y = get_post_meta($x->get_id(), '_book_year', true);
                return $y ? esc_html($y) : '-';
            }, $products),
        );
        $rows[] = array(
            'label'  => 'Mô tả',
            'values' => array_map(function($x) {
                return wp_trim_words($x->get_description(), 20) ?: '-';
            }, $products),
        );
    }

    wp_send_json_success(array(
        'products' => $product_data,
        'rows'     => $rows,
    ));
}

// AJAX: thêm vào giỏ hàng
add_action('wp_ajax_csc_add_to_cart', 'csc_compare_add_to_cart');
add_action('wp_ajax_nopriv_csc_add_to_cart', 'csc_compare_add_to_cart');

function csc_compare_add_to_cart() {
    if (!isset($_POST['product_id'])) {
        wp_send_json_error();
    }

    $product_id = intval($_POST['product_id']);
    WC()->cart->add_to_cart($product_id);

    wp_send_json_success();
}

// Thêm nút so sánh vào sản phẩm
add_action('woocommerce_after_shop_loop_item', 'csc_add_compare_button', 20);
add_action('woocommerce_single_product_summary', 'csc_add_compare_button_product', 35);

function csc_add_compare_button() {
    global $product;
    echo '<button type="button" class="csc-add-compare" data-id="' . esc_attr($product->get_id()) . '" title="Thêm vào so sánh">'
        . csc_compare_icon_svg() . '<span>So sánh</span></button>';
}

function csc_add_compare_button_product() {
    global $product;
    echo '<button type="button" class="csc-add-compare-product" data-id="' . esc_attr($product->get_id()) . '">'
        . csc_compare_icon_svg() . '<span>Thêm vào so sánh</span></button>';
}

/**
 * Icon cân so sánh dạng inline SVG (tránh bị WordPress convert sang emoji)
 */
function csc_compare_icon_svg() {
    return '<svg class="csc-compare-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true" focusable="false">'
        . '<path d="M12 3a1 1 0 0 1 1 1v1.07a7 7 0 0 1 4.5 2.68l.93-1.6a1 1 0 1 1 1.74.98l-.85 1.47A7 7 0 0 1 21 12a1 1 0 0 1-2 0 5 5 0 0 0-4.2-4.92A7 7 0 0 1 13 9.46v9.05a3 3 0 0 1-2 2.83V22a1 1 0 0 1-2 0v-.66A3 3 0 0 1 7 19.5v-1.05a3 3 0 0 1-2-2.83V9.46a7 7 0 0 1-2.32-1.96l-.85 1.47a1 1 0 0 1-1.74-.98l.93-1.6A7 7 0 0 1 6.5 5.07V4a1 1 0 0 1 2 0v1.07A7 7 0 0 1 11 5.07V4a1 1 0 0 1 1-1z"/>'
        . '</svg>';
}

// Tạo page so sánh
add_action('init', 'csc_create_compare_page');

function csc_create_compare_page() {
    $page = get_page_by_path('so-sanh-san-pham');

    if (!$page) {
        wp_insert_post(array(
            'post_title'    => 'So Sánh Sản Phẩm',
            'post_name'     => 'so-sanh-san-pham',
            'post_content'  => '[csc_compare]',
            'post_status'   => 'publish',
            'post_type'     => 'page',
        ));
    }
}

// Nạp CSS + JS toàn cục trên mọi trang (nút so sánh nằm ở mọi nơi)
add_action('wp_footer', 'csc_compare_global_assets', 5);

function csc_compare_global_assets() {
    if (is_admin()) return;
    echo '<style id="csc-compare-css">' . csc_compare_global_css() . '</style>';
    echo '<script id="csc-compare-js">' . csc_compare_global_js() . '</script>';
}

function csc_compare_global_css() {
    return '
        .csc-add-compare,
        .csc-add-compare-product {
            background: #f0f0f0;
            border: 1px solid #ddd;
            padding: 5px 10px;
            margin: 5px;
            cursor: pointer;
            font-size: 12px;
            border-radius: 4px;
            display: inline-block;
        }
        .csc-add-compare:hover,
        .csc-add-compare-product:hover {
            background: #0073aa;
            color: #fff;
            border-color: #0073aa;
        }
        .csc-add-compare.is-added,
        .csc-add-compare-product.is-added {
            background: #0073aa;
            color: #fff;
            border-color: #0073aa;
        }
        .csc-compare-count {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #0073aa;
            color: #fff;
            padding: 10px 18px;
            border-radius: 30px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.25);
            z-index: 999;
            cursor: pointer;
            font-size: 14px;
            display: none;
            text-decoration: none;
        }
        .csc-compare-count:hover {
            background: #005177;
            color: #fff;
        }
    ';
}

function csc_compare_global_js() {
    return "
    (function(\$) {
        var STORAGE_KEY = 'csc_compare';

        function getCompare() {
            try {
                var raw = localStorage.getItem(STORAGE_KEY);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr.map(Number) : [];
            } catch (e) { return []; }
        }

        function updateBadge() {
            var ids = getCompare();
            var \$badge = \$('#csc-compare-badge');
            if (!\$badge.length) {
                \$badge = \$('<a class=\"csc-compare-count\" id=\"csc-compare-badge\"></a>').appendTo('body');
            }
            if (ids.length > 0) {
                \$badge.text('⚖️ So sánh (' + ids.length + ')')
                    .attr('href', '" . esc_url(home_url('/so-sanh-san-pham')) . "')
                    .show();
            } else {
                \$badge.hide();
            }
            \$('.csc-add-compare, .csc-add-compare-product').each(function() {
                var id = parseInt(\$(this).data('id'), 10);
                var \$btn = \$(this);
                var \$span = \$btn.find('span');
                if (ids.indexOf(id) > -1) {
                    \$btn.addClass('is-added');
                    if (\$span.length) {
                        \$span.text('Đã thêm so sánh');
                    } else {
                        \$btn.html('✅ Đã thêm so sánh');
                    }
                } else {
                    \$btn.removeClass('is-added');
                    if (\$span.length) {
                        \$span.text('So sánh');
                    } else {
                        \$btn.html('⚖️ So sánh');
                    }
                }
            });
        }

        window.cscCompare = {
            get: getCompare,
            set: function(arr) {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(arr));
                updateBadge();
            },
            max: 4,
            toggle: function(id) {
                id = parseInt(id, 10);
                var ids = getCompare();
                var idx = ids.indexOf(id);
                if (idx > -1) {
                    ids.splice(idx, 1);
                    this.set(ids);
                    return false;
                }
                if (ids.length >= this.max) {
                    alert('Chỉ có thể so sánh tối đa ' + this.max + ' sản phẩm cùng lúc!');
                    return null;
                }
                ids.push(id);
                this.set(ids);
                return true;
            }
        };

        \$(document).ready(function() {
            updateBadge();
            \$(document).on('click', '.csc-add-compare, .csc-add-compare-product', function(e) {
                e.preventDefault();
                window.cscCompare.toggle(\$(this).data('id'));
            });
        });
    })(jQuery);
    ";
}
