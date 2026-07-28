#!/usr/bin/env bash
# =============================================================================
#  offline-setup.sh
#  RUN THIS ON THE MACHINE WITH **NO INTERNET**.
#
#  Requirements on that machine: PHP 8.2+ only.
#  No Composer, no npm, no MySQL server, no network access needed.
#
#  Safe to re-run.
# =============================================================================
set -euo pipefail

cd "$(dirname "$0")/.."

say()  { printf '\n\033[1;32m==> %s\033[0m\n' "$1"; }
warn() { printf '\033[1;33m!!  %s\033[0m\n' "$1"; }
die()  { printf '\n\033[1;31mERROR: %s\033[0m\n' "$1"; exit 1; }

# --- 0. Sanity checks ------------------------------------------------------
say "Checking PHP"
command -v php >/dev/null 2>&1 || die "PHP not found. Install PHP 8.2+ first (XAMPP/Laragon on Windows)."
php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' \
  || die "PHP $(php -r 'echo PHP_VERSION;') is too old. Need 8.2+."
echo "    PHP $(php -r 'echo PHP_VERSION;') OK"

say "Checking required PHP extensions"
MISSING=""
for ext in pdo mbstring openssl tokenizer xml ctype json fileinfo; do
  php -m | grep -qi "^${ext}$" || MISSING="$MISSING $ext"
done
php -m | grep -qi "^pdo_sqlite$" || MISSING="$MISSING pdo_sqlite"
[ -n "$MISSING" ] && die "Missing PHP extensions:$MISSING (enable them in php.ini)"
echo "    All required extensions present"

say "Checking vendor/ (Composer packages)"
if [ ! -f vendor/autoload.php ]; then
  die "vendor/ is missing.
  This machine has no internet, so 'composer install' cannot work here.
  Build the bundle on an ONLINE machine first:
      bash scripts/make-offline-bundle.sh
  then copy the resulting zip here and unzip it."
fi
echo "    vendor/autoload.php found"

# vendor/ is compiled against the PHP of the machine that built it. If that was
# newer than this machine's PHP, every artisan command fails with a confusing
# platform error. Catch it here with an actionable message instead.
if [ -f vendor/composer/platform_check.php ]; then
  GUARD=$(grep -o 'PHP_VERSION_ID >= [0-9]*' vendor/composer/platform_check.php | grep -o '[0-9]*' | head -1)
  HERE=$(php -r 'echo PHP_VERSION_ID;')
  if [ -n "$GUARD" ] && [ "$HERE" -lt "$GUARD" ]; then
    HUMAN=$(php -r '$v=$argv[1]; printf("%d.%d.%d", intdiv($v,10000), intdiv($v%10000,100), $v%100);' "$GUARD")
    die "vendor/ was built for PHP ${HUMAN}+, but this machine runs $(php -r 'echo PHP_VERSION;').

  The bundle was built on a machine with a newer PHP. Rebuild it on the
  ONLINE machine, pinned to this machine's PHP version:

      TARGET_PHP=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION.".0";') bash scripts/make-offline-bundle.sh

  (Or install a matching PHP here.)"
  fi
  echo "    vendor/ is compatible with this PHP"
fi

say "Checking offline front-end assets"
for f in public/assets/tailwind.min.js public/assets/nunito.css; do
  [ -f "$f" ] || warn "$f missing - UI will look unstyled. Run scripts/vendor-assets.sh on an online machine."
done

# --- 1. .env ---------------------------------------------------------------
say "Preparing .env"
if [ ! -f .env ]; then
  cp .env.example .env
  echo "    created .env from .env.example"
else
  echo "    .env already exists (kept as-is)"
fi

# Force SQLite so no MySQL server is needed offline.
php -r '
$p = ".env";
$e = file_get_contents($p);
$e = preg_replace("/^DB_CONNECTION=.*$/m", "DB_CONNECTION=sqlite", $e, 1);
// Comment out MySQL-only settings; SQLite ignores them but this avoids confusion.
foreach (["DB_HOST","DB_PORT","DB_DATABASE","DB_USERNAME","DB_PASSWORD"] as $k) {
    $e = preg_replace("/^{$k}=/m", "# {$k}=", $e, 1);
}
if (!preg_match("/^DB_CONNECTION=/m", $e)) { $e .= "\nDB_CONNECTION=sqlite\n"; }
file_put_contents($p, $e);
'
echo "    DB_CONNECTION forced to sqlite"

# --- 2. App key ------------------------------------------------------------
say "Application key"
if grep -qE '^APP_KEY=base64:.+' .env; then
  echo "    APP_KEY already set"
else
  php artisan key:generate --force
fi

# --- 3. Database -----------------------------------------------------------
say "Database (SQLite)"
mkdir -p database
[ -f database/database.sqlite ] || { touch database/database.sqlite; echo "    created database/database.sqlite"; }

php artisan migrate --force

# Always run the seeder. Every block inside DatabaseSeeder guards itself
# (count()===0 / firstOrCreate / updateOrCreate), so this is safe to repeat.
#
# Do NOT skip this when products already exist: the product catalog is created
# by a *migration*, while the admin account is created only by the *seeder*.
# Skipping on "products exist" would leave you with zero admins and no way to
# log into /admin/login.
php artisan db:seed --force || warn "Seeder failed (non-fatal)"

# Verify an admin actually exists, since that is the usual thing to break.
php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
    $n = \App\Models\Admin::count();
    echo $n > 0
        ? "    Admin accounts: $n\n"
        : "\033[1;33m!!  No admin account exists - run: php artisan db:seed --force\033[0m\n";
} catch (\Throwable $e) {
    echo "\033[1;33m!!  Could not verify admin account: ".$e->getMessage()."\n\033[0m";
}
'

# --- 4. Storage & permissions ---------------------------------------------
say "Storage"
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs \
         storage/app/public public/uploads
chmod -R ug+rw storage bootstrap/cache 2>/dev/null || true

# storage:link uses a symlink; fall back to a copy where symlinks aren't allowed
# (common on Windows without admin rights).
if [ ! -e public/storage ]; then
  php artisan storage:link 2>/dev/null || warn "storage:link failed - uploads may not display"
fi

# --- 5. Clear stale caches -------------------------------------------------
say "Clearing caches"
php artisan config:clear >/dev/null
php artisan route:clear  >/dev/null
php artisan view:clear   >/dev/null
echo "    done"

# --- 6. Done ---------------------------------------------------------------
cat <<'MSG'

============================================================
 ✔ SETUP COMPLETE - fully offline, no internet required
============================================================

Start the server:

    php artisan serve

Then open:

    Storefront   http://127.0.0.1:8000
    Admin panel  http://127.0.0.1:8000/admin/login

Default admin login (from the seeder):

    Email     admin@luntianghapag.com
    Password  Admin@123

    ^ Change this before showing it to anyone.

============================================================
MSG
