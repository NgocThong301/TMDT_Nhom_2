# Chợ Sách Cũ — TMDT Nhóm 2

> Website mô hình **C2C mua bán sách đã qua sử dụng**, xây dựng trên **WordPress + WooCommerce + Dokan Lite** (multi-vendor marketplace), chạy hoàn toàn bằng **Docker Compose** trên macOS/Linux.
>
> Sinh viên đăng sách cũ lên bán, người mua tìm kiếm – lọc – so sánh – đặt hàng, thanh toán COD. Có phân quyền 3 vai trò: **Admin / Người bán (Vendor) / Người mua (Customer)**.

| | |
|---|---|
| **Stack** | WordPress 7.1.3 · PHP 8.2 · WooCommerce 11.2.1 · Dokan Lite · MySQL 8.0.40 |
| **Theme** | Storefront 4.6.2 (parent) + `chsachcu-child` (theme con tùy biến toàn bộ UI) |
| **Cổng local** | `http://localhost:8081` |
| **Tài liệu** | [`cho-sach-cu/docs/01-HUONG-DAN-SU-DUNG.md`](cho-sach-cu/docs/01-HUONG-DAN-SU-DUNG.md) · [`cho-sach-cu/docs/02-DAC-TA-CHI-TIET.md`](cho-sach-cu/docs/02-DAC-TA-CHI-TIET.md) |

---

## 📑 Mục lục

