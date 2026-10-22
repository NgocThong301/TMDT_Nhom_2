#!/bin/sh
# Script chạy thủ công để cài Vietnam Store Toolkit + cấu hình shipping theo miền
# Chạy khi WordPress đã chạy: docker compose run --rm wpcli

set -e

echo "[setup] Bắt đầu cài Vietnam Store Toolkit..."
wp eval-file /opt/cho-sach-cu/install-vietnam-store-toolkit.php

echo ""
echo "[setup] Cấu hình shipping theo 3 miền..."
wp eval-file /opt/cho-sach-cu/configure-regional-shipping.php

echo ""
echo "[setup] Hoàn tất!"
