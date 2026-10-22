<?php
/**
 * Plugin Name: Login Limit Security
 * Description: Giới hạn số lần đăng nhập sai để phòng chống brute force
 * Version: 1.0
 * Author: Chợ Sách Cũ
 * Text Domain: login-limit
 * 
 * Yêu cầu bài tập lớn TMĐT:
 * - Giới hạn số lần đăng nhập sai
 * - Khóa tài khoản tạm thời
 * - Ghi vết đăng nhập
 */

if (!defined('ABSPATH')) {
    exit;
}

class CSC_Login_Limit {
    
    /**
     * Số lần thử tối đa
     */
    const MAX_ATTEMPTS = 5;
    
    /**
     * Thời gian khóa (phút)
     */
    const LOCKOUT_DURATION = 15;
    
    /**
     * Thời gian theo dõi (phút)
     */
    const TRACKING_WINDOW = 30;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook vào đăng nhập thất bại
        add_action('wp_login_failed', array($this, 'track_failed_login'));
        
        // Kiểm tra trước khi đăng nhập
        add_filter('authenticate', array($this, 'check_login_attempts'), 30, 3);
        
        // Thêm thông báo khóa
        add_filter('login_errors', array($this, 'custom_login_error'));
        
        // Hook vào đăng nhập thành công để reset
        add_action('wp_login', array($this, 'clear_failed_attempts'));
        
        // Thêm cột trong users list (admin)
        add_filter('manage_users_columns', array($this, 'add_attempts_column'));
        add_filter('manage_users_custom_column', array($this, 'show_attempts_column'), 10, 3);
        
        // AJAX để xem chi tiết
        add_action('wp_ajax_csc_view_login_attempts', array($this, 'ajax_view_attempts'));
        
