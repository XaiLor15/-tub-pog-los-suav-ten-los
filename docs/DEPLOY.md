# ຄູ່ມື Deploy, Migration, Backup ແລະ ການກູ້ຂໍ້ມູນ

ເປົ້າໝາຍ: Ubuntu 24.04 LTS · Nginx · PHP 8.3-FPM · MariaDB · HTTPS (Let's Encrypt).
ທຸກ script ຢູ່ໃນ `deploy/` (ດັດແປງມາຈາກ fdms ທີ່ໃຊ້ງານຈິງແລ້ວ).

> ⚠ ລະບົບນີ້ **ຍັງບໍ່ທັນໄດ້ deploy** — ຕ້ອງມີ server, domain ແລະ ບັນຊີ email ຈາກຄະນະນຳກ່ອນ.

## 0. ກຽມ

- VPS Ubuntu 24.04 (ຢ່າງໜ້ອຍ 2 vCPU / 2 GB RAM / 40 GB), IP ສາທາລະນະ
- Domain ຊີ້ A record ມາ IP ຂອງ server
- Email ຜູ້ດູແລທຳອິດ + ບັນຊີ SMTP (ສຳລັບລືມລະຫັດຜ່ານ)
- (ທາງເລືອກ) Google OAuth Client — ເບິ່ງຂໍ້ 6

## 1. ຕັ້ງຄ່າ server (ຄັ້ງດຽວ, ເປັນ root)

Copy ໂຟນເດີ `deploy/` ຂຶ້ນ server (run ໃນຄອມ ທີ່ໂຟນເດີໂປຣເຈັກ):

```bash
scp -r deploy root@<IP>:/root/kwvtij-deploy
```

```bash
ssh root@<IP>
```

```bash
sudo DOMAIN=<your-domain> bash /root/kwvtij-deploy/server-setup.sh
```

Script ຕິດຕັ້ງ: firewall (22/80/443), fail2ban, PHP 8.3, MariaDB + database `kwvtij`, Nginx, Node 22,
Composer, user `deploy`, cron ຂອງ Laravel scheduler (Backup ທຸກມື້ 02:00).
ມັນຈະສະແດງ **ລະຫັດ database** (ບັນທຶກໄວ້) ແລະ **Deploy key** → ເພີ່ມໃນ GitHub → repo → Settings → Deploy keys (read-only).

## 2. ຕິດຕັ້ງໂຄດ (ເປັນ user deploy)

```bash
sudo -iu deploy
```

```bash
cd /var/www/kwvtij && git clone git@github.com:XaiLor15/-tub-pog-los-suav-ten-los.git .
```

```bash
cp deploy/env.production.example .env && nano .env
```

ແກ້: `APP_URL`, `DB_PASSWORD`, `MAIL_*`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` (ລະຫັດຊົ່ວຄາວທີ່ແຂງແຮງ), `GOOGLE_*` ຖ້າໃຊ້.

```bash
composer install --no-dev --optimize-autoloader && npm ci && npm run build
```

```bash
php artisan key:generate --force && php artisan migrate --force && php artisan db:seed --force && php artisan optimize
```

> ⚠ ຫ້າມ run `DemoSeeder` ໃນ server ຈິງ (ມັນຈະປະຕິເສດເອງເມື່ອ `APP_ENV=production`).

```bash
exit
```

## 3. ສິດ folder ແລະ HTTPS (ເປັນ root)

```bash
sudo bash /var/www/kwvtij/deploy/fix-permissions.sh && sudo systemctl reload php8.3-fpm
```

```bash
sudo certbot --nginx -d <your-domain> --redirect -m <your-email> --agree-tos
```

ຈາກນັ້ນ ລຶບ `ADMIN_PASSWORD` ອອກຈາກ `.env`, ເຂົ້າສູ່ລະບົບ ແລະ **ປ່ຽນລະຫັດຜ່ານທັນທີ** (ໂປຣໄຟລ໌).

## 4. ອັບເດດເວີຊັນໃໝ່

```bash
sudo -iu deploy bash /var/www/kwvtij/deploy/deploy.sh
```

`deploy.sh`: ເປີດ maintenance → ດຶງໂຄດ → `composer install` → `npm ci && npm run build` →
**backup database** (`kwvtij:backup database`) → `migrate --force` → cache → ປິດ maintenance.
ຖ້າລົ້ມເຫຼວ ເວັບຈະຢູ່ໃນ maintenance ແລະ ສະແດງເວີຊັນກ່ອນໜ້າ ເພື່ອ rollback.

## 5. Database migration

Migrations ຢູ່ `database/migrations/` (users → family → content → finance → system).
ທຸກໄຟລ໌ມີ `down()` ແລະ ໄດ້ທົດສອບ up → down → up **ພ້ອມຂໍ້ມູນ** ທັງ MariaDB 10.11 ແລະ SQLite.

ກົດລະບຽບ:
1. Backup ກ່ອນທຸກຄັ້ງ (`deploy.sh` ເຮັດໃຫ້ອັດຕະໂນມັດ).
2. ທົດສອບ migration ໃໝ່ໃນ database ສຳເນົາກ່ອນ (restore backup ລ່າສຸດໄປ database ທົດສອບ — ຂໍ້ 7).
3. ຫ້າມລຶບ / ປ່ຽນຊື່ column ຫຼື table ທີ່ມີຂໍ້ມູນ ໂດຍບໍ່ໄດ້ຮັບອະນຸມັດ; ເພີ່ມ column ໃໝ່ເປັນ nullable.
4. ຫ້າມ `migrate:fresh` / `migrate:rollback` ໃນ server ຈິງ (ຈະລຶບຂໍ້ມູນ).

ກວດສະຖານະ:

```bash
php artisan migrate:status
```

## 6. Google OAuth (ທາງເລືອກ)

Google Cloud Console → APIs & Services → Credentials → OAuth client ID (Web application):
- Authorised redirect URI: `https://<your-domain>/auth/google/callback`
- ໃສ່ `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` ໃນ `.env` ແລ້ວ `php artisan optimize`

ປຸ່ມ "ດຳເນີນການດ້ວຍ Google" ຈະສະແດງເມື່ອໃສ່ຄົບທັງສອງຄ່າ. ບັນຊີ Google ໃໝ່ກໍເລີ່ມເປັນ **pending** ຄືກັນ.

## 7. Backup ແລະ ການກູ້ຂໍ້ມູນ

Backup ອັດຕະໂນມັດທຸກມື້ 02:00 (database `.sql.gz` + ໄຟລ໌ຮູບ/ສະລິບ `.tar.gz`) ໄປ
`storage/app/private/backups/`, ເກັບ 14 ຊຸດລ່າສຸດ (`BACKUP_KEEP`). Backup ດ້ວຍມື:

```bash
php artisan kwvtij:backup full
```

ສົ່ງ backup ອອກນອກ server (ແນະນຳຢ່າງຍິ່ງ) ດ້ວຍ rclone — ເບິ່ງຄຳອະທິບາຍໃນ `deploy/backup-offsite.sh`.

**ກູ້ database** (ທົດສອບແລ້ວ: ຈຳນວນສະມາຊິກ, ຍອດທຸລະກຳ ແລະ ການຊຳລະ ກົງກັນທຸກແຖວ):

```bash
php artisan down
```

```bash
zcat storage/app/private/backups/db-YYYYMMDD-HHMMSS.sql.gz | mariadb -u kwvtij -p kwvtij
```

```bash
php artisan up
```

ແນະນຳໃຫ້ກູ້ໄປ database ໃໝ່ກ່ອນ (ເຊັ່ນ `kwvtij_restore`) ເພື່ອກວດ ແລ້ວຈຶ່ງສະຫຼັບ `DB_DATABASE`.

**ກູ້ໄຟລ໌ຮູບ:**

```bash
tar -xzf storage/app/private/backups/files-YYYYMMDD-HHMMSS.tar.gz -C storage/app/private
```

ແລ້ວ run `fix-permissions.sh` ອີກຄັ້ງ.

## 8. Checklist ກ່ອນເປີດໃຊ້

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, HTTPS ເຮັດວຽກ, `SESSION_SECURE_COOKIE=true`
- [ ] ປ່ຽນລະຫັດ Admin ທຳອິດແລ້ວ ແລະ ລຶບ `ADMIN_PASSWORD` ອອກຈາກ `.env`
- [ ] ທົດສອບລືມລະຫັດຜ່ານ (email ມາຮອດ)
- [ ] Backup ຄັ້ງທຳອິດສຳເລັດ ແລະ ທົດລອງກູ້ໃສ່ database ທົດສອບ
- [ ] ເຂົ້າສູ່ລະບົບເປັນ Guest / Pending / Member / Admin ແລ້ວກວດສິດ
- [ ] ນຳເຂົ້າຂໍ້ມູນຈິງ (ສະມາຊິກ, ຄະນະນຳ, ຍອດເງິນເລີ່ມຕົ້ນ) ຜ່ານໜ້າເວັບ ຫຼື script import ທີ່ກວດແລ້ວ
