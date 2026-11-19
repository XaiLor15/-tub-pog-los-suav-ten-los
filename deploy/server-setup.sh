#!/usr/bin/env bash
# =====================================================================
# Kwv Tij Hmoob Xyooj — ຕັ້ງຄ່າ Server ໃໝ່ (Ubuntu 24.04 LTS) — run ຄັ້ງດຽວດ້ວຍ root
#   sudo DOMAIN=family.example.la ADMIN_EMAIL=you@example.com bash server-setup.sh
# ສິ່ງທີ່ເຮັດ: ອັບເດດລະບົບ, firewall, fail2ban, PHP 8.3, MariaDB, Nginx,
#            Node 22, Composer, user "deploy", database, cron backup, php/nginx config
# ບໍ່ໄດ້ເຮັດ: clone ໂຄດ ແລະ HTTPS (ເຮັດຕາມ docs/DEPLOY.md ຂັ້ນຕອນ 4–6)
# =====================================================================
set -euo pipefail

DOMAIN="${DOMAIN:?ກະລຸນາກຳນົດ DOMAIN=...}"
APP_DIR="${APP_DIR:-/var/www/kwvtij}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"
DB_NAME="${DB_NAME:-kwvtij}"
DB_USER="${DB_USER:-kwvtij}"
PHP_V=8.3
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

[[ $EUID -eq 0 ]] || { echo "ຕ້ອງ run ດ້ວຍ root (sudo)"; exit 1; }
grep -q 'VERSION_ID="24.04"' /etc/os-release || echo "⚠ script ນີ້ທົດສອບກັບ Ubuntu 24.04 — OS ອື່ນອາດຕ້ອງປັບ"

log() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
export DEBIAN_FRONTEND=noninteractive

log "1/9 ອັບເດດລະບົບ ແລະ ຕິດຕັ້ງ package ພື້ນຖານ"
apt-get update -q
apt-get upgrade -yq
apt-get install -yq curl git unzip acl ufw fail2ban unattended-upgrades ca-certificates gnupg

log "2/9 Firewall (ເປີດສະເພາະ SSH, HTTP, HTTPS) + fail2ban + ອັບເດດຄວາມປອດໄພອັດຕະໂນມັດ"
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
systemctl enable --now fail2ban
dpkg-reconfigure -f noninteractive unattended-upgrades

log "3/9 PHP ${PHP_V} + extensions"
apt-get install -yq "php${PHP_V}-fpm" "php${PHP_V}-cli" "php${PHP_V}-mysql" "php${PHP_V}-mbstring" \
  "php${PHP_V}-xml" "php${PHP_V}-curl" "php${PHP_V}-zip" "php${PHP_V}-intl" "php${PHP_V}-gd" \
  "php${PHP_V}-bcmath" "php${PHP_V}-opcache" composer
install -m 644 "${SCRIPT_DIR}/php-kwvtij.ini" "/etc/php/${PHP_V}/fpm/conf.d/99-kwvtij.ini"
# CLI ບໍ່ໃຊ້ opcache.validate_timestamps=0 (ໃຫ້ artisan ເຫັນໂຄດໃໝ່ສະເໝີ)
grep -v '^opcache' "${SCRIPT_DIR}/php-kwvtij.ini" > "/etc/php/${PHP_V}/cli/conf.d/99-kwvtij.ini"

log "4/9 Node.js 22 (ໃຊ້ build CSS/JS)"
if ! command -v node >/dev/null || [[ "$(node -v | cut -d. -f1 | tr -d v)" -lt 22 ]]; then
  install -d -m 0755 /etc/apt/keyrings
  curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor --yes -o /etc/apt/keyrings/nodesource.gpg
  echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_22.x nodistro main" > /etc/apt/sources.list.d/nodesource.list
  apt-get update -q
  apt-get install -yq nodejs
fi

