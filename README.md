# 🥬 Luntiang H.A.P.A.G. - Laravel E-commerce

**Luntiang H.A.P.A.G. (Health Awareness and Professional Advisory Group)** — Fresh Hydroponic Harvest-on-Demand Lettuce Farm E-commerce Platform.

Converted from vanilla PHP (`/original/`) to **Laravel 12** with full MVC, Eloquent ORM, and Tailwind UI.

> 📍 Nostalji Subdivision, Paliparan I, Dasmariñas, Cavite  
> 🚚 FREE delivery within Nostalji | ₱50 outside | Same-day harvest  
> 📞 0998-572-1327

---

## ✨ Features

### Customer Side
- **8 Lettuce Varieties**: Romaine, Batavia, Bianca, Dabi, Estrosa, Olmetie, Red Lettuce, Lalique
- **3 Sales Modes**: Retail (per cup), Bundles (5-cup), Wholesale (50-cup trays)
- **Harvest-on-Demand Flow**: Order → Confirmed → Harvest 1-3hrs → Pack → Deliver
- **Cart**: Session-based with DB sync, AJAX add/update/remove, select/deselect, stock multiplier logic
- **Coupons**: Claimable promotions, per-user claimed_coupons, used_at tracking, discount + free delivery
- **Checkout**: Delivery vs Pickup, payment methods (COD, GCash, Maya, Bank Transfer), address management, 24h return window check
- **Orders**: Tracking, cancellation, confirmation page
- **Support**: Tickets (with file upload, reply thread), Warranty/Freshness Guarantee, Returns & Refund, Live Chat with AI chatbot + human handover
- **Reviews**: Verified purchase check, moderation
- **Pages**: About, FAQ, Privacy, Terms, Contact, Feedback

### Chatbot
- `ChatbotEngine` with knowledge base (`chatbot-knowledge.php`), intent detection, state machine (`chat_bot_state` table)
- Handles: login help, forgot password step-by-step, order status, delivery, product info, escalation to human via `live_chat_messages`

### Admin Side
- Dashboard: today revenue, order counts by status, delivery stats
- Admins: login, role
- Products CRUD + stock management with `stock_product_id` + `stock_multiplier` linking bundles to base lettuce
- Orders management
- Customers overview
- Tickets, Warranty, Returns, Reviews, FAQs, Feedback, Promotions, Notifications, Live Chat, Reports

---

## 🛠 Tech Stack

