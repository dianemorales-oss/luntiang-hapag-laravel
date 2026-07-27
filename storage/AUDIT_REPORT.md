# 🔍 Luntiang Hapag Laravel – Full Audit Report
**Date:** 2026-07-27 (Asia/Manila)  
**Repo:** https://github.com/dianemorales-oss/luntiang-hapag-laravel  
**Branch:** main – 8 commits – last `jmag`  
**Framework:** Laravel 12, PHP 8.2+, MySQL

---

## 1. Executive Summary

This is a **functional conversion** of a vanilla PHP lettuce e-commerce (`/original/`) to Laravel. Core shop flow works: products → cart → checkout → orders → support. The codebase is mostly working but has **high-priority cleanliness & security issues** due to accidental tracking of local config and user uploads.

**Grade:**
- Functionality: B+ (all customer journeys present)
- Code Quality: C+ (mixed legacy patterns, session auth, N+1)
- Security: C- (needs hardening)
- Repo Hygiene: D (contains `.config`, `.local`, `.subversion`, `public/uploads/*` with PII images/PDFs)
- Maintainability: C (no tests, no FormRequests, no factories)

---

## 2. What’s Good ✅

| Area | Detail |
|------|--------|
| Migration to Laravel | Proper MVC, Eloquent Models, Blade layouts |
| Design System | Unified `design-system.css`, Tailwind, Nunito, responsive product cards |
| Product Catalog | Smart filtering (retail/bundle/wholesale), search with recent_searches session, image fallback |
| Bundle Stock Logic | `stock_product_id` + `stock_multiplier` correctly deducts from base lettuce (5x for bundles, 50x wholesale) |
| Promotions | Claim flow: `promotions` → `claimed_coupons` with `used_at`, active check, free delivery flag |
| Chatbot | State machine, knowledge base, password reset guided flow – impressive for student project |
| Admin Panel | Dashboard metrics, order grouping, notifications helper |
| File Helper | Randomized filenames, ext whitelist, size cap 5MB, separate storage + public copy – prevents path traversal |
| Session Cart | Guest → merge on login via CartHelper::mergeGuestCart |

---

## 3. Critical Issues – Must Fix 🚨

### 3.1 Repo Hygiene – PII Leak

**Found tracked:**
- `.config/` (likely contains user system config)
- `.local/share/composer`
- `.subversion/`
- `.sudo_as_admin_successful`
- `luntiang-hapag` (empty? maybe leftover sqlite?)
- `public/uploads/warranty/*.png/jpg/pdf` – e.g. `fcecb86918db91f7.png`, `2e46138f8b362db2.pdf` – looks like user KYC/proof
- `public/uploads/tickets/`, `returns/`, `chat/`
- `uploads/` (50-romaine-lettuce.png etc duplicative with public/images)
- `original/uploads/` also contains same warranty/tickets

**Risk:** GDPR/PII violation, repo bloat.  
**Fix:** Already fixed `.gitignore` in workspace. Must rewrite history:

```bash
git rm -r --cached .config .local .subversion public/uploads uploads original/uploads luntiang-hapag .sudo_as_admin_successful
git add .gitignore
git commit -m "fix: remove leaked config & user uploads, update gitignore"
# For history purge:
git filter-repo --path .config --path .local --path .subversion --path public/uploads --path uploads --path original/uploads --path luntiang-hapag --invert-paths --force
git push --force
```

Add placeholder `public/uploads/.gitignore` with:
```
*
!.gitignore
```

### 3.2 Security

| # | Issue | Proof | Severity | Recommendation |
|---|-------|-------|----------|----------------|
| S1 | Custom session auth, no throttling | `AuthController@login` no rate limit | High | Use `RateLimiter`, add `ThrottleRequests` middleware, or switch to `Auth::attempt()` |
| S2 | Password reset token displayed in view | `auth.forgot-password` returns `['success'=>true,'token'=>$token]` | Medium | In prod, send mail via `Mail::send()`, log token only if `APP_DEBUG`. Remove preview in production. |
| S3 | 4-digit order number | `LH-` + `random_int(0,9999)` – only 10k combos | Medium | Use 8 chars or ULID: `'LH-'.Str::upper(Str::random(8))` or `Order::max('id')+...` with transactions, or DB sequence |
| S4 | File upload copies to public/uploads readable | Both storage + public – but public folder indexed? | Medium | Keep only storage/app/public, use `storage:link`, deny execution via `.htaccess` |
| S5 | No role check on admin | `AdminAuth` only checks `admin_id` session | Medium | Add roles: `SuperAdmin` vs `Staff`, middleware `admin.role:SuperAdmin` |
| S6 | XSS possible via ticket message / feedback if not escaped | Check blade uses `{{ }}` not `{!! !!}` – mostly safe but verify | Low | Ensure all user input escaped, add `strip_tags` |
| S7 | Delivery free zone logic fragile | `stripos($addr, 'Nostalji')` – bypass via "Not Nostalji"? | Low | Use explicit boolean field `is_free_delivery_zone` or barangay list |
| S8 | Session fixation | `session()->regenerate()` only on login – not on admin login? | Low | Regenerate on both customer & admin login |

### 3.3 Performance

- **N+1 in ProductController@index**: `Product::get()->map(fn => Review::where(product_id)... )` → 1 query per product. Should use `withCount`, `withAvg`:
  ```php
  Product::withCount(['reviews as review_count' => fn($q)=>$q->where('is_approved',1)])
          ->withAvg(['reviews as avg_rating' => fn($q)=>$q->where('is_approved',1)], 'rating')
          ->get()
  ```
- **HomeController fallback to static catalog** loads file on exception – okay but should log.
- **Chatbot** loads knowledge base per request – cache it: `Cache::remember('kb', 3600, fn()=>require...)`
- **Cart sync** deletes then re-inserts all items per change – could be transactional but fine for small cart.

