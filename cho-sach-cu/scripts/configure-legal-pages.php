<?php
/**
 * Cấu hình các trang pháp lý theo yêu cầu bài tập lớn TMĐT
 * Tuân thủ: Luật Giao dịch điện tử 20/2023/QH15, Nghị định 52/2013/NĐ-CP,
 * Luật BVQLNT 19/2023/QH15, Nghị định 13/2023/NĐ-CP
 * 
 * Chạy: wp eval-file configure-legal-pages.php
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WooCommerce')) {
    if (class_exists('WP_CLI')) {
        WP_CLI::warning('WooCommerce chưa được kích hoạt.');
    }
    return;
}

// Helper function for logging
function csc_cli_log($message) {
    if (class_exists('WP_CLI')) {
        WP_CLI::log($message);
    } else {
        echo $message . "\n";
    }
}

function csc_cli_success($message) {
    if (class_exists('WP_CLI')) {
        WP_CLI::success($message);
    } else {
        echo "[OK] " . $message . "\n";
    }
}

/**
 * Tạo hoặc cập nhật trang
 */
function csc_create_page($title, $content, $option_key = '') {
    $found = get_posts(array(
        'post_type'      => 'page',
        'title'          => $title,
        'post_status'    => 'any',
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ));
    
    $existing = $found ? get_post($found[0]) : null;
    
    if ($existing) {
        $id = (int) $existing->ID;
        wp_update_post(array(
            'ID'           => $id,
            'post_content' => $content,
            'post_status'  => 'publish',
        ));
        csc_cli_log("Đã cập nhật trang: {$title}");
    } else {
        $id = wp_insert_post(array(
            'post_title'   => $title,
            'post_content'  => $content,
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_author'   => 1,
        ));
        csc_cli_success("Đã tạo trang: {$title}");
    }
    
    if ($option_key && $id) {
        update_option($option_key, $id);
    }
    
    return (int) $id;
}

