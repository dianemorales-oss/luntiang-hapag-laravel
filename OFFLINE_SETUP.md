# 🔌 Running Luntiang H.A.P.A.G. With No Internet

This guide makes the project run on a machine with **zero internet access** —
useful for a thesis defense, a classroom demo, or a laptop on a dead Wi‑Fi.

**Verified:** the whole app was tested inside an isolated network namespace with
no route to the internet. All pages returned `200`, the UI rendered correctly,
and admin login worked.

---

## The one thing you must understand

There are **four** separate reasons a Laravel app needs the internet. Three of
them only matter *once*, during install:

| # | Needs internet | When | Fix |
|---|---|---|---|
| 1 | `composer install` downloads `vendor/` | install only | Ship `vendor/` in the bundle |
| 2 | `npm install` downloads `node_modules/` | install only | **Not needed** — see below |
| 3 | Tailwind + Google Fonts from CDN | **every page load** | Vendored into `public/assets/` ✅ |
| 4 | MySQL server running | every page load | Use SQLite instead |

Item **3** is the one people miss. Even with `vendor/` present, the site loads
but looks completely broken offline, because every page pulled Tailwind and
Nunito from a CDN. That is now fixed in this repo.

> **Good news about npm:** this project does *not* need Node or Vite at runtime.
> Only `welcome.blade.php` uses `@vite`, and that file isn't routed — the real
> layouts use plain CSS + the Tailwind script. So you can skip npm entirely.

---

## ⚠️ Read this before building the bundle: PHP versions must match

`vendor/` is **not** universally portable. Composer resolves packages against
the PHP version of the machine that builds it.

This repo's committed `composer.lock` was resolved on PHP 8.4, so it pulled
**Symfony 8.1**, which hard-requires **PHP ≥ 8.4.1** — even though
`composer.json` says `"php": "^8.2"`. Copy that `vendor/` onto a PHP 8.2 machine
(XAMPP's common version) and every command dies immediately:

```
Your Composer dependencies require a PHP version ">= 8.4.1". You are running 8.2.x.
```

**The rule:** build the bundle pinned to the *lowest* PHP you'll actually run on.
`make-offline-bundle.sh` defaults to `8.2.0`, which downgrades Symfony to 7.4
(still Laravel 12, fully working — tested). To target a different version:

```bash
TARGET_PHP=8.3.0 bash scripts/make-offline-bundle.sh
```

Check your offline machine's PHP first with `php -v`, and pin to that.
`offline-setup.sh` also detects a mismatch and tells you the exact command to fix it.

---

## Quick start

### On a computer **with** internet (do this once)

```bash
git clone https://github.com/dianemorales-oss/luntiang-hapag-laravel.git
cd luntiang-hapag-laravel
bash scripts/make-offline-bundle.sh
```

This produces **`luntiang-hapag-offline.zip`** containing `vendor/`, the
vendored CDN assets, and a pre-migrated SQLite database.

### On the **offline** computer

Copy the zip across (USB / LAN), then:

```bash
unzip luntiang-hapag-offline.zip -d luntiang-hapag
cd luntiang-hapag
bash scripts/offline-setup.sh
php artisan serve
```

Open <http://127.0.0.1:8000>.

The offline machine needs **only PHP 8.2+**. No Composer, no npm, no MySQL.

**Default admin:** `admin@luntianghapag.com` / `Admin@123` — change it.

---

## Windows (XAMPP / Laragon)

The scripts are bash. On Windows, run them in **Git Bash**, or do it manually:

```bash
copy .env.example .env
```

Edit `.env` and set:

```env
DB_CONNECTION=sqlite
```

…then comment out `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.

```bash
type nul > database\database.sqlite
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan serve
```

If `storage:link` fails (Windows blocks symlinks without admin), either run the
terminal as Administrator or just copy `storage/app/public` to `public/storage`.

---

## Prefer to keep MySQL?

SQLite is only a convenience so you don't need a database *server*. If XAMPP is
already running MySQL locally, that works offline too — `localhost` is not the
internet. Just leave `.env` on MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=luntiang_hapag
DB_USERNAME=root
DB_PASSWORD=
```

Create the database in phpMyAdmin, then run `php artisan migrate --force && php artisan db:seed --force`.

All 15 migrations were tested on **both** SQLite and MySQL syntax paths — the
two MySQL-only `ALTER TABLE` migrations already guard themselves and no-op on
SQLite.

---

## What changed in the code

**Added**

- `public/assets/tailwind.min.js` — Tailwind Play CDN v3.4.16, pinned
- `public/assets/nunito.css` + `public/assets/fonts/*.woff2` — self-hosted Nunito
- `resources/views/partials/offline-assets.blade.php` — single include for both
- `scripts/vendor-assets.sh` — re-downloads the above (needs internet)
- `scripts/make-offline-bundle.sh` — builds the portable zip (needs internet)
- `scripts/offline-setup.sh` — installs on the offline machine

**Modified** — CDN `<script>`/`<link>` tags swapped for the local include in:

- `resources/views/layouts/app.blade.php`
- `resources/views/admin/layouts/app.blade.php`
- `resources/views/admin/auth/login.blade.php`
- `resources/views/welcome.blade.php` (removed Bunny Fonts)

Chart.js was already local at `public/assets/chart.umd.min.js` — no change needed.

---

## Verification results

Tested with `unshare -n` (no network interface except loopback):

```
external reachability
  fonts.googleapis.com ......... UNREACHABLE ✔ (proves the test is real)
  cdn.tailwindcss.com .......... UNREACHABLE ✔

pages
  200  /            200  /products    200  /cart
  200  /about       200  /faq         200  /contact-support
  200  /login       200  /register    200  /admin/login

local assets
  200  /assets/tailwind.min.js
  200  /assets/nunito.css
  200  /assets/fonts/nunito-latin.woff2

admin login POST -> 200, /admin/dashboard -> 200
```

---

## Troubleshooting

**Site loads but looks like plain unstyled HTML**
`public/assets/tailwind.min.js` is missing. Run `scripts/vendor-assets.sh` on an
online machine and copy `public/assets/` over.

**`Class "PDO" not found` / `could not find driver`**
Enable `pdo_sqlite` in `php.ini` (uncomment `extension=pdo_sqlite`), restart.

**`vendor/autoload.php` not found**
You copied the repo without `vendor/`. It cannot be rebuilt offline — build the
bundle on an online machine first.

**`Your Composer dependencies require a PHP version ">= 8.4.1"`**
The bundle was built on a newer PHP than the offline machine runs. Rebuild it on
the online machine pinned to the offline machine's version, e.g.
`TARGET_PHP=8.2.0 bash scripts/make-offline-bundle.sh`

**`Failed to open stream: No such file or directory ... database.sqlite`**
Create it: `touch database/database.sqlite`, then `php artisan migrate --force`.

**Admin login says invalid credentials**
The catalog comes from a migration but the admin comes from the seeder, so a
migrated-but-unseeded DB has products and no admin. Run:
`php artisan db:seed --force`

**Changed `.env` but nothing happened**
`php artisan config:clear`

---

## A note on the repo itself

Unrelated to offline use, but worth flagging: this repo has committed
`.config/`, `.local/`, `.subversion/`, and a 58 MB `storage/` folder that
duplicates the whole project. `.gitignore` lists them, but they were committed
*before* those rules were added, so the rules don't apply. `REPO_STATUS.md`
already documents the `git filter-repo` cleanup — worth doing, as the clone is
~284 MB (64 MB of it `.git`).
