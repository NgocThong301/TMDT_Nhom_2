#!/bin/sh
# Sao lưu dự án cho-sach-cu. Không đụng volume/container khác.
set -eu
ROOT="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
STAMP="$(date +%Y%m%d-%H%M%S)"
OUT="$ROOT/backups"
mkdir -p "$OUT"

echo "Dump database cho_sach_cu..."
docker exec cho-sach-cu-db sh -c 'mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' \
  > "$OUT/cho-sach-cu-$STAMP.sql"

echo "Đã ghi $OUT/cho-sach-cu-$STAMP.sql"
echo "File WordPress nằm trong Docker volume cho-sach-cu_wp_data."
echo "Sao lưu volume (không xóa volume gốc):"
echo "  docker run --rm -v cho-sach-cu_wp_data:/from -v \"$OUT\":/to alpine tar czf /to/wp-data-$STAMP.tar.gz -C /from ."
echo "  docker run --rm -v cho-sach-cu_db_data:/from -v \"$OUT\":/to alpine tar czf /to/db-data-$STAMP.tar.gz -C /from ."
