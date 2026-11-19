# Kwv Tij Hmoob Xyooj — ລະບົບຄຸ້ມຄອງຕະກູນ

Family management system for the Kwv Tij Hmoob Xyooj clan: members and a 6-generation (Phaj 1–6)
paternal family tree, leadership, elders, activities, news, finance, fund contributors and donation tracking.
UI in Lao, "Navy Trust" design (light / dark, font scaling 100 / 112 / 125 %).

- Design source: *Kwv Tij Hmoob Xyooj — UI Design* (19 artboards) and *Dev Handoff* (Nov 2026).
- How the Supabase-based spec was mapped to this Laravel stack: [`docs/DECISIONS.md`](docs/DECISIONS.md)
- Production deployment, migrations, backup & recovery: [`docs/DEPLOY.md`](docs/DEPLOY.md)

## Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3+, Laravel 13 |
| Database | MySQL 8 / MariaDB 10.6+ (utf8mb4). SQLite for local quick start and tests |
| Frontend | Blade, Tailwind CSS 4 (design tokens as CSS variables), vanilla JS modules, Lucide icons (vendored SVG), self-hosted Noto Sans Lao + Noto Sans |
| Auth | Email + password, optional Google OAuth (Laravel Socialite), password reset by email |
| Security | Server-side permission gates on every action, CSP + security headers, private file storage behind permission checks, audit log |

The stack follows the existing **fdms** project (same server setup and deploy scripts), as approved —
see `docs/DECISIONS.md` for what replaced Supabase RLS / RPC / Storage / Realtime.

## Roles

| | Guest | Pending | Member | Admin |
|---|:-:|:-:|:-:|:-:|
| Dashboard, members, tree, leadership, elders, public activities, published news | ✓ (birth **year** only, no phone/village) | same as Guest | ✓ full | ✓ |
| Add / edit members, reorder / unlink children | – | – | ✓ | ✓ + soft delete |
| Activities: create / edit own, join | – | – | ✓ | ✓ all |
| News comments (own) | read | read | ✓ | ✓ + write news, pin, delete |
| Finance, fund, donations | – | – | read | record / edit / delete |
| Leadership & elders editing, user approval, roles, audit log | – | – | – | ✓ |
| Profile, appearance | – | ✓ + pending banner | ✓ | ✓ |

New accounts (email or Google) always start as **pending** until an Admin approves them.
An Admin cannot change their own role, and the system always keeps at least one active Admin.

## Local development

Requirements: PHP 8.3+ (pdo_mysql or pdo_sqlite, mbstring, intl, gd, fileinfo, zip), Composer, Node 20+.

```bash
composer install
```
```bash
cp .env.example .env
```
```bash
php artisan key:generate
```

Quick start with SQLite (set `DB_CONNECTION=sqlite` and `DB_DATABASE=/absolute/path/database/database.sqlite` in `.env`):

```bash
touch database/database.sqlite
```
```bash
php artisan migrate --seed
```
```bash
npm install && npm run build
```
```bash
php artisan serve
```

Sign in with `ADMIN_EMAIL` / `ADMIN_PASSWORD` from `.env` (default `admin@example.com` / `ChangeMe@2026` — change it).

Optional sample family for local testing / screenshots (refuses to run in production):

```bash
php artisan db:seed --class=DemoSeeder
```

During frontend work use `npm run dev` instead of `npm run build`.

## Tests

```bash
php artisan test
```

55 tests / 313 assertions, run on SQLite in memory by default; the same suite passes on MariaDB 10.11
(`DB_CONNECTION=mariadb DB_DATABASE=<test db> php artisan test`). They cover the Handoff's critical cases:
guests never receive phone numbers (HTML, search JSON, lineage JSON, CSV), pending users cannot write,
members cannot reach admin functions or change roles, a member cannot become their own ancestor,
circular links are rejected, reordering keeps `birth_order` 1..n, unlinking never deletes,
donations are counted once in income, idempotent re-submits, donation transactions are locked in Finance,
CSV formula-injection protection, plus formatting unit tests and a render test of every page for every role.

Style: `vendor/bin/pint`.

## Project structure

```
app/Http/Controllers   one controller per page/module (Members, Activities, Finance, Donations, Admin …)
app/Http/Middleware    RequireAccess (guest|pending|member|admin), EnsureAccountIsActive, SecurityHeaders
app/Providers          AppServiceProvider — the permission matrix as Gates
app/Services           FamilyService (tree rules), FinanceService (money rules), UserAdminService,
                       MediaService (uploads, resize ≤ 1920 px), AuditLogger, StatsService, BackupService
app/Support            Format (₭, %, dates, +856 phones), Lookups (provinces, categories), MemberPresenter (privacy), Csv
database/migrations    users · family · content · finance · system (reversible)
resources/css/app.css  design tokens (light/dark) + component utilities
resources/js           ui, search (Ctrl/Cmd+K), notifications, member-form, tree, picker, finance, lightbox
resources/views        components/ (layouts, StatCard, StatusBadge, FilterChips, Dialog …) + one folder per page
deploy/                server-setup.sh, deploy.sh, nginx / php config, offsite backup
tests/                 Feature + Unit
```
