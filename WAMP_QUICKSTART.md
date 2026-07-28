# 🪟 WAMP Quickstart — PHP 8.2.12

For `C:\wamp64\www\luntiang-hapag-laravel` on **PHP 8.2.12**.

---

## ⚠️ Why plain `composer install` fails for you

The `composer.lock` committed to this repo was generated on PHP 8.4. It locked
**Symfony 8.1**, which hard-requires **PHP ≥ 8.4.1**. On your PHP 8.2.12 you'd get:

```
Your Composer dependencies require a PHP version ">= 8.4.1". You are running 8.2.12.
```

Six packages cause it: `symfony/clock`, `css-selector`, `event-dispatcher`,
`string`, `translation`, `yaml`.

**Fix:** pin Composer to PHP 8.2 and re-resolve. That drops Symfony to 7.4 —
still Laravel 12, everything works. Already done in this repo's `composer.json`:

```json
"config": { "platform": { "php": "8.2.0" } }
```

Because of that pin you must run **`composer update`**, not `composer install`,
the first time.

---

## The easy way

From your CMD prompt:

```cmd
cd C:\wamp64\www\luntiang-hapag-laravel
setup-offline.bat
```

`setup-offline.bat` checks PHP and extensions, pins to 8.2, installs packages,
creates `.env`, builds the SQLite database, seeds the admin, and clears caches.
**Run it once while online.** After that, no internet needed.

Then:

```cmd
php artisan serve
```

- Storefront — <http://127.0.0.1:8000>
- Admin — <http://127.0.0.1:8000/admin/login>
- Login — `admin@luntianghapag.com` / `Admin@123`

---

## The manual way

If you'd rather type each step:

```cmd
cd C:\wamp64\www\luntiang-hapag-laravel

composer update --optimize-autoloader

copy .env.example .env
```

Open `.env` in a text editor. Set `DB_CONNECTION=sqlite` and put `#` in front of
`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. Then:

```cmd
type nul > database\database.sqlite

php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan serve
```

---

## Prefer MySQL? WAMP already runs it

MySQL over `localhost` is **not** the internet, so it works offline fine. If you
prefer phpMyAdmin, skip the SQLite steps and leave `.env` as:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=luntiang_hapag
DB_USERNAME=root
DB_PASSWORD=
```

Create the `luntiang_hapag` database in phpMyAdmin first (collation
`utf8mb4_unicode_ci`), then run `migrate` and `db:seed` as above.

SQLite is only the default because it needs no server and no database creation.

---

## WAMP-specific gotchas

**`'php' is not recognized`**
PHP isn't on your PATH. Add `C:\wamp64\bin\php\php8.2.12` via
*System Properties → Environment Variables → Path*, then **open a new CMD**.

**`could not find driver`**
Enable `pdo_sqlite`: left-click the WAMP tray icon → *PHP → PHP extensions* →
tick `pdo_sqlite` → restart all services. Note WAMP has **two** `php.ini` files
(CLI and Apache); the tray menu updates both.

**`storage:link` fails**
Windows blocks symlinks without elevation. Either run CMD as Administrator, or
just copy `storage\app\public` to `public\storage`.

**Don't use `http://localhost/luntiang-hapag-laravel`**
Even though the project sits in `www\`, use `php artisan serve` and
<http://127.0.0.1:8000>. Laravel's document root must be the `public\` folder;
browsing the project folder directly through Apache exposes your `.env` and
breaks routing. (If you *want* Apache, point a VirtualHost at the `public\`
subfolder.)

**Changed `.env` and nothing happened**
`php artisan config:clear`

---

## Confirming it's truly offline

Turn off Wi-Fi, then hard-refresh with `Ctrl+F5`. The page should still be fully
styled in green with the Nunito font. If it looks like plain unstyled text,
`public\assets\tailwind.min.js` is missing — that file must be present.