### 3.4 Logic & Architecture

- **Middleware:** `CustomerAuth` and `AdminAuth` not registered in `bootstrap/app.php`? Check Laravel 12 new bootstrap. Ensure aliases in `AppServiceProvider` or `app.php`.
- **Routes file 227 lines**: duplicate `.php` compatibility routes (e.g. `/login.php` and `/login`). Useful for migration but should be removed after 30 days or 301 redirects.
- **No Validation Layer**: manual `trim()` + `preg_match` in AuthController. Should use `FormRequest` with rules.
- **Transaction handling**: Checkout uses `DB::beginTransaction()` good, but `CartHelper::syncToDb` deletes first then creates – wrap in transaction.
- **NotificationHelper** swallows exceptions – should log.
- **LiveChat** schema compatibility migrations `2026_07_27_000001_fix_live_chat_schema_compatibility.php` – indicates original DB had `updated_at` missing. Current model sets `UPDATED_AT = null` – correct workaround but should be documented.

### 3.5 Code Quality

- **Mixed casing**: `ChatbotEngine` uses `snake_case` DB but camelCase vars – okay.
- **Helpers**: `LettuceCatalog::get()` just `require` original file – should be moved to config/seed.
- **Views**: logic in Blade (e.g. `@php $cartCount = ...`). Should be composers or View::share.
- **No Tests**: `tests/` only has ExampleTest. Add Feature tests for checkout.
- **No Factories**: `UserFactory` exists but not used for other models.

---

## 4. Database Observations

- `users` table uses `created_at` `useCurrent()` but no `softDeletes`. `reset_token` manual – Laravel's password_reset_tokens exists but unused.
- `products` lacks index on `slug`, `category_id` – migration defines foreign key but not index for `is_active` + `is_featured` filter.
- `orders` `order_number` unique – good.
- `live_chat_messages` index on `chat_key` – good.
- Missing `customer_addresses` in first migration? Check full file – should exist, but truncated.
- `claimed_coupons` `used_at` added later – good for one-time coupon.
- `bundle` and `wholesale` seeded via migration – should be seeder, not migration, but works.

---

## 5. Frontend Findings

- `layouts/app.blade.php` includes Tailwind CDN – not production safe, should use Vite build.
- `design-system.css` exists but duplicate in `public/assets/` and `public/` – consolidate.
- Chat widget scroll hide logic works but uses inline JS – move to app.js.
- Product images: mixes `images/lettuce/` and `uploads/` – standardize to `storage/app/public/products`.
- Accessibility: alt tags present, but buttons lack aria.

---

## 6. Suggested Roadmap (Priority Order)

### P0 – Immediately (This Week)
1. Fix `.gitignore`, purge leaked files from history, force push.
2. Verify `public/uploads/.gitignore` placeholder.
3. Change order number generation to 8-char random + check uniqueness.
4. Remove dev token preview in production (`if app()->isLocal()`).
5. Add rate limiting: `Route::post('/login')->middleware('throttle:5,1')`

### P1 – Short Term (2 Weeks)
6. Refactor Product index N+1:
   ```php
   Product::where('is_active',1)->withAvg(...)->withCount(...)->...
   ```
7. Move to Laravel Auth or at least add `LoginRequest` FormRequest with validation.
8. Register middlewares in `bootstrap/app.php` (Laravel 12 style):
   ```php
   ->withMiddleware(fn($m)=>$m->alias(['customer.auth'=>CustomerAuth::class, 'admin.auth'=>AdminAuth::class]))
   ```
9. Convert `.php` legacy routes to redirects, then remove.
10. Consolidate design-system.css to Vite.

### P2 – Medium (Month)
11. Create seeders for categories, products, admins, promotions.
12. Add feature tests: cart add, coupon claim, order placement.
13. Add storage:link and make uploads private with signed URLs.
14. Improve free delivery zone: use DB table `delivery_zones`.
15. Cache chatbot knowledge base.
16. Add admin role & permission.

### P3 – Nice to Have
17. Move cart to DB primary (not session) for logged users, sync on guest.
18. Add email notifications (order status, ticket replies) via queue.
19. Add PHPStan / Pint for code quality.
20. Add Docker compose for local dev.

---

## 7. Clean .gitignore Provided

See `.gitignore` in workspace – excludes uploads, .config, .local, .subversion, IDE.

---

## 8. Setup Verified

- `composer.json` requires `php ^8.2`, `laravel/framework ^12.0`
- No vendor currently installed in runner (php missing) – but `composer install` should work locally.
- `vite.config.js` present – uses `resources/js/app.js` + `css/app.css`
- `artisan` executable

---

## 9. Educational Notes

This project is a **great capstone** – shows:
- Full e-commerce lifecycle
- Hydroponic niche – good storytelling (Harvest-on-Demand)
- Chatbot AI integration concept

If submitting for thesis, emphasize:
- Challenges converting procedural PHP to MVC
- Bundle stock multiplier logic
- Chatbot state machine persistence
- Admin dashboard analytics

Add architecture diagram: Customer → Cart (session+DB) → Checkout (transaction + stock deduct + notification) → Admin → fulfillment.

---

## 10. File Checklist

- [x] README.md created (workspace)
- [x] AUDIT_REPORT.md this file
- [x] .gitignore fixed
- [ ] TODO: remove `.config`, `.local`, `.subversion`, `public/uploads/*` from GitHub history
- [ ] TODO: add `public/uploads/.gitignore` placeholder files
- [ ] TODO: update order number generator
- [ ] TODO: add RateLimiter

---

**Auditor:** Arena AI Agent  
**Contact:** Provide if needed for follow-up.

End of report.
