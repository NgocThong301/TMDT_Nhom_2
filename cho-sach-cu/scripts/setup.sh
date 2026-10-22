#!/bin/sh
# Cài đặt idempotent cho Chợ Sách Cũ. Không xóa volume/database.
set -eu

cd /var/www/html

wp() {
  command wp --path=/var/www/html --url="${WP_URL}" "$@"
}

echo "[cho-sach-cu] Đợi wp-config.php..."
i=0
while [ ! -f /var/www/html/wp-config.php ]; do
  i=$((i + 1))
  if [ "$i" -gt 60 ]; then
    echo "[cho-sach-cu] Hết thời gian chờ wp-config.php" >&2
    exit 1
  fi
  sleep 2
done

echo "[cho-sach-cu] Đợi WordPress nạp được (không dùng wp db check vì client MariaDB/SSL)..."
i=0
while :; do
  set +e
  out="$(command wp --path=/var/www/html --url="${WP_URL}" core is-installed 2>&1)"
  code=$?
  set -e
  case "$out" in
    *"Error establishing a database connection"*|*"Unknown database"*|*"Access denied"*)
      i=$((i + 1))
      if [ "$i" -gt 60 ]; then
        echo "$out" >&2
        echo "[cho-sach-cu] Database chưa sẵn sàng" >&2
        exit 1
      fi
      sleep 2
      continue
      ;;
  esac
  break
done

if ! wp core is-installed >/dev/null 2>&1; then
  echo "[cho-sach-cu] Cài WordPress..."
  wp core install \
    --url="${WP_URL}" \
    --title="Chợ Sách Cũ" \
    --admin_user="${WP_ADMIN_USER}" \
    --admin_password="${WP_ADMIN_PASSWORD}" \
    --admin_email="${WP_ADMIN_EMAIL}" \
    --skip-email
else
  echo "[cho-sach-cu] WordPress đã được cài, bỏ qua core install."
fi

wp option update blogname "Chợ Sách Cũ"
wp option update blogdescription "Sàn C2C mua bán sách đã qua sử dụng"
wp option update timezone_string "Asia/Ho_Chi_Minh"
wp option update date_format "d/m/Y"
wp option update time_format "H:i"
wp rewrite structure "/%postname%/" --hard

echo "[cho-sach-cu] Ngôn ngữ tiếng Việt..."
wp language core install vi || true
wp site switch-language vi || wp language core activate vi || true

echo "[cho-sach-cu] Theme Storefront (WooCommerce chính thức, đang được duy trì)..."
wp theme install storefront --activate || wp theme activate storefront

echo "[cho-sach-cu] Thử cài Dokani từ wordpress.org (tương thích chưa được xác minh trước khi test)..."
if wp theme install dokani --activate; then
  echo "[cho-sach-cu] Dokani đã kích hoạt từ wordpress.org. Sẽ kiểm tra HTTP sau."
elif wp theme install "https://github.com/getdokan/dokani/archive/refs/tags/v1.0.1.zip" --activate; then
  echo "[cho-sach-cu] Dokani v1.0.1 đã cài từ GitHub tag. Tương thích WP 7.x chưa được xác minh trước khi test HTTP."
else
  echo "[cho-sach-cu] Không cài được Dokani. Giữ Storefront."
  wp theme activate storefront
fi

echo "[cho-sach-cu] Plugin WooCommerce + Dokan Lite..."
wp plugin install woocommerce --activate
wp plugin install dokan-lite --activate
wp language plugin install woocommerce vi || true
wp language plugin update --all || true

echo "[cho-sach-cu] Cấu hình cửa hàng Việt Nam / VND..."
wp option update woocommerce_store_address "123 Trần Duy Hưng"
wp option update woocommerce_store_city "Hà Nội"
wp option update woocommerce_default_country "VN"
wp option update woocommerce_store_postcode "100000"
wp option update woocommerce_currency "VND"
wp option update woocommerce_currency_pos "right_space"
wp option update woocommerce_price_thousand_sep "."
wp option update woocommerce_price_decimal_sep ","
wp option update woocommerce_price_num_decimals "0"
wp option update woocommerce_weight_unit "kg"
wp option update woocommerce_dimension_unit "cm"
wp option update woocommerce_calc_taxes "no"
wp option update woocommerce_enable_guest_checkout "yes"
wp option update woocommerce_enable_checkout_login_reminder "yes"
wp option update woocommerce_enable_signup_and_login_from_checkout "yes"
wp option update woocommerce_enable_myaccount_registration "yes"
wp option update woocommerce_coming_soon "no"
wp option update woocommerce_store_pages_only "no"
wp option update woocommerce_show_marketplace_suggestions "no"
wp option update woocommerce_allow_tracking "no"
wp option update woocommerce_task_list_hidden "yes" || true
wp option update woocommerce_onboarding_profile '{"skipped":true,"completed":true}' --format=json || true

wp wc --user="${WP_ADMIN_USER}" tool run install_pages || true

echo "[cho-sach-cu] Thanh toán thử nghiệm: COD + chuyển khoản (không dùng cổng thanh toán thật)..."
wp option update woocommerce_cod_settings '{"enabled":"yes","title":"Thanh toán khi nhận hàng","description":"Thanh toán tiền mặt khi nhận sách. Chỉ dùng để thử nghiệm local.","instructions":"Chuẩn bị đúng số tiền khi nhận hàng."}' --format=json
wp option update woocommerce_bacs_settings '{"enabled":"yes","title":"Chuyển khoản ngân hàng (thử nghiệm)","description":"Chuyển khoản giả lập cho bài tập. Không dùng tài khoản ngân hàng thật.","instructions":"Nội dung CK: CSC-TEST"}' --format=json

echo "[cho-sach-cu] Vận chuyển theo 3 miền + freeship 699k..."
wp eval-file /opt/cho-sach-cu/configure-shipping.php
wp eval-file /opt/cho-sach-cu/configure-regional-shipping.php

echo "[cho-sach-cu] Cài Vietnam Store Toolkit (chọn địa chỉ dropdown)..."
wp eval-file /opt/cho-sach-cu/install-vietnam-store-toolkit.php

echo "[cho-sach-cu] Cấu hình Dokan Lite (chỉ các tùy chọn Lite)..."
wp eval-file /opt/cho-sach-cu/configure-dokan.php

echo "[cho-sach-cu] Trang chủ..."
wp eval-file /opt/cho-sach-cu/configure-pages.php

echo "[cho-sach-cu] Tài khoản thử nghiệm + dữ liệu sách..."
wp eval-file /opt/cho-sach-cu/seed-products.php

wp rewrite flush --hard

echo "[cho-sach-cu] Phiên bản đã cài:"
wp core version
wp theme list --status=active --format=table
wp plugin list --status=active --fields=name,status,version --format=table
wp option get blogname
wp option get woocommerce_currency

echo "[cho-sach-cu] Hoàn tất setup."