- **Framework**: Laravel 12 (PHP 8.2+)
- **Frontend**: Blade + Tailwind CDN + custom design-system.css (Nunito font)
- **DB**: MySQL (config says `luntiang_hapag`), with migrations for all tables
- **Session**: database driver
- **Auth**: Custom session-based (not Laravel's default Auth) for legacy compatibility — stores `user_id`, `admin_id` in session
- **File Uploads**: `FormHelper::handleUpload` randomizes filename, validates ext/size (5MB total), stores to `storage/app/public/...` + copies to `public/uploads/...`

---

## 📁 Project Structure

```
app/
  Helpers/      CartHelper, FormHelper, AdminNoteHelper, LettuceCatalog, NotificationHelper
  Http/
    Controllers/  Home, Product, Cart, Checkout, Auth, Order, Ticket, Return, Chat, Profile, Review, Support
    Controllers/Admin/  Dashboard, Auth, Product, Order, Customer, Ticket, Warranty, Return, Review, LiveChat, etc.
    Middleware/   CustomerAuth, AdminAuth
  Models/       User, Admin, Product, Category, Order, OrderItem, CartItem, Ticket, TicketReply, etc.
  Services/     ChatbotEngine, chatbot-knowledge.php
resources/views/
  layouts/app.blade.php    Main storefront layout (header, footer, chat widget)
  home.blade.php           Hero + coupons + featured products + How-it-works
  products/                index (filter, search, viewMode) + show
  cart/index.blade.php
  checkout/index.blade.php
  orders/ tracking, confirmation
  tickets/ create, confirm, show
  returns/ index, confirm
  admin/   dashboard + layouts/app + subfolders
public/
  images/lettuce/   hero-farm.png, logo-cropped.png, 5-*/50-*/ etc.
  design-system.css
  uploads/          WARNING: user uploads – should be gitignored!
original/           Legacy vanilla PHP codebase – reference only
database/migrations/
  2024_01_01_... lutiang tables
  2026_07_27_... fix live chat schema, bundle stock fields, seed bundles/wholesale, etc.
routes/web.php      227 lines – includes public, customer.auth, admin groups + .php legacy compat routes
```

---

## 🚀 Quick Setup (Local)

### Prerequisites
- PHP 8.2+, Composer, Node.js, MySQL 8

### 1. Clone & Install
```bash
git clone https://github.com/dianemorales-oss/luntiang-hapag-laravel.git
cd luntiang-hapag-laravel
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### 2. Configure .env
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=luntiang_hapag
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
```

Create DB:
```sql
CREATE DATABASE luntiang_hapag CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Migrate & Seed
```bash
php artisan migrate
# Optional: seed base categories/products if migrations didn't
php artisan db:seed
# Storage link
php artisan storage:link
```

### 4. Build & Run
```bash
npm run build   # or npm run dev
php artisan serve
# visit http://localhost:8000
```

Admin: create manually
```sql
INSERT INTO admins (name,email,password,role) VALUES ('Admin','admin@luntiang.local','$2y$12$...hashed...', 'SuperAdmin');
```
Or add seeder (recommended).

---

## 🗄️ Database Overview

**Core Tables:**
- `users` - customers (first_name, last_name, email/phone unique, address, password hashed, reset_token)
- `admins` - admin accounts
- `categories` - green-lettuce, red-lettuce, salad-mix-bundles, wholesale, etc.
- `products` - slug unique, category_id, name, variety, price per unit, image, plants_available, stock_product_id, stock_multiplier, is_best_seller/new/active/featured
- `orders`, `order_items` - order_number LH-XXXX, status (preparing, ready, delivered...), subtotal/delivery_fee/discount/total
- `cart_items` - persistent cart sync
- `tickets`, `ticket_replies`
- `warranty_requests`, `return_requests`
- `notifications` - admin notifications (order_new, ticket_new etc)
- `feedback`, `faqs`
- `live_chat_messages`, `chat_bot_state`
- `promotions`, `claimed_coupons` (with used_at)
- `sessions`, `cache`, `jobs`

See `database/migrations/2024_01_01_000001_create_luntiang_hapag_tables.php` for full schema.

---

## 🔒 Security Notes (from audit)

**Current Strengths:**
- Passwords hashed via `Hash::make`
- Eloquent ORM binding (no raw SQL injection in new code)
- CSRF via @csrf
- File upload extension whitelist + size limit + randomized names
- Session regeneration on login

**Must Fix (see AUDIT_REPORT.md):**
- Remove tracked `/.config`, `/.local`, `/.subversion`, `/public/uploads/*` from repo
- Order number 4-digit collision risk → use 8+ char or ULID
- DEV reset token shown in view → should send email in production
- No rate limiting on login / chat / ticket create
- Delivery free zone check via `stripos(address, 'Nostalji')` fragile
- N+1 queries in product listing
- Admin routes only check session, no role middleware
- User uploads contain PII and are versioned – leak risk

---

## 🧹 Cleanup Done in this Workspace

- Fixed `.gitignore` to exclude uploads, `.config`, `.local`, `.subversion`, `luntiang-hapag`
- Added placeholder `.gitignore` files inside upload directories
- Created `AUDIT_REPORT.md` and `SETUP_GUIDE.md`

To clean history of bad files already pushed:

```bash
# Remove bad folders from git history (destructive – backup first!)
git filter-repo --path .config --path .local --path .subversion --path public/uploads --path uploads --path luntiang-hapag --invert-paths
# Or using BFG:
# bfg --delete-folders "{.config,.local,.subversion}" --delete-files "image.png"
# git push origin main --force

# Then update .gitignore as in this workspace and commit
```

---

## 📦 Deployment

- **Server**: Apache/Nginx + PHP 8.2+ with `public/` as docroot
- Ensure `storage/` and `bootstrap/cache/` writable
- `.htaccess` already present
- `composer install --no-dev --optimize-autoloader`
- `php artisan config:cache && route:cache && view:cache`
- Set `APP_ENV=production` and `APP_DEBUG=false`
- Configure MySQL and run migrations
- Setup cron for queue if needed: `php artisan queue:work`

---

## 🤝 Contributing

1. Never commit `.env`, `public/uploads`, `storage/app/public`, `.config`, `.local`
2. Use feature branches
3. Write migrations for DB changes
4. Test bundle stock deduction logic carefully

---

## 📄 License

MIT – educational project.

---
**Created for**: Diane Morales / Cavite Hydroponic Farm Demo  
**Maintained**: community fork welcome
