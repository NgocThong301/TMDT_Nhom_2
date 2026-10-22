#!/bin/sh
# Dừng container dự án này. Không dùng down -v, không prune.
set -eu
ROOT="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
docker compose --project-name cho-sach-cu stop
echo "Đã dừng cho-sach-cu. Volume cho-sach-cu_db_data và cho-sach-cu_wp_data vẫn giữ nguyên."
