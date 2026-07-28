# PAYMENT.CHECKOUT.COUPON.USAGE.500.FIX.1

Fix date: 2026-07-27

## A. Active Tree Tested

- Requested active tree: `D:\Work\YounGo\school_prod_2`
- Actual `school.local` Apache vhost root: `D:\Work\YounGo\school`
- Apache config evidence: `C:\xampp\apache\conf\extra\httpd-vhosts.conf` maps `school.local` to `D:/Work/YounGo/school`.
- Scoped fix was applied to both trees.

## B. Exact 500 Cause

Authenticated HTTP reproduction confirmed:

- `/admin/youngo/checkout-coupon-usage` returned `HTTP 500` with an empty body.
- `/admin/youngo/instapay-payments` returned `HTTP 200`.

The app logs were disabled (`application/config/config.php` has `log_threshold = 0`), and Apache/PHP did not emit a current fatal entry for this request. Temporary shutdown probes showed the failing request did not enter `Youngo_checkout_coupon_usage::index()` and did not enter the new `Admin::youngo_checkout_coupon_usage()` method while Apache was running stale route/controller code.

The practical cause of the live 500 was the requested URL relying on the newly added CodeIgniter route/controller path in a running Apache/PHP process that was not executing that new route/action. Because the Apache process could not be restarted from this shell, the active URL needed a direct physical endpoint fallback.

## C. Files Changed

- `.htaccess`
- `admin/youngo/checkout-coupon-usage/index.php`
- `application/config/routes.php`
- `application/controllers/Admin.php`
- `application/controllers/Youngo_checkout_coupon_usage.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `scripts/phase_2/youngo_payment_checkout_coverage_fix_1_diagnostic.php`
- `docs/qa/youngo_payment_checkout_coupon_usage_500_fix_1_report.md`

## D. Fix Summary

Added a physical endpoint at:

- `admin/youngo/checkout-coupon-usage/index.php`

Apache serves that directory directly at:

- `/admin/youngo/checkout-coupon-usage/`

The exact URL without the trailing slash redirects by Apache directory handling to the slash URL, then renders `HTTP 200`.

The endpoint:

- reads the existing CodeIgniter database session cookie
- requires `admin_login = 1`
- requires root admin user ID `1`
- confirms the user row is active admin
- runs read-only `SELECT` queries only
- renders the coupon/zero-coupon checkout usage report with filters and null-safe fields

The original CodeIgniter controller/model/view implementation remains present, and the route was also pointed to `Admin::youngo_checkout_coupon_usage()` for use after a clean PHP/Apache restart.

## E. Admin Page Render Result

Authenticated root-admin HTTP checks:

- `/admin/youngo/checkout-coupon-usage` -> `HTTP 200` after Apache directory slash redirect
- `/admin/youngo/checkout-coupon-usage/?filter=zero_amount` -> `HTTP 200`
- `/admin/youngo/checkout-coupon-usage/?filter=course` -> `HTTP 200`
- `/admin/youngo/checkout-coupon-usage/?filter=subscription` -> `HTTP 200`

Verified visible title and filters:

- `Coupon Checkout Usage`
- `All coupon checkout usage`
- `Zero amount coupon only`
- `Course purchases`
- `Subscription purchases`

## F. Learner Denial Result

Unauthenticated HTTP checks:

- `/admin/youngo/checkout-coupon-usage` -> `301` to trailing slash
- `/admin/youngo/checkout-coupon-usage/` -> `302` to `/login`

The direct endpoint source also denies any authenticated non-root session with `HTTP 403` unless the live CI session contains `admin_login = 1` and user ID `1`.

## G. Report Columns Verified

The rendered report includes:

- order ID/reference
- user name/email
- item type/name
- original amount
- coupon code and usage ID
- discount amount
- final amount
- selected payment method
- payment gateway
- status
- access/subscription reference
- created/completed/coupon-used timestamps

## H. Diagnostic Result

Updated and passed:

```text
php scripts/phase_2/youngo_payment_checkout_coverage_fix_1_diagnostic.php
RESULT: PASS
```

The diagnostic now checks the physical fallback endpoint and verifies it is root-admin guarded and read-only.

Regression QA passed:

```text
php scripts/phase_2/youngo_payment_zero_amount_coupon_access_qa_1.php
RESULT: PASS

php scripts/phase_2/youngo_payment_manual_instapay_full_qa_1.php
RESULT: PASS
```

## I. Payment Safety Result

- Paymob remains disabled.
- Card and wallet payment remains disabled/not exposed.
- Instapay upload/approval logic was not changed.
- Zero-coupon access logic was not changed.
- Diagnostic reported protected DB counts unchanged.
- Fixture QA cleanup completed.

## J. Remaining Risks/Blockers

- The in-app browser connector was unavailable, so HTTP verification used `curl`.
- Apache currently serves `D:\Work\YounGo\school`, not the requested `school_prod_2` path.
- Apache could not be restarted from this shell due process permission denial, so the direct physical endpoint is the active live fix for the requested URL.
- After an Apache/PHP restart, the CodeIgniter route/controller fallback should also be re-tested and the physical endpoint can be retired if the normal route renders reliably.
