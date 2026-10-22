<?php
/**
 * Plugin Name: CSC Chinh Sach Bao Ve Du Lieu
 * Description: Chinh sach bao ve du lieu ca nhan cho Chợ Sách Cũ
 */

if (!defined('ABSPATH')) exit;

// Tạo page Chính sách bảo vệ dữ liệu cá nhân
add_action('init', 'csc_create_privacy_policy_page');

function csc_create_privacy_policy_page() {
    $page = get_page_by_path('chinh-sach-bao-ve-du-lieu-ca-nhan');
    
    if (!$page) {
        $content = csc_get_privacy_policy_content();
        wp_insert_post(array(
            'post_title'    => 'Chính sách bảo vệ dữ liệu cá nhân',
            'post_name'     => 'chinh-sach-bao-ve-du-lieu-ca-nhan',
            'post_content'  => $content,
            'post_status'   => 'publish',
            'post_type'     => 'page',
        ));
    }
}

function csc_get_privacy_policy_content() {
    return '
<div class="policy-page" style="max-width: 900px; margin: 30px auto; padding: 20px;">
<h2 style="text-align: center; color: #0073aa;">CHÍNH SÁCH BẢO VỆ DỮ LIỆU CÁ NHÂN</h2>
<p style="text-align: center;"><strong>Cập nhật lần cuối: Tháng 10/2026</strong></p>
<p style="text-align: center;"><em>Áp dụng theo Nghị định số 13/2023/NĐ-CP về bảo vệ dữ liệu cá nhân</em></p>

<h3>1. Mục đích thu thập dữ liệu</h3>
<p>Chợ Sách Cũ thu thập dữ liệu cá nhân của bạn với các mục đích sau:</p>
<ul>
<li><strong>Tạo và quản lý tài khoản:</strong> Email, họ tên, mật khẩu (đã mã hóa)</li>
<li><strong>Xử lý đơn hàng:</strong> Địa chỉ giao hàng, số điện thoại, thông tin thanh toán</li>
<li><strong>Hỗ trợ khách hàng:</strong> Lịch sử đơn hàng, phản hồi, khiếu nại</li>
<li><strong>Marketing:</strong> Thông báo khuyến mãi (chỉ khi được đồng ý)</li>
<li><strong>Cải thiện dịch vụ:</strong> Phân tích hành vi người dùng để nâng cao trải nghiệm</li>
</ul>

<h3>2. Dữ liệu cá nhân được thu thập</h3>
<table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
<tr style="background: #f0f0f0;">
<th>Loại dữ liệu</th>
<th>Mục đích</th>
<th>Cơ sở pháp lý</th>
</tr>
<tr>
<td>Họ tên, email, số điện thoại</td>
<td>Đăng ký, đặt hàng, liên hệ</td>
<td>Thực hiện hợp đồng</td>
</tr>
<tr>
<td>Địa chỉ giao hàng</td>
<td>Giao hàng theo yêu cầu</td>
<td>Thực hiện hợp đồng</td>
</tr>
<tr>
<td>Thông tin thanh toán</td>
<td>Xử lý thanh toán</td>
<td>Thực hiện hợp đồng</td>
</tr>
<tr>
<td>Địa chỉ IP, cookie</td>
<td>Bảo mật, phân tích</td>
<td>Đồng ý của người dùng</td>
</tr>
<tr>
<td>Lịch sử duyệt web</td>
<td>Cải thiện trải nghiệm</td>
<td>Đồng ý của người dùng</td>
</tr>
</table>

<h3>3. Phương thức bảo vệ dữ liệu</h3>
<p>Chúng tôi áp dụng các biện pháp bảo mật sau:</p>
<ul>
<li><strong>Mã hóa dữ liệu:</strong> Sử dụng giao thức HTTPS (SSL/TLS) cho mọi kết nối</li>
<li><strong>Bảo mật mật khẩu:</strong> Mật khẩu được băm bằng thuật toán bcrypt, không lưu dạng rõ</li>
<li><strong>Kiểm soát truy cập:</strong> Phân quyền rõ ràng, chỉ nhân viên được ủy quyền mới truy cập dữ liệu</li>
<li><strong>Firewall & Anti-DDoS:</strong> Bảo vệ hệ thống khỏi tấn công mạng</li>
<li><strong>Backup định kỳ:</strong> Sao lưu dữ liệu hàng ngày, lưu trữ an toàn</li>
<li><strong>Giới hạn truy cập sai:</strong> Tạm khóa tài khoản sau 5 lần đăng nhập sai</li>
</ul>

<h3>4. Thời gian lưu trữ</h3>
<p>Dữ liệu cá nhân được lưu trữ trong thời gian:</p>
<ul>
<li><strong>Tài khoản người dùng:</strong> Lưu trữ cho đến khi người dùng yêu cầu xóa hoặc tài khoản không hoạt động trong 3 năm</li>
<li><strong>Đơn hàng:</strong> Lưu trữ tối thiểu 5 năm theo quy định kế toán</li>
<li><strong>Log đăng nhập:</strong> Lưu trữ 12 tháng phục vụ mục đích bảo mật</li>
</ul>

<h3>5. Quyền của người dùng</h3>
<p>Theo Nghị định 13/2023/NĐ-CP, bạn có các quyền sau:</p>
<ul>
<li><strong>Quyền được thông báo:</strong> Biết việc thu thập dữ liệu cá nhân</li>
<li><strong>Quyền đồng ý:</strong> Cho phép hoặc rút lại đồng ý thu thập dữ liệu</li>
<li><strong>Quyền truy cập:</strong> Xem, tải dữ liệu cá nhân của mình</li>
<li><strong>Quyền chỉnh sửa:</strong> Cập nhật thông tin cá nhân trong tài khoản</li>
<li><strong>Quyền xóa:</strong> Yêu cầu xóa dữ liệu cá nhân (trừ trường hợp pháp luật yêu cầu lưu)</li>
<li><strong>Quyền hạn chế xử lý:</strong> Yêu cầu hạn chế việc sử dụng dữ liệu</li>
<li><strong>Quyền khiếu nại:</strong> Khiếu nại nếu dữ liệu bị sử dụng sai mục đích</li>
</ul>

<h3>6. Chia sẻ dữ liệu với bên thứ ba</h3>
<p>Chúng tôi có thể chia sẻ dữ liệu với:</p>
<ul>
<li><strong>Người bán:</strong> Thông tin đơn hàng để giao hàng (địa chỉ, số điện thoại)</li>
<li><strong>Đơn vị vận chuyển:</strong> Thông tin giao hàng cần thiết</li>
<li><strong>Cổng thanh toán (VNPAY):</strong> Xử lý giao dịch - không lưu thông tin thẻ</li>
<li><strong>Cơ quan chức năng:</strong> Khi có yêu cầu pháp lý hợp lệ</li>
</ul>
<p>Chúng tôi cam kết không bán dữ liệu cá nhân cho bên thứ ba.</p>

<h3>7. Cookies và công nghệ theo dõi</h3>
<p>Website sử dụng cookies để:</p>
<ul>
<li><strong>Cookies cần thiết:</strong> Đăng nhập, giỏ hàng, ngôn ngữ</li>
<li><strong>Cookies phân tích:</strong> Google Analytics (ẩn danh) để cải thiện dịch vụ</li>
<li><strong>Cookies marketing:</strong> Chỉ khi bạn đồng ý</li>
</ul>
<p>Bạn có thể từ chối cookies trong cài đặt trình duyệt.</p>

<h3>8. Liên hệ về dữ liệu cá nhân</h3>
<p>Để thực hiện quyền của bạn hoặc báo cáo vấn đề bảo mật:</p>
<ul>
<li>Email: <strong>privacy@chosachcu.vn</strong></li>
<li>Hotline: <strong>1900 xxxx</strong> (8h00 - 17h00)</li>
<li>Gửi yêu cầu trực tiếp trong mục "Hỗ trợ" của tài khoản</li>
</ul>

<h3>9. Thay đổi chính sách</h3>
<p>Chúng tôi có thể cập nhật chính sách này theo thời gian. Thông báo sẽ được đăng trên website và gửi email nếu có thay đổi quan trọng.</p>
</div>
';
}
