#!/bin/sh
# Script này chạy sau setup.sh để cấu hình bổ sung
# Bao gồm: trang tài khoản, dữ liệu test, v.v.

set -e

cd /var/www/html

wp() {
  command wp --path=/var/www/html --url="${WP_URL}" "$@"
}

echo "[cho-sach-cu] Chờ WordPress..."
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
    echo "[cho-sach-cu] WordPress chưa được cài. Chạy setup.sh trước."
    exit 1
fi

echo "[cho-sach-cu] Chạy configure-account-pages.php..."
wp eval-file /opt/cho-sach-cu/configure-account-pages.php

echo "[cho-sach-cu] Chạy configure-legal-pages.php..."
wp eval-file /opt/cho-sach-cu/configure-legal-pages.php

echo "[cho-sach-cu] Chạy configure-coupons.php..."
wp eval-file /opt/cho-sach-cu/configure-coupons.php

echo "[cho-sach-cu] Chạy seed-orders.php..."
wp eval-file /opt/cho-sach-cu/seed-orders.php

echo "[cho-sach-cu] Cài Vietnam Store Toolkit + shipping theo miền..."
wp plugin install woo-vietnam-delivery --activate 2>/dev/null || true
wp eval-file /opt/cho-sach-cu/configure-regional-shipping.php

echo "[cho-sach-cu] Hoàn tất cấu hình bổ sung."
