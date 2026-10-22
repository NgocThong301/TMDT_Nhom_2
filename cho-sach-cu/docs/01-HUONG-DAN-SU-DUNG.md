# Chợ Sách Cũ — Hướng dẫn sử dụng (Bản sơ lượng)

> Sàn C2C mua bán sách đã qua sử dụng. Chạy hoàn toàn local bằng Docker.
> Đối tác: giáo viên / người đánh giá bài tập. Đọc tài liệu này để hiểu **hệ thống gồm những gì, chạy thế nào, vận hành ra sao**.
> Tài liệu đặc tả chi tiết từng chức năng: [`02-DAC-TA-CHI-TIET.md`](./02-DAC-TA-CHI-TIET.md)

---

## Mục lục

1. [Tổng quan hệ thống](#1-tổng-quan-hệ-thống)
2. [Truy cập hệ thống](#2-truy-cập-hệ-thống)
3. [Tài khoản thử nghiệm](#3-tài-khoản-thử-nghiệm)
4. [Các công cụ / plugin đang sử dụng](#4-các-công-cụ--plugin-đang-sử-dụng)
5. [Vòng đời vận hành](#5-vòng-đời-vận-hành)
6. [Luồng nghiệp vụ C2C](#6-luồng-nghiệp-vụ-c2c)
7. [Bảng cấu hình nghiệp vụ](#7-bảng-cấu-hình-nghiệp-vụ-quan-trọng)
8. [Khắc phục sự cố thường gặp](#8-khắc-phục-sự-cố-thường-gặp)

---

## 1. Tổng quan hệ thống

### 1.1. Kiến trúc ba tầng vận hành

```
                    ┌─────────────────────────────┐
                    │   WordPress 7.1.3 / PHP 8.2  │
                    │   WooCommerce 11.2.1         │
                    │   MySQL 8.0.40               │
                    └──────────────┬──────────────┘
                                   │
        ┌──────────────────────────┼──────────────────────────┐
        │                          │                          │
┌───────▼────────┐       ┌────────▼────────┐       ┌────────▼────────┐
│  MU-PLUGINS    │       │  CHILD THEME    │       │  SCRIPTS (CLI)  │
│  15 plugin     │       │ chsachcu-child  │       │  setup / seed / │
│  toàn bộ logic │       │  UI/UX + layout │       │  cấu hình       │
│  nghiệp vụ C2C │       │  ~1700 dòng CSS│       │  wp eval-file   │
└────────────────┘       └─────────────────┘       └─────────────────┘
```

### 1.2. Bốn tầng kiến trúc

| Tầng | Thư mục / Thành phần | Cơ chế nạp | Trách nhiệm |
|---|---|---|---|
| **Tầng dữ liệu** | `docker-compose.yml` → service `db` | MySQL 8.0.40, volume `cho-sach-cu_db_data` | Sản phẩm, đơn hàng, user meta, coupon, log |
| **Tầng ứng dụng** | `mu-plugins/*.php` (15 file) | WordPress tự nạp mọi file trong `mu-plugins/` | Toàn bộ logic nghiệp vụ C2C |
| **Tầng giao diện** | `themes/chsachcu-child/` | Theme con của Storefront 4.6.2 | Bố cục, màu sắc, JS bù layout Storefront |
| **Tầng vận hành** | `scripts/*.sh`, `scripts/*.php` | `wp eval-file` qua container `wpcli` | Cài đặt, seed dữ liệu, cấu hình, sao lưu |

> **Điểm mấu chốt:** `mu-plugins` được mount `rw` trong `docker-compose.yml` (dòng 49) nên sửa file trên máy host là có hiệu lực ngay sau khi restart container — không cần rebuild image.

### 1.3. Cấu trúc thư mục

```
cho-sach-cu/
├── docker-compose.yml          # db + wordpress + wpcli (profile: setup)
├── .env / .env.example         # biến môi trường, port 8081
├── README.md                   # hướng dẫn cài đặt nhanh
├── docs/
│   ├── HUONG-DAN-GIAO-VIEN.md  # tài liệu cũ (giữ lại tham khảo)
│   ├── 01-HUONG-DAN-SU-DUNG.md # ← bản sơ lượng (tài liệu này)
│   └── 02-DAC-TA-CHI-TIET.md   # ← bản đặc tả chi tiết
├── backups/                    # dump SQL sinh bởi backup.sh
├── scripts/                    # 25 script vận hành
│   ├── setup.sh                # orchestrator chính
│   ├── configure-*.php         # 8 script cấu hình
│   ├── seed-*.php              # 2 script seed dữ liệu
│   ├── vnpay-gateway/          # gateway VNPay (2 file PHP)
│   └── *.sh                    # backup.sh, stop.sh
├── mu-plugins/                 # 15 plugin nghiệp vụ
└── themes/chsachcu-child/
    ├── functions.php           # 405 dòng — hook, menu, cart badge, grid fix
    └── style.css               # ~1700 dòng — 25 nhóm CSS
```

---

## 2. Truy cập hệ thống

| Trang | URL | Mã HTTP thực tế |
|---|---|---|
| Trang chủ | `http://localhost:8081/` | 200 |
| Cửa hàng | `/cua-hang/` | 200 |
| Đăng Sách | `/dang-sach/` | 200 |
| Khuyến mãi | `/khuyen-mai/` | 200 |
| My Orders | `/my-orders/` | 302 → cần đăng nhập |
| So Sánh Sản Phẩm | `/so-sanh-san-pham/` | 200 |
| Thanh toán | `/thanh-toan/` | 302 → giỏ hàng trống |
| Tài khoản của tôi | `/tai-khoan-cua-toi/` | 200 |
| Đăng Ký Bán Hàng | `/seller-registration/` | 200 |
| Thống Kê | `/analytics/` | 200 (chỉ admin) |
| Kịch Bản Quảng Cáo | `/kien-bang-quang-cao/` | 200 |
| Chính sách bảo vệ dữ liệu | `/chinh-sach-bao-ve-du-lieu-ca-nhan/` | 200 |
| Chính sách đổi – trả | `/chinh-sach-doi-tra/` | 200 |
| Giới thiệu | `/gioi-thieu/` | 200 |
| Kênh người bán (Dokan) | `/kenh-nguoi-ban/` | 302 (vendor) |
| Quản trị | `/wp-admin/` | 302 → login |

### 2.1. Yêu cầu môi trường

- Docker Desktop đang chạy
- Cổng **8081** trống
- Không chạy `docker compose down -v` / `docker volume prune` (sẽ mất dữ liệu)

---

## 3. Tài khoản thử nghiệm

| Vai trò | Username | Mật khẩu | Ghi chú |
|---|---|---|---|
| Admin | `csc_admin` | trong `.env` | Toàn quyền quản trị |
| Vendor | `vendor_sgk_demo` | `VENDOR1_PASSWORD` | "Tủ sách lớp 12" — Sách giáo khoa |
| Vendor | `vendor_vanhoc_demo` | `VENDOR2_PASSWORD` | "Sách cũ Nguyễn Du" — Văn học, Kinh tế, Kỹ năng |
| Vendor | `vendor_ngoaingu_demo` | `VENDOR3_PASSWORD` | "Góc IELTS secondhand" — Ngoại ngữ, Truyện tranh |
| Customer | `buyer_demo` | `BUYER_PASSWORD` | Hà Nội |
| Customer | `customer_hanoi` | `BUYER_PASSWORD` | Nhiều đơn hàng mẫu |
| Customer | `customer_hcm` | `BUYER_PASSWORD` | Nhiều đơn hàng mẫu |
| Customer | `customer_danang` | `BUYER_PASSWORD` | Nhiều đơn hàng mẫu |

> Mật khẩu lấy từ file `.env` ở thư mục gốc dự án (`VENDOR1_PASSWORD`…`BUYER_PASSWORD`, `WP_ADMIN_PASSWORD`). File `.env` **không** được commit lên Git.

> Đổi mật khẩu ngay trong wp-admin nếu máy này không chỉ dùng một mình.

### 3.1. Quyền hạn theo vai trò

**Admin** — quản lý sản phẩm, đơn hàng, tài khoản; cấu hình WooCommerce/Dokan; xem báo cáo doanh thu, thống kê hành vi, kịch bản marketing; quản lý coupon.

**Vendor (người bán)** — đăng/chỉnh sửa sản phẩm của mình; xem và xử lý đơn hàng liên quan; xem doanh thu cá nhân; quản lý địa chỉ giao hàng.

**Customer (người mua)** — xem và mua sản phẩm; quản lý đơn hàng cá nhân; cập nhật thông tin tài khoản; yêu thích, so sánh sản phẩm.

**Khách (chưa đăng nhập)** — xem cửa hàng, dùng yêu thích (lưu cookie) và so sánh (localStorage), thêm giỏ, thanh toán COD.

---

## 4. Các công cụ / plugin đang sử dụng

### 4.1. Plugin cài từ kho (runtime)

| Plugin | Phiên bản | Vai trò |
|---|---|---|
| **WooCommerce** | 11.2.1 | Nền tảng thương mại: sản phẩm, giỏ hàng, checkout, coupon, vận chuyển, đơn hàng |
| **Dokan Lite** | build `1792430382` | Marketplace đa người bán: dashboard vendor, endpoint tùy biến, cửa hàng, rút tiền |
| **Font Awesome 5.3.1** | (đi kèm Dokan) | Icon cho wishlist, compare, menu, admin |
| **SweetAlert2** | (đi kèm Dokan) | Hộp thoại cảnh báo |
| **iziModal** | (đi kèm Dokan) | Modal popup |
| **blockUI** | (đi kèm WooCommerce) | Chặn UI khi AJAX đang chạy |
| **js-cookie** | (đi kèm WooCommerce) | Đọc/ghi cookie |

### 4.2. Theme

| Theme | Phiên bản | Vai trò |
|---|---|---|
| **Storefront** (parent) | 4.6.2 | Giao diện gốc chính thức WooCommerce |
| **chsachcu-child** | 1.0.0 | Theme con — toàn bộ tùy biến UI/UX, màu nâu ấm, layout full-width |

### 4.3. MU-Plugins tự viết (15 file) — công cụ nghiệp vụ

| # | File | Công cụ / tính năng |
|---|---|---|
| 1 | `admin-book-management.php` | **Dashboard Admin**: Tổng quan, QL Sách, QL Tài khoản, QL Đơn hàng; ẩn menu Dokan/Vendor; chuyển hướng onboarding |
| 2 | `wishlist.php` | **Yêu thích**: nút tròn 36px chỉ icon, lưu cookie + user meta, shortcode `[csc_wishlist]` |
| 3 | `product-comparison.php` | **So sánh sản phẩm**: localStorage tối đa 4, bảng so sánh 9 tiêu chí, badge đếm cố định |
| 4 | `vendor-book-submission.php` | **Đăng sách**: form 12 trường, upload ảnh bìa + 3 ảnh gallery, xuất bản tức thì |
| 5 | `seller-registration.php` | **Đăng ký bán hàng**: form 3 nhóm, tự duyệt seller, gán role `seller` |
| 6 | `custom-my-orders.php` | **My Orders**: bảng đơn hàng riêng từng khách, responsive |
| 7 | `login-limit.php` | **Chống brute-force**: khóa 15 phút sau 5 lần sai, ghi vết, cột trong trang Users |
| 8 | `marketing-seo.php` | **SEO + Marketing**: meta OG, Schema.org Product, kịch bản quảng cáo FB, backup metadata DB |
| 9 | `user-analytics.php` | **Phân tích hành vi**: pageview, lượt xem sản phẩm, Top 10 sản phẩm xem nhiều nhất |
| 10 | `product-suggestions.php` | **Gợi ý sản phẩm**: 8 sách cùng danh mục, thêm giỏ bằng AJAX |
| 11 | `vietnam-provinces.php` | **Tỉnh/thành Việt Nam**: 63 tỉnh dropdown, ẩn postcode/city, VN mặc định |
| 12 | `privacy-policy.php` | **Chính sách bảo vệ dữ liệu cá nhân** (tự tạo page) |
| 13 | `return-policy.php` | **Chính sách đổi – trả** (tự tạo page) |
| 14 | `create-vendor-book-page.php` | Tự tạo page "Đăng Sách" (1 lần/tháng) |
| 15 | `assets/wishlist.css` + `assets/wishlist.js` | Asset riêng của tính năng yêu thích (v1.0 / v1.2) |

### 4.4. Script vận hành (25 file trong `scripts/`)

**Shell:** `setup.sh` (orchestrator), `stop.sh`, `backup.sh`, `run-extra-setup.sh`, `run-vietnam-shipping.sh`

**Cấu hình WooCommerce:** `configure-pages.php`, `configure-account-pages.php`, `configure-coupons.php`, `configure-shipping.php`, `configure-regional-shipping.php`, `configure-dokan.php`, `configure-legal-pages.php`

**Cài đặt / Tích hợp:** `install-vietnam-store-toolkit.php`, `check-vietnam-config.php`, `activate-vnpay.php`, `configure-vnpay.php`, `update-vnpay-return-url.php`, `simulate-vnpay-callback.php`

**Dữ liệu mẫu:** `seed-products.php`, `seed-orders.php`, `create-test-order.php`

**Tiện ích / Kiểm thử:** `test-book-submission.php`, `update-dang-sach-page.php`, `custom-my-orders.php` (bản sao), `vnpay-gateway/` (2 file PHP)

### 4.5. Endpoint WooCommerce (đã đổi sang tiếng Việt)

| Endpoint gốc | Slug tiếng Việt |
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

### 4.6. Cổng AJAX (10)

| Action | Đã đăng nhập | Chưa đăng nhập | Mục đích |
|---|:-:|:-:|---|
| `csc_add_to_wishlist` | ✓ | ✓ | Thêm vào yêu thích |
| `csc_remove_from_wishlist` | ✓ | ✓ | Xóa khỏi yêu thích |
| `csc_get_wishlist_count` | ✓ | ✓ | Đếm sản phẩm yêu thích |
| `csc_compare_data` | ✓ | ✓ | Dữ liệu bảng so sánh |
| `csc_add_to_cart` | ✓ | ✓ | Thêm giỏ hàng (gợi ý & so sánh) |
| `csc_track_pageview` | ✓ | ✓ | Ghi lượt xem trang |
| `csc_track_product_view` | ✓ | ✓ | Ghi lượt xem sản phẩm |
| `csc_get_user_data` | ✓ | — | Lấy thông tin user (sửa trong admin) |
| `csc_view_login_attempts` | ✓ | — | Xem log đăng nhập |
| `csc_backup_database` | ✓ | — (luôn lỗi) | Tạo metadata backup |

---

## 5. Vòng đời vận hành

```
┌─ LẦN 1: CÀI ĐẶT ──────────────────────────────────────────────────┐
│ docker compose --project-name cho-sach-cu up -d db wordpress        │
│ docker compose --project-name cho-sach-cu --profile setup run --rm wpcli
│   └─ setup.sh: core install → theme → plugin → cấu hình VN/VND      │
│      → COD + BACS → shipping 3 miền + freeship → VN toolkit          │
│      → Dokan → tạo trang → seed sản phẩm → flush rewrite           │
└──────────────────────────────────────────────────────────────────────┘
              ↓
┌─ MỖI LẦN CHẠY LẠI (giữ nguyên dữ liệu) ───────────────────────────┐
│ docker compose --project-name cho-sach-cu restart                    │
│ hoặc: docker restart cho-sach-cu-wordpress                          │
│   (Sau khi sửa mu-plugins/*.php hoặc themes/chsachcu-child/*)      │
└──────────────────────────────────────────────────────────────────────┘
              ↓
┌─ SAO LƯU ──────────────────────────────────────────────────────────┐
│ ./scripts/backup.sh   →  backups/*.sql                               │
│ Volume MySQL: cho-sach-cu_db_data                                   │
│ Volume WordPress: cho-sach-cu_wp_data                                │
│ ⚠ KHÔNG chạy: docker compose down -v / docker volume prune          │
└──────────────────────────────────────────────────────────────────────┘
```

### 5.1. Thứ tự cài đặt trong `setup.sh`

| # | Bước | Nội dung |
|---|---|---|
| 1 | Đợi `wp-config.php` | Tối đa 120s |
| 2 | Đợi DB sẵn sàng | Tối đa 120s |
| 3 | `wp core install` | Bỏ qua nếu đã cài |
| 4 | Options | Tên site, múi giờ `Asia/Ho_Chi_Minh`, định dạng `d/m/Y`, `H:i` |
| 5 | Permalink | `/%postname%/` |
| 6 | Ngôn ngữ | Cài + kích hoạt tiếng Việt |
| 7 | Theme | Storefront (ưu tiên), thử Dokani → fallback Storefront |
| 8 | Plugin | WooCommerce + Dokan Lite + bản dịch `vi` |
| 9 | Cửa hàng VN | `VN`, `VND`, sep `.`/`,`, 0 chữ số thập phân, tắt thuế, cho phép checkout khách |
| 10 | Trang WooCommerce | `wp wc tool run install_pages` |
| 11 | Thanh toán | COD + BACS (chuyển khoản giả lập, nội dung CK `CSC-TEST`) |
| 12 | Vận chuyển | `configure-shipping.php` + `configure-regional-shipping.php` |
| 13 | VN Toolkit | `install-vietnam-store-toolkit.php` |
| 14 | Dokan | `configure-dokan.php` |
| 15 | Trang | `configure-pages.php` |
| 16 | Seed | `seed-products.php` |
| 17 | Flush | `wp rewrite flush --hard` |
| 18 | Báo cáo | In phiên bản WP, theme, plugin, currency |

---

## 6. Luồng nghiệp vụ C2C

### 6.1. Luồng Người bán (Vendor)

```
Customer đăng ký tài khoản
   └─→ /tai-khoan-cua-toi/ (Đăng ký)
         └─→ /dang-sach/  → bị chặn: "Bạn cần là người bán để đăng sách"
               └─→ /seller-registration/  (form 3 nhóm)
                     ├─ Nhóm 1 — Thông tin cửa hàng
                     │    ├─ Tên cửa hàng (bắt buộc)
                     │    ├─ Địa chỉ cửa hàng (slug tự sinh, có preview JS)
                     │    └─ Giới thiệu cửa hàng
                     ├─ Nhóm 2 — Thông tin liên hệ
                     │    ├─ Số điện thoại (bắt buộc, 10–11 số)
                     │    ├─ Tỉnh/Thành (63 dropdown)
                     │    └─ Địa chỉ chi tiết
                     └─ Nhóm 3 — ☑ Đồng ý điều khoản (bắt buộc)
                           ↓
              Tự động: dokan_enabled=1, dokan_store_name, gán role `seller`
              Điều khoản: sách cũ không lậu / mô tả trung thực
                           giao trong 3 ngày làm việc / chịu trách nhiệm chất lượng
                           ↓
              Redirect → /dang-sach/  (giờ đã đăng được)
                           ↓
              Form đăng sách (12 trường + 1 ảnh bìa + 3 ảnh chi tiết)
                           ↓
              publish NGAY (không cần admin duyệt) → hiện trong Cửa hàng
```

### 6.2. Luồng Người mua (Customer)

```
1. /cua-hang/  → lọc danh mục, tìm kiếm, sắp xếp
2. Click thẻ sách → trang chi tiết
   ├─ Nút "So sánh" (nâu bo góc, 1 icon SVG) → thêm vào localStorage (tối đa 4)
   ├─ Nút "Yêu thích" (tròn, 1 icon trái tim) → cookie csc_wishlist
   └─ "Sách bạn có thể thích" → 8 gợi ý cùng danh mục, thêm giỏ bằng AJAX
3. Thêm vào giỏ hàng → badge đếm xuất hiện trên logo giỏ (góc phải)
4. /gio-hang/ → cập nhật số lượng, áp coupon
5. /thanh-toan/
   ├─ Chọn Tỉnh/Thành (dropdown 63 tỉnh, bắt buộc)
   ├─ Phí ship: Miền Bắc 30.000đ / Miền Trung 40.000đ / Miền Nam 45.000đ
   │             FREE toàn quốc từ 699.000đ
   ├─ COD (thanh toán khi nhận hàng) hoặc Chuyển khoản (nội dung CSC-TEST)
   └─ Đặt hàng
6. /tai-khoan-cua-toi/don-hang/ → theo dõi trạng thái đơn
7. Vendor xác nhận → processing → shipped → completed
```

### 6.3. Luồng Admin

```
/wp-admin/ → menu "C2C Admin"
├── Tổng quan      : 5 stat card (sách đang bán, chờ duyệt, đơn hàng,
│                    người bán, doanh thu) + 4 quick-link
│                    + 2 bảng mini (sách mới nhất, đơn hàng gần nhất)
├── QL Sách        : lọc theo trạng thái, tìm kiếm, phân trang 20/trang
│                    → Xem / Sửa / Duyệt / Chuyển chờ duyệt / Xóa
├── QL Tài khoản  : lọc theo role, tìm kiếm, modal Sửa (AJAX)
│                    → Kích hoạt / Tạm ngưng / Xóa
├── QL Đơn hàng    : lọc trạng thái, cập nhật nhanh
│                    → processing / completed / cancelled
└── Thống Kê       : /analytics/ — tổng lượt xem, lượt truy cập,
                     Top 10 sản phẩm xem nhiều nhất
```

### 6.4. Ma trận chức năng ↔ vai trò

| Chức năng | Khách | Customer | Vendor | Admin |
|---|:-:|:-:|:-:|:-:|
| Xem cửa hàng / chi tiết / tìm kiếm / lọc | ✓ | ✓ | ✓ | ✓ |
| Thêm giỏ hàng | ✓ | ✓ | ✓ | ✓ |
| Yêu thích (cookie, không cần login) | ✓ | ✓ | ✓ | ✓ |
| So sánh sản phẩm (localStorage, max 4) | ✓ | ✓ | ✓ | ✓ |
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

---

## 7. Bảng cấu hình nghiệp vụ quan trọng

### 7.1. Phí vận chuyển theo miền

Nguồn: `scripts/configure-regional-shipping.php`

| Zone | Phí ship | Số tỉnh | Free ship |
|---|---|:-:|---|
| Miền Bắc | 30.000đ | 25 | từ 699.000đ |
| Miền Trung | 40.000đ | 18 | từ 699.000đ |
| Miền Nam | 45.000đ | 19 | từ 699.000đ |
| Toàn Quốc (mặc định) | — | — | từ 699.000đ |

### 7.2. Coupon khuyến mãi

Nguồn: `scripts/configure-coupons.php`

| Mã | Loại | Giá trị | Điều kiện | Hạn dùng | Giới hạn |
|---|---|---|---|---|---|
| `SINHVIENMOI` | percent | 10% (tối đa 50.000đ) | — | 30 ngày | 1 lần / 1 user |
| `FREESHIP` | fixed_cart | 0đ (miễn phí ship) | đơn ≥ 200.000đ | 60 ngày | 100 lần / 3 user |
| `GIAM50K` | fixed_cart | 50.000đ | — | 45 ngày | 200 lần / 2 user |
| `BLACKFRIDAY20` | percent | 20% | — | không giới hạn | không giới hạn / 1 user |

### 7.3. Cấu hình bảo mật đăng nhập

Nguồn: `mu-plugins/login-limit.php`

| Hằng số | Giá trị | Ý nghĩa |
|---|---|---|
| `MAX_ATTEMPTS` | 5 | Số lần đăng nhập sai tối đa |
| `LOCKOUT_DURATION` | 15 | Số phút bị khóa |
| `TRACKING_WINDOW` | 30 | Số phút theo dõi lần thử |
| Giới hạn log | 100 entry / user | Số bản ghi tối đa mỗi tài khoản |

### 7.4. Cấu hình Dokan

Nguồn: `scripts/configure-dokan.php` + `scripts/configure-account-pages.php`

| Option | Giá trị | Ý nghĩa |
|---|---|---|
| `dokan_general[dashboard]` | `0` | Ẩn dashboard Dokan mặc định (dùng menu C2C Admin) |
| `dokan_general[enable_pricing]` | `on` | Cho phép vendor đặt giá |
| `dokan_general[enable_report]` | `on` | Cho phép vendor xem báo cáo |
| `dokan_selling[new_seller_enable_selling]` | `on` | Seller mới được bán ngay |
| `dokan_selling[one_step_product_create]` | `on` | Tạo sản phẩm 1 bước |
| `dokan_selling[order_status_change]` | `on` | Vendor tự đổi trạng thái đơn |
| `dokan_selling[enable_tos]` | `on` | Bắt buộc chấp nhận điều khoản khi đăng bán |
| `dokan_withdraw[withdraw_order_status]` | `completed, processing` | Trạng thái cho phép rút tiền |
| `dokan_withdraw[withdraw_limit]` | 50.000đ | Hạn mức rút tối thiểu |
| `dokan_withdraw[payment_methods]` | `bank_transfer` | Rút tiền qua chuyển khoản |
| `dokan_route_base` | `cua-hang` | Tiền tố URL dashboard vendor |

### 7.5. Thiết kế giao diện (theme con)

| Biến / Nhóm | Giá trị | Ý nghĩa |
|---|---|---|
| `--csc-primary` | `#8B4513` | Nâu chủ đạo (nút, viền, giá) |
| `--csc-primary-light` | `#A0522D` | Nâu sáng |
| `--csc-primary-dark` | `#5D2E0C` | Nâu đậm (hover) |
| `--csc-secondary` | `#F5F0E8` | Kem nhạt (nền phụ) |
| `--csc-accent` | `#D2691E` | Cam đất (badge, nhấn) |
| `--csc-bg-primary` | `#FDFBF7` | Nền trang |
| `--csc-text-primary` | `#2C2C2C` | Chữ chính |
| `--csc-border` | `#E8E4DE` | Đường viền |
| Font tiêu đề | Playfair Display | Serif cho tiêu đề |
| Font nội dung | Nunito | Sans-serif cho giao diện |
| Grid sản phẩm | 4 cột (3 cột ≤1000px, 2 cột ≤600px) | Vá lỗi ô trống đầu tiên |
| Layout | 100% full-width, ẩn toàn bộ sidebar | Tận dụng không gian |
| Nút yêu thích | tròn 36×36, viền nâu 1.5px, nền trong suốt, chỉ icon trái tim | |
| Nút so sánh | bo góc 8px, nền nâu, chữ trắng, 1 icon SVG + "So sánh" | |

---

## 8. Khắc phục sự cố thường gặp

### 8.1. Không truy cập được website

```bash
cd cho-sach-cu
docker compose ps
docker compose restart
```

### 8.2. Trang trắng / Lỗi 500

```bash
docker compose logs wordpress
docker compose restart wordpress
```

### 8.3. Sửa code nhưng không thấy thay đổi

```bash
docker restart cho-sach-cu-wordpress
```

Sau khi restart, xóa cache trình duyệt (Cmd+Shift+R). Nếu vẫn cũ, kiểm tra version asset đã bump chưa (ví dụ `wp_enqueue_script` dùng `ver` không đổi sẽ bị cache).

### 8.4. Không đăng nhập được

1. Kiểm tra username/password trong `.env`
2. Reset password qua wp-admin → Users
3. Xóa cache trình duyệt
4. Nếu bị khóa do 5 lần sai → chờ 15 phút hoặc xóa transient `csc_login_attempts_*`

### 8.5. Không thấy sản phẩm

1. Trạng thái phải là Publish
2. Kiểm tra visibility trong Edit Product
3. Sản phẩm đăng qua form vendor đã tự publish — nếu vẫn ẩn, kiểm tra quyền của role `seller`

### 8.6. Thanh toán không hiện phí ship

1. Kiểm tra đã chạy `configure-regional-shipping.php` chưa
2. Xác nhận tỉnh đã chọn (bắt buộc) — zone được map theo state
3. Kiểm tra tổng đơn ≥ 699.000đ để được freeship

### 8.7. Menu điều hướng bị ẩn

Menu 7 mục cốt lõi do `chsachcu_create_essential_menu()` tạo: Cửa hàng, Đăng Sách, Khuyến mãi, My Orders, So Sánh Sản Phẩm, Thanh toán, Tài khoản của tôi. Nếu menu biến mất, kiểm tra các rule `display:none` trong `admin-book-management.php` và `style.css` (nhóm 23/24b/25).

### 8.8. Đăng nhập thành công nhưng không thấy menu Vendor

Admin đăng nhập sẽ không thấy sidebar Dokan (`csc_hide_dokan_nav_for_admin` trả mảng rỗng). Dùng tài khoản vendor để kiểm thử kênh người bán.

---

## Liên hệ hỗ trợ

1. Chụp ảnh lỗi
2. Ghi log: `docker compose logs wordpress > error.log`
3. Ghi rõ bước tái hiện lỗi

---

*Phiên bản: 2.0 · Cập nhật: 10/2026 · Dành cho thử nghiệm local — Chợ Sách Cũ*
