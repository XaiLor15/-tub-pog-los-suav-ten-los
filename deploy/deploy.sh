#!/usr/bin/env bash
# =====================================================================
# Kwv Tij Hmoob Xyooj — ອັບເດດເວັບໄຊທ໌ເປັນເວີຊັນລ່າສຸດຈາກ GitHub
#   run ດ້ວຍ user deploy:   cd /var/www/kwvtij && bash deploy/deploy.sh
# ຖ້າຂັ້ນຕອນໃດລົ້ມເຫຼວ script ຈະຢຸດ ແລະ ເວັບຍັງຢູ່ໃນໂໝດ maintenance
# → ແກ້ບັນຫາ ແລ້ວ run ໃໝ່ ຫຼື ກັບຄືນເວີຊັນກ່ອນ (ເບິ່ງ docs/DEPLOY.md "Rollback")
# =====================================================================
set -euo pipefail
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BRANCH="${BRANCH:-main}"
PHP_V="${PHP_V:-8.3}"
cd "${APP_DIR}"

[[ "$(id -un)" != "root" ]] || { echo "ຢ່າ run ດ້ວຍ root — ໃຊ້ user deploy"; exit 1; }
[[ -f .env ]] || { echo "ບໍ່ພົບ .env — ເບິ່ງ docs/DEPLOY.md ຂັ້ນຕອນ 4"; exit 1; }

log() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }

PREV="$(git rev-parse --short HEAD)"
log "ເວີຊັນປັດຈຸບັນ: ${PREV} — ດຶງໂຄດໃໝ່ຈາກ ${BRANCH}"
git fetch --quiet origin "${BRANCH}"
if [[ "$(git rev-parse HEAD)" == "$(git rev-parse "origin/${BRANCH}")" && "${FORCE:-0}" != "1" ]]; then
  echo "ເປັນເວີຊັນລ່າສຸດຢູ່ແລ້ວ (ໃຊ້ FORCE=1 ເພື່ອ build ໃໝ່)"; exit 0
fi

log "ເປີດໂໝດ maintenance"
php artisan down --retry=30 || true
trap 'echo; echo "❌ Deploy ລົ້ມເຫຼວ — ເວັບຍັງຢູ່ໃນ maintenance. ເວີຊັນກ່ອນ: ${PREV}"' ERR

git merge --ff-only --quiet "origin/${BRANCH}"
log "ເວີຊັນໃໝ່: $(git rev-parse --short HEAD)"; git log --oneline "${PREV}..HEAD" | head -20

log "ຕິດຕັ້ງ PHP dependencies"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --quiet

log "Build CSS/JS"
npm ci --no-audit --no-fund --silent
npm run build --silent

log "Backup database ກ່ອນ migrate"
php artisan kwvtij:backup database

log "Migrate database"
php artisan migrate --force

log "Cache config/route/view"
php artisan optimize:clear >/dev/null
php artisan optimize

log "Reload PHP-FPM (ລ້າງ OPcache)"
sudo /usr/bin/systemctl reload "php${PHP_V}-fpm"

php artisan up
trap - ERR
log "✅ Deploy ສຳເລັດ: ${PREV} → $(git rev-parse --short HEAD)"
