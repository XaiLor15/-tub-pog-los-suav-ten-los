#!/usr/bin/env bash
# =====================================================================
# Copy ໄຟລ໌ Backup ຂອງ Kwv Tij Hmoob Xyooj ໄປເກັບນອກ server ດ້ວຍ rclone
# (Google Drive, OneDrive, S3, server ອື່ນຜ່ານ SFTP ...)
# ຕັ້ງຄ່າຄັ້ງທຳອິດ:  sudo apt install rclone && sudo -u www-data rclone config   (ສ້າງ remote ຊື່ "offsite")
# ທົດສອບ:          sudo -u www-data bash deploy/backup-offsite.sh
# Cron (03:00):    0 3 * * * www-data bash /var/www/kwvtij/deploy/backup-offsite.sh >> /var/log/kwvtij-offsite.log 2>&1
# =====================================================================
set -euo pipefail
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REMOTE="${REMOTE:-offsite:kwvtij-backups}"
KEEP_DAYS="${KEEP_DAYS:-90}"
SRC="${APP_DIR}/storage/app/private/backups"

command -v rclone >/dev/null || { echo "ບໍ່ພົບ rclone"; exit 1; }
[[ -d "${SRC}" ]] || { echo "ບໍ່ພົບ ${SRC} — ຍັງບໍ່ມີ backup"; exit 1; }

echo "$(date '+%F %T') copy ${SRC} → ${REMOTE}"
rclone copy "${SRC}" "${REMOTE}" --include "*.zip" --transfers 2
rclone delete "${REMOTE}" --min-age "${KEEP_DAYS}d" --include "*.zip"
echo "$(date '+%F %T') ສຳເລັດ"
