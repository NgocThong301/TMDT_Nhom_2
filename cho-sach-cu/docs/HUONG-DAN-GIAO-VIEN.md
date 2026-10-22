# Hướng dẫn sử dụng Chợ Sách Cũ cho Giáo viên

## Mục lục
1. [Tổng quan hệ thống](#1-tổng-quan-hệ-thống)
2. [Truy cập hệ thống](#2-truy-cập-hệ-thống)
3. [Tài khoản thử nghiệm](#3-tài-khoản-thử-nghiệm)
4. [Luồng mua hàng C2C](#4-luồng-mua-hàng-c2c)
5. [Quản lý đơn hàng](#5-quản-lý-đơn-hàng)
6. [Tính năng Vendor (Người bán)](#6-tính-năng-vendor-người-bán)
7. [Xem báo cáo và thống kê](#7-xem-báo-cáo-và-thống-kê)
8. [Khắc phục sự cố thường gặp](#8-khắc-phục-sự-cố-thường-gặp)

---

## 1. Tổng quan hệ thống

### Công nghệ sử dụng
- **WordPress 7.x** - Hệ quản trị nội dung
- **WooCommerce** - Thương mại điện tử
- **Dokan Lite** - Plugin marketplace đa người bán
- **Theme Storefront** - Giao diện chính thức WooCommerce
- **Docker** - Container hóa (chạy local)

### Mô hình C2C
```
Người mua ←→ Website Chợ Sách Cũ ←→ Người bán
                              ↑
                         Admin quản lý
```

- **Người bán**: Sinh viên / cá nhân đăng bán sách cũ
- **Người mua**: Sinh viên / độc giả tìm và mua sách
- **Admin**: Giáo viên quản lý toàn bộ hệ thống

---

## 2. Truy cập hệ thống

### URL Local
| Trang | Địa chỉ |
|-------|---------|
| Trang chủ | http://localhost:8081/ |
| Cửa hàng | http://localhost:8081/cua-hang/ |
| Quản trị | http://localhost:8081/wp-admin/ |
| Tài khoản | http://localhost:8081/tai-khoan-cua-toi/ |
| Vendor Dashboard | http://localhost:8081/kenh-nguoi-ban/ |

### Yêu cầu
- Docker Desktop đang chạy
- Cổng 8081 không bị chiếm dụng

---

## 3. Tài khoản thử nghiệm

### 3.1 Tài khoản Admin (Quản trị viên)

| Trường | Giá trị |
|--------|---------|
| Username | `csc_admin` |
| Password | Xem file `.env` |
| Email | `admin@cho-sach-cu.local` |
| Quyền hạn | Toàn quyền quản lý |

**Đăng nhập:** http://localhost:8081/wp-admin/

**Quyền hạn:**
- Quản lý tất cả sản phẩm, đơn hàng
- Quản lý tài khoản người dùng
- Cấu hình WooCommerce, Dokan
- Xem báo cáo doanh thu

### 3.2 Tài khoản Vendor (Người bán)

| Vendor | Username | Cửa hàng | Danh mục |
|--------|----------|-----------|----------|
| SGK | `vendor_sgk_demo` | Tủ sách lớp 12 | Sách giáo khoa |
| Văn học | `vendor_vanhoc_demo` | Sách cũ Nguyễn Du | Văn học, Kinh tế, Kỹ năng |
| Ngoại ngữ | `vendor_ngoaingu_demo` | Góc IELTS secondhand | Ngoại ngữ, Truyện tranh |

**Đăng nhập Vendor Dashboard:** http://localhost:8081/kenh-nguoi-ban/

**Quyền hạn:**
- Đăng / chỉnh sửa sản phẩm của mình
- Xem và xử lý đơn hàng liên quan
- Xem doanh thu cá nhân
- Quản lý địa chỉ giao hàng

### 3.3 Tài khoản Customer (Người mua)

| Username | Địa chỉ | Đơn hàng mẫu |
|----------|---------|--------------|
| `buyer_demo` | Hà Nội | 0 |
| `customer_hanoi` | Hà Nội | Nhiều |
| `customer_hcm` | TP.HCM | Nhiều |
| `customer_danang` | Đà Nẵng | Nhiều |

**Password tất cả:** lấy từ file `.env` ở thư mục gốc dự án (`BUYER_PASSWORD`) — không commit file này lên Git.

**Đăng nhập:** http://localhost:8081/tai-khoan-cua-toi/

**Quyền hạn:**
- Xem và mua sản phẩm
- Quản lý đơn hàng cá nhân
- Cập nhật thông tin tài khoản
- Xem lịch sử giao dịch

---

## 4. Luồng mua hàng C2C

### 4.1 Bước 1: Đăng nhập / Đăng ký (Người mua)

```
1. Vào http://localhost:8081/
2. Click "Tài khoản" ở header
3. Đăng nhập với tài khoản customer
   hoặc Đăng ký tài khoản mới
```

### 4.2 Bước 2: Tìm kiếm và xem sản phẩm

```
1. Vào Cửa hàng
2. Lọc theo danh mục (Sách giáo khoa, Văn học, v.v.)
3. Click vào sản phẩm để xem chi tiết
4. Kiểm tra: Tác giả, Tình trạng, Giá, Người bán
```

### 4.3 Bước 3: Thêm vào giỏ hàng

```
1. Chọn số lượng (nếu > 1)
2. Click "Thêm vào giỏ hàng"
3. Tiếp tục mua hoặc Xem giỏ hàng
```

### 4.4 Bước 4: Thanh toán

**Cách 1: COD (Thanh toán khi nhận hàng)**
```
1. Chọn "Thanh toán khi nhận hàng"
2. Nhập địa chỉ giao hàng
3. Đặt hàng
4. Chờ người bán xác nhận
5. Nhận hàng và trả tiền mặt
```

**Cách 2: Chuyển khoản (Test)**
```
1. Chọn "Chuyển khoản ngân hàng"
2. Ghi nhớ nội dung chuyển khoản: CSC-TEST
3. Đặt hàng
4. Chuyển khoản (giả lập trong test mode)
5. Admin/Vendor xác nhận đã nhận tiền
```

### 4.5 Bước 5: Theo dõi đơn hàng

```
1. Vào "Tài khoản của tôi"
2. Click "Đơn hàng"
3. Xem trạng thái đơn hàng:
   - Đang chờ xử lý (pending)
   - Đang xử lý (processing)
   - Đã giao cho vận chuyển (shipped)
   - Hoàn thành (completed)
   - Đã hủy (cancelled)
```

---

## 5. Quản lý đơn hàng

### 5.1 Xem đơn hàng (Admin)

```
1. Đăng nhập wp-admin
2. WooCommerce > Đơn hàng
3. Click vào đơn hàng để xem chi tiết
```

### 5.2 Các trạng thái đơn hàng

| Trạng thái | Màu | Ý nghĩa |
|------------|-----|---------|
| Đang chờ (Pending) | Cam | Chờ thanh toán |
| Đang chờ xử lý (On Hold) | Xám | Chờ xác nhận |
| Đang xử lý (Processing) | Xanh dương | Đang chuẩn bị hàng |
| Đã giao (Shipped) | Tím | Đã giao cho vận chuyển |
| Hoàn thành (Completed) | Xanh | Giao hàng thành công |
| Đã hủy (Cancelled) | Đỏ | Đơn đã bị hủy |
| Hoàn tiền (Refunded) | Đỏ | Đã hoàn tiền |

### 5.3 Thao tác với đơn hàng

- **Cập nhật trạng thái**: Edit > Status > Update
- **Thêm ghi chú**: Box "Order Notes"
- **Xem chi tiết sản phẩm**: Tab "Order items"
- **In hóa đơn**: "Print PDF Invoice"

### 5.4 Luồng xử lý đơn hàng cho Vendor

```
1. Nhận đơn mới (trạng thái: processing)
2. Đóng gói sách
3. Giao cho đơn vị vận chuyển
4. Cập nhật trạng thái: Shipped
5. (Tùy chọn) Thêm tracking number
```

---

## 6. Tính năng Vendor (Người bán)

### 6.1 Đăng sản phẩm mới

```
1. Đăng nhập Kênh người bán
2. Products > Thêm sản phẩm mới
3. Điền thông tin:
   - Tiêu đề: Tên sách
   - Giá: Giá bán (VND)
   - Danh mục: Chọn loại sách
   - Thuộc tính: Tác giả, Tình trạng
   - Mô tả: Chi tiết sách
   - Ảnh bìa: Upload hình
4. Publish
```

### 6.2 Quản lý đơn hàng Vendor

```
1. Vendor Dashboard > Orders
2. Xem đơn hàng liên quan đến cửa hàng
3. Cập nhật trạng thái:
   - Xác nhận đơn
   - Đánh dấu đã gửi
   - Thêm tracking (nếu có)
```

### 6.3 Xem doanh thu

```
1. Vendor Dashboard > Dashboard
2. Xem thống kê:
   - Tổng doanh thu
   - Số đơn hàng
   - Sản phẩm bán chạy
   - Đánh giá khách hàng
```

---

## 7. Xem báo cáo và thống kê

### 7.1 Báo cáo WooCommerce (Admin)

```
wp-admin > WooCommerce > Báo cáo
```

**Các loại báo cáo:**
- **Tổng quan**: Doanh thu, đơn hàng, sản phẩm bán
- **Theo ngày/tháng/năm**: Biểu đồ doanh thu
- **Theo sản phẩm**: Top sản phẩm bán chạy
- **Theo danh mục**: Doanh thu theo loại sách
- **Theo khách hàng**: Top khách hàng mua nhiều

### 7.2 Báo cáo Dokan (Vendor)

```
Vendor Dashboard > Reports
```

**Thông tin có:**
- Doanh thu theo kỳ
- Phí hoa hồng (nếu có)
- Số dư khả dụng
- Lịch sử rút tiền

---

## 8. Khắc phục sự cố thường gặp

### 8.1 Không truy cập được website

**Kiểm tra Docker:**
```bash
cd cho-sach-cu
docker compose ps
```

**Khởi động lại:**
```bash
docker compose restart
```

### 8.2 Trang trắng / Lỗi 500

```bash
# Xem log
docker compose logs wordpress

# Khởi động lại
docker compose restart wordpress
```

### 8.3 Không đăng nhập được

1. Kiểm tra username/password trong `.env`
2. Reset password qua wp-admin > Users
3. Xóa cache trình duyệt

### 8.4 Không thấy sản phẩm

1. Kiểm tra trạng thái sản phẩm (phải là Publish)
2. Kiểm tra Dokan > Product Status (pending/approved)
3. Kiểm tra visibility trong Edit Product

### 8.5 Thanh toán không hoạt động

**Hiện tại chỉ có:**
- COD (thanh toán khi nhận hàng)
- BACS (chuyển khoản - test mode)

**Chưa cấu hình:**
- PayPal (cần tài khoản PayPal Developer)
- Stripe (cần đăng ký merchant)

---

## Liên hệ hỗ trợ

Nếu gặp lỗi không xử lý được:
1. Chụp ảnh lỗi
2. Gửi file log: `docker compose logs wordpress > error.log`
3. Liên hệ người phụ trách kỹ thuật

---

*Document version: 1.0*
*Last updated: October 2026*
*For local testing only - Chợ Sách Cũ*