// ============================================
// 1. TRANG ĐIỀU KIỆN GIAO DỊCH CHUNG
// ============================================
$dieu_khoan_content = '
<div class="terms-of-service">
    <h2>ĐIỀU KIỆN GIAO DỊCH CHUNG</h2>
    <p class="update-date"><em>Cập nhật lần cuối: Tháng 10/2026</em></p>
    
    <h3>1. Giới thiệu</h3>
    <p>Chợ Sách Cũ ("Công ty", "Chúng tôi") vận hành website <strong>chosachcu.vn</strong> với mô hình thương mại điện tử C2C (Consumer-to-Consumer), cho phép người dùng mua bán sách đã qua sử dụng. Khi sử dụng dịch vụ, Quý khách ("Bạn", "Người dùng") đồng ý tuân thủ các điều kiện dưới đây.</p>
    
    <h3>2. Đối tượng sử dụng</h3>
    <ul>
        <li>Người bán: Sinh viên và độc giả có tài khoản đã được xác thực trên nền tảng.</li>
        <li>Người mua: Mọi cá nhân có nhu cầu mua sách cũ qua website.</li>
        <li>Độ tuổi tối thiểu: 16 tuổi. Người dưới 18 tuổi cần có sự đồng ý của người giám hộ.</li>
    </ul>
    
    <h3>3. Quy trình giao kết hợp đồng điện tử</h3>
    <p>Theo Điều 16, Luật Giao dịch điện tử 20/2023/QH15:</p>
    <ol>
        <li>Người dùng chọn sản phẩm và thêm vào giỏ hàng.</li>
        <li>Người dùng xác nhận thông tin đơn hàng (sản phẩm, số lượng, địa chỉ giao hàng).</li>
        <li>Người dùng nhấn nút "Đặt hàng" - đây là bước xác nhận đơn hàng theo quy định pháp luật.</li>
        <li>Hệ thống gửi email xác nhận đơn hàng tới địa chỉ email đã đăng ký.</li>
        <li>Hợp đồng được xem là giao kết khi người dùng nhận được xác nhận từ hệ thống.</li>
    </ol>
    
    <h3>4. Trách nhiệm của các bên</h3>
    <h4>4.1. Người bán</h4>
    <ul>
        <li>Cung cấp thông tin sản phẩm chính xác, trung thực (tiêu đề, mô tả, tình trạng, hình ảnh thật).</li>
        <li>Giao hàng trong thời hạn đã thỏa thuận (tối đa 5 ngày làm việc kể từ khi xác nhận thanh toán).</li>
        <li>Chịu trách nhiệm về nguồn gốc và tính hợp pháp của sản phẩm.</li>
        <li>Không được bán sách có nội dung vi phạm pháp luật Việt Nam.</li>
    </ul>
    
    <h4>4.2. Người mua</h4>
    <ul>
        <li>Cung cấp thông tin giao hàng chính xác và đầy đủ.</li>
        <li>Thanh toán đúng hạn theo phương thức đã chọn.</li>
        <li>Kiểm tra sản phẩm ngay khi nhận hàng và thông báo khiếu nại trong 48 giờ.</li>
    </ul>
    
    <h4>4.3. Nền tảng Chợ Sách Cũ</h4>
    <ul>
        <li>Cung cấp nền tảng giao dịch an toàn và thông tin cần thiết cho người dùng.</li>
        <li>Hỗ trợ giải quyết tranh chấp phát sinh trong quá trình giao dịch.</li>
        <li>Bảo mật thông tin cá nhân theo Chính sách bảo vệ dữ liệu cá nhân.</li>
    </ul>
    
    <h3>5. Giá cả và thanh toán</h3>
    <ul>
        <li>Giá sản phẩm được niêm yết bằng đồng Việt Nam (VND), đã bao gồm thuế.</li>
        <li>Phí vận chuyển được tính riêng và hiển thị trước khi xác nhận đặt hàng.</li>
        <li>Phương thức thanh toán: COD (thanh toán khi nhận hàng), VNPay (thẻ ATM/Visa/MasterCard).</li>
    </ul>
    
    <h3>6. Hạn chế trách nhiệm</h3>
    <p>Công ty không chịu trách nhiệm trong các trường hợp:</p>
    <ul>
        <li>Sự chậm trễ giao hàng do người bán hoặc đơn vị vận chuyển.</li>
        <li>Thông tin sản phẩm không chính xác do người bán cung cấp sai.</li>
        <li>Sự cố kỹ thuật nằm ngoài tầm kiểm soát (thiên tai, điện lưới quốc gia, v.v.).</li>
    </ul>
    
    <h3>7. Thay đổi điều khoản</h3>
    <p>Công ty có quyền sửa đổi các điều khoản này. Thông báo thay đổi sẽ được đăng tải trên website và có hiệu lực sau 07 ngày kể từ ngày đăng thông báo.</p>
    
    <h3>8. Luật áp dụng và giải quyết tranh chấp</h3>
    <p>Các điều khoản này được điều chỉnh bởi pháp luật Việt Nam. Mọi tranh chấp phát sinh sẽ được giải quyết thông qua thương lượng; nếu không thành, sẽ đưa ra Tòa án nhân dân có thẩm quyền.</p>
    
    <h3>9. Thông tin liên hệ</h3>
    <p>
        <strong>Công ty TNHH Chợ Sách Cũ</strong><br>
        Địa chỉ: Tầng 3, Tòa nhà A, Đại học ...<br>
        Email: hotro@chosachcu.vn<br>
        Điện thoại: 0123 456 789<br>
        Mã số thuế: 0123456789
    </p>
</div>
';

$csc_dieu_khoan_id = csc_create_page('Điều khoản giao dịch', $dieu_khoan_content, 'csc_page_dieu_khoan');

