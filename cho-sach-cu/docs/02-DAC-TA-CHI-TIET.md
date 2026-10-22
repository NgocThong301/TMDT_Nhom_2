# Chợ Sách Cũ — Đặc tả chi tiết (Use Case)

> Đặc tả theo **logic thực thi** của từng chức năng, bao gồm cả các chức năng nhỏ nhất.
> Mỗi mục ghi rõ: **Mã** · **Vị trí** · **Hook / Điều kiện** · **Logic xử lý** · **Kết quả đầu ra**.
> Tài liệu hướng dẫn tổng quan: [`01-HUONG-DAN-SU-DUNG.md`](./01-HUONG-DAN-SU-DUNG.md)

---

## Mục lục

- [PHẦN A — HẠ TẦNG & CẤU HÌNH](#phần-a--hạ-tầng--cấu-hình)
- [PHẦN B — MU-PLUGINS (LOGIC NGHIỆP VỤ)](#phần-b--mu-plugins-logic-nghiệp-vụ)
- [PHẦN C — CHILD THEME](#phần-c--child-theme)
- [PHẦN D — MA TRẬN CHỨC NĂNG ↔ ROLE](#phần-d--ma-trận-chức-năng--role)
- [PHỤ LỤC E — NHẬT KÝ LỖI ĐÃ SỬA](#phụ-lục-e--nhật-ký-lỗi-đã-sửa)

---

# PHẦN A — HẠ TẦNG & CẤU HÌNH

## A.1. Docker Compose

**Vị trí:** `cho-sach-cu/docker-compose.yml`

### A.1.1. Service `db`

| Thuộc tính | Giá trị |
|---|---|
| Image | `mysql:8.0.40` |
| Container | `cho-sach-cu-db` |
| Restart | `unless-stopped` |
| Env | `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD` (từ `.env`) |
| Volume | `cho_sach_cu_db_data:/var/lib/mysql` |
| Healthcheck | `mysqladmin ping -h 127.0.0.1 --silent` — interval 5s, timeout 5s, retries 30, start 20s |
| Network | `cho_sach_cu_net` |
| Port | **không publish** ra host (MySQL chỉ nội bộ) |

### A.1.2. Service `wordpress`

| Thuộc tính | Giá trị |
|---|---|
| Image | `wordpress:7.1.3-php8.2-apache` |
| Container | `cho-sach-cu-wordpress` |
| Restart | `unless-stopped` |
| Depends on | `db` với `condition: service_healthy` |
| Port | `${WP_HOST_PORT}:80` → **8081** |
| Volume | `cho_sach_cu_wp_data:/var/www/html` |
| Volume | `./scripts:/opt/cho-sach-cu:ro` (read-only) |
| Volume | `./mu-plugins:/var/www/html/wp-content/mu-plugins:rw` (**ghi được**) |
| Healthcheck | `php -r "exit(@fsockopen('127.0.0.1', 80) ? 0 : 1);"` — interval 10s, timeout 5s, retries 30, start 30s |

**`WORDPRESS_CONFIG_EXTRA`:**
```php
define('WP_HOME', '${WP_URL}');
define('WP_SITEURL', '${WP_URL}');
define('DISALLOW_FILE_EDIT', true);
```

### A.1.3. Service `wpcli`

| Thuộc tính | Giá trị |
|---|---|
| Image | `wordpress:cli-2.12.0-php8.2` |
| Container | `cho-sach-cu-wpcli` |
| Profiles | `["setup"]` — chỉ chạy khi gọi `--profile setup` |
| User | `33:33` |
| Working dir | `/var/www/html` |
| Env | `WORDPRESS_DB_*`, `WP_URL`, `WP_ADMIN_USER/PASSWORD/EMAIL`, `VENDOR1/2/3_PASSWORD`, `BUYER_PASSWORD` |
| Volume | `cho_sach_cu_wp_data:/var/www/html`, `./scripts:/opt/cho-sach-cu:ro` |
| Entrypoint | `sh /opt/cho-sach-cu/setup.sh` |

### A.1.4. Volume & Network

| Loại | Tên |
|---|---|
| Volume MySQL | `cho-sach-cu_db_data` |
| Volume WordPress | `cho-sach-cu_wp_data` |
| Network | `cho-sach-cu_net` (driver `bridge`) |

### A.1.5. Biến môi trường (`.env`)

```
COMPOSE_PROJECT_NAME=cho-sach-cu
WP_HOST_PORT=8081
WP_URL=http://localhost:8081
MYSQL_DATABASE=cho_sach_cu
MYSQL_USER=cho_sach_cu
MYSQL_PASSWORD=...
MYSQL_ROOT_PASSWORD=...
WORDPRESS_TABLE_PREFIX=csc_
WP_ADMIN_USER=csc_admin
WP_ADMIN_PASSWORD=...
WP_ADMIN_EMAIL=admin@cho-sach-cu.local
VENDOR1_PASSWORD / VENDOR2_PASSWORD / VENDOR3_PASSWORD / BUYER_PASSWORD
```

> `.env` nằm trong `.gitignore` — **không commit**.

---

## A.2. `scripts/setup.sh` — Orchestrator cài đặt

**Điều kiện tiên quyết:** container `db` healthy **AND** container `wordpress` healthy.

**Wrapper:**
```sh
wp() { command wp --path=/var/www/html --url="${WP_URL}" "$@"; }
```

### A.2.1. Bước chờ

| Bước | Logic |
|---|---|
| Chờ `wp-config.php` | `while [ ! -f /var/www/html/wp-config.php ]` — tối đa 60 vòng × 2s = **120s**, hết giờ → exit 1 |
| Chờ DB | Lặp `wp core is-installed`; nếu output chứa `"Error establishing a database connection"` \| `"Unknown database"` \| `"Access denied"` → tiếp tục chờ (tối đa 60 × 2s = **120s**), ngược lại thoát vòng lặp |

### A.2.2. Bước cài đặt (thứ tự thực thi)

| # | Lệnh | Ý nghĩa |
|---|---|---|
| 1 | `wp core install --url --title="Chợ Sách Cũ" --admin_user --admin_password --admin_email --skip-email` | Bỏ qua nếu `wp core is-installed` trả 0 |
| 2 | `wp option update blogname "Chợ Sách Cũ"` | Tên site |
| 3 | `wp option update blogdescription "Sàn C2C mua bán sách đã qua sử dụng"` | Mô tả |
| 4 | `wp option update timezone_string "Asia/Ho_Chi_Minh"` | Múi giờ VN |
| 5 | `wp option update date_format "d/m/Y"` · `time_format "H:i"` | Định dạng ngày giờ |
| 6 | `wp rewrite structure "/%postname%/" --hard` | Permalink đẹp |
| 7 | `wp language core install vi` → `wp site switch-language vi` (fallback `wp language core activate vi`) | Ngôn ngữ VN |
| 8 | `wp theme install storefront --activate` (fallback `wp theme activate storefront`) | Theme chính |
| 9 | `wp theme install dokani --activate` → fallback tag `v1.0.1` từ GitHub → fallback Storefront | Thử theme marketplace |
| 10 | `wp plugin install woocommerce --activate` | WooCommerce |
| 11 | `wp plugin install dokan-lite --activate` | Dokan Lite |
| 12 | `wp language plugin install woocommerce vi` · `wp language plugin update --all` | Bản dịch |
| 13 | Cấu hình cửa hàng VN (12 option) | Xem A.2.3 |
| 14 | `wp wc --user=... tool run install_pages` | Tạo trang WooCommerce mặc định |
| 15 | Cấu hình COD + BACS (2 option JSON) | Xem A.2.4 |
| 16 | `wp eval-file configure-shipping.php` | Shipping cơ bản |
| 17 | `wp eval-file configure-regional-shipping.php` | Shipping 3 miền + freeship |
| 18 | `wp eval-file install-vietnam-store-toolkit.php` | Dropdown tỉnh VN |
| 19 | `wp eval-file configure-dokan.php` | Option Dokan |
| 20 | `wp eval-file configure-pages.php` | Tạo trang nền |
| 21 | `wp eval-file seed-products.php` | Seed sản phẩm + user |
| 22 | `wp rewrite flush --hard` | Flush rewrite |
| 23 | In báo cáo: `wp core version`, `wp theme list`, `wp plugin list`, `blogname`, `woocommerce_currency` | Xác nhận |

### A.2.3. Option cửa hàng Việt Nam (bước 13)

| Option | Giá trị |
|---|---|
| `woocommerce_store_address` | `123 Trần Duy Hưng` |
| `woocommerce_store_city` | `Hà Nội` |
| `woocommerce_default_country` | `VN` |
| `woocommerce_store_postcode` | `100000` |
| `woocommerce_currency` | `VND` |
| `woocommerce_currency_pos` | `right_space` |
| `woocommerce_price_thousand_sep` | `.` |
| `woocommerce_price_decimal_sep` | `,` |
| `woocommerce_price_num_decimals` | `0` |
| `woocommerce_weight_unit` | `kg` |
| `woocommerce_dimension_unit` | `cm` |
| `woocommerce_calc_taxes` | `no` |
| `woocommerce_enable_guest_checkout` | `yes` |
| `woocommerce_enable_checkout_login_reminder` | `yes` |
| `woocommerce_enable_signup_and_login_from_checkout` | `yes` |
| `woocommerce_enable_myaccount_registration` | `yes` |
| `woocommerce_coming_soon` | `no` |
| `woocommerce_store_pages_only` | `no` |
| `woocommerce_show_marketplace_suggestions` | `no` |
| `woocommerce_allow_tracking` | `no` |
| `woocommerce_task_list_hidden` | `yes` |
| `woocommerce_onboarding_profile` | `{"skipped":true,"completed":true}` |

### A.2.4. Cấu hình cổng thanh toán (bước 15)

| Gateway | Option | Nội dung |
|---|---|---|
| **COD** | `woocommerce_cod_settings` | `enabled=yes`, title "Thanh toán khi nhận hàng", description "Thanh toán tiền mặt khi nhận sách. Chỉ dùng để thử nghiệm local.", instructions "Chuẩn bị đúng số tiền khi nhận hàng." |
| **BACS** | `woocommerce_bacs_settings` | `enabled=yes`, title "Chuyển khoản ngân hàng (thử nghiệm)", description "Chuyển khoản giả lập cho bài tập. Không dùng tài khoản ngân hàng thật.", instructions "Nội dung CK: CSC-TEST" |

> Cổng thanh toán thật (VNPay) có mã nguồn trong `scripts/vnpay-gateway/` nhưng **không kích hoạt** trong bản chạy hiện tại (chỉ COD + chuyển khoản giả lập, an toàn cho môi trường bài tập).

---

## A.3. `scripts/configure-regional-shipping.php` — Logic vận chuyển

### A.3.1. Bước 1 — Dọn dẹp (idempotent)

```
$all_zones = WC_Shipping_Zones::get_zones();
foreach ($all_zones as $zone) {
    $zone_obj = new WC_Shipping_Zone($zone['id']);
    foreach ($zone_obj->get_shipping_methods() as $method) {
        $zone_obj->delete_shipping_method($method->instance_id);
    }
    $zone_obj->delete();
}
```
→ Xóa toàn bộ method và zone cũ để chạy lại không nhân bản.

### A.3.2. Bước 2 — Chia 3 miền theo mã tỉnh

**Miền Bắc (25):**
`VN-HN VN-HP VN-HD VN-HY VN-BN VN-VP VN-TN VN-HB VN-QN VN-BG VN-BK VN-LS VN-CB VN-TQ VN-YB VN-TB VN-ND VN-NBI VN-HM VN-SL VN-DB VN-LC VN-VC VN-HG VN-PT`

**Miền Trung (18):**
`VN-HT VN-NA VN-TH VN-QB VN-QT VN-TTH VN-DN VN-QNA VN-QNG VN-BD VN-PY VN-KH VN-NT VN-BTH VN-GL VN-DL VN-DNO VN-LD`

**Miền Nam (19):**
`VN-SG VN-CT VN-AG VN-BL VN-BTE VN-BDI VN-BP VN-CM VN-DNI VN-DTH VN-HGI VN-KG VN-LA VN-ST VN-TNI VN-TGI VN-TV VN-VL VN-VT`

**Hằng:** `$freeship_threshold = 699000`

### A.3.3. Bước 3 — Tạo zone

Mẫu chung cho mỗi zone:
```
$zone = new WC_Shipping_Zone();
$zone->set_zone_name('<Tên>');
$zone->save();
foreach ($codes as $code) { $zone->add_location($code, 'state'); }
$zone->save();
```

| Zone | Method 1 — Flat rate | Method 2 — Free shipping |
|---|---|---|
| **Miền Bắc** | title "Phí vận chuyển Miền Bắc", cost `30000`, type `order`, tax_status `none` | title "Miễn phí vận chuyển Miền Bắc (đơn từ 699.000đ)", requires `min_amount`, min_amount `699000`, tax_status `none` |
| **Miền Trung** | title "Phí vận chuyển Miền Trung", cost `40000` | title "Miễn phí vận chuyển Miền Trung (đơn từ 699.000đ)", min `699000` |
| **Miền Nam** | title "Phí vận chuyển Miền Nam", cost `45000` | title "Miễn phí vận chuyển Miền Nam (đơn từ 699.000đ)", min `699000` |
| **Toàn Quốc (Mặc định)** | — | title "Miễn phí vận chuyển toàn quốc (đơn từ 699.000đ)", min `699000` |

**Quy trình thêm method:**
```
$instance_id = $zone->add_shipping_method('flat_rate');   // hoặc 'free_shipping'
$method = WC_Shipping_Zones::get_shipping_method($instance_id);
$method->set_post_data([...]);
$method->process_admin_options();
```

### A.3.4. Bước 4 — Xác nhận
In từng zone: tên, số location, số method.

---

## A.4. `scripts/configure-dokan.php` — Option Dokan Lite

| Option | Key | Giá trị | Ý nghĩa |
|---|---|---|---|
| `dokan_general` | `dashboard` | `0` | Ẩn dashboard Dokan mặc định (dùng menu C2C Admin) |
| | `my_orders` | `0` | Ẩn my-orders mặc định |
| `dokan_selling` | `new_seller_enable_selling` | `on` | Seller mới được bán ngay |
| | `one_step_product_create` | `on` | Tạo sản phẩm 1 bước |
| | `product_status` | `pending` | *(bị override bởi mu-plugin → publish)* |
| | `disable_product_popup` | `off` | — |
| `dokan_appearance` | `store_map` | `off` | Tắt bản đồ (Lite không hỗ trợ) |
| | `contact_seller` | `on` | Bật liên hệ người bán |
| | `store_header_template` | `default` | — |
| `dokan_pages_created` | — | `1` | Đánh dấu đã tạo trang |

> `dokan_selling['product_status'] = 'pending'` bị **vô hiệu hoá** vì `vendor-book-submission.php` gọi `$product->set_status('publish')` trực tiếp → sách đăng qua form vendor hiện ngay, không chờ duyệt.

---

## A.5. `scripts/configure-pages.php` — Trang nền

### A.5.1. Helper `csc_page($title, $content, $option_key = '')`

| Bước | Logic |
|---|---|
| 1 | `get_posts(['post_type'=>'page','title'=>$title,'post_status'=>'any','posts_per_page'=>1,'fields'=>'ids'])` |
| 2 | Có kết quả → `get_post($found[0])` → dùng ID hiện có |
| 3 | Không có → `wp_insert_post(['post_title'=>$title,'post_content'=>$content,'post_status'=>'publish','post_type'=>'page','post_author'=>1])` |
| 4 | Nếu `$option_key` khác rỗng → `update_option($option_key, $id)` |
| 5 | `return (int) $id` |

### A.5.2. Danh sách trang

| Trang | Nội dung | Option liên kết |
|---|---|---|
| **Trang chủ** | `<h1>Chợ Sách Cũ</h1>` + `<p>Sàn C2C để sinh viên và độc giả mua bán sách đã qua sử dụng: sách giáo khoa, giáo trình, văn học, kinh tế, kỹ năng, ngoại ngữ và truyện tranh.</p>` + shortcode `[products limit="8" columns="4" orderby="date"]` | `page_on_front`; `show_on_front = page` |
| **Cửa hàng** | `wc_get_page_id('shop')` → `wp_update_post` đổi title thành "Cửa hàng" | `woocommerce_shop_page_id` (mặc định WC) |
| **Giới thiệu** | "Chợ Sách Cũ là website bài tập mô hình C2C: người bán đăng sách cũ, người mua tìm kiếm, lọc và đặt hàng." + "Dữ liệu và tài khoản trên site này chỉ dùng để thử nghiệm local, không phải dữ liệu khách hàng thật." | — |
| **Đăng Sách** | `[vendor_add_book]` | `vendor_add_book_page_id` |

---

## A.6. `scripts/configure-account-pages.php` — Tài khoản & Endpoint

### A.6.1. Trang My Account

| Bước | Logic |
|---|---|
| 1 | Tìm `get_page_by_title('Tài khoản của tôi')`; nếu null thì `get_page_by_title('My Account')` |
| 2 | Nếu vẫn null → `wp_insert_post(['post_title'=>'Tài khoản của tôi','post_content'=>'[woocommerce_my_account]','post_status'=>'publish','post_author'=>1])` |
| 3 | Nếu đã có → `wp_update_post` đặt lại title "Tài khoản của tôi", content `[woocommerce_my_account]`, status `publish` |
| 4 | `update_option('woocommerce_myaccount_page_id', $page_id)` |
| 5 | `update_option('woocommerce_myaccount_menu_order', 1)` |

### A.6.2. Endpoint (10) — đổi sang tiếng Việt

| Key | Slug |
|---|---|
| `orders` | `don-hang` |
| `view-order` | `chi-tiet-don-hang` |
| `downloads` | `tai-xuong` |
| `edit-account` | `thong-tin-tai-khoan` |
| `edit-address` | `dia-chi` |
| `payment-methods` | `phuong-thuc-thanh-toan` |
| `add-payment-method` | `them-phuong-thuc-thanh-toan` |
| `remove-payment-method` | `xoa-phuong-thuc-thanh-toan` |
| `customer-logout` | `dang-xuat` |
| `lost-password` | `quen-mat-khau` |

Logic: `if (get_option('woocommerce_'.$key.'_endpoint') !== $slug) update_option(...)` — chỉ ghi khi khác.

### A.6.3. Dokan Dashboard

| Bước | Logic |
|---|---|
| 1 | Tìm page "Dashboard"; nếu không có, thử lần lượt "Cửa hàng của tôi", "Vendor Dashboard", "Kênh người bán" |
| 2 | Điều kiện tạo mới: page không tồn tại **HOẶC** `post_content` không chứa `[dokan-dashboard]` |
| 3 | Tạo: title "Kênh người bán", content `[dokan-dashboard]`, publish, author 1 |
| 4 | `update_option('dokan_pages', ['dashboard' => $dashboard_page_id])` |
| 5 | `update_option('dokan_route_base', 'cua-hang')` |

### A.6.4. Option Dokan bổ sung

| Option | Key | Giá trị |
|---|---|---|
| `dokan_general` | `dashboard` | `$dashboard_page_id` |
| | `enable_pricing` | `on` |
| | `enable_report` | `on` |
| `dokan_selling` | `new_seller_enable_selling` | `on` |
| | `order_status_change` | `on` |
| | `enable_tos` | `on` |
| | `store_terms_and_conditions` | 4 điều khoản (xem bên dưới) |
| `dokan_withdraw` | `withdraw_order_status` | `['completed','processing']` |
| | `withdraw_limit` | `50000` |
| | `withdraw_threshold` | `0` |
| | `payment_methods` | `['bank_transfer']` |

**Nội dung `store_terms_and_conditions`:**
> Khi đăng bán sách trên Chợ Sách Cũ, bạn đồng ý với các điều khoản sau:
> 1. Sách đăng bán phải là sách đã qua sử dụng, không phải sách lậu.
> 2. Mô tả tình trạng sách phải trung thực.
> 3. Giao hàng trong vòng 3 ngày làm việc sau khi xác nhận đơn.
> 4. Chịu trách nhiệm về chất lượng sách đăng bán.

### A.6.5. Đăng ký / đăng nhập

| Option | Giá trị |
|---|---|
| `woocommerce_enable_myaccount_registration` | `yes` |
| `woocommerce_registration_generate_username` | `yes` |
| `woocommerce_registration_generate_password` | `yes` |
| `woocommerce_enable_signup_and_login_from_checkout` | `yes` |
| `woocommerce_enable_customer_creation_on_order_created` | `yes` |

Cuối script: `flush_rewrite_rules()` + in danh sách trang & endpoint.

---

## A.7. `scripts/configure-coupons.php` — Coupon khuyến mãi

### A.7.1. Dữ liệu 4 coupon

| Mã | `discount_type` | `discount_amount` | `minimum_amount` | `maximum_amount` | `usage_limit` | `usage_limit_per_user` | `expiry_days` | `free_shipping` |
|---|---|---|---|---|---|---|---|---|
| `SINHVIENMOI` | `percent` | `10` | 0 | 50000 | 1 | 1 | 30 | — |
| `FREESHIP` | `fixed_cart` | `0` | 200000 | 0 | 100 | 3 | 60 | ✓ |
| `GIAM50K` | `fixed_cart` | `50000` | 0 | 0 | 200 | 2 | 45 | — |
| `BLACKFRIDAY20` | `percent` | `20` | 0 | 0 | 0 (không giới hạn) | 1 | 0 (set khi kích hoạt) | — |

### A.7.2. Hàm `csc_create_coupon($data)`

| Bước | Logic |
|---|---|
| 1 | Tìm `get_posts(['post_type'=>'shop_coupon','title'=>$data['code'],...])` → đã tồn tại → log "đã tồn tại, bỏ qua" và return |
| 2 | `wp_insert_post(['post_title'=>$data['code'],'post_type'=>'shop_coupon','post_status'=>'publish'])` |
| 3 | Dựng `$coupon_data` từ mảng input |
| 4 | Nếu `discount_amount == 0` → `$coupon_data['free_shipping'] = true` |
| 5 | `date_expires` = `date('Y-m-d', strtotime('+'.$expiry_days.' days'))` nếu `expiry_days > 0`, ngược lại `0` |
| 6 | `foreach ($coupon_data as $k => $v) update_post_meta($coupon_id, $k, $v)` |
| 7 | Log "Đã tạo coupon: {code}" |

### A.7.3. Trang chiếu khuyến mãi

Helper `csc_cli_log()` / `csc_cli_success()` — dùng `WP_CLI::log` nếu có, ngược lại `echo`.

Script cũng sinh HTML hiển thị **4 thẻ coupon** (coupon-card) cho trang `/khuyen-mai/`, mỗi thẻ gồm header (mã coupon) + body (mô tả, điều kiện, hạn dùng).

---

## A.8. `scripts/seed-products.php` & `seed-orders.php`

**Mục đích:** tạo dữ liệu mẫu để demo và kiểm thử nhanh mà không cần nhập tay.

| Script | Tạo |
|---|---|
| `seed-products.php` | User: admin, 3 vendor (`vendor_sgk_demo`, `vendor_vanhoc_demo`, `vendor_ngoaingu_demo`), 4 customer (`buyer_demo`, `customer_hanoi`, `customer_hcm`, `customer_danang`) + danh mục sản phẩm + sản phẩm sách (có tác giả, tình trạng, NXB, năm xuất bản, giá VND, ảnh bìa) |
| `seed-orders.php` | Đơn hàng mẫu ở nhiều trạng thái cho các customer |
| `create-test-order.php` | Tạo 1 đơn hàng test đơn lẻ |

**Mật khẩu mẫu:** lấy từ biến môi trường trong `.env` (`VENDOR1_PASSWORD`…`VENDOR3_PASSWORD`, `BUYER_PASSWORD`) — không hard-code trong mã nguồn.

---

## A.9. `scripts/backup.sh` — Sao lưu

Dump SQL MySQL vào thư mục `backups/`. Volume WordPress: `cho-sach-cu_wp_data`. Volume MySQL: `cho-sach-cu_db_data`.

> ⚠ Không bao giờ chạy `docker compose down -v` hay `docker volume prune` — sẽ xoá toàn bộ dữ liệu bài tập.

---

# PHẦN B — MU-PLUGINS (LOGIC NGHIỆP VỤ)

## B.1. `vietnam-provinces.php` — Địa chỉ Việt Nam

### B.1.1. Đăng ký hook

| Hook | Callback | Priority |
|---|---|---|
| `woocommerce_states` | `vietnam_provinces_woocommerce_states` | **1** |
| `woocommerce_get_country_locale` | `vietnam_provinces_country_locale` | 10 |
| `default_checkout_billing_country` | `vietnam_provinces_default_country` | — |
| `default_checkout_shipping_country` | `vietnam_provinces_default_country` | — |
| `wp_head` | `vietnam_provinces_custom_css` | — |

### B.1.2. UC-1: Danh sách tỉnh/thành

**Điều kiện:** WooCommerce đang load states cho quốc gia `VN`.
**Logic:** `$states['VN'] = array( 63 mã => tên )` — chia 3 khối comment Bắc / Trung / Nam. `return $states;`
**Kết quả:** dropdown Tỉnh/Thành 63 lựa chọn thay vì ô nhập tay. Mỗi mã map 1:1 sang shipping zone → phí ship đúng 3 miền.

### B.1.3. UC-2: Cấu hình field checkout

**Điều kiện:** `woocommerce_get_country_locale` cho `VN`.
**Logic:**

| Field | required | hidden | label | placeholder | priority |
|---|---|---|---|---|---|
| `postcode` | false | **true** | — | — | — |
| `city` | false | **true** | — | — | — |
| `state` | **true** | false | `Tỉnh / Thành phố` | — | 40 |
| `address_1` | — | — | `Địa chỉ` | `Số nhà, tên đường` | 50 |
| `address_2` | — | — | `Căn hộ, phòng, tầng (tùy chọn)` | `Căn hộ, dãy phòng, đơn vị, v.v.` | 60 |

**Kết quả:** form checkout chỉ còn: Họ tên · SĐT · Tỉnh/Thành (bắt buộc) · Địa chỉ · Căn hộ/tầng (tùy chọn) · Email.

### B.1.4. UC-3: Quốc gia mặc định
`default_checkout_billing_country` + `default_checkout_shipping_country` → luôn trả `'VN'`.

### B.1.5. UC-4: CSS checkout

**Điều kiện:** `is_checkout()`.
**Logic:**
- Ẩn: `#billing_postcode_field`, `#shipping_postcode_field`, `#billing_city_field`, `#shipping_city_field` → `display:none!important`
- Select state (`#billing_state_field select`, `#shipping_state_field select`, `#calc_shipping_state_field select`): width 100%, padding 12px, radius 4px, border 1px #ccc, bg #fff, font 14px, cursor pointer
- Label: block, font-weight 500, màu #333, margin-bottom 5px
- `.woocommerce-input-wrapper`: width 100%, display block
- `#billing_state_field`, `#shipping_state_field`: margin-bottom 15px

---

## B.2. `wishlist.php` — Yêu thích

**Class:** `CSC_Wishlist` · **Const:** `COOKIE_NAME = 'csc_wishlist'`

### B.2.1. Hook đăng ký

| Hook | Callback |
|---|---|
| `wp_ajax_csc_add_to_wishlist` | `ajax_add_to_wishlist` |
| `wp_ajax_csc_remove_from_wishlist` | `ajax_remove_from_wishlist` |
| `wp_ajax_csc_get_wishlist_count` | `ajax_get_count` |
| `wp_ajax_nopriv_csc_add_to_wishlist` | `ajax_add_to_wishlist` |
| `wp_ajax_nopriv_csc_remove_from_wishlist` | `ajax_remove_from_wishlist` |
| `wp_ajax_nopriv_csc_get_wishlist_count` | `ajax_get_count` |
| `add_shortcode('csc_wishlist')` | `shortcode_wishlist` |
| `woocommerce_after_shop_loop_item` (20) | `add_wishlist_button` |
| `woocommerce_single_product_summary` (35) | `add_wishlist_button_single` |
| `wp_enqueue_scripts` | `enqueue_scripts` |
| `woocommerce_add_to_cart_fragments` | `cart_fragments` |

### B.2.2. UC-1: Đọc danh sách yêu thích — `get_wishlist()`

| Bước | Điều kiện | Hành động |
|---|---|---|
| 1 | `isset($_COOKIE['csc_wishlist'])` | `json_decode(..., true)`; nếu là array → trả về; ngược lại trả `[]` |
| 2 | `is_user_logged_in()` | `get_user_meta($user_id, 'csc_wishlist', true)`; nếu là array → trả về |
| 3 | — | trả `[]` |

> Ưu tiên cookie → user meta. Khách không đăng nhập vẫn dùng được (lưu cookie).

### B.2.3. UC-2: Ghi danh sách — `save_wishlist($wishlist)` (private)

| Bước | Hành động |
|---|---|
| 1 | `setcookie('csc_wishlist', json_encode($wishlist), time()+EXPIRY, '/')` |
| 2 | `$_COOKIE['csc_wishlist'] = json_encode($wishlist)` (để request hiện tại đọc được ngay) |
| 3 | Nếu `is_user_logged_in()` → `update_user_meta(get_current_user_id(), 'csc_wishlist', $wishlist)` |

### B.2.4. UC-3: AJAX thêm vào yêu thích — `ajax_add_to_wishlist()`

| Bước | Điều kiện | Hành động |
|---|---|---|
| 1 | `!$product_id` (intval `$_POST['product_id']`) | `wp_send_json_error('Invalid product')` |
| 2 | — | `$wishlist = get_wishlist()` |
| 3 | `!in_array($product_id, $wishlist)` | append → `save_wishlist()` → `wp_send_json_success(['message'=>'Đã thêm vào yêu thích','count'=>count($wishlist),'added'=>true])` |
| 4 | đã có | `wp_send_json_success(['message'=>'Đã có trong danh sách yêu thích','count'=>count($wishlist),'added'=>false])` |

### B.2.5. UC-4: AJAX xóa khỏi yêu thích — `ajax_remove_from_wishlist()`

| Bước | Hành động |
|---|---|
| 1 | `!$product_id` → `wp_send_json_error('Invalid product')` |
| 2 | `$wishlist = array_diff($wishlist, [$product_id])` |
| 3 | `$wishlist = array_values($wishlist)` (re-index) |
| 4 | `save_wishlist($wishlist)` |
| 5 | `wp_send_json_success(['message'=>'Đã xóa khỏi yêu thích','count'=>count($wishlist)])` |

### B.2.6. UC-5: Nút yêu thích trong danh sách sách — `add_wishlist_button()`

**Điều kiện:** hook `woocommerce_after_shop_loop_item` priority 20.
**Logic:**
```
$product_id = $product->get_id();
$wishlist = get_wishlist();
$is_in_wishlist = in_array($product_id, $wishlist);
```
**Output HTML:**
```html
<a href="#" class="csc-wishlist-btn {active}" data-product-id="{id}"
   title="{Thêm vào yêu thích | Xóa khỏi yêu thích}"
   aria-label="{cùng title}">
    <i class="{far|fas} fa-heart"></i>
</a>
```
| Biến | Giá trị |
|---|---|
| `active` | `'active'` nếu đã trong wishlist, ngược lại `''` |
| `far`/`fas` | `far` (outline) nếu chưa thích · `fas` (solid) nếu đã thích |
| `<span>` chữ | **KHÔNG có** — chỉ icon |

### B.2.7. UC-6: Nút yêu thích trang chi tiết — `add_wishlist_button_single()`

**Điều kiện:** hook `woocommerce_single_product_summary` priority 35.
**Output HTML:**
```html
<button class="csc-wishlist-btn-single csc-wishlist-btn {active}"
        data-product-id="{id}" title="{…}">
    <i class="{far|fas} fa-heart"></i>
    <span>{Thêm vào yêu thích | Xóa khỏi yêu thích}</span>
</button>
```
> Khác UC-5: dạng **button**, **giữ chữ**, CSS riêng (pill, không tròn) — xem C.2.

### B.2.8. UC-7: Nạp asset — `enqueue_scripts()`

| Asset | Handle | Version | Deps | Footer |
|---|---|---|---|---|
| `assets/wishlist.css` | `csc-wishlist` | `1.0` | `[]` | — |
| `assets/wishlist.js` | `csc-wishlist` | **`1.2`** | `['jquery']` | `true` |

**`wp_localize_script('csc-wishlist', 'cscWishlist', …)`:**

| Key | Giá trị |
|---|---|
| `ajaxUrl` | `admin_url('admin-ajax.php')` |
| `nonce` | `wp_create_nonce('csc_wishlist_nonce')` |
| `textAdded` | "Đã thêm vào yêu thích" |
| `textRemoved` | "Đã xóa khỏi yêu thích" |
| `textViewWishlist` | "Xem danh sách" |

> **Lưu ý kỹ thuật:** version `1.2` phải được bump mỗi khi sửa JS, nếu không trình duyệt sẽ dùng bản cache cũ.

### B.2.9. UC-8: Hành vi nút — `assets/wishlist.js`

**Khởi tạo:** `$(document).ready(...)` — đăng ký handler trên `document` (delegate) nên áp dụng cho nút render động.

**Handler 1 — Toggle:**
```
$(document).on('click', '.csc-wishlist-btn, .csc-wishlist-btn-single', function(e) {
    e.preventDefault();
    var $btn = $(this);
    var productId = $btn.data('product-id');
    ...
});
```

| Nhánh | Điều kiện | Request | Xử lý success |
|---|---|---|---|
| **Xóa** | `$btn.hasClass('active')` | POST `csc_remove_from_wishlist` | `removeClass('active')` · `i.removeClass('fas fa-heart').addClass('far fa-heart')` · span → "Thêm vào yêu thích" · `title` + `aria-label` → "Thêm vào yêu thích" · `showNotification(cscWishlist.textRemoved,'success')` |
| **Thêm** | ngược lại | POST `csc_add_to_wishlist` | `addClass('active')` · `i.removeClass('far fa-heart').addClass('fas fa-heart')` · span → "Xóa khỏi yêu thích" · `title` + `aria-label` → "Xóa khỏi yêu thích" · `showNotification(cscWishlist.textAdded,'success')` |

**Handler 2 — Xóa trong trang wishlist:**
```
$(document).on('click', '.csc-wishlist-remove', function() {
    var productId = $(this).data('product-id');
    var $item = $(this).closest('.csc-wishlist-item');
    // POST csc_remove_from_wishlist
    // success → $item.fadeOut(300, function(){ $(this).remove();
    //            if ($('.csc-wishlist-item').length === 0) location.reload();
    //          });
    // showNotification('Đã xóa sản phẩm khỏi danh sách yêu thích','success')
});
```

**Handler 3 — `showNotification(message, type)`:**
| Bước | Hành động |
|---|---|
| 1 | `$('.csc-notification').remove()` — xóa thông báo cũ |
| 2 | Tạo `<div class="csc-notification {type}">{message}</div>` |
| 3 | `$('body').append($notification)` |
| 4 | `animate({top:'20px', opacity:1}, 300)` |
| 5 | `setTimeout(3000)` → `animate({top:'-50px', opacity:0}, 300, → remove)` |

**CSS injected bằng JS (ngoài file .css):**
| Selector | Thuộc tính |
|---|---|
| `.csc-notification` | `position:fixed; top:-50px; right:20px; padding:15px 25px; border-radius:8px; color:#fff; font-size:14px; z-index:99999; opacity:0; box-shadow:0 4px 12px rgba(0,0,0,.15)` |
| `.csc-notification.success` | `background:#00a32a` |
| `.csc-notification.error` | `background:#d63638` |

### B.2.10. UC-9: Trang danh sách yêu thích — `shortcode_wishlist()`

**Điều kiện:** shortcode `[csc_wishlist]`.
| Trường hợp | Logic | Output |
|---|---|---|
| Wishlist rỗng | `empty($wishlist)` | Block: icon trái tim 48px màu #ccc · "Danh sách yêu thích trống" · "Bạn chưa có sản phẩm nào trong danh sách yêu thích." · nút "Tiếp tục mua sắm" → `get_permalink(wc_get_page_id('shop'))` |
| Có sản phẩm | `WP_Query(['post_type'=>'product','post__in'=>$wishlist,'posts_per_page'=>-1])` | `<div class="csc-wishlist-container">` · h2 "Danh sách yêu thích của bạn" · `<div class="csc-wishlist-products">` → `woocommerce_product_loop_start()` → mỗi item: `woocommerce_template_single_add_to_cart()` + `<button class="csc-wishlist-remove" data-product-id>` (icon thùng rác + "Xóa") + `woocommerce_template_single_price()` |

### B.2.11. UC-10: Fragment giỏ hàng — `cart_fragments()`

Hook `woocommerce_add_to_cart_fragments` → trả về fragment HTML cập nhật badge giỏ hàng sau khi thêm sản phẩm bằng AJAX.

---

## B.3. `product-comparison.php` — So sánh sản phẩm

### B.3.1. Hook đăng ký

| Hook | Callback |
|---|---|
| `add_shortcode('csc_compare')` | `csc_compare_shortcode` |
| `wp_ajax_csc_compare_data` + `wp_ajax_nopriv_csc_compare_data` | `csc_ajax_compare_data` |
| `wp_ajax_csc_add_to_cart` + `wp_ajax_nopriv_csc_add_to_cart` | `csc_compare_add_to_cart` |
| `woocommerce_after_shop_loop_item` (20) | `csc_add_compare_button` |
| `woocommerce_single_product_summary` (35) | `csc_add_compare_button_product` |
| `init` | `csc_create_compare_page` |
| `wp_footer` (5) | `csc_compare_global_assets` |

### B.3.2. UC-1: Shortcode trang so sánh — `csc_compare_shortcode($atts)`

**Attrs:** `max` (mặc định **4**).

**Output HTML:**
```html
<div class="csc-compare-container" id="csc-compare-app" data-max="4">
  <h3>📊 So Sánh Sản Phẩm</h3>
  <div class="csc-compare-loading"><p>Đang tải danh sách so sánh...</p></div>
  <div class="csc-compare-body" style="display:none;">
    <div class="compare-table-wrapper"><table class="compare-table" id="csc-compare-table"></table></div>
    <div class="compare-clear-row">
      <button class="compare-clear-all" id="csc-compare-clear">🗑️ Xóa tất cả</button>
      <a class="compare-continue" href="{permalink shop}">← Tiếp tục mua sắm</a>
    </div>
  </div>
  <div class="csc-compare-empty" style="display:none;">
    <p>📋 Chưa có sản phẩm nào để so sánh.</p>
    <p>Hãy thêm sản phẩm vào danh sách so sánh từ trang chi tiết sản phẩm.</p>
  </div>
</div>
```

**CSS nhúng (chi tiết):**

| Selector | Thuộc tính chính |
|---|---|
| `.csc-compare-container` | margin 30px 0, bg #fff, radius 12px, padding 20px, shadow `0 2px 10px rgba(0,0,0,.1)` |
| `.csc-compare-container h3` | margin-bottom 20px, color #333 |
| `.compare-table-wrapper` | `overflow-x:auto` |
| `.compare-table` | width 100%, border-collapse collapse, min-width 600px |
| `.compare-table th, td` | padding 15px, text-align center, border 1px #eee, vertical-align middle |
| `.compare-table th.compare-feature-header` | bg #f5f5f5, text-align left, bold, width 150px |
| `.compare-product-header` | position relative, bg #f9f9f9 |
| `.compare-product-header a` | display block, no underline, color #333 |
| `.compare-product-header img` | 100×100, `object-fit:cover`, margin-bottom 10px |
| `.remove-compare` | absolute top 5 right 5, nền #e2401c, tròn 24×24, font 16px |
| `.compare-table tr:nth-child(even)` | bg #f9f9f9 |
| `.compare-table td:first-child` | bold, text-align left, bg #f5f5f5 |
| `.compare-add-cart` | nền #0073aa, chữ #fff, không border |
| `.compare-clear-all` | style nút xóa tất cả |
| `.compare-continue` | link quay lại cửa hàng |

### B.3.3. UC-2: Nạp & dựng bảng — JS shortcode

**`loadCompareTable()`:**
| Bước | Điều kiện | Hành động |
|---|---|---|
| 1 | `!$('#csc-compare-app').length` | return (không có shortcode trên trang) |
| 2 | — | `ids = window.cscCompare.get()` |
| 3 | `ids.length < 2` | ẩn `.csc-compare-loading` + `.csc-compare-body`; hiện `.csc-compare-empty`; return |
| 4 | — | hiện `.csc-compare-loading`; ẩn `.csc-compare-body` + `.csc-compare-empty` |
| 5 | — | POST `csc_compare_data`, `product_ids = ids.join(',')` |
| 6 | success | ẩn loading; nếu `!response.success` hoặc `response.data.products.length < 2` → hiện empty; ngược lại `renderCompareTable(response.data)` + hiện body |
| 7 | error | ẩn loading; hiện empty |

**`renderCompareTable(data)`:**
| Phần | Logic |
|---|---|
| `<thead>` | `<th class="compare-feature-header">Tiêu chí</th>` + mỗi sản phẩm `<th class="compare-product-header">` chứa `<a href="{permalink}">` (ảnh 100×100 nếu có + `<span>{tên}</span>`) + `<button class="remove-compare" data-id="{id}">×</button>` |
| `<tbody>` | mỗi row: `<td>{label}</td>` + N `<td>{value}</td>` |
| Row cuối `.compare-action` | `<td></td>` + mỗi sản phẩm `<button class="compare-add-cart" data-id="{id}">Thêm vào giỏ</button>` |

**Event handlers (delegate trên `document`):**
| Sự kiện | Selector | Logic |
|---|---|---|
| Xóa 1 sản phẩm | `.remove-compare` | `ids = cscCompare.get().filter(i => i !== id)` → `cscCompare.set(ids)` → `loadCompareTable()` |
| Xóa tất cả | `#csc-compare-clear` | `confirm('Xóa tất cả sản phẩm khỏi danh sách so sánh?')` → `cscCompare.set([])` → `loadCompareTable()` |
| Thêm vào giỏ | `.compare-add-cart` | text → "Đang thêm..." + `prop('disabled',true)` → POST `csc_add_to_cart` → success: text "Đã thêm ✓" + `$(document.body).trigger('added_to_cart')`; fail/error: text "Thử lại" + enable lại |

### B.3.4. UC-3: AJAX dữ liệu so sánh — `csc_ajax_compare_data()`

| Bước | Logic |
|---|---|
| 1 | `$ids = sanitize_text_field($_POST['product_ids'])` |
| 2 | `array_filter(array_map('intval', explode(',', $ids)))` |
| 3 | Rỗng → `wp_send_json_error()` |
| 4 | `foreach ($id_array as $id) { $p = wc_get_product($id); if ($p) $products[] = $p; }` |
| 5 | `count($products) < 2` → `wp_send_json_error()` |
| 6 | Dựng `$product_data[]` — mỗi phần tử: `id`, `name` (`get_name()`), `permalink` (`get_permalink()`), `image` (`wp_get_attachment_url(get_image_id())` hoặc `wc_placeholder_img_src()`) |
| 7 | Dựng `$rows[]` — **9 tiêu chí** (xem bảng dưới) |
| 8 | `wp_send_json_success(['products'=>$product_data, 'rows'=>$rows])` |

**9 tiêu chí so sánh:**

| # | Label | Nguồn dữ liệu | Giá trị hiển thị |
|---|---|---|---|
| 1 | `Giá` | `$x->get_price_html()` | HTML giá có định dạng VND |
| 2 | `Tình trạng` | `$x->is_in_stock()` | `✓ Còn hàng` / `✗ Hết hàng` |
| 3 | `Danh mục` | `wp_get_post_terms($id,'product_cat')` | `implode(', ', wp_list_pluck($c,'name'))` hoặc `-` |
| 4 | `Tác giả` | meta `_book_author` | text hoặc `-` |
| 5 | `Tình trạng sách` | meta `_book_condition` | `new`→Mới, `like_new`→Như mới, `good`→Tốt, `acceptable`→Chấp nhận được; khác → `-` |
| 6 | `Nhà xuất bản` | meta `_book_publisher` | text hoặc `-` |
| 7 | `Năm xuất bản` | meta `_book_year` | số hoặc `-` |
| 8 | `Mô tả` | `$x->get_description()` | `wp_trim_words(desc, 20)` hoặc `-` |
| 9 | *(hàng hành động)* | — | Nút "Thêm vào giỏ" |

### B.3.5. UC-4: AJAX thêm giỏ — `csc_compare_add_to_cart()`

| Bước | Logic |
|---|---|
| 1 | `!isset($_POST['product_id'])` → `wp_send_json_error()` |
| 2 | `$product_id = intval($_POST['product_id'])` |
| 3 | `WC()->cart->add_to_cart($product_id)` |
| 4 | `wp_send_json_success()` |

### B.3.6. UC-5: Nút so sánh trong danh sách sách — `csc_add_compare_button()`

**Điều kiện:** `woocommerce_after_shop_loop_item` priority 20.
**Output:**
```php
echo '<button type="button" class="csc-add-compare" data-id="'.esc_attr($product->get_id()).'" title="Thêm vào so sánh">'
    . csc_compare_icon_svg() . '<span>So sánh</span></button>';
```

### B.3.7. UC-6: Nút so sánh trang chi tiết — `csc_add_compare_button_product()`
```php
echo '<button type="button" class="csc-add-compare-product" data-id="'.esc_attr($product->get_id()).'">'
    . csc_compare_icon_svg() . '<span>Thêm vào so sánh</span></button>';
```

### B.3.8. UC-7: Icon SVG — `csc_compare_icon_svg()`

**Mục đích:** thay emoji `⚖️` — vì WordPress tự động convert emoji thành `<img class="emoji">`, gây **icon trùng** khi kết hợp với `::before` trong CSS.

```html
<svg class="csc-compare-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
     width="15" height="15" fill="currentColor" aria-hidden="true" focusable="false">
  <path d="M12 3a1 1 0 0 1 1 1v1.07a7 7 0 0 1 4.5 2.68l.93-1.6a1 1 0 1 1 1.74.98l-.85 1.47A7 7 0 0 1 21 12a1 1 0 0 1-2 0 5 5 0 0 0-4.2-4.92A7 7 0 0 1 13 9.46v9.05a3 3 0 0 1-2 2.83V22a1 1 0 0 1-2 0v-.66A3 3 0 0 1 7 19.5v-1.05a3 3 0 0 1-2-2.83V9.46a7 7 0 0 1-2.32-1.96l-.85 1.47a1 1 0 0 1-1.74-.98l.93-1.6A7 7 0 0 1 6.5 5.07V4a1 1 0 0 1 2 0v1.07A7 7 0 0 1 11 5.07V4a1 1 0 0 1 1-1z"/>
</svg>
```

**Kết quả:** nút có **đúng 1 icon**, không bị WP convert, luôn render giống nhau trên mọi trình duyệt.

### B.3.9. UC-8: Tạo page so sánh — `csc_create_compare_page()` (init)

| Bước | Logic |
|---|---|
| 1 | `get_page_by_path('so-sanh-san-pham')` |
| 2 | Null → `wp_insert_post(['post_title'=>'So Sánh Sản Phẩm','post_content'=>'[csc_compare]','post_status'=>'publish','post_type'=>'page'])` |

### B.3.10. UC-9: Asset toàn cục — `csc_compare_global_assets()` (wp_footer, 5)

Output 2 thẻ:
```html
<style id="csc-compare-css">…</style>
<script id="csc-compare-js">…</script>
```

**CSS global (`.csc-add-compare`):** nền `#f0f0f0`, border 1px #ddd, padding 5/10, margin 5, radius 4, inline-block; `:hover` → nền #0073aa chữ #fff; `.is-added` → nền #0073aa chữ #fff.
**Badge `.csc-compare-count`:** fixed, bottom 20px, right 20px, nền #0073aa, chữ #fff, padding 10/18, radius 30px, shadow, z-index 999, cursor pointer, font 14px, `display:none` mặc định.

**JS global:**

| Hàm | Logic |
|---|---|
| `getCompare()` | `localStorage.getItem('csc_compare')` → `JSON.parse` → `Array.isArray` → `.map(Number)`; try/catch → `[]` |
| `set(arr)` | `localStorage.setItem('csc_compare', JSON.stringify(arr))` → `updateBadge()` |
| `updateBadge()` | cập nhật badge đếm; ẩn nếu = 0 |
| `toggle(id)` | có trong list → `splice` → `set` → return `false`; đủ `max` → `alert('Chỉ có thể so sánh tối đa 4 sản phẩm cùng lúc!')` → return `null`; ngược lại thêm → `set` → return `true` |
| Export `window.cscCompare` | `{ get, set, max: 4, toggle }` |
| **Đồng bộ nút** | Mỗi `.csc-add-compare, .csc-add-compare-product`: nếu `id` trong list → `addClass('is-added')`, `span.text = 'Đã thêm so sánh'`; ngược lại → `removeClass('is-added')`, `span.text = 'So sánh'`. *(Fallback khi không có `span`: `.html('⚖️ So sánh')`)* |

---

## B.4. `vendor-book-submission.php` — Đăng sách

### B.4.1. Hook đăng ký

| Hook | Callback |
|---|---|
| `add_shortcode('vendor_add_book')` | `vendor_add_book_shortcode` |
| `dokan_get_endpoints` | `vendor_add_dokan_endpoint` |
| `dokan_load_custom_template` | `vendor_load_add_book_template` |
| `dokan_get_dashboard_nav` | `vendor_add_book_nav` |

### B.4.2. UC-1: Kiểm tra quyền đăng sách

**Điều kiện vào shortcode:**

| Bước | Điều kiện | Kết quả |
|---|---|---|
| 1 | `!is_user_logged_in()` | `<div class="woocommerce-message">Vui lòng <a href="wp_login_url()">đăng nhập</a> để đăng sách.</div>` |
| 2 | `$is_vendor` = role ∈ {`seller`,`vendor`,`administrator`} **HOẶC** `dokan_is_user_seller($user->ID)` | `true` → hiện form |
| 3 | `$is_vendor === false` | `<div class="woocommerce-error">Bạn cần là người bán để đăng sách. <a href="home_url('/seller-registration')">Đăng ký bán hàng ngay</a></div>` |

### B.4.3. UC-2: Xử lý submit

**Điều kiện:** `isset($_POST['vendor_add_book_nonce'])` **AND** `wp_verify_nonce($_POST['vendor_add_book_nonce'], 'vendor_add_book')`

| Kết quả | Hành động |
|---|---|
| `WP_Error` | `$error = $result->get_error_message()` → hiện `<div class="woocommerce-error">` |
| Thành công | `$message = 'Sách đã được đăng thành công! <a href="get_edit_post_link($id)">Chỉnh sửa</a> \| <a href="get_permalink($id)">Xem</a>'` → hiện `<div class="woocommerce-message">` |

### B.4.4. UC-3: Form đăng sách (12 trường + 3 ảnh gallery)

**Form:** `id="vendor-add-book-form"`, `method="post"`, `enctype="multipart/form-data"`, nonce field `vendor_add_book_nonce`.

| # | Field name | Loại | Bắt buộc | Ràng buộc / Option | Label |
|---|---|---|:-:|---|---|
| 1 | `book_title` | text | ✓ | — | Tiêu đề sách |
| 2 | `book_author` | text | ✓ | — | Tác giả |
| 3 | `book_price` | number | ✓ | `min=1000` `step=1000` | Giá bán (VNĐ) |
| 4 | `book_regular_price` | number | — | `min=1000` `step=1000` | Giá gốc (VNĐ) |
| 5 | `book_condition` | select | — | `new` Mới · `like_new` Như mới · `good` Tốt · `acceptable` Chấp nhận được | Tình trạng |
| 6 | `book_quantity` | number | — | `min=1`, value `1` | Số lượng |
| 7 | `book_category` | dropdown | ✓ | `wp_dropdown_categories(['taxonomy'=>'product_cat', 'show_option_none'=>'— Chọn danh mục —', 'required'=>true])` | Danh mục |
| 8 | `book_publisher` | text | — | — | Nhà xuất bản |
| 9 | `book_year` | number | — | `min=1900` `max=date('Y')` | Năm xuất bản |
| 10 | `book_description` | textarea (rows=5) | — | — | Mô tả sách |
| 11 | `book_image` | file | ✓ | `accept="image/*"`, tối đa 5MB | Ảnh bìa sách |
| 12 | `book_gallery_1` | file | — | `accept="image/*"` | Ảnh chi tiết 1 |
| 13 | `book_gallery_2` | file | — | `accept="image/*"` | Ảnh chi tiết 2 |
| 14 | `book_gallery_3` | file | — | `accept="image/*"` | Ảnh chi tiết 3 |

**Nút submit:** `type="submit"`, `class="woocommerce-Button button"`, `name="vendor_add_book_submit"`, nhãn "📖 Đăng Sách".

**CSS nhúng (container):** max-width 800px, margin 30px auto, padding 30px, bg #fff, radius 8px, shadow; h2 border-bottom 2px #0073aa; `.form-row` margin-bottom 20px; `.form-row-group` flex gap 20px (mỗi con flex 1); label block bold #333; `.required` màu #e2401c; input/select/textarea width 100% padding 12px border 1px #ddd radius 4px; focus border #0073aa; `.submit-row button` nền #0073aa chữ #fff padding 15/40 font 16px; ≤600px `.form-row-group` chuyển column.

**JS:**
| Sự kiện | Logic |
|---|---|
| `#book_image.change` | `FileReader.readAsDataURL(file)` → `$('#image-preview').html('<img src="…" alt="Preview">')` |
| `#vendor-add-book-form.submit` | `price < 1000` → `alert('Giá sách phải từ 1,000 VNĐ trở lên')` + `return false`; file bìa > 5MB → `alert('Ảnh bìa không được vượt quá 5MB')` + `return false` |

### B.4.5. UC-4: Xử lý tạo sản phẩm — `vendor_process_book_submission($data, $vendor_id)`

| Bước | Logic | Kết quả |
|---|---|---|
| 1 | `empty($data['book_title']) \|\| empty($data['book_price']) \|\| empty($data['book_category'])` | `WP_Error('missing_fields','Vui lòng điền đầy đủ thông tin bắt buộc')` |
| 2 | `$product = new WC_Product_Simple()` | — |
| 3 | `set_name(sanitize_text_field($data['book_title']))` | — |
| 4 | **`set_status('publish')`** | Sách **xuất bản ngay**, không chờ duyệt |
| 5 | `set_regular_price((string) floatval($data['book_price']))` + `set_price(...)` | Giá bán |
| 6 | Nếu có `book_regular_price` | `set_regular_price(floatval(...))` — giá gốc (để hiện giá gạch ngang) |
| 7 | Nếu có `book_quantity` | `set_stock_quantity(intval(...))` + `set_manage_stock(true)` |
| 8 | Nếu có `book_description` | `set_description(wp_kses_post(...))` |
| 9 | — | `set_short_description(sanitize_text_field($data['book_author']))` — tác giả hiện dưới tên sách |
| 10 | `$product->save()` | `$product_id`; 0 → `WP_Error('save_error','Không thể lưu sản phẩm')` |
| 11 | `wp_update_post(['ID'=>$product_id,'post_author'=>$vendor_id])` | Sách thuộc về vendor |
| 12 | `wp_set_object_terms($product_id, intval($data['book_category']), 'product_cat')` | Gán danh mục |
| 13 | Nếu `$_FILES['book_image']['name']` | `vendor_upload_product_image($product_id,'book_image')` → `set_post_thumbnail($product_id, $attachment_id)` |
| 14 | Loop i=1..3 field `book_gallery_{i}` | `vendor_upload_product_image()` → `$gallery_ids[]` |
| 15 | `$gallery_ids` không rỗng | `update_post_meta($product_id,'_product_image_gallery', implode(',', $gallery_ids))` |
| 16 | — | `update_post_meta($product_id,'_book_author', sanitize_text_field(...))` |
| 17 | — | `update_post_meta($product_id,'_book_condition', sanitize_text_field(...))` |
| 18 | Nếu có publisher | `update_post_meta($product_id,'_book_publisher', ...)` |
| 19 | Nếu có year | `update_post_meta($product_id,'_book_year', intval(...))` |
| 20 | — | `return $product_id` |

**Meta sản phẩm sinh ra:** `_book_author`, `_book_condition`, `_book_publisher`, `_book_year`, `_product_image_gallery`, `_product_image_gallery` thumbnail.

### B.4.6. UC-5: Upload ảnh — `vendor_upload_product_image($product_id, $file_key)`

| Bước | Điều kiện | Kết quả |
|---|---|---|
| 1 | — | `require_once` `wp-admin/includes/image.php`, `file.php`, `media.php` |
| 2 | `$_FILES[$file_key]['error'] !== UPLOAD_ERR_OK` | `WP_Error('upload_error','Lỗi upload ảnh')` |
| 3 | `mime_content_type($_FILES[$file_key]['tmp_name'])` ∉ `['image/jpeg','image/png','image/webp','image/gif']` | `WP_Error('invalid_file','Chỉ chấp nhận file ảnh (JPG, PNG, WebP, GIF)')` |
| 4 | — | `media_handle_upload($file_key, $product_id)` → trả `attachment_id` hoặc `WP_Error` |

### B.4.7. UC-6: Endpoint Dokan "Đăng Sách"

| UC | Hook | Logic |
|---|---|---|
| Thêm endpoint | `dokan_get_endpoints` | `$endpoints['add-book'] = 'add-book'` |
| Render template | `dokan_load_custom_template` | nếu `isset($query_vars['add-book'])` → `wp_enqueue_media()` → output `<div class="dokan-dashboard-wrap">` + `do_action('dokan_dashboard_content_start')` + `<div class="dokan-dashboard-content">` + `do_shortcode('[vendor_add_book]')` + `do_action('dokan_dashboard_content_end')` → `exit` |
| Menu vendor | `dokan_get_dashboard_nav` | `$nav['add-book'] = ['title'=>'📖 Đăng Sách','icon'=>'<i class="fa fa-plus"></i>','url'=>dokan_get_navigation_url('add-book'),'pos'=>50]` |

---

## B.5. `seller-registration.php` — Đăng ký bán hàng

### B.5.1. Hook đăng ký
| Hook | Callback |
|---|---|
| `add_shortcode('csc_seller_register')` | `csc_seller_register_shortcode` |
| `init` | `csc_create_seller_register_page` |

### B.5.2. UC-1: Kiểm tra trạng thái người dùng

| Bước | Điều kiện | Kết quả |
|---|---|---|
| 1 | `!is_user_logged_in()` | `<div class="woocommerce-info">Vui lòng <a href="wp_login_url(home_url('/seller-registration'))">đăng nhập</a> để đăng ký bán hàng.</div>` |
| 2 | `dokan_is_user_seller($user_id)` | `<div class="woocommerce-message">✅ Bạn đã là người bán! <a href="home_url('/dang-sach')">Đăng sách ngay</a></div>` |
| 3 | Có nonce hợp lệ | Xử lý đăng ký (UC-3) |
| 4 | Thành công | `<div class="woocommerce-message">🎉 Chúc mừng! Bạn đã đăng ký bán hàng thành công!</div>` + `<script>window.location.href = "home_url('/dang-sach')";</script>` |

**Điều kiện nonce:** `isset($_POST['csc_seller_register_nonce'])` **AND** `wp_verify_nonce($_POST['csc_seller_register_nonce'], 'csc_seller_register')`

### B.5.3. UC-2: Form đăng ký (3 nhóm)

**Form:** `method="post"`, class `seller-register-form`, nonce field `csc_seller_register_nonce`.

**Nhóm 1 — Thông tin cửa hàng**

| Field | Loại | Bắt buộc | Chi tiết |
|---|---|:-:|---|
| `store_name` | text | ✓ | value mặc định = `{display_name} Bookstore` |
| `store_url` | text | — | placeholder "shop-sach-cu-abc (không dấu, không khoảng trắng)"; hiển thị preview `home_url('/') + <span id="store-url-preview">` |
| `store_description` | textarea (rows=3) | — | "Giới thiệu ngắn về cửa hàng của bạn…" |

**Nhóm 2 — Thông tin liên hệ**

| Field | Loại | Bắt buộc | Chi tiết |
|---|---|:-:|---|
| `phone` | tel | ✓ | `pattern="[0-9]{10,11}"`, placeholder "0xxx xxx xxx" |
| `city` | select | — | 63 tỉnh/thành Việt Nam |
| `address` | text | — | — |

**Nhóm 3 — Điều khoản**
| Field | Loại | Bắt buộc |
|---|---|:-:|
| `agree_terms` | checkbox | ✓ |

### B.5.4. UC-3: Xử lý đăng ký — `csc_process_seller_registration($user_id, $data)`

| Bước | Điều kiện | Hành động |
|---|---|---|
| 1 | `empty($data['store_name'])` | `WP_Error('missing_store_name','Vui lòng nhận tên cửa hàng')` |
| 2 | `empty($data['phone'])` | `WP_Error('missing_phone','Vui lòng nhập số điện thoại')` |
| 3 | `empty($data['agree_terms'])` | `WP_Error('missing_terms','Bạn cần đồng ý với điều khoản')` |
| 4 | — | `sanitize_text_field(store_name)` |
| 5 | — | `store_url` → `sanitize_title()`; nếu rỗng → `sanitize_title($store_name)` |
| 6 | — | `sanitize_textarea_field(store_description)`, `sanitize_text_field(phone/city/address)` |
| 7 | — | `update_user_meta($user_id,'dokan_profile_settings', ['store_name'=>…, 'social'=>[], 'payment'=>[], 'phone'=>…, 'show_email'=>'no', 'address'=>['street_1'=>$address,'city'=>$city,'country'=>'VN'], 'dokan_category'=>[]])` |
| 8 | — | `update_user_meta($user_id,'dokan_store_name',$store_name)` |
| 9 | — | `update_user_meta($user_id,'store_name',$store_name)` |
| 10 | — | `update_user_meta($user_id,'dokan_enabled','1')` ← **tự duyệt, không cần admin** |
| 11 | — | `update_user_meta($user_id,'dokan_registration_method','web')` |
| 12 | — | `(new WP_User($user_id))->add_role('seller')` |
| 13 | — | `return true` |

### B.5.5. UC-4: Tạo page — `csc_create_seller_register_page()` (init)
`get_page_by_path('seller-registration')` null → `wp_insert_post(['post_title'=>'Đăng Ký Bán Hàng','post_name'=>'seller-registration','post_content'=>'[csc_seller_register]','post_status'=>'publish','post_type'=>'page'])`

---

## B.6. `admin-book-management.php` — Dashboard Admin

### B.6.1. Hook đăng ký

| Hook | Callback | Priority |
|---|---|---|
| `admin_menu` | `csc_hide_dokan_menu` | 99 |
| `wp_nav_menu_items` | `csc_hide_vendor_menu_frontend` | 10 (2 tham số) |
| `wp_head` | `csc_hide_vendor_menu_css` | — |
| `wp_footer` | `csc_hide_vendor_menu_css_footer` | 99 |
| `template_redirect` | `csc_redirect_vendor_onboarding` | — |
| `dokan_get_dashboard_nav` | `csc_hide_dokan_nav_for_admin` | 10 |
| `wp` | `csc_disable_dokan_sidebar` | 1 |
| `admin_menu` | `csc_admin_menu` | — |
| `wp_ajax_csc_get_user_data` | `csc_ajax_get_user_data` | — |

### B.6.2. UC-1: Ẩn menu backend cho Admin

**Điều kiện:** `current_user_can('manage_options')`.
**Hành động:** `remove_menu_page('dokan')`, `remove_menu_page('edit.php')`, `remove_menu_page('edit-comments.php')`, `remove_menu_page('tools.php')`.
**Kết quả:** Admin không thấy menu Dokan, Bài viết, Bình luận, Công cụ — chỉ còn menu "C2C Admin" + WooCommerce gốc.

### B.6.3. UC-2: Ẩn menu thừa ở frontend (DOM parsing)

**Hàm:** `csc_hide_vendor_menu_frontend($items, $args)`

| Bước | Logic |
|---|---|
| 1 | `empty($items)` → trả nguyên `$items` |
| 2 | `$hide_slugs = ['vendor-onboarding','store-listing','kenh-nguoi-ban']` |
| 3 | `new DOMDocument()`; `libxml_use_internal_errors(true)`; `loadHTML('<?xml encoding="UTF-8"><div id="csc-menu-wrapper">' . $items . '</div>')`; `libxml_clear_errors()` |
| 4 | `$wrapper = $dom->getElementById('csc-menu-wrapper')`; null → trả nguyên `$items` |
| 5 | `foreach (iterator_to_array($wrapper->getElementsByTagName('li')) as $li)` — dùng `iterator_to_array` để an toàn khi xóa node trong vòng lặp |
| 6 | Tìm `<a>` đầu tiên trong `$li`; không có → `continue` |
| 7 | `$href = (string) $link->getAttribute('href')` |
| 8 | `$text = trim(preg_replace('/\s+/u',' ', $link->textContent))` |
| 9 | `$should_hide = false`; với mỗi slug: nếu `strpos($href,$slug) !== false` → `true`, `break` |
| 10 | Nếu chưa hide **VÀ** `$text === 'Tài khoản'` → `$should_hide = true` (giữ "Tài khoản của tôi") |
| 11 | Nếu hide → `$li->parentNode->removeChild($li)` |
| 12 | Rebuild: `foreach ($wrapper->childNodes as $child) $result .= $dom->saveHTML($child)` → `return $result` |

> **Ghi chú kỹ thuật:** trước đây dùng regex tham lam `/<li[^>]*>.*?<a[^>]*>[^<]*Tài khoản[^<]*<\/a>.*?<\/li>/is` → khớp từ `<li>` đầu đến `<li>` cuối chứa "Tài khoản", xoá **sạch 7 menu item**. Đã thay bằng `DOMDocument` an toàn.

### B.6.4. UC-3: Ẩn menu bằng CSS

**Điều kiện:** `wp_head` — áp dụng cho **mọi** user.

**Selector ẩn (kèm `display:none!important; visibility:hidden!important`):**
`a[href*="vendor-onboarding"]` · `a[href*="store-list"]` · `a[href*="kenh-nguoi-ban"]` · `a[href*="dokan-dashboard"]` · `a[href*="/kenh-nguoi-ban/"]` · `.menu-item a[href*="dokan"]` · các biến thể `#site-navigation …` và `.nav.navbar-nav …` · `li:has(> a:contains("Vendor Onboarding"))` · `li:has(> a:contains("Store List"))` · `li:has(> a:contains("Kênh người bán"))`

**Selector ẩn "Tài khoản" nhưng giữ "Tài khoản của tôi":**
```
.nav.navbar-nav li:has(> a:contains("Tài khoản")):not(:has(> a:contains("của tôi"))),
.menu li:has(> a:contains("Tài khoản")):not(:has(> a:contains("của tôi"))),
#site-navigation li:has(> a:contains("Tài khoản")):not(:has(> a:contains("của tôi")))
```

### B.6.5. UC-4: Ẩn menu bằng JS (backup)

**Điều kiện:** `wp_footer` priority 99, trong `DOMContentLoaded`.

| Bước | Logic |
|---|---|
| 1 | Selector array: `a[href*='vendor-onboarding']`, `a[href*='store-list']`, `a[href*='kenh-nguoi-ban']`, `a[href*='dokan-dashboard']` |
| 2 | Mỗi match → `el.closest('li') \|\| el.closest('.menu-item')` → `li.style.display='none'` |
| 3 | Duyệt `li.menu-item, li.page_item, nav li` → `<a>.textContent.trim() === 'Tài khoản'` → `li.style.display='none'` (so khớp **chính xác**, không đụng "Tài khoản của tôi") |
| 4 | `isAdmin = body.classList.contains('logged-in') && body.classList.contains('admin-bar')` |
| 5 | Nếu **không** phải admin → ẩn mọi `<li>` có text "Dashboard" |

### B.6.6. UC-5: Redirect trang vendor-onboarding
`csc_redirect_vendor_onboarding()` (template_redirect) → `is_page('vendor-onboarding')` → `wp_redirect(home_url()); exit;`

### B.6.7. UC-6: Dokan sidebar cho Admin
`csc_hide_dokan_nav_for_admin($nav)` → nếu `manage_options` → `return array()`; ngược lại trả `$nav` nguyên.

### B.6.8. UC-7: Menu Admin — `csc_admin_menu()`

| Menu | Slug | Callback | Icon | Position |
|---|---|---|---|---|
| **C2C Admin** (top-level, label "TD Tong quan") | `csc-dashboard` | `csc_dashboard_page` | `dashicons-dashboard` | 1 |
| Dashboard | `csc-dashboard` | `csc_dashboard_page` | — | — |
| QL Sach | `csc-books` | `csc_books_page` | — | — |
| QL Tai khoan | `csc-users` | `csc_users_page` | — | — |
| QL Don hang | `csc-orders` | `csc_orders_page` | — | — |

Tất cả dùng capability `manage_options`.

### B.6.9. UC-8: Trang Tổng quan — `csc_dashboard_page()`

**Thu thập dữ liệu:**

| Dữ liệu | Nguồn |
|---|---|
| Tổng sách đang bán | `wp_count_posts('product')->publish` |
| Chờ duyệt | `wp_count_posts('product')->pending` |
| Tổng đơn hàng | `wp_count_posts('shop_order')->publish` |
| Tổng người bán | `count(get_users(['role__in'=>['seller','vendor']]))` |
| Doanh thu | tổng `get_total()` của `wc_get_orders(['limit'=>-1,'status'=>['completed','processing']])` |
| 5 sách mới nhất | `get_posts(['post_type'=>'product','posts_per_page'=>5,'orderby'=>'date','order'=>'DESC'])` |
| 5 đơn mới nhất | `wc_get_orders(['limit'=>5,'orderby'=>'date','order'=>'DESC'])` |

**Hiển thị:**
| Thành phần | Nội dung |
|---|---|
| Header | "TD Tong quan Cua hang" + "Cho Sach Cu - He thong quan tri" |
| 5 stat card | **Sách đang bán** (blue #0073aa) · **Chờ duyệt** (orange #f39c12) · **Đơn hàng** (green #2ecc71) · **Người bán** (teal #1abc9c) · **Doanh thu** (purple #9b59b6) |
| 4 quick-link | gradient `linear-gradient(135deg,#667eea,#764ba2)` → Tất cả sách · Tài khoản · Đơn hàng · Duyệt sách (`&status=pending`) |
| Bảng mini 1 | **Sách mới nhất** — Tên sách (link edit) · Giá · Trạng thái (badge success "Da dang" / warning "Cho duyet") |
| Bảng mini 2 | **Đơn hàng gần nhất** — Mã đơn `#N` (link edit) · Khách hàng · Tổng · Trạng thái (badge theo `wc_get_order_status_name`) |

**CSS:** container max-width 1400px; stat-card hover `translateY(-3px)`; badge bo tròn 20px.

### B.6.10. UC-9: Trang QL Sách — `csc_books_page()`

**UC-9.1 — Xử lý hành động (POST, nonce `csc_books`):**

| `csc_action` | Hành động | Thông báo |
|---|---|---|
| `delete` | `wp_delete_post($book_id)` | "Da xoa sach!" |
| `publish` | `wp_update_post(['ID'=>$book_id,'post_status'=>'publish'])` | "Da xuat ban sach!" |
| `pending` | `wp_update_post(['ID'=>$book_id,'post_status'=>'pending'])` | "Da chuyen sang cho duyet!" |

**UC-9.2 — Bộ lọc & phân trang:**
| Tham số | Giá trị | Áp dụng |
|---|---|---|
| `?status=` | `publish` \| `pending` | `post_status` |
| `?s=` | chuỗi tìm kiếm | `s` |
| `?paged=` | số trang (min 1) | `paged` |

Query: `post_type=product`, `posts_per_page=20`, `orderby=date`, `order=DESC`.
Thống kê pill: **Tất cả** (publish+pending) · **Đã xuất bản** · **Chờ duyệt**.

**UC-9.3 — Bảng (8 cột):**

| Cột | Nội dung |
|---|---|
| Ảnh | `get_the_post_thumbnail_url($id,[60,60])` → `<img class="product-thumb">`; không có → `<div class="product-thumb-placeholder">B</div>` |
| Tên sách | `<strong><a href="get_edit_post_link()">` + `<small>ID: {id}</small>` |
| Tác giả | meta `_book_author` hoặc `-` |
| Giá | `number_format($product->get_price())` + `d`, màu #0073aa |
| Người bán | `get_user_by('id',$post->post_author)` → link edit user |
| Trạng thái | badge: `publish`→success/"Đã xuất bản" · `pending`→warning/"Chờ duyệt" · `draft`→secondary/"Bản nháp" |
| Ngày tạo | `date('d/m/Y', strtotime($post->post_date))` |
| Hành động | **Xem** (permalink, target blank) · **Sửa** (edit link) · **Duyệt** (POST publish — chỉ khi status ≠ publish) · **Xóa** (POST delete + `confirm('Xoa sach nay?')`) |

**UC-9.4 — CSS:** bảng bg #fff radius 10px overflow hidden; th bg #f8f9fa padding 15px; td border-bottom 1px #eee; `tr:hover` bg #f8f9fa; `.product-thumb` 60×60 object-fit cover radius 8px; pagination bo tròn 5px, trang hiện tại nền #0073aa.

### B.6.11. UC-10: Trang QL Tài khoản — `csc_users_page()`

**UC-10.1 — Xử lý hành động (POST, nonce `csc_users`):**

| `csc_user_action` | Hành động | Thông báo |
|---|---|---|
| `activate` | `update_user_meta($user_id,'dokan_enabled','on')` | "Da kich hoat tai khoan!" |
| `deactivate` | `update_user_meta($user_id,'dokan_enabled','off')` | "Da tam ngung tai khoan!" |
| `delete` | `require_once wp-admin/includes/user.php` → `wp_delete_user($user_id, 1)` (reassign ID 1) | "Da xoa tai khoan!" |

**UC-10.2 — Cập nhật thông tin (POST, nonce `update_user`):**
`wp_update_user(['ID', 'first_name', 'last_name', 'display_name', 'user_email'])` + `update_user_meta($user_id,'billing_phone',$phone)` → "Da cap nhat thong tin!"

**UC-10.3 — Bộ lọc:**
| Tham số | Logic |
|---|---|
| `?role=` | nếu có → `role` cụ thể; ngược lại `role__in = ['administrator','seller','vendor','customer','subscriber']` |
| `?s=` | `search = '*' . $search . '*'` |

Query: `orderby=registered`, `order=DESC`.
Thống kê pill: **Tất cả** · **Vendor** · **Khách hàng** · **Admin**.

**UC-10.4 — Bảng (7 cột):**

| Cột | Nội dung |
|---|---|
| Avatar | `get_avatar($user->ID, 40)` |
| Tên | `display_name` + `<small>@{user_login}</small>` |
| Email | `<a href="mailto:…">` |
| ĐT | meta `billing_phone` hoặc `-` |
| Vai trò | badge: `administrator`→đỏ #f8d7da/#721c24 · `seller`/`vendor`→xanh #cce5ff/#004085 · `customer`→xanh lá #d4edda/#155724 |
| Ngày ĐK | `date('d/m/Y', strtotime($user_registered))` |
| Hành động | **Sửa** (modal AJAX) · **Kích hoạt/Tạm ngưng** (chỉ vendor) · **Xóa** (chỉ khi `user_id != current_user_id()` **VÀ** role ≠ administrator) |

**UC-10.5 — Modal sửa thông tin (`#editModal`):**
| Bước | Logic |
|---|---|
| 1 | Click "Sửa" → `showEditModal(userId)` |
| 2 | `jQuery.ajax({url:ajaxurl, type:'POST', data:{action:'csc_get_user_data', user_id, nonce:'<wp_create_nonce("csc_user_data")>'}})` |
| 3 | Success → điền `#edit_user_id`, `#edit_first_name`, `#edit_last_name`, `#edit_display_name`, `#edit_email`, `#edit_phone` → `$('#editModal').addClass('show')` |
| 4 | Nút "Hủy" → `hideEditModal()` → `removeClass('show')` |
| 5 | Click overlay (`e.target === this`) → `hideEditModal()` |
| 6 | Nút "Lưu" → POST `update_user` + nonce `update_user` → reload trang |

CSS: overlay `position:fixed; inset 0; background rgba(0,0,0,.5); z-index 1000; display:flex; justify-content:center; align-items:center`; `.show` → `display:flex`; nội dung bg #fff padding 30px radius 10px max-width 500px max-height 90vh overflow-y auto.

**UC-10.6 — AJAX lấy thông tin user (`csc_ajax_get_user_data`):**
| Bước | Điều kiện | Kết quả |
|---|---|---|
| 1 | `!current_user_can('manage_options')` **HOẶC** `!wp_verify_nonce($_POST['nonce'],'csc_user_data')` | `wp_send_json_error()` |
| 2 | `get_user_by('id', $user_id)` null | `wp_send_json_error()` |
| 3 | — | `wp_send_json_success(['first_name','last_name','display_name','email','phone'])` |

### B.6.12. UC-11: Trang QL Đơn hàng — `csc_orders_page()`

**UC-11.1 — Xử lý hành động (POST, nonce `csc_orders`):**

| `csc_order_action` | Hành động |
|---|---|
| `processing` | `$order->update_status('processing')` |
| `completed` | `$order->update_status('completed')` |
| `cancelled` | `$order->update_status('cancelled') |

**UC-11.2 — Bộ lọc:** `?status=` · limit 20 · orderby date DESC.
Thống kê pill: **Tất cả** (`wp_count_posts('shop_order')->publish`) · **Hoàn thành** (`wc_orders_count('wc-completed')`) · **Đang xử lý** (`wc_orders_count('wc-processing')`) · **Chờ thanh toán** (`wc_orders_count('wc-pending')`).

**UC-11.3 — Bảng (7 cột):** Mã đơn `#N` (link edit) · Khách hàng (`get_user()->display_name` hoặc `get_billing_first_name()`) · Sản phẩm (`get_items()`) · Tổng tiền · Trạng thái (badge) · Ngày đặt · Hành động (3 nút đổi status).

**Badge màu theo trạng thái:** success (xanh lá) · info (xanh dương) · warning (vàng) · danger (đỏ).

---

## B.7. `custom-my-orders.php` — My Orders

**Hook:** `add_shortcode('custom-my-orders')` → `custom_my_orders_shortcode($atts, $content)`

### B.7.1. UC-1: Bảo vệ nội dung

| Bước | Điều kiện | Kết quả |
|---|---|---|
| 1 | `!is_user_logged_in()` | `<div class="woocommerce-message">Vui lòng <a href="wp_login_url()">đăng nhập</a> để xem đơn hàng của bạn.</div>` |
| 2 | `!class_exists('WooCommerce')` | `<div class="woocommerce-error">WooCommerce chưa được kích hoạt.</div>` |

### B.7.2. UC-2: Lấy đơn hàng
```
wc_get_orders(['customer_id'=>$user_id,'limit'=>-1,'orderby'=>'date','order'=>'DESC']);
```
Fallback nếu thiếu hàm → mảng rỗng.

### B.7.3. UC-3: Render bảng

| Trường hợp | Output |
|---|---|
| Rỗng | `<p class="woocommerce-no-orders-found">Bạn chưa có đơn hàng nào.</p>` |
| Có đơn | Bảng `.shop_table.shop_table_responsive.my_account_orders` |

| Cột | Nội dung |
|---|---|
| Order | `<a href="get_view_order_url()">#{order_number}</a>` + `data-title="Order"` |
| Date | `<time datetime="{ISO 8601}">` + `wc_format_datetime(get_date_created())` |
| Status | `<span class="woocommerce-label woocommerce-order-label status-{status}">` + `wc_get_order_status_name($status)` |
| Total | `get_formatted_order_total()` |
| Actions | `<a href="get_view_order_url()" class="woocommerce-button button view">View</a>` |

### B.7.4. UC-4: CSS nhúng

| Nhóm | Thuộc tính |
|---|---|
| Container | margin 20px 0; h2 font 24px margin-bottom 20px |
| Table | width 100%, border-collapse collapse, margin-bottom 30px |
| th | bg #f8f8f8, padding 12px, text-align left, bold |
| td | padding 15px 12px, border-bottom 1px #eee |
| tr:hover | bg #fafafa |
| `.order-number a` | bold, màu #0073aa, underline on hover |
| `.order-actions .button` | inline-block, padding 6/12, nền #0073aa, chữ #fff, radius 3px, 13px, margin-right 5px, hover #005177 |

**Badge trạng thái:**
| Trạng thái | Nền | Chữ |
|---|---|---|
| `completed` | #d4edda | #155724 |
| `processing` | #cce5ff | #004085 |
| `pending` | #fff3cd | #856404 |
| `on-hold` | #e2e3e5 | #383d41 |
| `cancelled` / `refunded` / `failed` | #f8d7da | #721c24 |

**Responsive ≤600px:** table/thead/tbody/tr/th/td → `display:block`; `thead tr` → `position:absolute; top:-9999px; left:-9999px`; `tr` → border 1px #ccc margin-bottom 15px; `td` → `display:block; border:none; border-bottom 1px #eee; position:relative; padding-left:50%`; `td:before` → `position:absolute; top:15px; left:10px; width:45%; padding-right:10px; white-space:nowrap; font-weight:600; content:attr(data-title)`.

---

## B.8. `login-limit.php` — Chống brute-force

**Class:** `CSC_Login_Limit`

### B.8.1. Hằng số
| Hằng | Giá trị | Ý nghĩa |
|---|---|---|
| `MAX_ATTEMPTS` | **5** | Số lần đăng nhập sai tối đa |
| `LOCKOUT_DURATION` | **15** | Số phút bị khóa |
| `TRACKING_WINDOW` | **30** | Số phút theo dõi lần thử |

### B.8.2. Hook đăng ký

| Hook | Callback | Priority / số tham số |
|---|---|---|
| `wp_login_failed` | `track_failed_login` | — |
| `authenticate` | `check_login_attempts` | 30, 3 |
| `login_errors` | `custom_login_error` | — |
| `wp_login` | `clear_failed_attempts` | — |
| `manage_users_columns` | `add_attempts_column` | — |
| `manage_users_custom_column` | `show_attempts_column` | 10, 3 |
| `wp_ajax_csc_view_login_attempts` | `ajax_view_attempts` | — |
| `admin_menu` | `add_admin_menu` | — |
| `admin_enqueue_scripts` | `enqueue_admin_assets` | — |

### B.8.3. UC-1: Lấy IP — `get_client_ip()` (private)

Thứ tự ưu tiên:
1. `$_SERVER['HTTP_CLIENT_IP']` (nếu có và khác rỗng)
2. `$_SERVER['HTTP_X_FORWARDED_FOR']`
3. `$_SERVER['REMOTE_ADDR']`
4. mặc định `''`

### B.8.4. UC-2: Khoá lưu trữ

| Hàm | Key |
|---|---|
| `get_attempt_key($login)` | `'csc_login_attempts_' . md5($login . '|' . $ip)` |
| `get_log_key($login)` | `'csc_login_log_' . sanitize_key($login)` |

### B.8.5. UC-3: Đếm lần thử — `get_attempt_count($login)`

| Bước | Logic |
|---|---|
| 1 | `$attempts = get_transient($key)`; không phải array → trả `0` |
| 2 | `$cutoff = time() - (TRACKING_WINDOW * 60)` = 30 phút trước |
| 3 | `array_filter` giữ lại timestamp `> $cutoff` (bỏ lần thử cũ) |
| 4 | `return count($attempts)` |

### B.8.6. UC-4: Thời gian khóa còn lại — `get_lockout_remaining($login)`
`$lockout = get_transient($key.'_lockout')`; không có → `0`; ngược lại `max(0, $lockout - time())` (giây).

### B.8.7. UC-5: Ghi nhận đăng nhập sai — `track_failed_login($login)`

| Bước | Logic |
|---|---|
| 1 | `empty($login) \|\| $login === ' '` → return (bỏ qua trường hợp bấm nút khi form rỗng) |
| 2 | Đọc transient `key` → mảng (nếu chưa có → `[]`) |
| 3 | `$attempts[] = time()` |
| 4 | `set_transient($key, $attempts, TRACKING_WINDOW * 60)` = 1800s |
| 5 | `$count = get_attempt_count($login)`; `log_failed_attempt($login, $count)` |
| 6 | Nếu `$count >= MAX_ATTEMPTS` (5) → `set_transient($key.'_lockout', time() + 900, 900)`; `log_lockout($login)` |

### B.8.8. UC-6: Ghi log — `log_failed_attempt($login, $attempt_count)`

| Bước | Logic |
|---|---|
| 1 | `$logs = get_option($log_key, array())` |
| 2 | Entry: `['type'=>'failed','ip'=>get_client_ip(),'user_agent'=>substr($_SERVER['HTTP_USER_AGENT'],0,200),'timestamp'=>time(),'attempt'=>$attempt_count]` |
| 3 | `$logs[] = $log_entry` |
| 4 | Nếu `count($logs) > 100` → `$logs = array_slice($logs, -100)` (giữ 100 gần nhất) |
| 5 | `update_option($log_key, $logs, false)` (không autoload) |

### B.8.9. UC-7: Ghi log khóa — `log_lockout($login)`
Entry: `['type'=>'lockout','ip','timestamp','duration'=>LOCKOUT_DURATION]` → `update_option` → `notify_admin_lockout($login)`.

### B.8.10. UC-8: Cảnh báo admin — `notify_admin_lockout($login)`

| Thành phần | Nội dung |
|---|---|
| Subject | `[{site_name}] Cảnh báo: Tài khoản bị khóa tạm thời` |
| Body | "Tài khoản '{login}' đã bị khóa tạm thời do đăng nhập sai quá {MAX_ATTEMPTS} lần." + IP + Thời gian (`current_time('d/m/Y H:i:s')`) + Thời gian mở khóa (`date('d/m/Y H:i:s', time()+900)`) + "Vào wp-admin để xem chi tiết." |
| Hàm gửi | `wp_mail(get_option('admin_email'), $subject, $message)` |

### B.8.11. UC-9: Chặn trước khi đăng nhập — `check_login_attempts($user, $username, $password)`

| Bước | Điều kiện | Hành động |
|---|---|---|
| 1 | `is_wp_error($user)` | return `$user` nguyên (bỏ qua) |
| 2 | `empty($username)` | return `$user` nguyên |
| 3 | `$lockout_remaining > 0` | `WP_Error('too_many_attempts', "<strong>LỖI:</strong> Tài khoản tạm thời bị khóa do đăng nhập sai nhiều lần.<br>Vui lòng thử lại sau <strong>{ceil(min/60)} phút</strong>.<br><br>Nếu bạn quên mật khẩu, hãy sử dụng chức năng \"Quên mật khẩu\".")` |
| 4 | — | return `$user` |

### B.8.12. UC-10: Thông báo lỗi — `custom_login_error($error)`

| Bước | Điều kiện | Hành động |
|---|---|---|
| 1 | `!is_wp_error($errors)` | return `$error` |
| 2 | `$errors->get_error_codes()` chứa `too_many_attempts` | return `$errors->get_error_message('too_many_attempts')` |
| 3 | Codes chứa `invalid_username` \| `invalid_email` \| `incorrect_password` | `$login = $_POST['log']`; `$count = get_attempt_count($login)`; `$remaining = 5 - $count`; nếu `0 < $remaining < 5` → nối `<br><br><em style="color:#666">Bạn còn {remaining} lần thử trước khi tài khoản bị khóa tạm thời.</em>` |
| 4 | — | return `$error` |

### B.8.13. UC-11: Xóa lần thử khi đăng nhập thành công — `clear_failed_attempts($login)`
| Bước | Hành động |
|---|---|
| 1 | `delete_transient($key)` |
| 2 | `delete_transient($key.'_lockout')` |
| 3 | `log_successful_login($login)` |

`log_successful_login()` → entry `['type'=>'success','ip','timestamp']`, cap 100, `update_option`.

### B.8.14. UC-12: Giao diện admin
| UC | Logic |
|---|---|
| Cột trong Users list | `add_attempts_column($columns)` → thêm `$columns['csc_login_attempts'] = 'Đăng nhập'` |
| Nội dung cột | `show_attempts_column($value, $column_name, $user_id)` → nếu `$column_name !== 'csc_login_attempts'` return nguyên; lấy user, đếm attempt hiện tại, hiển thị số lần + trạng thái |
| Menu admin | `add_admin_menu()` → trang riêng xem log đăng nhập |
| AJAX | `ajax_view_attempts()` → trả chi tiết log theo user |
| Assets | `enqueue_admin_assets()` → CSS/JS cho trang admin |

---

## B.9. `marketing-seo.php` — SEO & Marketing

### B.9.1. UC-1: Meta tags trang chủ — `csc_seo_meta_tags()` (wp_head)

**Điều kiện:** `is_front_page()`.
**Output:**

| Tag | Nội dung |
|---|---|
| `<meta name="description">` | "Chợ Sách Cũ - Sàn C2C mua bán sách đã qua sử dụng: sách giáo khoa, giáo trình, văn học, kinh tế, kỹ năng, ngoại ngữ và truyện tranh. Mua bán dễ dàng, giá cả hợp lý." |
| `<meta name="keywords">` | "sách cũ, mua bán sách, sách giáo khoa, sách secondhand, thương mại điện tử sách" |
| `<meta property="og:title">` | "Chợ Sách Cũ - Sàn C2C mua bán sách đã qua sử dụng" |
| `<meta property="og:description">` | "Sàn C2C để sinh viên và độc giả mua bán sách đã qua sử dụng với giá cả hợp lý." |
| `<meta property="og:type">` | "website" |
| `<meta property="og:url">` | `home_url()` |
| `<meta property="og:image">` | `esc_url(wc_placeholder_img_src())` |

### B.9.2. UC-2: Schema.org Product — `csc_product_schema()`

**Điều kiện:** `woocommerce_single_product_summary` priority 99.
**Output:** `<script type="application/ld+json">` chứa:

```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "{tên sách}",
  "description": "{mô tả}",
  "image": "{url ảnh}",
  "url": "{permalink}",
  "offers": {
    "@type": "Offer",
    "priceCurrency": "VND",
    "price": "{giá}",
    "availability": "https://schema.org/InStock | OutOfStock",
    "seller": { "@type": "Organization", "name": "{tên site}" }
  }
}
```
**Bổ sung:** nếu `$product->get_regular_price()` không rỗng → thêm `offers.priceValidUntil = date('Y-m-d', strtotime('+30 days'))`.
**Encode:** `json_encode($schema, JSON_UNESCAPED_UNICODE)`.

### B.9.3. UC-3: Tạo trang Thống Kê — `csc_create_analytics_page()` (init)
`get_page_by_path('analytics')` null → `wp_insert_post(['post_title'=>'Thống Kê','post_name'=>'analytics','post_content'=>'[csc_analytics_dashboard]','post_status'=>'publish','post_type'=>'page'])`

### B.9.4. UC-4: Backup metadata DB — `csc_backup_database()`

**Điều kiện:** `current_user_can('manage_options')` → ngược lại `wp_send_json_error('Unauthorized')`.
**Logic:**
```
$backup_info = [
  'created' => current_time('mysql'),
  'version' => get_bloginfo('version'),
  'plugins' => get_option('active_plugins'),
  'theme'   => wp_get_theme()->get('Name'),
];
update_option('csc_last_backup', $backup_info);
wp_send_json_success(['message'=>'Backup metadata đã được tạo','info'=>$backup_info]);
```
**nopriv:** `add_action('wp_ajax_nopriv_csc_backup_database', fn() => wp_send_json_error())` — luôn lỗi.

> **Lưu ý:** đây là **metadata backup** (ghi thông tin phiên bản/plugin/theme vào option), chưa phải dump SQL đầy đủ. Dump SQL thật dùng `scripts/backup.sh`.

### B.9.5. UC-5: Kịch bản quảng cáo Facebook — `csc_get_facebook_ad_template()`

**Output HTML** (6 mục):

| Mục | Nội dung |
|---|---|
| 1. Mục tiêu chiến dịch | Tăng nhận diện thương hiệu · Thu hút người bán mới · Tăng lượng đơn hàng |
| 2. Đối tượng mục tiêu | Độ tuổi 18–35 · Địa điểm TP.HCM/Hà Nội/Đà Nẵng và thành phố lớn · Sở thích sách, văn học, giáo dục, sinh viên · Hành vi đã mua sách online, thích tiết kiệm |
| 3. Nội dung quảng cáo | **Copy 1 (Awareness):** "📚 Sách cũ giá rẻ? Có ngay tại Chợ Sách Cũ! 🏷️ Sách giáo khoa, văn học, kỹ năng... giá chỉ từ 20K 🚚 Giao hàng toàn quốc - COD thanh toán khi nhận ✨ Bán sách cũ cũng dễ lắm! Đăng bán miễn phí, nhận tiền ngay!" — CTA: Tìm hiểu ngay<br>**Copy 2 (Conversion):** "🔓 ĐĂNG KÝ BÁN HÀNG NGAY - HOÀN TOÀ MIỄN PHÍ! ✅ Không tốn phí đăng bán ✅ Thanh toán qua VNPAY - an toàn ✅ Hỗ trợ 24/7 📖 Sách cũ của bạn có thể là kho báu của người khác!" — CTA: Đăng ký ngay - 100% miễn phí |
| 4. Ngân sách & lịch chạy | Awareness tuần 1 — 100.000đ/ngày — 10.000 reach · Consideration tuần 2-3 — 150.000đ/ngày — 5.000 clicks · Conversion tuần 4 — 200.000đ/ngày — 100 đăng ký |
| 5. A/B Test | A: ảnh sách stack cao vs B: ảnh sinh viên đọc sách · C: copy tập trung "Mua" vs D: copy tập trung "Bán" |
| 6. Đo lường | CPI < 5.000đ · CPC < 3.000đ · CPA < 30.000đ · ROAS mục tiêu 300% |

Container: `max-width 800px; margin 30px auto; padding 20px; background #fff`.

### B.9.6. UC-6: Shortcode & trang kịch bản
| Bước | Logic |
|---|---|
| Shortcode | `add_shortcode('csc_marketing_doc', fn() => csc_get_facebook_ad_template())` |
| Trang (init) | `csc_create_marketing_page()` → `get_page_by_path('kien-bang-quang-cao')` null → `wp_insert_post(['post_title'=>'Kịch Bản Quảng Cáo','post_name'=>'kien-bang-quang-cao','post_content'=>'[csc_marketing_doc]','post_status'=>'publish','post_type'=>'page'])` |

---

## B.10. `user-analytics.php` — Phân tích hành vi

### B.10.1. UC-1: Gán định danh khách — `csc_track_user_behavior()` (wp_head)

**Điều kiện:** `!is_user_logged_in()` **AND** chưa có cookie `csc_visitor_id`.
**Logic:**
```
$visitor_id = 'v_' . uniqid() . '_' . time();
setcookie('csc_visitor_id', $visitor_id, time() + DAY_IN_SECONDS, '/');
$_COOKIE['csc_visitor_id'] = $visitor_id;
```

### B.10.2. UC-2: Ghi lượt xem trang — `csc_track_pageview()`

**Điều kiện:** `wp_ajax_csc_track_pageview` + `wp_ajax_nopriv_csc_track_pageview`.
**Dữ liệu ghi:**

| Trường | Nguồn | Sanitize |
|---|---|---|
| `visitor_id` | `$_COOKIE['csc_visitor_id']` | `sanitize_text_field` |
| `user_id` | `get_current_user_id()` | — |
| `page_url` | `$_POST['page_url']` | `esc_url_raw` |
| `page_title` | `$_POST['page_title']` | `sanitize_text_field` |
| `referrer` | `$_SERVER['HTTP_REFERER']` | `esc_url_raw` |
| `timestamp` | `current_time('mysql')` | — |

**Lưu trữ:** transient `csc_page_views` — mảng; nếu `count > 1000` → `array_slice($views, -1000)`; TTL `WEEK_IN_SECONDS`.
**Kết quả:** `wp_send_json_success()`.

### B.10.3. UC-3: Ghi lượt xem sản phẩm — `csc_track_product_view()`

**Điều kiện:** `wp_ajax_csc_track_product_view` + `_nopriv_`.
| Bước | Logic |
|---|---|
| 1 | `$product_id = intval($_POST['product_id'])`; rỗng → `wp_send_json_error()` |
| 2 | `$views = get_post_meta($product_id,'_csc_views',true) ?: 0` |
| 3 | `update_post_meta($product_id,'_csc_views', intval($views)+1)` |
| 4 | Đọc cookie `csc_recent_views` (`json_decode(stripslashes(...), true)`) |
| 5 | `array_filter` bỏ id trùng → `array_unshift` thêm lên đầu → `array_slice(0,10)` |
| 6 | `setcookie('csc_recent_views', json_encode($recent_views), time()+WEEK_IN_SECONDS, '/')` |
| 7 | `wp_send_json_success(['total_views'=>intval($views)+1])` |

### B.10.4. UC-4: Dashboard thống kê — `csc_analytics_dashboard_shortcode()`

**Điều kiện:** `!current_user_can('manage_options')` → `<p>Bạn không có quyền xem thống kê.</p>`.

**Dữ liệu:**
| Biến | Nguồn |
|---|---|
| `$page_views` | `get_transient('csc_page_views') ?: []` |
| `$top_products` | `csc_get_top_viewed_products(10)` |
| `$stats` | `csc_get_analytics_stats()` |

**Output:**
| Thành phần | Nội dung |
|---|---|
| H2 | "📊 Thống Kê Hành Vi Người Dùng" |
| 3 stat card | **Tổng lượt xem sản phẩm** (`$stats['total_views']`) · **Lượt truy cập trang** (`count($page_views)`) · **Tổng sản phẩm** (`$stats['total_products']`) |
| H3 | "🏆 Top 10 Sản Phẩm Xem Nhiều Nhất" |
| Bảng | STT · Sản phẩm (link permalink) · Lượt xem |

**CSS:** stat card gradient `linear-gradient(135deg,#0073aa,#005177)`, chữ trắng, radius 12px; bảng shadow `0 2px 10px rgba(0,0,0,.1)`.

### B.10.5. UC-5: Top sản phẩm — `csc_get_top_viewed_products($limit=10)`

| Bước | Logic |
|---|---|
| 1 | `get_posts(['post_type'=>'product','posts_per_page'=>-1,'post_status'=>'publish','meta_query'=>[['key'=>'_csc_views','compare'=>'EXISTS']]])` |
| 2 | Map mỗi sản phẩm → `['product_id'=>$id,'views'=>intval(meta)]` |
| 3 | `usort` giảm dần theo `views` |
| 4 | `return array_slice($views, 0, $limit)` |

### B.10.6. UC-6: Tổng quan — `csc_get_analytics_stats()`
`wc_get_products(['limit'=>-1,'return'=>'ids'])` → tổng `_csc_views` → trả `['total_views'=>…, 'total_products'=>count($products)]`.

### B.10.7. UC-7: Script tracking — `csc_tracking_script()` (wp_footer)

**Điều kiện:** `!is_admin()`.
**Logic (trong `jQuery(document).ready`):**
1. POST `csc_track_pageview` với `page_url = window.location.href`, `page_title = document.title`
2. Nếu `is_product()` → POST `csc_track_product_view` với `product_id = $product->get_id()`

---

## B.11. `product-suggestions.php` — Gợi ý sản phẩm

### B.11.1. UC-1: Query gợi ý — `csc_product_suggestions_shortcode($atts)`

**Attrs:** `limit` (mặc định 8) · `title` (mặc định "📚 Sách bạn có thể thích").

| Bước | Logic |
|---|---|
| 1 | `!is_a($product,'WC_Product')` → `return ''` (không có sản phẩm hiện tại) |
| 2 | `$product_id = $product->get_id()` |
| 3 | `$categories = wp_get_post_terms($product_id,'product_cat',['fields'=>'ids'])` |
| 4 | `$tags = wp_get_post_terms($product_id,'product_tag',['fields'=>'ids'])` |
| 5 | Query: `post_type=product`, `posts_per_page=limit`, `post__not_in=[product_id]`, `post_status=publish`, `orderby=rand` |
| 6 | Nếu có `$categories` → thêm `tax_query` (`relation=OR`, taxonomy `product_cat`, field `id`, terms `$categories`) |
| 7 | `$products->posts` rỗng → `return ''` |

### B.11.2. UC-2: Render lưới gợi ý

| Thành phần | Nội dung |
|---|---|
| Section | `<section class="csc-product-suggestions">` |
| H3 | `$atts['title']` |
| Grid | `.suggestions-grid` → `repeat(auto-fill, minmax(180px,1fr))` gap 20px |
| Item | `.suggestion-item` (bg #fff radius 8px overflow hidden, hover translateY(-5px) + shadow) |
| Ảnh | `.suggestion-image` aspect-ratio 1, img object-fit cover |
| Tên | `.suggestion-name` 14px, `-webkit-line-clamp: 2` |
| Giá | `.suggestion-price` màu #e2401c bold |
| Nút | `<button class="add-to-cart-btn" data-product-id="{id}">Thêm vào giỏ</button>` width 100% nền #0073aa |

### B.11.3. UC-3: Thêm giỏ từ gợi ý — JS

| Bước | Hành động |
|---|---|
| 1 | `e.preventDefault()`; lấy `productId` từ `data-product-id` |
| 2 | `$btn.text('Đang thêm...').prop('disabled', true)` |
| 3 | POST `csc_add_to_cart` với `product_id` |
| 4 | Success | `$btn.text('Đã thêm ✓')` + `$(document.body).trigger('added_to_cart')` |
| 5 | Fail / error | `$btn.text('Thử lại').prop('disabled', false)` |

### B.11.4. UC-4: AJAX thêm giỏ — `csc_ajax_add_to_cart()`
| Bước | Logic |
|---|---|
| 1 | `!isset($_POST['product_id'])` → `wp_send_json_error()` |
| 2 | `WC()->cart->add_to_cart(intval($_POST['product_id']))` |
| 3 | `wp_send_json_success()` |

### B.11.5. UC-5: Hook tự động — `csc_add_product_suggestions()`
`woocommerce_after_main_content` priority 20 → `is_product()` → `do_shortcode('[csc_product_suggestions limit=8]')`

---

## B.12. `privacy-policy.php` & `return-policy.php` — Trang chính sách

### B.12.1. `privacy-policy.php`

**Hook:** `init` → `csc_create_privacy_policy_page()`.

| Bước | Logic |
|---|---|
| 1 | `get_page_by_path('chinh-sach-bao-ve-du-lieu-ca-nhan')` |
| 2 | Null → `wp_insert_post(['post_title'=>'Chính sách bảo vệ dữ liệu cá nhân','post_name'=>'chinh-sach-bao-ve-du-lieu-ca-nhan','post_content'=>csc_get_privacy_policy_content(),'post_status'=>'publish','post_type'=>'page'])` |

**Nội dung** (`csc_get_privacy_policy_content()`): HTML inline — container `.policy-page` (`max-width:900px; margin:30px auto; padding:20px`), H2 căn giữa màu #0073aa, các mục chính sách về thu thập / sử dụng / lưu trữ / chia sẻ / quyền của người dùng.

### B.12.2. `return-policy.php`

**Hook:** `init` → `csc_create_return_policy_page()`.
Tương tự: `get_page_by_path('chinh-sach-doi-tra')` → null → tạo page title "Chính sách đổi – trả", content `csc_get_return_policy_content()`.
**Nội dung:** điều kiện đổi trả (sai lệch mô tả, hư hỏng) · thời hạn 7 ngày · quy trình yêu cầu · phí vận chuyển · hoàn tiền trong 3-5 ngày làm việc.

---

## B.13. `create-vendor-book-page.php` — Tự tạo page Đăng Sách

**Hook:** `init` → `ensure_vendor_book_page()`.

| Bước | Logic |
|---|---|
| 1 | `get_transient('vendor_book_page_created')` → return (đã chạy trong 1 tháng) |
| 2 | `get_posts(['post_type'=>'page','title'=>'Đăng Sách','post_status'=>'any','posts_per_page'=>1])` |
| 3 | Rỗng → `wp_insert_post(['post_title'=>'Đăng Sách','post_content'=>'<!-- wp:shortcode -->[vendor_add_book]<!-- /wp:shortcode -->','post_status'=>'publish','post_type'=>'page','post_author'=>1])` |
| 4 | `set_transient('vendor_book_page_created', true, MONTH_IN_SECONDS)` |

---

# PHẦN C — CHILD THEME

## C.1. `functions.php` (405 dòng)

### C.1.1. UC-1: Nạp style theme con
**Hook:** `wp_enqueue_scripts` priority 20 → `chsachcu_child_enqueue_styles()`
```
wp_enqueue_style('chsachcu-child-style', get_stylesheet_uri(),
  ['storefront-style','storefront-icons'], wp_get_theme()->get('Version'));
```

### C.1.2. UC-2: Ép hiển thị menu điều hướng (CSS)
**Hook:** `wp_head` priority 1 → inline `<style id="csc-show-all-menu">`.

| Selector | Thuộc tính |
|---|---|
| `#site-navigation .primary-navigation` | `display:block!important; visibility:visible!important` |
| `#site-navigation .primary-navigation ul.nav-menu` | `display:flex!important; flex-wrap:wrap!important; align-items:center!important; gap:0 18px!important; height:auto!important; max-height:none!important; overflow:visible!important; visibility:visible!important; margin:0!important; padding:0!important` |
| `#site-navigation .primary-navigation ul.nav-menu > li` | `display:inline-flex!important; visibility:visible!important; opacity:1!important; height:auto!important` |
| `… ul.nav-menu > li > a` | `display:inline-flex!important; align-items:center!important; visibility:visible!important; opacity:1!important; height:auto!important; padding:8px 0!important` |
| `#site-navigation` | `display:block!important; visibility:visible!important; height:auto!important; overflow:visible!important; margin:0!important` |

> Chống Storefront collapse menu (`max-height:0; overflow:hidden`).

### C.1.3. UC-3: Google Fonts
**Hook:** `wp_enqueue_scripts` priority 25 → `chsachcu_enqueue_fonts()`
URL: `https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Nunito:wght@400;500;600;700&display=swap`

### C.1.4. UC-4: Logo trang đăng nhập
**Hook:** `login_enqueue_scripts` → `chsachcu_login_logo()`
- `#login h1 a`: `background-image:none`, 320×80px, font 40px bold, màu #8B4513, `text-indent:0`, `line-height:80px`
- `#login h1 a::before`: `content: "📚 "`
- `body.login`: `background: linear-gradient(135deg,#F5F0E8 0%,#fff 100%)`

### C.1.5. UC-5: Tạo menu 7 mục cốt lõi — `chsachcu_create_essential_menu()` (init)

| Bước | Logic |
|---|---|
| 1 | `wp_get_nav_menu_object('Essential Navigation')` |
| 2 | Nếu chưa có → `wp_create_nav_menu('Essential Navigation')` → lặp map 7 item: `get_page_by_path($slug)`; nếu tồn tại → `wp_update_nav_menu_item($menu_id, 0, ['menu-item-title'=>$title,'menu-item-object-id'=>$page->ID,'menu-item-object'=>'page','menu-item-type'=>'post_type','menu-item-status'=>'publish'])` |
| 3 | `get_theme_mod('nav_menu_locations')` → gán `['primary'=>$menu_id]` → `set_theme_mod('nav_menu_locations', $locations)` |

**Bảng 7 mục:**

| Tiêu đề | Slug |
|---|---|
| Cửa hàng | `cua-hang` |
| Đăng Sách | `dang-sach` |
| Khuyến mãi | `khuyen-mai` |
| My Orders | `my-orders` |
| So Sánh Sản Phẩm | `so-sanh-san-pham` |
| Thanh toán | `thanh-toan` |
| Tài khoản của tôi | `tai-khoan-cua-toi` |

### C.1.6. UC-6: Badge số lượng giỏ hàng — PHP
**Hook:** `woocommerce_cart_link` priority 10, 2 tham số → `chsachcu_custom_cart_link($link, $cart_has_items)`
```
$count = WC()->cart->get_cart_contents_count();
$badge = $count > 0 ? '<span class="csc-cart-badge">' . $count . '</span>' : '';
return str_replace('</a>', $badge . '</a>', $link);
```

### C.1.7. UC-7: Badge số lượng giỏ hàng — JS (dự phòng)
**Hook:** `wp_footer` priority 100 → `updateCartBadge()`.

| Bước | Logic |
|---|---|
| 1 | Tìm link giỏ: `.site-header-cart .cart-contents`, `.storefront-header-cart .cart-contents`, `a[href*="gio-hang"]`, `a.cart-contents` |
| 2 | Đếm: nếu `.woocommerce-mini-cart li` có item → `count` = số item; nếu `.cart-contents[data-count]` → `parseInt(dataset.count)` |
| 3 | Với mỗi link: xóa `.csc-cart-badge` cũ; nếu `count > 0` → tạo `<span class="csc-cart-badge">`, `link.style.position='relative'`, append, `textContent = count`, `display='flex'`; ngược lại ẩn badge |
| 4 | Trigger: `DOMContentLoaded` hoặc chạy ngay; `MutationObserver` trên `.site-header-cart` / `.storefront-header-cart` (`childList:true, subtree:true`) → `setTimeout(updateCartBadge, 500)`; `setInterval(updateCartBadge, 2000)` |

### C.1.8. UC-8: Ép lưới sản phẩm 4 cột — JS
**Hook:** `wp_footer` priority 100, chỉ khi `is_front_page() || is_shop() || is_archive()` → `fixGrid()`.

| Bước | Logic |
|---|---|
| 1 | `document.querySelectorAll('.woocommerce ul.products')` → `list.className.replace(/columns-\d+/g,'columns-custom').trim()` |
| 2 | `list.setAttribute('style','display:grid!important; grid-template-columns:repeat(4,1fr)!important; gap:20px!important; width:100%!important;')` |
| 3 | Mỗi `li.product` → `style="flex:none!important; width:auto!important; max-width:none!important; float:none!important; margin:0!important;"` |
| 4 | Chạy ngay + `for(i=1;i<=10;i++) setTimeout(fixGrid, i*200)` |

### C.1.9. UC-9: Số cột sản phẩm
**Hook:** `loop_shop_columns` → `chsachcu_loop_columns()` → trả **4**.

### C.1.10. UC-10: Bỏ CSS mặc định WooCommerce
**Hook:** `woocommerce_enqueue_styles` → `__return_empty_array`.

### C.1.11. UC-11: Ép lưới sản phẩm (CSS dự phòng)
**Hook:** `wp_head` priority 100 → `<style id="csc-force-grid">`.

| Rule | Giá trị |
|---|---|
| `.woocommerce ul.products` | `grid-template-columns:repeat(4,1fr)!important; display:grid!important; flex-wrap:wrap!important` |
| `.woocommerce ul.products li.product` | `flex:1 1 calc(25% - 20px)!important; min-width:0!important; max-width:100%!important` |
| `@media (max-width:1400px)` | 4 cột |
| `@media (max-width:1000px)` | 3 cột |
| `@media (max-width:600px)` | 2 cột |

### C.1.12. UC-12: Ép menu hiển thị (JS dự phòng)
**Hook:** `wp_footer` priority 100 → `ensureNavVisible()`.
Đặt inline style cho `#site-navigation` (`display:block; visibility:visible; height:auto; overflow:visible`), `.primary-navigation` (`display:block; visibility:visible`), `ul.nav-menu` (`display:flex; flex-wrap:wrap; height:auto; visibility:visible`), mỗi `li` (`display:inline-flex; visibility:visible; opacity:1; height:auto`).
Chạy ngay + `setTimeout` 500ms + 1500ms.

### C.1.13. UC-13: Bọc nút So sánh & Yêu thích — JS
**Hook:** `wp_footer` priority 100, chỉ trang shop/archive → `fixAll()` (gồm cả `fixGrid()`).
| Bước | Logic |
|---|---|
| 1 | Mỗi `li.product`: tìm `button.csc-add-compare` và `a.csc-wishlist-btn` |
| 2 | Nếu cả hai tồn tại **VÀ** chưa có `.csc-product-actions` → tạo `<div class="csc-product-actions">`, `compareBtn.parentNode.insertBefore(container, compareBtn)`, `container.appendChild(compareBtn)`, `container.appendChild(wishlistBtn)` |
| 3 | Chạy ngay + 10 lần × 200ms |

**Kết quả:** 2 nút nằm **cạnh nhau theo hàng ngang** trong 1 container.

### C.1.14. UC-14: Body class
**Hook:** `body_class` → `chsachcu_body_classes($classes)` → thêm `'chsachcu-theme'`.

### C.1.15. UC-15: Link "Đọc tiếp"
**Hook:** `the_content_more_link` → `chsachcu_read_more_link()` → `<a class="more-link" href="{permalink}">Đọc tiếp →</a>`

### C.1.16. UC-16: Bỏ thanh footer handheld
`remove_action('storefront_footer','storefront_handheld_footer_bar',999)`

---

## C.2. `style.css` — 25 nhóm CSS

### C.2.1. Nhóm 1 — CSS Variables

| Biến | Giá trị | Dùng cho |
|---|---|---|
| `--csc-primary` | `#8B4513` | Nút chính, viền, giá |
| `--csc-primary-light` | `#A0522D` | Hover nhẹ |
| `--csc-primary-dark` | `#5D2E0C` | Hover đậm |
| `--csc-secondary` | `#F5F0E8` | Nền phụ (kem) |
| `--csc-secondary-dark` | `#E8DFD0` | Nền phụ đậm hơn |
| `--csc-accent` | `#D2691E` | Badge, nhấn |
| `--csc-accent-light` | `#E67E22` | Badge sáng |
| `--csc-bg-primary` | `#FDFBF7` | Nền trang |
| `--csc-bg-secondary` | `#FFFFFF` | Nền card |
| `--csc-bg-card` | `#FFFFFF` | Nền thẻ sản phẩm |
| `--csc-text-primary` | `#2C2C2C` | Chữ chính |
| `--csc-text-secondary` | `#666666` | Chữ phụ |
| `--csc-text-muted` | `#999999` | Chữ mờ |
| `--csc-border` | `#E8E4DE` | Đường viền |
| `--csc-shadow-sm/md/lg` | `0 2px 8px` / `0 4px 16px` / `0 8px 32px` rgba(0,0,0,.08/.12/.16) | 3 mức đổ bóng |
| `--csc-radius-sm/md/lg` | `8px` / `12px` / `16px` | 3 mức bo góc |

### C.2.2. Nhóm 2-4 — Base, Header, Hero
- **Base:** reset, body font Nunito, màu text `--csc-text-primary`, nền `--csc-bg-primary`
- **Header:** top bar, main header, logo, nav menu (`display:flex`, gap), highlight menu item hiện tại
- **Hero:** banner trang chủ — nền gradient kem, tiêu đề Playfair Display lớn, nút CTA nâu

### C.2.3. Nhóm 5 — Product Card (thẻ sách)

| Thành phần | Thuộc tính chính |
|---|---|
| Grid container | `auto-fill` grid, gap 20px, justify-items stretch, align-items stretch |
| Card | bg #fff, radius `--csc-radius-md`, shadow `--csc-shadow-sm`, hover shadow-md + translateY(-4px) |
| Sale badge | nền `--csc-accent`, chữ trắng, bo tròn, absolute góc trên trái |
| Image wrapper | aspect-ratio 1, overflow hidden; img object-fit cover, transition transform |
| Title | font Playfair Display, clamp 2 dòng, màu text-primary, hover `--csc-primary` |
| Author | font nhỏ, màu `--csc-text-muted` |
| Price | màu `--csc-primary`, bold, size lớn |
| Vendor name | font nhỏ, màu text-secondary, prefix icon |
| Add to cart button | nền `--csc-primary`, chữ trắng, radius `--csc-radius-sm`, hover `--csc-primary-dark`, full width |
| **Action row** | `.csc-product-actions` — `display:flex; gap:8px; align-items:center; justify-content:center` → 2 nút cạnh nhau |

### C.2.4. Nút So sánh (nhóm phụ trong 5)

```css
.csc-add-compare,
.csc-add-compare-product {
    background: #8B4513 !important;
    border: none !important;
    border-radius: 8px !important;
    padding: 6px 12px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px;
    font-size: 13px !important;
    font-weight: 500 !important;
    color: #fff !important;
    cursor: pointer;
    transition: all 0.2s ease;
    margin: 0 !important;
    white-space: nowrap;
    line-height: 1.4;
}
.csc-add-compare::before { content: none; }               /* ← bỏ icon trùng */
.csc-add-compare img.emoji,                                /* ← ẩn ảnh emoji của WP */
.csc-add-compare-product img.emoji { display: none !important; }
.csc-compare-icon { font-size: 14px; line-height: 1; display: inline-flex; align-items: center; }
```

### C.2.5. Nút Yêu thích (nhóm phụ trong 5)

```css
.csc-wishlist-btn,
a.csc-wishlist-btn,
.wishlist-btn,
.wishlist-btn.button {
    background: transparent !important;      /* không màu nền */
    border: 1.5px solid #8B4513 !important;  /* viền nâu */
    border-radius: 50% !important;           /* HÌNH TRÒN */
    width: 36px !important;                  /* kích thước cố định */
    height: 36px !important;
    padding: 0 !important;
    margin: 0 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    position: relative !important;
    overflow: visible !important;
    vertical-align: middle !important;
    font-size: 13px !important;
    color: #8B4513 !important;
    font-weight: 500 !important;
    text-decoration: none !important;
    white-space: nowrap;
    line-height: 1;
    flex-shrink: 0 !important;
}
/* Ẩn chữ "Yêu thích" — chỉ giữ icon */
.csc-wishlist-btn span,
a.csc-wishlist-btn span,
.wishlist-btn span { display: none !important; }

.csc-wishlist-btn .far, .wishlist-btn .far { font-size: 15px !important; color: #8B4513 !important; }
.csc-wishlist-btn .fas, .wishlist-btn .fas { font-size: 15px !important; color: #8B4513 !important; }

.csc-wishlist-btn.active,
.csc-wishlist-btn.is-added,
.csc-wishlist-btn.is-active,
a.csc-wishlist-btn.active,
.wishlist-btn.is-added,
.wishlist-btn.button.is-added {
    background: rgba(139, 69, 19, 0.1) !important;   /* nền nâu nhạt khi đã thích */
    border-color: #8B4513 !important;
    color: #8B4513 !important;
}

/* Nút yêu thích ở trang chi tiết sản phẩm — giữ chữ, dạng pill */
.csc-wishlist-btn.csc-wishlist-btn-single {
    width: auto !important;
    height: auto !important;
    border-radius: 8px !important;
    padding: 8px 16px !important;
    gap: 6px;
    line-height: 1.4;
}
.csc-wishlist-btn.csc-wishlist-btn-single span {
    display: inline !important;
    font-size: 13px !important;
    color: #8B4513 !important;
}
```

**Kết quả mong đợi:**

| Trạng thái | Hiển thị |
|---|---|
| Chưa bấm | Tròn 36px, nền trong suốt, viền nâu 1.5px, **trái tim rỗng** (`far fa-heart`) màu nâu |
| Đã bấm | Tròn 36px, nền nâu nhạt `rgba(139,69,19,.1)`, viền nâu, **trái tim đặc** (`fas fa-heart`) màu nâu |

### C.2.6. Nhóm 23 — Hiển thị đầy đủ menu
`.site-navigation` / `.primary-navigation` / `ul.nav-menu` — ép `display:flex`, `visibility:visible`, `opacity:1`, `height:auto`, không `overflow:hidden`.

### C.2.7. Nhóm 24 — Cart badge
`.csc-cart-badge` — absolute, top -6px, right -8px, nền `--csc-accent` hoặc `--csc-primary`, chữ trắng, tròn, font 11px bold, min-width 18px, height 18px, flex center. Khi giỏ trống → `display:none`.

### C.2.8. Nhóm 24b — Ẩn menu thừa
Giữ 7 mục cốt lõi; ẩn các mục khác theo class/selector đã định nghĩa.

### C.2.9. Nhóm 25 — Ép tất cả menu item hiện
Override mọi rule `display:none` áp cho `#site-navigation li`, `ul.nav-menu > li` bằng `display:inline-flex!important`.

### C.2.10. Nhóm 6-21 — Các nhóm còn lại

| Nhóm | Nội dung chính |
|---|---|
| **6. Tabs** | Style tab của trang sản phẩm (woocommerce tabs) |
| **7. Hero Section** | Banner trang chủ |
| **8. Shopping Cart** | Bảng giỏ hàng, cột tổng, mini-cart, ẩn badge khi rỗng |
| **9. Checkout Page** | Form checkout, shipping method, payment method, nút đặt hàng nâu |
| **10. Compare Page** | Bảng so sánh, header sản phẩm, nút xóa tròn đỏ, `.csc-compare-count` badge fixed |
| **11. Footer** | Footer bottom, cột liên kết, social icon, copyright |
| **12. Forms & Buttons** | Input/select/textarea focus ring nâu, `.woocommerce-Button` nền nâu hover đậm |
| **13. Policy Pages** | `.policy-page` container max-width 900px, tiêu đề căn giữa, đoạn văn line-height 1.8 |
| **14. Responsive** | Breakpoint 1200 / 980 / 767 / 480 — điều chỉnh cột, padding, font |
| **15. Animations** | `@keyframes fadeInUp` (fade in), stagger product grid (delay theo index), `pulse` cho cart badge, skeleton loading |
| **16. Custom Scrollbar** | `::-webkit-scrollbar` width 10px, thumb nâu bo tròn, track kem |
| **17. Layout Full Width** | Ẩn tất cả sidebar (`.widget-area`, `.sidebar`), `.content-area`/`.site-content` 100%, homepage/archive/page full width, fix footer trùng (`display:none` cho footer thứ 2) |
| **18. Header Enhancements** | Sticky header, search box, cart icon, sticky shop sidebar |
| **19. Storefront Overrides** | Neutralize mặc định Storefront (masthead, buttons, forms) |
| **20. Additional Polish** | Focus ring, smooth scroll, selection color, placeholder color |
| **21. Pagination** | `.page-numbers` bo tròn 5px, trang hiện tại nền nâu, hover nền nâu |
| **22. Product Grid Fix Empty Slot** | `ul.products` grid 4 cột gap 20px, `::before`/`::after` ẩn, `li.product` reset `float/clear/margin/padding` — **vá lỗi ô trống đầu tiên** |

---

# PHẦN D — MA TRẬN CHỨC NĂNG ↔ ROLE

| Chức năng | Khách | Customer | Vendor | Admin |
|---|:-:|:-:|:-:|:-:|
| Xem cửa hàng / chi tiết / tìm kiếm / lọc | ✓ | ✓ | ✓ | ✓ |
| Thêm giỏ hàng | ✓ | ✓ | ✓ | ✓ |
| Yêu thích (cookie, không cần login) | ✓ | ✓ | ✓ | ✓ |
| So sánh sản phẩm (localStorage, max 4) | ✓ | ✓ | ✓ | ✓ |
| Xem gợi ý sản phẩm | ✓ | ✓ | ✓ | ✓ |
| Checkout COD / Chuyển khoản | ✓ | ✓ | — | ✓ |
| Xem đơn hàng của mình | — | ✓ | ✓ | ✓ |
| Đăng ký bán hàng | ✓ | ✓ | — | — |
| Đăng sách (kèm ảnh) | — | — | ✓ | ✓ |
| Quản lý sản phẩm của mình | — | — | ✓ | ✓ |
| Xem doanh thu / báo cáo | — | — | ✓ | ✓ |
| Dashboard tổng quan | — | — | — | ✓ |
| QL Sách (duyệt / xoá / sửa) | — | — | — | ✓ |
| QL Tài khoản (kích hoạt / xoá / sửa) | — | — | — | ✓ |
| QL Đơn hàng (đổi trạng thái) | — | — | — | ✓ |
| Thống kê hành vi / Top 10 sản phẩm | — | — | — | ✓ |
| Kịch bản quảng cáo | — | — | — | ✓ |
| Ghi vết đăng nhập | — | — | — | ✓ |
| Backup metadata DB | — | — | — | ✓ |
| Ghi vết lượt xem trang | ✓ | ✓ | ✓ | ✓ |

---

# PHỤ LỤC E — NHẬT KÝ LỖI ĐÃ SỬA

| # | Triệu chứng | Nguyên nhân gốc | Cách sửa | File |
|---|---|---|---|---|
| 1 | Menu điều hướng biến mất hoàn toàn (mất cả 7 mục) | Regex tham lam `/<li[^>]*>.*?<a[^>]*>[^<]*Tài khoản[^<]*<\/a>.*?<\/li>/is` khớp từ `<li>` đầu tiên đến `<li>` cuối chứa "Tài khoản" | Chuyển sang `DOMDocument` + duyệt node, chỉ `removeChild` node cụ thể | `admin-book-management.php` |
| 2 | Storefront collapse menu (không thấy item nào) | `.nav-menu { max-height: 0; overflow: hidden; }` của Storefront | Override `max-height:none!important; overflow:visible!important` + `display:flex` ở cả CSS và JS | `functions.php`, `style.css` |
| 3 | Trái tim outline không hiển thị (chỉ thấy ô vuông) | `fa-heart-o` là cú pháp FontAwesome **4**, Dokan dùng FA **5.3.1** | Đổi sang `far fa-heart` (rỗng) và `fas fa-heart` (đặc) | `wishlist.php` |
| 4 | Nút So sánh hiện **2 icon cân** | `::before { content: "⚖️" }` (CSS) **+** `⚖️ So sánh` (PHP) → WP tự convert emoji thành `<img class="emoji">` | Bỏ `::before`; thay bằng **inline SVG** `csc_compare_icon_svg()`; ẩn `img.emoji` | `style.css`, `product-comparison.php` |
| 5 | Nút Yêu thích không phản ứng với khách chưa đăng nhập | Chỉ có `wp_ajax_*`, thiếu `wp_ajax_nopriv_*` | Bổ sung 3 hook `wp_ajax_nopriv_csc_{add,remove}_to_wishlist` + `csc_get_wishlist_count` | `wishlist.php` |
| 6 | Sửa JS nhưng trình duyệt vẫn chạy bản cũ | `wp_enqueue_script` giữ nguyên `ver=1.0` → browser cache | Bump version `1.0` → `1.2` | `wishlist.php` |
| 7 | Nút yêu thích hiện chữ "Yêu thích" chen giữa layout | `<span>` được render trong PHP | Bỏ `<span>` ở nút loop; ở nút trang chi tiết dùng class riêng `.csc-wishlist-btn-single` + CSS `span{display:inline!important}` | `wishlist.php`, `style.css` |

---

## Phụ lục F — Thống kê tổng hệ thống

| Hạng mục | Số lượng |
|---|---|
| Container Docker | 3 (`db`, `wordpress`, `wpcli`) |
| Theme | 2 (Storefront 4.6.2 + chsachcu-child 1.0.0) |
| Plugin runtime | 2 (WooCommerce 11.2.1, Dokan Lite) + 7 thư viện đi kèm |
| MU-Plugin | 15 file (2 file assets) |
| Script vận hành | 25 file (`.sh` + `.php`) |
| Dòng CSS | ~1700 (25 nhóm) |
| Dòng PHP theme con | 405 |
| Trang public | 14 |
| Endpoint WooCommerce | 10 (đã Việt hóa) |
| Cổng AJAX | 10 (6 có `nopriv`) |
| Shortcode | 7 (`vendor_add_book`, `csc_seller_register`, `csc_wishlist`, `csc_compare`, `custom-my-orders`, `csc_analytics_dashboard`, `csc_product_suggestions`, `csc_marketing_doc`) |
| Shipping zone | 4 (3 miền + toàn quốc) |
| Tỉnh/thành | 63 |
| Coupon | 4 |
| Tiêu chí so sánh | 9 |
| Bước cài đặt trong `setup.sh` | 19 |

---

*Phiên bản: 2.0 · Cập nhật: 10/2026 · Đặc tả cho hệ thống Chợ Sách Cũ (local testing)*
