# Chợ Sách Cũ

Website WordPress + WooCommerce + Dokan Lite cho mô hình **C2C mua bán sách cũ**, chạy local bằng Docker trên macOS.

Dự án này **độc lập** với Bút Chì Xanh (`butchixanh.local`, compose `01-cua-hang`). Không dùng chung database, volume, container hay cổng.

## Vì sao không dùng nguyên 3 repository đã chỉ định

| Repository | Thực tế | Kết luận |
|---|---|---|
| [admaskinfu/demostore](https://github.com/admaskinfu/demostore) | Site WooCommerce luyện test tự động. 1 container gộp WordPress + DB, mật khẩu mặc định yếu, không volume, last push 2023, không có Dokan/C2C. | **Không dùng** — không an toàn, dễ đụng cổng 8080 của bài tập khác. |
| [getdokan/dokan](https://github.com/getdokan/dokan) | Mã nguồn plugin Dokan (nhánh `develop`), không phải website hoàn chỉnh, không có Docker Compose hay dữ liệu sách. | **Không clone develop**. Cài bản ổn định **Dokan Lite** từ wordpress.org. |
| [getdokan/dokani](https://github.com/getdokan/dokani) | Theme marketplace. Release `v1.0.1` (2024), tài liệu ghi tested WordPress 6.5.2 / WooCommerce 8.8.2. Dokan Lite hiện yêu cầu WordPress 7.0+. | **Không làm nền tảng**. Có thể thử cài thêm; nếu lỗi sẽ giữ **Storefront**. |

**Nền tảng đã chọn:** image Docker chính thức WordPress + MySQL, plugin từ kho WordPress.org, theme Storefront (Automattic, tương thích WooCommerce, vẫn được cập nhật).

## Yêu cầu

- Docker Desktop đang chạy
- Cổng `8081` trống

## Lệnh

```bash
cd cho-sach-cu
docker compose --project-name cho-sach-cu up -d db wordpress
docker compose --project-name cho-sach-cu --profile setup run --rm wpcli
```

Khởi động lại (giữ dữ liệu):

```bash
docker compose --project-name cho-sach-cu start
# hoặc
docker compose --project-name cho-sach-cu restart
```

Dừng (không xóa volume):

```bash
docker compose --project-name cho-sach-cu stop
# hoặc ./scripts/stop.sh
```

**Không chạy** `docker compose down -v`, `docker volume prune`, hay xóa volume `01-cua-hang_*` / `buoi11_*`.

## URL local

- Trang chủ: http://localhost:8081/
- Cửa hàng: http://localhost:8081/cua-hang/ (slug có thể là `/shop/` nếu ngôn ngữ chưa gắn permalink tiếng Việt)
- Quản trị: http://localhost:8081/wp-admin/

## Tài khoản thử nghiệm (chỉ local)

Đổi mật khẩu ngay trong wp-admin nếu máy này không chỉ dùng một mình.

Xem file `.env` (không commit):

- Admin: `csc_admin`
- Người bán: `vendor_sgk_demo`, `vendor_vanhoc_demo`, `vendor_ngoaingu_demo`
- Người mua: `buyer_demo`

## Sao lưu

```bash
./scripts/backup.sh
```

File SQL nằm trong `backups/`. Volume WordPress: `cho-sach-cu_wp_data`. Volume MySQL: `cho-sach-cu_db_data`.