// ============================================
// 2. TRANG CHÍNH SÁCH ĐỔI TRẢ
// ============================================
$doi_tra_content = '
<div class="return-policy">
    <h2>CHÍNH SÁCH ĐỔI – TRẢ HÀNG</h2>
    <p class="update-date"><em>Cập nhật lần cuối: Tháng 10/2026</em></p>
    
    <h3>1. Phạm vi áp dụng</h3>
    <p>Chính sách này áp dụng cho tất cả sản phẩm sách được bán trên website <strong>chosachcu.vn</strong>, bao gồm sách giáo khoa, giáo trình, sách văn học, và các loại sách khác.</p>
    
    <h3>2. Điều kiện đổi trả</h3>
    <h4>2.1. Các trường hợp được đổi trả</h4>
    <ul>
        <li><strong>Sai sản phẩm:</strong> Giao sai tên sách, sai ISBN, sai phiên bản (bìa cứng/bìa mềm).</li>
        <li><strong>Sản phẩm hỏng:</strong> Bìa rách, trang bị rách, ẩm mốc, chữ mờ không đọc được.</li>
        <li><strong>Không đúng mô tả:</strong> Tình trạng sách thực tế khác biệt nghiêm trọng so với mô tả của người bán.</li>
        <li><strong>Thiếu phụ kiện:</strong> Đơn hàng thiếu phần nội dung, đĩa CD, tài liệu đi kèm (nếu có ghi trong mô tả).</li>
    </ul>
    
    <h4>2.2. Các trường hợp không được đổi trả</h4>
    <ul>
        <li>Sách có dấu hiệu đã sử dụng, ghi chép, tô màu (trừ khi được mô tả là sách mới).</li>
        <li>Sách có mùi烟, mùi ẩm do quá trình vận chuyển nhưng không được báo cáo trong 48 giờ.</li>
        <li>Sách không còn trong tình trạng ban đầu (mất hộp, mất tem, v.v.).</li>
        <li>Yêu cầu đổi trả vì lý do thị hiếu (không thích, không cần nữa).</li>
        <li>Đơn hàng đã được xác nhận giao hàng quá 48 giờ mà không có khiếu nại.</li>
    </ul>
    
    <h3>3. Thời hạn đổi trả</h3>
    <table>
        <tr>
            <th>Loại khiếu nại</th>
            <th>Thời hạn khiếu nại</th>
            <th>Thời gian xử lý</th>
        </tr>
        <tr>
            <td>Sai sản phẩm / Hỏng</td>
            <td>Trong 48 giờ sau khi nhận hàng</td>
            <td>3-5 ngày làm việc</td>
        </tr>
        <tr>
            <td>Không đúng mô tả</td>
            <td>Trong 48 giờ sau khi nhận hàng</td>
            <td>3-5 ngày làm việc</td>
        </tr>
    </table>
    
    <h3>4. Quy trình yêu cầu đổi trả</h3>
    <ol>
        <li><strong>Bước 1 - Thông báo:</strong> Gửi yêu cầu qua email hotro@chosachcu.vn hoặc qua trang "Liên hệ" với:
            <ul>
                <li>Mã đơn hàng</li>
                <li>Hình ảnh sản phẩm nhận được</li>
                <li>Mô tả vấn đề</li>
            </ul>
        </li>
        <li><strong>Bước 2 - Phản hồi:</strong> Bộ phận hỗ trợ sẽ phản hồi trong vòng 24 giờ.</li>
        <li><strong>Bước 3 - Gửi hàng:</strong> Nếu yêu cầu được chấp nhận, gửi sản phẩm về địa chỉ được cung cấp.</li>
        <li><strong>Bước 4 - Hoàn tiền/Đổi hàng:</strong> Sau khi xác nhận sản phẩm, hoàn tiền hoặc gửi sản phẩm thay thế.</li>
    </ol>
    
    <h3>5. Chi phí vận chuyển</h3>
    <ul>
        <li><strong>Lỗi từ người bán/nền tảng:</strong> Chi phí vận chuyển đổi trả do bên bán chịu.</li>
        <li><strong>Lỗi từ người mua:</strong> Chi phí vận chuyển do người mua chịu.</li>
    </ul>
    
    <h3>6. Hoàn tiền</h3>
    <p>Hoàn tiền sẽ được xử lý trong vòng 7-10 ngày làm việc kể từ khi xác nhận sản phẩm đổi trả:</p>
    <ul>
        <li><strong>Thanh toán VNPay:</strong> Hoàn tiền vào tài khoản đã thanh toán.</li>
        <li><strong>Thanh toán COD:</strong> Hoàn tiền qua chuyển khoản ngân hàng.</li>
    </ul>
    
    <h3>7. Liên hệ hỗ trợ</h3>
    <p>
        <strong>Bộ phận Chăm sóc Khách hàng</strong><br>
        Email: hotro@chosachcu.vn<br>
        Điện thoại: 0123 456 789<br>
        Giờ làm việc: 8h00 - 17h00 (Thứ 2 - Thứ 6)
    </p>
</div>
';

$csc_doi_tra_id = csc_create_page('Chính sách đổi trả', $doi_tra_content, 'csc_page_doi_tra');