        // Thêm menu admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Lấy IP của người dùng
     */
    private function get_client_ip() {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP']) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR']) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        }
        return $ipaddress;
    }
    
    /**
     * Tạo key lưu trữ
     */
    private function get_attempt_key($login) {
        $ip = $this->get_client_ip();
        return 'csc_login_attempts_' . md5($login . '|' . $ip);
    }
    
    /**
     * Tạo key ghi log
     */
    private function get_log_key($login) {
        return 'csc_login_log_' . sanitize_key($login);
    }
    
    /**
     * Lấy số lần thử hiện tại
     */
    private function get_attempt_count($login) {
        $key = $this->get_attempt_key($login);
        $attempts = get_transient($key);
        
        if (!$attempts || !is_array($attempts)) {
            return 0;
        }
        
        // Lọc bỏ các lần thử cũ hơn tracking window
        $cutoff = time() - (self::TRACKING_WINDOW * 60);
        $attempts = array_filter($attempts, function($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });
        
        return count($attempts);
    }
    
    /**
     * Lấy thời gian khóa còn lại
     */
    private function get_lockout_remaining($login) {
        $key = $this->get_attempt_key($login);
        $lockout = get_transient($key . '_lockout');
        
        if (!$lockout) {
            return 0;
        }
        
        $remaining = $lockout - time();
        return max(0, $remaining);
    }
    
    /**
     * Ghi nhận đăng nhập thất bại
     */
    public function track_failed_login($login) {
        if (empty($login) || $login === ' ') {
            return;
        }
        
        $key = $this->get_attempt_key($login);
        
        // Lấy danh sách attempts hiện tại
        $attempts = get_transient($key);
        if (!$attempts || !is_array($attempts)) {
            $attempts = array();
        }
        
        // Thêm timestamp mới
        $attempts[] = time();
        
        // Lưu attempts (với thời hạn = tracking window)
        set_transient($key, $attempts, self::TRACKING_WINDOW * 60);
        
        // Đếm số lần thử
        $count = $this->get_attempt_count($login);
        
        // Ghi log
        $this->log_failed_attempt($login, $count);
        
        // Nếu đạt max attempts, khóa tài khoản
        if ($count >= self::MAX_ATTEMPTS) {
            $lockout_key = $key . '_lockout';
            set_transient($lockout_key, time() + (self::LOCKOUT_DURATION * 60), self::LOCKOUT_DURATION * 60);
            
            // Ghi log khóa
            $this->log_lockout($login);
        }
    }
    
    /**
     * Ghi log đăng nhập thất bại
     */
    private function log_failed_attempt($login, $attempt_count) {
        $log_key = $this->get_log_key($login);
        
        $logs = get_option($log_key, array());
        
        $log_entry = array(
            'type'      => 'failed',
            'ip'        => $this->get_client_ip(),
            'user_agent'=> isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 200) : '',
            'timestamp' => time(),
            'attempt'   => $attempt_count,
        );
        
        // Giữ tối đa 100 log
        $logs[] = $log_entry;
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        update_option($log_key, $logs, false);
    }
    
    /**
     * Ghi log khóa tài khoản
     */
    private function log_lockout($login) {
        $log_key = $this->get_log_key($login);
        
        $logs = get_option($log_key, array());
        
        $log_entry = array(
            'type'      => 'lockout',
            'ip'        => $this->get_client_ip(),
            'timestamp' => time(),
            'duration'  => self::LOCKOUT_DURATION,
        );
        
        $logs[] = $log_entry;
        update_option($log_key, $logs, false);
        
        // Gửi email thông báo cho admin
        $this->notify_admin_lockout($login);
    }
    
    /**
     * Gửi email thông báo cho admin
     */
    private function notify_admin_lockout($login) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf('[%s] Cảnh báo: Tài khoản bị khóa tạm thời', $site_name);
        
        $message = sprintf(
            "Tài khoản '%s' đã bị khóa tạm thời do đăng nhập sai quá %d lần.\n\n" .
            "IP: %s\n" .
            "Thời gian: %s\n" .
            "Thời gian mở khóa: %s\n\n" .
            "Vào wp-admin để xem chi tiết.",
            $login,
            self::MAX_ATTEMPTS,
            $this->get_client_ip(),
            current_time('d/m/Y H:i:s'),
            date('d/m/Y H:i:s', time() + self::LOCKOUT_DURATION * 60)
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Kiểm tra trước khi đăng nhập
     */
    public function check_login_attempts($user, $username, $password) {
        // Bỏ qua nếu đã có lỗi
        if (is_wp_error($user)) {
            return $user;
        }
        
        // Bỏ qua nếu không có username
        if (empty($username)) {
            return $user;
        }
        
        $lockout_remaining = $this->get_lockout_remaining($username);
        
        if ($lockout_remaining > 0) {
            $minutes = ceil($lockout_remaining / 60);
            return new WP_Error(
                'too_many_attempts',
                sprintf(
                    '<strong>LỖI:</strong> Tài khoản tạm thời bị khóa do đăng nhập sai nhiều lần.<br>' .
                    'Vui lòng thử lại sau <strong>%d phút</strong>.<br><br>' .
                    'Nếu bạn quên mật khẩu, hãy sử dụng chức năng "Quên mật khẩu".',
                    $minutes
                )
            );
        }
        
        return $user;
    }
    
    /**
     * Tùy chỉnh thông báo lỗi đăng nhập
     */
    public function custom_login_error($error) {
        // Kiểm tra nếu đang hiển thị lỗi liên quan đến đăng nhập
        global $errors;
        
        if (!is_wp_error($errors)) {
            return $error;
        }
        
        $codes = $errors->get_error_codes();
        
        if (in_array('too_many_attempts', $codes)) {
            return $errors->get_error_message('too_many_attempts');
        }
        
        // Thêm thông báo về số lần thử còn lại cho các lỗi khác
        if (in_array('invalid_username', $codes) || in_array('invalid_email', $codes) || in_array('incorrect_password', $codes)) {
            $login = isset($_POST['log']) ? $_POST['log'] : '';
            if ($login) {
                $count = $this->get_attempt_count($login);
                $remaining = self::MAX_ATTEMPTS - $count;
                
                if ($remaining > 0 && $remaining < self::MAX_ATTEMPTS) {
                    $error .= '<br><br><em style="color: #666;">Bạn còn ' . $remaining . ' lần thử trước khi tài khoản bị khóa tạm thời.</em>';
                }
            }
        }
        
        return $error;
    }
    
    /**
     * Xóa failed attempts khi đăng nhập thành công
     */
    public function clear_failed_attempts($login) {
        $key = $this->get_attempt_key($login);
        delete_transient($key);
        delete_transient($key . '_lockout');
        
        // Ghi log đăng nhập thành công
        $this->log_successful_login($login);
    }
    
    /**
     * Ghi log đăng nhập thành công
     */
    private function log_successful_login($login) {
        $log_key = $this->get_log_key($login);
        
        $logs = get_option($log_key, array());
        
        $log_entry = array(
            'type'      => 'success',
            'ip'        => $this->get_client_ip(),
            'timestamp' => time(),
        );
        
        $logs[] = $log_entry;
        
        // Giữ tối đa 100 log
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        update_option($log_key, $logs, false);
    }
    
    /**
     * Thêm cột trong users list
     */
    public function add_attempts_column($columns) {
        $columns['csc_login_attempts'] = 'Đăng nhập';
        return $columns;
    }
    
    /**
     * Hiển thị nội dung cột
     */
    public function show_attempts_column($value, $column_name, $user_id) {
        if ($column_name !== 'csc_login_attempts') {
            return $value;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return $value;
        }
        
        $count = $this->get_attempt_count($user->user_login);
        
        if ($count > 0) {
            return '<span style="color: #d63638;">⚠️ ' . $count . '/' . self::MAX_ATTEMPTS . '</span>';
        }
        
        return '<span style="color: #00a32a;">✓ OK</span>';
    }
    
    /**
     * Thêm menu admin
     */
    public function add_admin_menu() {
        add_users_page(
            'Lịch sử đăng nhập',
            'Lịch sử đăng nhập',
            'manage_options',
            'csc-login-history',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'users_page_csc-login-history') {
            return;
        }
        
        wp_enqueue_style(
            'csc-login-limit-admin',
            plugins_url('assets/admin.css', __FILE__),
            array(),
            '1.0'
        );
        
        wp_enqueue_script(
            'csc-login-limit-admin',
            plugins_url('assets/admin.js', __FILE__),
            array('jquery'),
            '1.0',
            true
        );
    }
    
    /**
     * Render trang admin
     */
    public function render_admin_page() {
        // Lấy danh sách users có log
        global $wpdb;
        
        $users = get_users(array(
            'orderby' => 'user_registered',
            'order' => 'DESC',
            'number' => 50,
        ));
        
        $users_with_logs = array();
        
        foreach ($users as $user) {
            $log_key = $this->get_log_key($user->user_login);
            $logs = get_option($log_key, array());
            
            if (!empty($logs)) {
                // Lọc log trong 30 ngày gần nhất
                $cutoff = time() - (30 * 24 * 60 * 60);
                $recent_logs = array_filter($logs, function($log) use ($cutoff) {
                    return $log['timestamp'] > $cutoff;
                });
                
                if (!empty($recent_logs)) {
                    $users_with_logs[$user->ID] = array(
                        'user' => $user,
                        'logs' => $recent_logs,
                        'failed_count' => count(array_filter($recent_logs, function($l) { return $l['type'] === 'failed'; })),
                        'lockouts' => count(array_filter($recent_logs, function($l) { return $l['type'] === 'lockout'; })),
                    );
                }
            }
        }
        ?>
        <div class="wrap">
            <h1>Lịch sử đăng nhập</h1>
            
            <p class="description">
                Plugin giới hạn đăng nhập: <strong><?php echo self::MAX_ATTEMPTS; ?> lần</strong> trong <strong><?php echo self::TRACKING_WINDOW; ?> phút</strong>.<br>
                Thời gian khóa khi vượt quá giới hạn: <strong><?php echo self::LOCKOUT_DURATION; ?> phút</strong>.
            </p>
            
            <?php if (empty($users_with_logs)): ?>
                <p>Chưa có lịch sử đăng nhập thất bại nào được ghi nhận.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Người dùng</th>
                            <th>Đăng nhập thất bại (30 ngày)</th>
                            <th>Số lần khóa</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users_with_logs as $data): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($data['user']->display_name); ?></strong><br>
                                    <small><?php echo esc_html($data['user']->user_email); ?></small>
                                </td>
                                <td><span style="color: #d63638;"><?php echo $data['failed_count']; ?></span></td>
                                <td><?php echo $data['lockouts']; ?></td>
                                <td>
                                    <button type="button" class="button csc-view-logs" data-user="<?php echo $data['user']->ID; ?>">
                                        Xem chi tiết
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div id="csc-log-modal" class="hidden" style="display: none;">
                    <div class="csc-modal-content">
                        <h2>Lịch sử đăng nhập</h2>
                        <div id="csc-log-details"></div>
                        <p><button type="button" class="button" onclick="jQuery('#csc-log-modal').hide();">Đóng</button></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.csc-view-logs').on('click', function() {
                var userId = $(this).data('user');
                var modal = $('#csc-log-modal');
                var details = $('#csc-log-details');
                
                details.html('<p>Đang tải...</p>');
                modal.show();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'csc_view_login_attempts',
                        user_id: userId
                    },
                    success: function(response) {
                        if (response.success) {
                            var html = '<table class="wp-list-table widefat fixed">';
                            html += '<thead><tr><th>Thời gian</th><th>Loại</th><th>IP</th></tr></thead><tbody>';
                            
                            response.data.forEach(function(log) {
                                var typeLabel = log.type === 'success' ? '<span style="color:green">✓ Thành công</span>' :
                                               log.type === 'lockout' ? '<span style="color:red">🔒 Khóa</span>' :
                                               '<span style="color:orange">✗ Thất bại</span>';
                                
                                var date = new Date(log.timestamp * 1000);
                                var dateStr = date.toLocaleString('vi-VN');
                                
                                html += '<tr>';
                                html += '<td>' + dateStr + '</td>';
                                html += '<td>' + typeLabel + '</td>';
                                html += '<td><code>' + log.ip + '</code></td>';
                                html += '</tr>';
                            });
                            
                            html += '</tbody></table>';
                            details.html(html);
                        } else {
                            details.html('<p>Lỗi: ' + response.data + '</p>');
                        }
                    }
                });
            });
        });
        </script>
        
        <style>
        #csc-log-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 100000;
            overflow: auto;
        }
        .csc-modal-content {
            background: white;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 8px;
        }
        </style>
        <?php
    }
    
    /**
     * AJAX xem chi tiết attempts
     */
    public function ajax_view_attempts() {
        check_ajax_referer('csc_login_limit_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Không có quyền');
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error('User ID không hợp lệ');
        }
        
        $user = get_userdata($user_id);
        
        if (!$user) {
            wp_send_json_error('Người dùng không tồn tại');
        }
        
        $log_key = $this->get_log_key($user->user_login);
        $logs = get_option($log_key, array());
        
        // Lọc 30 ngày gần nhất
        $cutoff = time() - (30 * 24 * 60 * 60);
        $recent_logs = array_filter($logs, function($log) use ($cutoff) {
            return $log['timestamp'] > $cutoff;
        });
        
        wp_send_json_success(array_values($recent_logs));
    }
    
    /**
     * Hàm static để admin có thể mở khóa tài khoản
     */
    public static function unlock_user($login) {
        $key = 'csc_login_attempts_' . md5($login . '|' . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''));
        delete_transient($key);
        delete_transient($key . '_lockout');
    }
}

// Khởi tạo plugin
new CSC_Login_Limit();