log "5/9 MariaDB + ສ້າງ database"
apt-get install -yq mariadb-server mariadb-client
systemctl enable --now mariadb
# ຄວາມປອດໄພພື້ນຖານ (ແທນ mysql_secure_installation)
mariadb -e "DELETE FROM mysql.global_priv WHERE User=''; DROP DATABASE IF EXISTS test; FLUSH PRIVILEGES;"
if mariadb -e "USE \`${DB_NAME}\`" 2>/dev/null; then
  echo "Database ${DB_NAME} ມີຢູ່ແລ້ວ — ຂ້າມ (ບໍ່ປ່ຽນລະຫັດຜ່ານ)"
  DB_PASS="(ບໍ່ປ່ຽນ — ໃຊ້ຄ່າເດີມໃນ .env)"
else
  DB_PASS="$(openssl rand -base64 24 | tr -d '/+=' | cut -c1-24)"
  mariadb -e "CREATE DATABASE \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
    GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
    FLUSH PRIVILEGES;"
fi

log "6/9 Nginx + certbot"
apt-get install -yq nginx certbot python3-certbot-nginx
sed -e "s|__DOMAIN__|${DOMAIN}|g" -e "s|__APP_DIR__|${APP_DIR}|g" "${SCRIPT_DIR}/nginx-kwvtij.conf" > /etc/nginx/sites-available/kwvtij
ln -sf /etc/nginx/sites-available/kwvtij /etc/nginx/sites-enabled/kwvtij
rm -f /etc/nginx/sites-enabled/default
sed -i 's/# server_tokens off;/server_tokens off;/' /etc/nginx/nginx.conf

log "7/9 User '${DEPLOY_USER}' ແລະ folder ${APP_DIR}"
id "${DEPLOY_USER}" >/dev/null 2>&1 || adduser --disabled-password --gecos "" "${DEPLOY_USER}"
usermod -aG www-data "${DEPLOY_USER}"
install -d -o "${DEPLOY_USER}" -g www-data -m 2775 "${APP_DIR}"
# deploy ອະນຸຍາດ reload php-fpm ຫຼັງ deploy ໂດຍບໍ່ຕ້ອງໃສ່ລະຫັດ (ສະເພາະຄຳສັ່ງນີ້)
echo "${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/systemctl reload php${PHP_V}-fpm" > /etc/sudoers.d/kwvtij-deploy
chmod 440 /etc/sudoers.d/kwvtij-deploy
visudo -cf /etc/sudoers.d/kwvtij-deploy
# SSH key ສຳລັບ clone repo private (Deploy key ແບບ read-only)
sudo -u "${DEPLOY_USER}" bash -c '[[ -f ~/.ssh/id_ed25519 ]] || ssh-keygen -q -t ed25519 -N "" -C "kwvtij-deploy@'"${DOMAIN}"'" -f ~/.ssh/id_ed25519'
sudo -u "${DEPLOY_USER}" bash -c 'ssh-keyscan -t ed25519 github.com >> ~/.ssh/known_hosts 2>/dev/null; sort -u -o ~/.ssh/known_hosts ~/.ssh/known_hosts'

log "8/9 Cron: Laravel scheduler (Backup ທຸກມື້ 02:00) ແລະ ລຶບ log ເກົ່າ"
echo "* * * * * www-data cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/kwvtij
chmod 644 /etc/cron.d/kwvtij

log "9/9 Restart services"
systemctl reload "php${PHP_V}-fpm"
nginx -t && systemctl reload nginx

cat <<MSG

======================================================================
 ✅ ຕັ້ງຄ່າ Server ສຳເລັດ
----------------------------------------------------------------------
 Database : ${DB_NAME}
 DB user  : ${DB_USER}
 DB pass  : ${DB_PASS}
            ⚠ ບັນທຶກລະຫັດນີ້ໄວ້ (ຈະບໍ່ສະແດງອີກ) — ໃຊ້ໃນ .env

 Deploy key (ເພີ່ມໃນ GitHub → repo -tub-pog-los-suav-ten-los → Settings → Deploy keys,
             ບໍ່ຕ້ອງຕິກ "Allow write access"):
$(cat "/home/${DEPLOY_USER}/.ssh/id_ed25519.pub")

 ຂັ້ນຕໍ່ໄປ: docs/DEPLOY.md ຂັ້ນຕອນ 4 (ຕິດຕັ້ງໂຄດ)
======================================================================
MSG