// ============================================
// 3. TRANG CHÍNH SÁCH BẢO VỆ DỮ LIỆU CÁ NHÂN
// ============================================
$bao_mat_content = '
<div class="privacy-policy">
    <h2>CHÍNH SÁCH BẢO VỆ DỮ LIỆU CÁ NHÂN</h2>
    <p class="update-date"><em>Cập nhật lần cuối: Tháng 10/2026</em></p>
    
    <h3>1. Mục đích và phạm vi</h3>
    <p>Chính sách này được xây dựng tuân thủ <strong>Nghị định số 13/2023/NĐ-CP</strong> về bảo vệ dữ liệu cá nhân và <strong>Luật Bảo vệ quyền lợi người tiêu dùng số 19/2023/QH15</strong>.</p>
    <p>Chúng tôi thu thập và xử lý dữ liệu cá nhân của bạn với mục đích:</p>
    <ul>
        <li>Tạo và quản lý tài khoản người dùng.</li>
        <li>Xử lý đơn hàng và giao hàng.</li>
        <li>Hỗ trợ khách hàng và giải quyết khiếu nại.</li>
        <li>Cải thiện chất lượng dịch vụ và trải nghiệm người dùng.</li>
        <li>Tuân thủ yêu cầu pháp lý.</li>
    </ul>
    
    <h3>2. Dữ liệu cá nhân được thu thập</h3>
    <h4>2.1. Dữ liệu bắt buộc</h4>
    <table>
        <tr>
            <th>Loại dữ liệu</th>
            <th>Mục đích</th>
            <th>Cơ sở pháp lý</th>
        </tr>
        <tr>
            <td>Họ tên</td>
            <td>Xác định danh tính, giao hàng</td>
            <td>Thực hiện hợp đồng</td>
        </tr>
        <tr>
            <td>Email</td>
            <td>Tài khoản, thông báo đơn hàng</td>
            <td>Thực hiện hợp đồng</td>
        </tr>
        <tr>
            <td>Số điện thoại</td>
            <td>Liên hệ giao hàng, hỗ trợ</td>
            <td>Thực hiện hợp đồng</td>
        </tr>
        <tr>
            <td>Địa chỉ giao hàng</td>
            <td>Giao sản phẩm</td>
            <td>Thực hiện hợp đồng</td>
        </tr>
    </table>
    
    <h4>2.2. Dữ liệu tùy chọn</h4>
    <ul>
        <li>Ảnh đại diện (avatar).</li>
        <li>Thông tin thanh toán (chỉ lưu thông tin cần thiết, không lưu số thẻ).</li>
        <li>Lịch sử đơn hàng và hoạt động trên website.</li>
    </ul>
    
    <h3>3. Phương thức thu thập</h3>
    <ul>
        <li>Đăng ký tài khoản trực tiếp trên website.</li>
        <li>Đăng nhập qua tài khoản mạng xã hội (nếu có).</li>
        <li>Đặt hàng và thanh toán.</li>
        <li>Liên hệ hỗ trợ qua email hoặc form.</li>
        <li>Cookie và công nghệ theo dõi tương tự.</li>
    </ul>
    
    <h3>4. Bảo mật dữ liệu</h3>
    <h4>4.1. Biện pháp kỹ thuật</h4>
    <ul>
        <li><strong>Mã hóa:</strong> Dữ liệu truyền tải qua giao thức HTTPS (SSL/TLS).</li>
        <li><strong>Băm mật khẩu:</strong> Sử dụng thuật toán bcrypt/Argon2, không lưu mật khẩu dạng rõ.</li>
        <li><strong>Kiểm soát truy cập:</strong> Phân quyền theo vai trò, chỉ nhân viên được ủy quyền mới truy cập dữ liệu.</li>
        <li><strong>Tường lửa &amp; Anti-DDoS:</strong> Bảo vệ hệ thống khỏi tấn công.</li>
    </ul>
    
    <h4>4.2. Biện pháp tổ chức</h4>
    <ul>
        <li>Giới hạn số lượng nhân viên có quyền truy cập dữ liệu cá nhân.</li>
        <li>Đào tạo nhân viên về bảo vệ dữ liệu.</li>
        <li>Ký cam kết bảo mật với các bên thứ ba có quyền truy cập dữ liệu.</li>
    </ul>
    
    <h3>5. Lưu trữ và xóa dữ liệu</h3>
    <ul>
        <li><strong>Thời gian lưu trữ:</strong> Dữ liệu được lưu trong thời gian tài khoản hoạt động và 02 năm sau khi tài khoản bị xóa hoặc hủy.</li>
        <li><strong>Xóa dữ liệu:</strong> Bạn có quyền yêu cầu xóa dữ liệu cá nhân; chúng tôi sẽ thực hiện trong vòng 72 giờ (trừ khi pháp luật yêu cầu lưu lâu hơn).</li>
        <li><strong>Dữ liệu ẩn danh:</strong> Có thể được giữ lại cho mục đích thống kê, phân tích.</li>
    </ul>
    
    <h3>6. Quyền của chủ sở hữu dữ liệu</h3>
    <p>Theo Nghị định 13/2023/NĐ-CP, bạn có các quyền sau:</p>
    <ol>
        <li><strong>Quyền được thông báo:</strong> Biết mục đích và phương thức xử lý dữ liệu.</li>
        <li><strong>Quyền đồng ý:</strong> Cho phép hoặc rút lại sự đồng ý xử lý dữ liệu.</li>
        <li><strong>Quyền truy cập:</strong> Xem, tải dữ liệu cá nhân của mình.</li>
        <li><strong>Quyền chỉnh sửa:</strong> Cập nhật, sửa đổi thông tin cá nhân.</li>
        <li><strong>Quyền xóa:</strong> Yêu cầu xóa dữ liệu cá nhân.</li>
        <li><strong>Quyền hạn chế:</strong> Yêu cầu giới hạn xử lý dữ liệu.</li>
        <li><strong>Quyền phản đối:</strong> Phản đối việc xử lý cho mục đích tiếp thị.</li>
        <li><strong>Quyền khiếu nại:</strong> Khiếu nại với cơ quan nhà nước có thẩm quyền.</li>
    </ol>
    
    <h3>7. Chia sẻ dữ liệu với bên thứ ba</h3>
    <p>Chúng tôi có thể chia sẻ dữ liệu với:</p>
    <ul>
        <li><strong>Người bán:</strong> Thông tin cần thiết để thực hiện đơn hàng (họ tên, địa chỉ, số điện thoại).</li>
        <li><strong>Đơn vị vận chuyển:</strong> Thông tin giao hàng (Tên, Địa chỉ, SĐT).</li>
        <li><strong>Cổng thanh toán:</strong> VNPay để xử lý thanh toán (theo chính sách bảo mật của VNPay).</li>
        <li><strong>Cơ quan chức năng:</strong> Khi được yêu cầu theo quy định pháp luật.</li>
    </ul>
    <p><strong>Không bán dữ liệu cá nhân</strong> cho bên thứ ba vì mục đích tiếp thị.</p>
    
    <h3>8. Cookie</h3>
    <p>Website sử dụng cookie để:</p>
    <ul>
        <li>Ghi nhớ đăng nhập và tùy chọn người dùng.</li>
        <li>Phân tích lưu lượng truy cập (Google Analytics ẩn danh).</li>
        <li>Quản lý giỏ hàng và phiên mua sắm.</li>
    </ul>
    <p>Bạn có thể tắt cookie trong trình duyệt, tuy nhiên một số chức năng có thể bị hạn chế.</p>
    
    <h3>9. Thay đổi chính sách</h3>
    <p>Chúng tôi có thể cập nhật chính sách này. Thông báo sẽ được đăng trên website; nếu thay đổi quan trọng, sẽ gửi email thông báo tới bạn.</p>
    
    <h3>10. Liên hệ</h3>
    <p>
        <strong>Để thực hiện quyền hoặc báo cáo vấn đề về dữ liệu cá nhân:</strong><br><br>
        <strong>Công ty TNHH Chợ Sách Cũ</strong><br>
        Email: privacy@chosachcu.vn<br>
        Địa chỉ: Tầng 3, Tòa nhà A, Đại học ...<br><br>
        Ngoài ra, bạn có quyền khiếu nại với <strong>Cục An toàn thông tin, Bộ Thông tin và Truyền thông</strong>.
    </p>
