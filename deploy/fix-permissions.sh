#!/usr/bin/env bash
# ກຳນົດສິດ folder ໃຫ້ທັງ user deploy ແລະ PHP (www-data) ຂຽນ storage/ ໄດ້ — run ດ້ວຍ root
#   sudo bash deploy/fix-permissions.sh
set -euo pipefail
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEPLOY_USER="${DEPLOY_USER:-deploy}"
[[ $EUID -eq 0 ]] || { echo "ຕ້ອງ run ດ້ວຍ root (sudo)"; exit 1; }

chown -R "${DEPLOY_USER}:www-data" "${APP_DIR}"
find "${APP_DIR}" -path "${APP_DIR}/node_modules" -prune -o -type d -exec chmod 2755 {} +
chmod 640 "${APP_DIR}/.env"
for d in storage bootstrap/cache; do
  chmod -R g+rwX "${APP_DIR}/${d}"
  setfacl -R -m "u:www-data:rwX,u:${DEPLOY_USER}:rwX" "${APP_DIR}/${d}"
  setfacl -R -d -m "u:www-data:rwX,u:${DEPLOY_USER}:rwX" "${APP_DIR}/${d}"
done
echo "✅ ກຳນົດສິດແລ້ວ: ${APP_DIR}"
