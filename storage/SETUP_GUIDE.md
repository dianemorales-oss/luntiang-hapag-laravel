# Setup Guide – Luntiang Hapag Laravel

This guide gets the project running from scratch on Windows / Mac / Linux.

## 1. Requirements

- PHP 8.2+ with extensions: pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, fileinfo, gd
- Composer 2.7+
- Node.js 20+ & npm
- MySQL 8 or MariaDB 10.6+
- Git

Check:
```bash
php -v
composer -v
node -v
mysql --version
```

## 2. Clone

```bash
git clone https://github.com/dianemorales-oss/luntiang-hapag-laravel.git luntiang-hapag
cd luntiang-hapag
```

## 3. Install deps

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

## 4. Create DB

```bash
mysql -u root -p
```
```sql
CREATE DATABASE luntiang_hapag CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'luntiang'@'localhost' IDENTIFIED BY 'password123';
GRANT ALL ON luntiang_hapag.* TO 'luntiang'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Update `.env`:
```env
APP_NAME="Luntiang HAPAG"
APP_URL=http://localhost:8000
DB_DATABASE=luntiang_hapag
DB_USERNAME=luntiang
DB_PASSWORD=password123
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
```

## 5. Migrate + Link

```bash
php artisan migrate
php artisan storage:link
```

If you see error about live_chat schema compatibility, migrations already handle it (2026_07_27_...).

Seed products (if empty):
```bash
php artisan db:seed
# or run specific migration that seeds bundles:
php artisan migrate --path=database/migrations/2026_07_27_000003_seed_bundle_and_wholesale_products.php
```

## 6. Create Admin

Via tinker:
```bash
php artisan tinker
```
```php
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
Admin::create(['name'=>'Super Admin','email'=>'admin@luntiang.local','password'=>Hash::make('Admin123!'),'role'=>'SuperAdmin']);
exit
```

## 7. Build frontend

Dev:
```bash
npm run dev
# in another terminal
php artisan serve
```

Prod build:
```bash
npm run build
php artisan serve --port=8000
# or configure Apache docroot to /public
```

Visit:
- Customer: http://localhost:8000
- Admin login: http://localhost:8000/admin/login  (check routes/web.php – route name admin.login)

## 8. Default Test Data

Create customer via Register page.

Coupons – insert manually or via admin:
```sql
INSERT INTO promotions (code, description, discount_type, discount_value, min_order, is_active, is_free_delivery, expires_at) VALUES
('WELCOME10','10% off first order','percentage',10,0,1,0,'2026-12-31'),
('FREESHIP','Free delivery','fixed',0,200,1,1,'2026-12-31');
```

## 9. Permissions

```bash
# Linux/Mac
chmod -R 775 storage bootstrap/cache
chown -R $USER:www-data storage bootstrap/cache
```

## 10. Apache/Nginx Example

**Apache VHost:**
```apache
<VirtualHost *:80>
 ServerName luntiang.local
 DocumentRoot /var/www/luntiang-hapag-laravel/public
 <Directory /var/www/luntiang-hapag-laravel/public>
   AllowOverride All
   Require all granted
 </Directory>
</VirtualHost>
```

**Nginx:**
```nginx
server {
  listen 80;
  server_name luntiang.local;
  root /var/www/luntiang-hapag-laravel/public;
  index index.php;
  location / { try_files $uri $uri/ /index.php?$query_string; }
  location ~ \.php$ { fastcgi_pass unix:/var/run/php/php8.2-fpm.sock; include fastcgi_params; fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name; }
}
```

## 11. Queue (Optional)

For mail / notifications:
```bash
php artisan queue:work
```

Or supervisord.

## 12. Cron?

Not required now, but if you add scheduled reports:
```cron
* * * * * cd /var/www/luntiang-hapag && php artisan schedule:run >> /dev/null 2>&1
```

## 13. Troubleshooting

- `SQLSTATE[42S02]: Table 'sessions' doesn't exist` → run `php artisan migrate`
- `Vite manifest not found` → `npm run build`
- Images 404 → `php artisan storage:link` + check `public/images/lettuce/` exists
- 419 Page Expired → clear cache: `php artisan config:clear`, check APP_KEY
- `composer` memory limit → `php -d memory_limit=-1 /usr/local/bin/composer install`

## 14. Production Checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `php artisan config:cache`, `route:cache`, `view:cache`
- [ ] Remove `.env.example` secrets, set strong DB password
- [ ] Set up SSL
- [ ] Backup DB daily
- [ ] `chmod 600 .env`
- [ ] Disable `public/uploads` listing via `.htaccess`
- [ ] Configure mail driver (smtp) not log

End.