</div>
';

$csc_bao_mat_id = csc_create_page('Chính sách bảo mật', $bao_mat_content, 'csc_page_bao_mat');

// ============================================
// 4. TRANG THÔNG TIN NGƯỜI BÁN
// ============================================
$thong_tin_content = '
<div class="seller-info">
    <h2>THÔNG TIN NGƯỜI BÁN</h2>
    
    <h3>1. Thông tin công ty</h3>
    <table class="info-table">
        <tr>
            <th>Tên doanh nghiệp:</th>
            <td>Công ty TNHH Chợ Sách Cũ</td>
        </tr>
        <tr>
            <th>Địa chỉ trụ sở:</th>
            <td>Tầng 3, Tòa nhà A, Đại học ...</td>
        </tr>
        <tr>
            <th>Giấy CNĐKKD số:</th>
            <td>0123456789 - Cấp ngày: .../.../2026</td>
        </tr>
        <tr>
            <th>Nơi cấp:</th>
            <td>Sở Kế hoạch và Đầu tư ...</td>
        </tr>
        <tr>
            <th>Mã số thuế:</th>
            <td>0123456789</td>
        </tr>
        <tr>
            <th>Email:</th>
            <td>hotro@chosachcu.vn</td>
        </tr>
        <tr>
            <th>Điện thoại:</th>
            <td>0123 456 789</td>
        </tr>
    </table>
    
    <h3>2. Cam kết với người tiêu dùng</h3>
    <ul>
        <li>Cung cấp thông tin chính xác, minh bạch về sản phẩm và dịch vụ.</li>
        <li>Tuân thủ các quy định về bảo vệ quyền lợi người tiêu dùng theo Luật BVQLNT số 19/2023/QH15.</li>
        <li>Giải quyết khiếu nại, tranh chấp một cách thiện chí.</li>
        <li>Bảo vệ thông tin cá nhân của khách hàng theo Nghị định 13/2023/NĐ-CP.</li>
    </ul>
    
    <h3>3. Thời gian hỗ trợ</h3>
    <p><strong>Giờ làm việc:</strong> 8h00 - 17h00 (Thứ 2 - Thứ 6)</p>
    <p><strong>Phản hồi khiếu nại:</strong> Trong vòng 24 giờ làm việc</p>
