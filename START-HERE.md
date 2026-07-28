# 📥 START HERE

You downloaded the **`DOWNLOAD-ME`** folder. It contains only the files that
were added or changed to make Luntiang H.A.P.A.G. run with no internet on
**WAMP / PHP 8.2.12**.

---

## How to install it

The folder mirrors your project's structure, so you just **merge it in**.

1. Open your project folder:
   `C:\wamp64\www\luntiang-hapag-laravel`

2. Select **everything inside `DOWNLOAD-ME`** (not the folder itself — its
   contents) and copy it into the project folder.

3. Windows will ask to merge folders and replace files. Choose
   **"Replace the files in the destination"**. That's expected — 6 existing
   files are meant to be overwritten.

4. Open CMD and run it once **while you still have internet**:

   ```cmd
   cd C:\wamp64\www\luntiang-hapag-laravel
   setup-offline.bat
   ```

5. Start the site:

   ```cmd
   php artisan serve
   ```

   Storefront <http://127.0.0.1:8000> · Admin <http://127.0.0.1:8000/admin/login>
   Login `admin@luntianghapag.com` / `Admin@123`

After step 4 you can disconnect the internet permanently.

---

## What's inside

**Overwrites 7 existing files**

| File | Why |
|---|---|
| `composer.json` | Pins Composer to PHP 8.2 (`config.platform.php`) |
| `composer.lock` | Re-resolved for PHP 8.2 — Symfony 8.1 → 7.4 |
| `app/Http/Controllers/Admin/ReportController.php` | MySQL-only `DATE_FORMAT()` crashed `/admin/reports` on SQLite — now works on both |
| `resources/views/layouts/app.blade.php` | CDN tags → local include |
| `resources/views/admin/layouts/app.blade.php` | CDN tags → local include |
| `resources/views/admin/auth/login.blade.php` | CDN tags → local include |
| `resources/views/welcome.blade.php` | Removed Bunny Fonts link |

**Adds new files**

| File | Why |
|---|---|
| `setup-offline.bat` | One-click Windows setup |
| `public/assets/tailwind.min.js` | Tailwind, self-hosted |
| `public/assets/nunito.css` + `fonts/*.woff2` | Nunito, self-hosted |
| `resources/views/partials/offline-assets.blade.php` | Loads both locally |
| `scripts/*.sh` | Linux/macOS equivalents (ignore on Windows) |
| `WAMP_QUICKSTART.md` | Your walkthrough + troubleshooting |
| `OFFLINE_SETUP.md` | Full technical reference |

Nothing else in your project is touched. Your controllers, models, routes,
migrations, and product images are all left alone.

---

## Two things to remember

**Run `composer update`, not `composer install`.** The platform pin means the
lock must be re-resolved. `setup-offline.bat` handles this.

**Don't open `http://localhost/luntiang-hapag-laravel`.** Use `php artisan serve`
and `http://127.0.0.1:8000`. Browsing the project folder through Apache exposes
your `.env` and breaks routing.

---

## Verified

Tested on a fresh clone of your repo with this folder merged in, running inside
an isolated network namespace with **no internet at all**:

```
internet reachability (proves the test is real)
  cdn.tailwindcss.com .................. UNREACHABLE
  fonts.googleapis.com ................. UNREACHABLE

vendor/ compatible with PHP 8.2 ........ PHP_VERSION_ID >= 80200
Admin accounts ......................... 1

customer pages
  200 /   200 /products   200 /cart   200 /about
  200 /faq   200 /login   200 /register   200 /product/romaine-lettuce

admin pages (all 10)
  200 /admin/login      200 /admin/dashboard   200 /admin/reports
  200 /admin/products   200 /admin/orders      200 /admin/customers
  200 /admin/tickets    200 /admin/reviews     200 /admin/promotions
  200 /admin/live-chat  200 /admin/feedback

dynamic features
  add to cart (AJAX) ... {"success":true,"message":"Added to cart"}
  chatbot .............. replied "FREE delivery within Nostalji Subdivision..."

local assets
  200 /assets/tailwind.min.js
  200 /assets/nunito.css
  200 /assets/fonts/nunito-latin.woff2
```

Full troubleshooting (missing `pdo_sqlite`, `storage:link` errors, PATH issues)
is in **`WAMP_QUICKSTART.md`**.
