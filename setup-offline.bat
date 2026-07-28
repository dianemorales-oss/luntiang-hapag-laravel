@echo off
REM ===========================================================================
REM  setup-offline.bat  -  Luntiang H.A.P.A.G. offline setup for WAMP / Windows
REM
REM  Double-click this file, or run it from CMD:
REM      cd C:\wamp64\www\luntiang-hapag-laravel
REM      setup-offline.bat
REM
REM  Run it ONCE while you still have internet. After that the app works
REM  with no internet at all.
REM ===========================================================================
setlocal enabledelayedexpansion
cd /d "%~dp0"

echo.
echo ============================================================
echo   Luntiang H.A.P.A.G. - Offline Setup (Windows / WAMP)
echo ============================================================

REM --- 1. PHP present? -------------------------------------------------------
echo.
echo [1/8] Checking PHP...
where php >nul 2>&1
if errorlevel 1 (
  echo.
  echo   ERROR: 'php' is not on your PATH.
  echo   Add this to PATH, then reopen CMD:
  echo       C:\wamp64\bin\php\php8.2.12
  echo.
  pause
  exit /b 1
)
for /f "tokens=*" %%v in ('php -r "echo PHP_VERSION;"') do set PHPVER=%%v
echo       PHP !PHPVER! found

php -r "exit(PHP_VERSION_ID >= 80200 ? 0 : 1);"
if errorlevel 1 (
  echo   ERROR: PHP 8.2+ required. You have !PHPVER!.
  pause
  exit /b 1
)

REM --- 2. Required extensions ------------------------------------------------
echo.
echo [2/8] Checking PHP extensions...
set MISSING=
for %%e in (pdo_sqlite mbstring openssl tokenizer xml ctype json fileinfo curl gd) do (
  php -r "exit(extension_loaded('%%e') ? 0 : 1);" 2>nul
  if errorlevel 1 set MISSING=!MISSING! %%e
)
if not "!MISSING!"=="" (
  echo.
  echo   ERROR: Missing PHP extensions:!MISSING!
  echo.
  echo   Fix in WAMP: left-click the WAMP tray icon
  echo       PHP  ^>  PHP extensions  ^>  tick the missing ones
  echo   Then restart all services.
  echo.
  pause
  exit /b 1
)
echo       All required extensions enabled

REM --- 3. Composer -----------------------------------------------------------
echo.
echo [3/8] Checking Composer...
where composer >nul 2>&1
if errorlevel 1 (
  echo.
  echo   ERROR: Composer not found. Install it from
  echo       https://getcomposer.org/Composer-Setup.exe
  echo   ^(You only need it for this one-time setup, while online.^)
  echo.
  pause
  exit /b 1
)
echo       Composer found

REM --- 4. Pin dependencies to PHP 8.2 ---------------------------------------
echo.
echo [4/8] Pinning dependencies to your PHP version...
echo       ^(the shipped composer.lock wants PHP 8.4+, which would fail here^)
php -r "$j=json_decode(file_get_contents('composer.json'),true); $j['config']['platform']['php']='8.2.0'; file_put_contents('composer.json', json_encode($j, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL);"
echo       composer.json pinned to PHP 8.2.0

REM --- 5. Install packages (NEEDS INTERNET) ---------------------------------
echo.
echo [5/8] Installing PHP packages ^(needs internet, a few minutes^)...
if exist vendor\autoload.php (
  echo       vendor\ already exists - re-resolving for PHP 8.2
)
call composer update --no-interaction --optimize-autoloader
if errorlevel 1 (
  echo.
  echo   ERROR: composer update failed. Check your internet connection.
  pause
  exit /b 1
)
echo       Packages installed

REM --- 6. Environment + database --------------------------------------------
echo.
echo [6/8] Configuring environment...
if not exist .env (
  copy /y .env.example .env >nul
  echo       created .env
) else (
  echo       .env already exists
)

REM Force SQLite so no MySQL database needs to be created by hand.
php -r "$p='.env'; $e=file_get_contents($p); $e=preg_replace('/^DB_CONNECTION=.*$/m','DB_CONNECTION=sqlite',$e,1); foreach(['DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME','DB_PASSWORD'] as $k){ $e=preg_replace('/^'.$k.'=/m','# '.$k.'=',$e,1);} file_put_contents($p,$e);"
echo       using SQLite ^(no MySQL setup needed^)

if not exist database\database.sqlite (
  type nul > database\database.sqlite
  echo       created database\database.sqlite
)

php -r "$e=file_get_contents('.env'); exit(preg_match('/^APP_KEY=base64:.+/m',$e) ? 0 : 1);"
if errorlevel 1 (
  call php artisan key:generate --force
) else (
  echo       APP_KEY already set
)

REM --- 7. Migrate + seed -----------------------------------------------------
echo.
echo [7/8] Building the database...
call php artisan migrate --force
if errorlevel 1 (
  echo   ERROR: migration failed.
  pause
  exit /b 1
)
call php artisan db:seed --force
php -r "require 'vendor/autoload.php'; $a=require 'bootstrap/app.php'; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo '      Admin accounts: '.App\Models\Admin::count().PHP_EOL;"

REM --- 8. Storage + caches ---------------------------------------------------
echo.
echo [8/8] Finishing up...
if not exist public\storage (
  call php artisan storage:link 2>nul
  if errorlevel 1 echo       NOTE: storage:link failed - run CMD as Administrator if uploads don't show
)
call php artisan config:clear >nul
call php artisan route:clear  >nul
call php artisan view:clear   >nul
echo       caches cleared

echo.
echo ============================================================
echo   SETUP COMPLETE - you can now unplug the internet
echo ============================================================
echo.
echo   Start the site with:
echo.
echo       php artisan serve
echo.
echo   Then open:
echo       Storefront    http://127.0.0.1:8000
echo       Admin panel   http://127.0.0.1:8000/admin/login
echo.
echo   Admin login:
echo       admin@luntianghapag.com
echo       Admin@123
echo.
echo   ^(Change that password before showing anyone.^)
echo.
pause