</div>
';

$csc_thong_tin_id = csc_create_page('Thông tin người bán', $thong_tin_content, 'csc_page_thong_tin');

// ============================================
// 5. TẠO MENU FOOTER
// ============================================
$footer_menuLocations = get_nav_menu_locations();
$footer_menu_id = isset($footer_menuLocations['footer']) ? $footer_menuLocations['footer'] : 0;

if (!$footer_menu_id) {
    // Tạo menu mới nếu chưa có
    $menu_name = 'Footer Menu';
    $menu_exists = wp_get_nav_menu_object($menu_name);
    
    if (!$menu_exists) {
        $footer_menu_id = wp_create_nav_menu($menu_name);
        
        // Thêm các trang vào menu
        wp_update_nav_menu_item($footer_menu_id, 0, array(
            'menu-item-title' => 'Điều khoản giao dịch',
            'menu-item-object' => 'page',
            'menu-item-object-id' => $csc_dieu_khoan_id,
            'menu-item-type' => 'post_type',
            'menu-item-status' => 'publish',
        ));
        
        wp_update_nav_menu_item($footer_menu_id, 0, array(
            'menu-item-title' => 'Chính sách đổi trả',
            'menu-item-object' => 'page',
            'menu-item-object-id' => $csc_doi_tra_id,
            'menu-item-type' => 'post_type',
            'menu-item-status' => 'publish',
        ));
        
        wp_update_nav_menu_item($footer_menu_id, 0, array(
            'menu-item-title' => 'Chính sách bảo mật',
            'menu-item-object' => 'page',
            'menu-item-object-id' => $csc_bao_mat_id,
            'menu-item-type' => 'post_type',
            'menu-item-status' => 'publish',
        ));
        
        wp_update_nav_menu_item($footer_menu_id, 0, array(
            'menu-item-title' => 'Thông tin người bán',
            'menu-item-object' => 'page',
            'menu-item-object-id' => $csc_thong_tin_id,
            'menu-item-type' => 'post_type',
            'menu-item-status' => 'publish',
        ));
        
        // Gán menu location
        $theme_mods = get_theme_mods();
        if (function_exists('register_nav_menus')) {
            // Đảm bảo location tồn tại
            add_action('after_setup_theme', function() {
                register_nav_menus(array('footer' => 'Footer Menu'));
            });
        }
        
        // Gán location cho theme
        $locations = get_theme_mod('nav_menu_locations', array());
        $locations['footer'] = $footer_menu_id;
        set_theme_mod('nav_menu_locations', $locations);
        
        csc_cli_success('Đã tạo Footer Menu với các trang pháp lý.');
    }
}

// ============================================
// 6. THÊM WIDGET FOOTER NẾU CẦN
// ============================================

csc_cli_success('Đã cấu hình xong các trang pháp lý!');
csc_cli_log('Các trang đã tạo:');
csc_cli_log('- Điều khoản giao dịch (ID: ' . $csc_dieu_khoan_id . ')');
csc_cli_log('- Chính sách đổi trả (ID: ' . $csc_doi_tra_id . ')');
csc_cli_log('- Chính sách bảo mật (ID: ' . $csc_bao_mat_id . ')');
csc_cli_log('- Thông tin người bán (ID: ' . $csc_thong_tin_id . ')');
csc_cli_log('');
csc_cli_log('Bổ sung vào footer menu để hiển thị ở chân trang website.');
