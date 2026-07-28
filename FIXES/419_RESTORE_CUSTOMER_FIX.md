# Fix: "419 Page Expired" when restoring a soft-deleted customer

## Summary

Restoring a soft-deleted customer intermittently failed with a bare
**419 | Page Expired** screen. The action was lost and the admin had no
indication of what went wrong or what to do next.

## Investigation

The repository was cloned, dependencies installed, and the app booted against a
local SQLite database so the bug could be reproduced through the real HTTP
stack (session middleware, `VerifyCsrfToken`, and all) rather than guessed at.

Each item from the checklist was verified:

| Checked | Finding |
| --- | --- |
| Restore route | `POST /admin/customers/{id}/restore` — correct, named `admin.customers.restore` |
| HTTP method | Form is `method="POST"` matching the route — correct |
| CSRF token in form | `@csrf` present in the restore form — correct |
| Session config | `SESSION_DRIVER=database`, `sessions` table migration exists — correct |
| Controller logic | `onlyTrashed()->findOrFail()` then `restore()` — correct |

A restore in a clean, freshly-authenticated session **succeeded**, which is why
the bug looked intermittent.

## Root cause

The 419 was never caused by a missing or malformed CSRF token. It happened when
the token in the already-rendered page went **stale** — the session expired or
was regenerated while the *Deleted Customer Accounts* page sat open in the
browser.

Reproduced deterministically: capture the restore form's token, expire the
session, submit the form.

```
RESTORE after expiry: 419
Page Expired
```

Laravel's default behaviour for a `TokenMismatchException` is to render the
static 419 error page — a dead end that discards the admin's action.

Two secondary problems made this worse:

1. `restore()` redirected to `admin.customers.deleted`, but a restored account
   is active and no longer appears on that list — so the admin got no visible
   confirmation the restore had worked.
2. `admin/customers/index.blade.php` rendered **no** `session('success')` or
   `session('error')` banner at all, so any flash message sent there was
   silently dropped.

## Changes

**`bootstrap/app.php`** — expired/stale CSRF tokens now redirect back to the
originating page with a clear message instead of the 419 screen. AJAX callers
receive JSON (including a fresh `csrf_token` so the client can retry).

Note: Laravel maps `TokenMismatchException` to a generic `HttpException(419)`
in `prepareException()` *before* render callbacks run, so the handler matches
on the 419 status code rather than the exception class.

**`app/Http/Controllers/Admin/CustomerController.php`** — `restore()` now
redirects to the **Customers** page with a success notification, and returns
JSON when called via AJAX.

**`resources/views/admin/layouts/app.blade.php`** — added
`<meta name="csrf-token">` so AJAX calls can send the token in headers.

**`resources/views/admin/customers/index.blade.php`** — added the missing
success/error banners.

**`resources/views/admin/customers/deleted.blade.php`** — added an error banner.

## Security

CSRF protection is **not** weakened. The forged request is still rejected and
never executes — only the *presentation* of the rejection changed. Verified:

```
--- forged request with BAD token ---
  status: 302
  still trashed (must be YES) = YES - CSRF ENFORCED
```

## Verification

Restore (normal): `302 -> /admin/customers`, banner reads
*"Customer account for Resto Tester was restored successfully."*,
DB shows `trashed=no`, `deleted_by=NULL`.

Restore (stale token): `302` back to the deleted page with
*"Your session expired for security reasons. Please try that action again."*
— no 419 page.

Restore (AJAX, valid token): `200` with `{"success":true,...}`.
Restore (AJAX, stale token): `419` JSON with a fresh token, not an HTML page.

Regression: all admin pages (dashboard, customers, deleted, products, orders,
tickets, faqs, feedback, reviews, profile) and public pages (`/`, `/login`,
`/products`, `/cart`, `/faq`) return `200`. Soft delete, restore, and admin
profile update all still work. 404s render normally.

Test suite: **6 passed** (2 pre-existing + 4 new in
`tests/Feature/CustomerRestoreTest.php`).
