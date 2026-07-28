#!/usr/bin/env bash
# =============================================================================
#  make-offline-bundle.sh
#  RUN THIS ONCE ON A COMPUTER **WITH** INTERNET.
#
#  Produces: luntiang-hapag-offline.zip
#  That single zip can be copied by USB to a machine with NO internet at all
#  and installed there with scripts/offline-setup.sh
#
#  What it bakes in:
#    - vendor/                (all Composer packages, so no `composer install`)
#    - public/assets/*        (Tailwind + Nunito, so no CDN calls)
#    - database/database.sqlite (optional pre-migrated+seeded DB)
# =============================================================================
set -euo pipefail

cd "$(dirname "$0")/.."
ROOT="$(pwd)"
OUT="${ROOT}/luntiang-hapag-offline.zip"

say() { printf '\n\033[1;32m==> %s\033[0m\n' "$1"; }
warn() { printf '\033[1;33m!! %s\033[0m\n' "$1"; }
die()  { printf '\n\033[1;31mERROR: %s\033[0m\n' "$1"; exit 1; }

# ---------------------------------------------------------------------------
say "1/4  Installing PHP dependencies into vendor/"
# IMPORTANT - PHP version portability.
#
# Composer resolves packages against whatever PHP *this* machine runs. If you
# build on PHP 8.4, it picks Symfony 8.x, which hard-requires PHP >= 8.4.1.
# Copy that vendor/ to a PHP 8.2 machine (XAMPP's usual version) and every
# artisan command dies instantly with:
#   "Your Composer dependencies require a PHP version >= 8.4.1"
#
# TARGET_PHP pins resolution to the LOWEST PHP you intend to run on, so the
# bundle works on that machine and every newer one. Override if needed:
#   TARGET_PHP=8.3.0 bash scripts/make-offline-bundle.sh
TARGET_PHP="${TARGET_PHP:-8.2.0}"
echo "    Targeting PHP ${TARGET_PHP} for maximum portability"

php -r '
$j = json_decode(file_get_contents("composer.json"), true);
$cur = $j["config"]["platform"]["php"] ?? null;
if ($cur !== $argv[1]) {
    $j["config"]["platform"]["php"] = $argv[1];
    file_put_contents("composer.json",
        json_encode($j, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n");
    echo "    composer.json: config.platform.php = {$argv[1]}\n";
}' "$TARGET_PHP"

# `update` (not `install`) so the lock is re-resolved against TARGET_PHP.
composer update --no-interaction --no-progress --optimize-autoloader

# Verify the built vendor/ really is usable on the target PHP.
GUARD=$(grep -o 'PHP_VERSION_ID >= [0-9]*' vendor/composer/platform_check.php | grep -o '[0-9]*' || echo 0)
WANT=$(php -r 'printf("%d", sprintf("%d%02d%02d", ...array_pad(explode(".", $argv[1]), 3, 0)));' "$TARGET_PHP")
if [ "$GUARD" -gt "$WANT" ]; then
  die "vendor/ still demands PHP_VERSION_ID >= $GUARD (wanted <= $WANT).
  Resolution failed to honour the platform pin."
fi
echo "    vendor/ runs on PHP ${TARGET_PHP}+ (guard: ${GUARD})"

# ---------------------------------------------------------------------------
say "2/4  Vendoring front-end assets (Tailwind + Nunito)"
bash scripts/vendor-assets.sh

# ---------------------------------------------------------------------------
say "3/4  Building a pre-migrated SQLite database"
# Ships a ready-to-use DB so the offline machine needs no MySQL server.
if [ ! -f .env ]; then
  cp .env.example .env
fi
touch database/database.sqlite
php -r '
$e = file_get_contents(".env");
$e = preg_replace("/^DB_CONNECTION=.*$/m", "DB_CONNECTION=sqlite", $e);
file_put_contents(".env", $e);
'
php artisan key:generate --force >/dev/null 2>&1 || true
php artisan migrate --force
php artisan db:seed --force || warn "Seeder skipped/failed (non-fatal)"

# ---------------------------------------------------------------------------
say "4/4  Zipping bundle"
rm -f "$OUT"
zip -qr "$OUT" . \
  -x '*.git/*' \
  -x 'node_modules/*' \
  -x '.env' \
  -x 'storage/logs/*.log' \
  -x 'storage/framework/cache/data/*' \
  -x 'storage/framework/sessions/*' \
  -x 'storage/framework/views/*' \
  -x 'luntiang-hapag-offline.zip'

printf '\n\033[1;32m✔ Bundle ready:\033[0m %s (%s)\n' "$OUT" "$(du -h "$OUT" | cut -f1)"
cat <<'MSG'

NEXT STEPS
  1. Copy luntiang-hapag-offline.zip to the offline machine (USB, LAN, etc.)
  2. Unzip it there.
  3. Run:  bash scripts/offline-setup.sh
  4. Open: http://127.0.0.1:8000

The offline machine only needs PHP 8.2+ installed. No internet, no Composer,
no npm, no MySQL server required.
MSG
