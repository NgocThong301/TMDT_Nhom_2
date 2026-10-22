<?php
/**
 * Plugin Name: CSC Chinh Sach Doi Tra
 * Description: Chinh sach doi tra hang cho Chợ Sách Cũ
 */

if (!defined('ABSPATH')) exit;

// Tạo page Chính sách đổi - trả
add_action('init', 'csc_create_return_policy_page');

function csc_create_return_policy_page() {
    $page = get_page_by_path('chinh-sach-doi-tra');
    
    if (!$page) {
        $content = csc_get_return_policy_content();
        wp_insert_post(array(
            'post_title'    => 'Chính sách đổi - trả',
            'post_name'     => 'chinh-sach-doi-tra',
            'post_content'  => $content,
            'post_status'   => 'publish',
            'post_type'     => 'page',
        ));
    }
}

function csc_get_return_policy_content() {
    return '
<div class="policy-page" style="max-width: 900px; margin: 30px auto; padding: 20px;">
<h2 style="text-align: center; color: #0073aa;">CHÍNH SÁCH ĐỔI - TRẢ HÀNG</h2>
<p style="text-align: center;"><strong>Cập nhật lần cuối: Tháng 10/2026</strong></p>

<h3>1. Điều kiện đổi - trả hàng</h3>
<p>Chợ Sách Cũ chấp nhận đổi/trả hàng trong các trường hợp sau:</p>
<ul>
<li><strong>Sách bị lỗi in:</strong> Trang bị mờ, chữ bị lem, thiếu trang, trang thừa.</li>
<li><strong>Sách bị hư hỏng trong quá trình vận chuyển:</strong> Bìa rách, méo, ướt, có dấu hiệu bị va đập.</li>
<li><strong>Sách không đúng với mô tả:</strong> Sai tiêu đề, tác giả, hình ảnh không đúng với sản phẩm thực tế.</li>
<li><strong>Sách không đúng tình trạng đã công bố:</strong> Ví dụ: đăng bán "Như mới" nhưng thực tế "Cũ rích".</li>
</ul>

<h3>2. Thời hạn đổi - trả</h3>
<table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
<tr style="background: #f0f0f0;">
<th>Lý do đổi/trả</th>
<th>Thời hạn</th>
<th>Chi phí</th>
</tr>
<tr>
<td>Lỗi từ nhà in/sách hư hỏng</td>
<td>48 giờ sau khi nhận hàng</td>
<td>Miễn phí</td>
</tr>
<tr>
<td>Không đúng mô tả/sai thông tin</td>
<td>48 giờ sau khi nhận hàng</td>
<td>Miễn phí</td>
</tr>
<tr>
<td>Người mua đổi ý</td>
<td>Không áp dụng</td>
<td>Không hoàn tiền</td>
</tr>
</table>

<h3>3. Quy trình đổi - trả</h3>
<ol>
<li><strong>Bước 1:</strong> Chụp ảnh sản phẩm và gửi qua mục "Hỗ trợ" trong tài khoản hoặc email: hotro@chosachcu.vn</li>
<li><strong>Bước 2:</strong> Chờ xác nhận từ người bán trong vòng 24 giờ</li>
<li><strong>Bước 3:</strong> Gửi hàng về địa chỉ được cung cấp (người bán sẽ gửi địa chỉ sau khi duyệt)</li>
<li><strong>Bước 4:</strong> Sau khi nhận hàng, người bán sẽ hoàn tiền qua tài khoản đã thanh toán trong vòng 3-5 ngày làm việc</li>
</ol>

<h3>4. Các trường hợp không được đổi - trả</h3>
<ul>
<li>Sách có dấu hiệu đã được sử dụng, ghi chép, tô màu</li>
<li>Sách có mùi khó chịu (thuốc lá, ẩm mốc trước khi giao)</li>
<li>Sách không còn nguyên vẹn: rách bìa, rơi ruột, mất trang</li>
<li>Đơn hàng đã quá thời hạn đổi/trả (48 giờ)</li>
<li>Người mua không cung cấp đầy đủ thông tin và hình ảnh lỗi</li>
</ul>

<h3>5. Hoàn tiền</h3>
<p>Tiền sẽ được hoàn vào tài khoản đã sử dụng thanh toán:</p>
<ul>
<li><strong>Thanh toán COD:</strong> Hoàn qua chuyển khoản ngân hàng</li>
<li><strong>Thanh toán online (VNPAY):</strong> Hoàn ngược vào tài khoản thanh toán trong 3-5 ngày làm việc</li>
</ul>

<h3>6. Liên hệ hỗ trợ</h3>
<p>Nếu có thắc mắc về chính sách đổi - trả, vui lòng liên hệ:</p>
<ul>
<li>Email: <strong>hotro@chosachcu.vn</strong></li>
<li>Hotline: <strong>1900 xxxx</strong> (8h00 - 17h00, Thứ 2 - Thứ 6)</li>
<li>Fanpage: fb.com/chosachcu</li>
</ul>
</div>
';
}