1. [Tính năng nổi bật](#1-tính-năng-nổi-bật)
2. [Yêu cầu hệ thống](#2-yêu-cầu-hệ-thống)
3. [Cài đặt từ đầu (3 bước)](#3-cài-đặt-từ-đầu-3-bước)
4. [Cấu trúc thư mục](#4-cấu-trúc-thư-mục)
5. [Tài khoản thử nghiệm](#5-tài-khoản-thử-nghiệm)
6. [Các lệnh thường dùng](#6-các-lệnh-thường-dùng)
7. [Cấu hình `.env`](#7-cấu-hình-env)
8. [Troubleshooting](#8-troubleshooting)
9. [Cấu trúc hệ thống](#9-cấu-trúc-hệ-thống)
10. [Đóng góp / Ghi chú an toàn](#10-đóng-góp--ghi-chú-an-toàn)

---

## 1. Tính năng nổi bật

### Người mua (Customer / Khách)
| Tính năng | Mô tả |
|---|---|
| 🛒 Giỏ hàng & checkout | COD + chuyển khoản (môi trường test), phí ship 3 miền, freeship từ 699k |
| ❤️ Yêu thích | Lưu bằng cookie (không cần đăng nhập) hoặc user meta, có trang danh sách riêng |
| ⚖️ So sánh sản phẩm | So sánh tối đa 4 sách, 9 tiêu chí, lưu `localStorage` |
| 🔎 Tìm kiếm & lọc | Tìm theo danh mục, tình trạng sách, tên tác giả, NXB, năm xuất bản |
| 📦 Gợi ý sản phẩm | 8 sách cùng danh mục, thêm giỏ bằng AJAX không reload |
| 🧾 My Orders | Bảng đơn hàng riêng, responsive trên mobile |
| 📍 Địa chỉ Việt Nam | 63 tỉnh/thành dạng dropdown, tự map sang 3 zone vận chuyển |

### Người bán (Vendor / Seller)
| Tính năng | Mô tả |
|---|---|
| 📝 Đăng ký bán hàng | Form 3 nhóm (cửa hàng / liên hệ / điều khoản), tự duyệt, tự gán role `seller` |
| 📖 Đăng sách | Form 12 trường + 1 ảnh bìa + 3 ảnh chi tiết, **xuất bản ngay** |
| 🏪 Kênh người bán | Dashboard Dokan: sản phẩm, đơn hàng, doanh thu, rút tiền |
| 🏷️ Phân loại | Danh mục + meta riêng: tác giả, tình trạng, NXB, năm xuất bản |

### Quản trị viên (Admin)
| Tính năng | Mô tả |
|---|---|
| 📊 Dashboard tổng quan | 5 thẻ số liệu, 4 quick-link, bảng sách mới & đơn hàng gần nhất |
| 📚 Quản lý sách | Lọc theo trạng thái, duyệt / chuyển chờ duyệt / xóa |
| 👥 Quản lý tài khoản | Lọc theo vai trò, sửa qua modal AJAX, kích hoạt / tạm ngưng / xóa |
| 🧾 Quản lý đơn hàng | Lọc theo trạng thái, cập nhật nhanh processing / completed / cancelled |
| 🔐 Chống brute-force | Khóa 15 phút sau 5 lần đăng nhập sai, ghi vết, cột trong trang Users |
| 📈 Phân tích hành vi | Đếm lượt xem trang & sản phẩm, Top 10 sản phẩm xem nhiều nhất |
| 🎯 Kịch bản quảng cáo | Mẫu chiến dịch Facebook Ads (kịch bản, ngân sách, A/B test, chỉ số) |
| 🧩 SEO | Meta OG, Schema.org Product, trang kịch bản marketing |
| 📄 Trang pháp lý | Tự tạo: Chính sách bảo vệ dữ liệu cá nhân, Chính sách đổi – trả |

---

## 2. Yêu cầu hệ thống

| Yêu cầu | Phiên bản | Ghi chú |
|---|---|---|
| **Docker Desktop** | 4.x trở lên | Hoặc Docker Engine + Docker Compose v2 trên Linux |
| **Docker Compose** | v2 (`docker compose`, không phải `docker-compose`) | Dùng `profiles` → cần bản ≥ 1.28 |
| **RAM khả dụng** | ≥ 4 GB | WordPress + MySQL + build theme |
| **Dung lượng** | ≥ 2 GB | Image Docker ~1.5 GB + volume |
| **Cổng trống** | `8081` | Đổi được qua `WP_HOST_PORT` |
| **Hệ điều hành** | macOS / Linux / Windows (WSL2) | Script dùng `sh` POSIX |

> Không cần cài sẵn PHP, MySQL hay WordPress trên máy — tất cả nằm trong container.

---

## 3. Cài đặt từ đầu (3 bước)

### Bước 1 — Clone repo

```bash
git clone https://github.com/NgocThong301/TMDT_Nhom_2.git
cd TMDT_Nhom_2
```

### Bước 2 — Tạo file cấu hình `.env`

```bash
cd cho-sach-cu
cp .env.example .env
```

Mở `.env` và điền các giá trị:

```dotenv
COMPOSE_PROJECT_NAME=cho-sach-cu
WP_HOST_PORT=8081
WP_URL=http://localhost:8081

MYSQL_DATABASE=cho_sach_cu
MYSQL_USER=cho_sach_cu
MYSQL_PASSWORD=<đặt mật khẩu mạnh>
MYSQL_ROOT_PASSWORD=<đặt mật khẩu mạch>
WORDPRESS_TABLE_PREFIX=csc_

WP_ADMIN_USER=csc_admin
WP_ADMIN_PASSWORD=<mật khẩu admin>
WP_ADMIN_EMAIL=admin@cho-sach-cu.local

VENDOR1_PASSWORD=<mật khẩu vendor 1>
VENDOR2_PASSWORD=<mật khẩu vendor 2>
VENDOR3_PASSWORD=<mật khẩu vendor 3>
BUYER_PASSWORD=<mật khẩu buyer>
```

> ⚠️ File `.env` chứa mật khẩu thật → **không được commit** (đã có trong `.gitignore`).

### Bước 3 — Khởi động

```bash
# 3.1 Bật MySQL + WordPress
docker compose --project-name cho-sach-cu up -d db wordpress

# 3.2 Chạy script cài đặt (tự động: WordPress, theme, plugin, cấu hình VN, seed dữ liệu)
docker compose --project-name cho-sach-cu --profile setup run --rm wpcli
```

Script `setup.sh` chạy khoảng **3–5 phút** và thực hiện:

1. Đợi `wp-config.php` + database sẵn sàng
2. `wp core install` → tạo site, admin
3. Cấu hình múi giờ `Asia/Ho_Chi_Minh`, định dạng `d/m/Y`, permalink `/%postname%/`
4. Cài ngôn ngữ tiếng Việt
5. Cài & kích hoạt theme **Storefront**
6. Cài & kích hoạt plugin **WooCommerce** + **Dokan Lite**
7. Cấu hình cửa hàng Việt Nam: `VND`, tỷ lệ `.`/`,`, tắt thuế, cho phép checkout khách
8. Tạo trang WooCommerce mặc định, bật COD + chuyển khoản
9. Cấu hình shipping **3 miền** + freeship từ 699.000đ
10. Cài Vietnam Store Toolkit (dropdown 63 tỉnh)
11. Cấu hình Dokan Lite + các trang tài khoản, trang pháp lý
12. Seed **3 vendor + 4 customer + sản phẩm mẫu**
13. `wp rewrite flush --hard`

### Kiểm tra kết quả

```bash
docker compose --project-name cho-sach-cu ps      # container đang chạy
curl -I http://localhost:8081/                    # kỳ vọng: HTTP/1.1 200 OK
```

| Trang | URL |
|---|---|
| Trang chủ | http://localhost:8081/ |
| Cửa hàng | http://localhost:8081/cua-hang/ |
| Đăng Sách | http://localhost:8081/dang-sach/ |
| Khuyến mãi | http://localhost:8081/khuyen-mai/ |
| My Orders | http://localhost:8081/my-orders/ |
| So sánh sản phẩm | http://localhost:8081/so-sanh-san-pham/ |
| Thống kê (admin) | http://localhost:8081/analytics/ |
| Quản trị | http://localhost:8081/wp-admin/ |

---

## 4. Cấu trúc thư mục

```
TMDT_Nhom_2/
├── README.md                    # ← file này
├── .gitignore
└── cho-sach-cu/
    ├── .env.example             # mẫu cấu hình (copy thành .env)
    ├── .env                     # mật khẩu thật — KHÔNG commit
    ├── docker-compose.yml       # db + wordpress + wpcli (profile: setup)
    ├── README.md                # ghi chú nhanh dự án
    ├── backups/                 # dump SQL (không commit)
    ├── docs/
    │   ├── 01-HUONG-DAN-SU-DUNG.md   # hướng dẫn sử dụng tổng quan
    │   ├── 02-DAC-TA-CHI-TIET.md     # đặc tả chi tiết từng chức năng
    │   └── HUONG-DAN-GIAO-VIEN.md     # tài liệu dành cho giáo viên
    ├── scripts/                 # 25 script vận hành
    │   ├── setup.sh             # orchestrator cài đặt
    │   ├── configure-*.php      # cấu hình WooCommerce / Dokan / trang
    │   ├── seed-*.php           # dữ liệu mẫu
    │   ├── backup.sh
    │   ├── stop.sh
    │   └── vnpay-gateway/       # gateway VNPay (sandbox)
    ├── mu-plugins/              # 15 plugin nghiệp vụ (tự viết)
    │   ├── admin-book-management.php
    │   ├── wishlist.php
    │   ├── product-comparison.php
    │   ├── vendor-book-submission.php
    │   ├── seller-registration.php
    │   ├── custom-my-orders.php
    │   ├── login-limit.php
    │   ├── marketing-seo.php
    │   ├── user-analytics.php
    │   ├── product-suggestions.php
    │   ├── vietnam-provinces.php
    │   ├── privacy-policy.php
    │   ├── return-policy.php
    │   ├── create-vendor-book-page.php
    │   └── assets/              # wishlist.css / wishlist.js
    └── themes/chsachcu-child/
        ├── functions.php        # hook, menu, cart badge, grid fix
        └── style.css            # ~1700 dòng, 25 nhóm CSS
```

> **Điểm mấu chốt:** `mu-plugins/` được mount `rw` vào container → sửa file trên máy là có hiệu lực ngay sau `docker compose restart`, **không cần rebuild image**.

---

## 5. Tài khoản thử nghiệm

Tài khoản được tạo tự động bởi `seed-products.php` và `seed-orders.php`.

| Vai trò | Username | Mật khẩu lấy từ `.env` | Ghi chú |
|---|---|---|---|
| Admin | `csc_admin` | `WP_ADMIN_PASSWORD` | Toàn quyền quản trị |
| Vendor | `vendor_sgk_demo` | `VENDOR1_PASSWORD` | "Tủ sách lớp 12" — Sách giáo khoa |
| Vendor | `vendor_vanhoc_demo` | `VENDOR2_PASSWORD` | "Sách cũ Nguyễn Du" — Văn học, Kinh tế, Kỹ năng |
| Vendor | `vendor_ngoaingu_demo` | `VENDOR3_PASSWORD` | "Góc IELTS secondhand" — Ngoại ngữ, Truyện tranh |
| Customer | `buyer_demo` | `BUYER_PASSWORD` | Hà Nội |
| Customer | `customer_hanoi` | `BUYER_PASSWORD` | Nhiều đơn hàng mẫu |
| Customer | `customer_hcm` | `BUYER_PASSWORD` | Nhiều đơn hàng mẫu |
| Customer | `customer_danang` | `BUYER_PASSWORD` | Nhiều đơn hàng mẫu |

> 🔒 Đổi mật khẩu ngay trong wp-admin nếu máy dùng chung. `.env` không được push lên Git.

---

## 6. Các lệnh thường dùng

| Mục đích | Lệnh |
|---|---|
| Bật dịch vụ | `docker compose --project-name cho-sach-cu up -d db wordpress` |
| Cài đặt lần đầu | `docker compose --project-name cho-sach-cu --profile setup run --rm wpcli` |
| Cài đặt bổ sung (trang tài khoản, pháp lý, coupon, đơn mẫu) | `docker compose --project-name cho-sach-cu --profile setup run --rm wpcli sh /opt/cho-sach-cu/run-extra-setup.sh` |
| Khởi động lại (giữ dữ liệu) | `docker compose --project-name cho-sach-cu restart` |
| Dừng (giữ volume) | `./scripts/stop.sh` |
| Sao lưu database | `./scripts/backup.sh` → `backups/*.sql` |
| Xem log | `docker compose --project-name cho-sach-cu logs -f wordpress` |
| Chạy lệnh WP-CLI | `docker compose --project-name cho-sach-cu run --rm wpcli wp option get blogname` |
| Đặt lại cấu hình WooCommerce | `docker compose --project-name cho-sach-cu run --rm wpcli wp wc tool run install_pages` |

> ⛔ **Không bao giờ chạy** `docker compose down -v`, `docker volume prune`, hoặc xóa volume `cho-sach-cu_db_data` / `cho-sach-cu_wp_data` — sẽ mất toàn bộ dữ liệu.

---

## 7. Cấu hình `.env`

| Biến | Bắt buộc | Mặc định | Ý nghĩa |
|---|:-:|---|---|
| `COMPOSE_PROJECT_NAME` | ✓ | `cho-sach-cu` | Tên project Docker (tiền tố container/volume) |
| `WP_HOST_PORT` | ✓ | `8081` | Cổng host publish ra ngoài |
| `WP_URL` | ✓ | `http://localhost:8081` | `WP_HOME` + `WP_SITEURL` |
| `MYSQL_DATABASE` | ✓ | `cho_sach_cu` | Tên database |
| `MYSQL_USER` / `MYSQL_PASSWORD` | ✓ | — | Tài khoản MySQL |
| `MYSQL_ROOT_PASSWORD` | ✓ | — | Root MySQL |
| `WORDPRESS_TABLE_PREFIX` | ✓ | `csc_` | Prefix bảng WordPress (tránh trùng khi dùng chung host) |
| `WP_ADMIN_USER` / `WP_ADMIN_PASSWORD` / `WP_ADMIN_EMAIL` | ✓ | — | Tài khoản admin tạo lúc cài |
| `VENDOR1..3_PASSWORD` | ✓ | — | Mật khẩu 3 tài khoản người bán mẫu |
| `BUYER_PASSWORD` | ✓ | — | Mật khẩu tài khoản người mua mẫu |
| `VNPAY_TMN_CODE` | — | rỗng | Mã merchant VNPay sandbox |
| `VNPAY_HASH_SECRET` | — | rỗng | Secret key VNPay sandbox |
| `VNPAY_RETURN_URL` | — | rỗng | URL trả về sau thanh toán |

### Đưa lên hosting thật (tùy chọn)

1. Đặt `WP_URL=https://ten-mien-cua-ban.vn` và đổi mật khẩu trong `.env`.
2. Trỏ DNS `A` record `@` và `www` về IP server.
3. Cấu hình reverse proxy (nginx/Caddy) + Let's Encrypt cho cổng 80/443.
4. Đặt `VNPAY_RETURN_URL` và `VNPAY_HASH_SECRET` thật nếu dùng cổng thanh toán.
5. Cân nhắc `WP_DEBUG` + `DISALLOW_FILE_EDIT` (đã bật sẵn) và sao lưu định kỳ.

---

## 8. Troubleshooting

| Triệu chứng | Cách xử lý |
|---|---|
| **Không truy cập được website** | `docker compose ps` → `docker compose restart` |
| **Trang trắng / Lỗi 500** | `docker compose logs wordpress` → `docker compose restart wordpress` |
| **Sửa code nhưng không thấy thay đổi** | `docker compose restart wordpress` + xóa cache trình duyệt (`Cmd+Shift+R`). Nếu vẫn cũ → kiểm tra `ver` của `wp_enqueue_script` đã bump chưa |
| **Không đăng nhập được** | Kiểm tra mật khẩu trong `.env`; nếu bị khóa do 5 lần sai → chờ 15 phút hoặc xóa transient `csc_login_attempts_*` |
| **Không thấy sản phẩm** | Kiểm tra trạng thái = Publish và quyền của role `seller` |
| **Không hiện phí ship** | Đã chạy `configure-regional-shipping.php` chưa; tỉnh phải được chọn; freeship từ 699.000đ |
| **Menu điều hướng biến mất** | Kiểm tra các rule `display:none` trong `themes/chsachcu-child/style.css` (nhóm 23 / 24b / 25) |
| **Đăng nhập xong không thấy menu Vendor** | Admin cố tình bị ẩn sidebar Dokan — dùng tài khoản vendor để kiểm thử |
| **Cổng 8081 bị chiếm** | `lsof -i :8081` → đổi `WP_HOST_PORT` trong `.env` → `docker compose up -d` |

---

## 9. Cấu trúc hệ thống

```
┌──────────────────────────────────────────────┐
│  WordPress 7.1.3 · PHP 8.2 · Apache          │
│  WooCommerce 11.2.1 · Dokan Lite              │
├──────────────────────────────────────────────┤
│  themes/chsachcu-child/   → UI/UX, menu, CSS  │
├──────────────────────────────────────────────┤
│  mu-plugins/ (15 file)    → toàn bộ logic C2C │
├──────────────────────────────────────────────┤
│  MySQL 8.0.40  (volume: cho-sach-cu_db_data) │
└──────────────────────────────────────────────┘
```

Chi tiết đầy đủ (từng use case, hook, điều kiện, kết quả đầu ra) nằm trong:
- [`cho-sach-cu/docs/02-DAC-TA-CHI-TIET.md`](cho-sach-cu/docs/02-DAC-TA-CHI-TIET.md)

---

## 10. Đóng góp / Ghi chú an toàn

### Quy trình đóng góp

```bash
git clone https://github.com/NgocThong301/TMDT_Nhom_2.git
cd TMDT_Nhom_2
git checkout -b feature/ten-tinh-nang

# ... sửa code ...
docker compose --project-name cho-sach-cu restart   # kiểm thử

git add -A
git commit -m "feat: mô tả thay đổi"
git push -u origin feature/ten-tinh-nang
```

### Quy tắc bắt buộc

| Quy tắc | Chi tiết |
|---|---|
| 🚫 **Không commit `.env`** | Chứa mật khẩu DB, admin, vendor — đã có trong `.gitignore` |
| 🚫 **Không hard-code mật khẩu/secret** | Dùng `getenv()` hoặc biến trong `.env` |
| 🚫 **Không commit `backups/`** | Chứa dump SQL toàn bộ database |
| 🚫 **Không xóa volume** | `docker compose down -v` sẽ mất dữ liệu |
| ✅ **Bump version asset** | Khi sửa JS/CSS trong `mu-plugins` hoặc theme con, tăng `ver` trong `wp_enqueue_*` để tránh cache trình duyệt |
| ✅ **Idempotent** | Script trong `scripts/` phải chạy lại được nhiều lần mà không nhân bản dữ liệu |

---

## 📄 Giấy phép & tác giả

Dự án học phần **TMDT (Thiết kế & Mô hình hóa) — Nhóm 2**.

- Mã nguồn WordPress / WooCommerce / Dokan: theo giấy phép GPL của từng dự án
- Theme Storefront: Automattic, GPL v2
- Toàn bộ mã trong `mu-plugins/`, `scripts/`, `themes/chsachcu-child/` do nhóm tự viết

---

<div align="center">

**Chợ Sách Cũ** — Mô hình C2C mua bán sách đã qua sử dụng
📚 Sinh viên bán · Người mua tìm · Giá cả hợp lý

</div>
