# PAYMENT.CHECKOUT.COUPON.USAGE.ADMIN.LAYOUT.FIX.1 Report

## A. Result

PASS. `/admin/youngo/checkout-coupon-usage/` now renders through the real CodeIgniter backend layout instead of the standalone mini "YounGo Admin" shell.

## B. Folder Used

`D:\Work\YounGo\school`

## C. Exact Layout Problem Found

The URL with a trailing slash was being served by a physical fallback script at:

`admin/youngo/checkout-coupon-usage/index.php`

Because that physical directory existed, Apache served it before the normal CodeIgniter rewrite path. That fallback file contained a complete standalone HTML shell with its own "YounGo Admin" header/sidebar, so the page bypassed `application/views/backend/index.php` and did not show the normal backend header/sidebar used by `/admin/dashboard`.

After removing the fallback, the report initially hit a blank `HTTP 500`. The root cause was a read-only report query-builder condition in `Youngo_checkout_model::admin_coupon_checkout_usage_query()` that compiled an empty-string comparison into invalid SQL.

## D. Files Changed

- `application/config/routes.php`
- `application/models/Youngo_checkout_model.php`
- `scripts/phase_2/youngo_payment_checkout_coverage_fix_1_diagnostic.php`
- `docs/qa/youngo_payment_checkout_coupon_usage_admin_layout_fix_1_report.md`
- Removed `admin/youngo/checkout-coupon-usage/index.php` and its empty parent fallback directories.

Inspected and validated without additional source edits in this phase: `application/controllers/Youngo_checkout_coupon_usage.php`, `application/views/backend/admin/youngo_checkout_coupon_usage.php`, and `application/views/backend/admin/navigation.php`.

## E. Admin Layout Fix Summary

The route now targets the dedicated controller:

`admin/youngo/checkout-coupon-usage` -> `youngo_checkout_coupon_usage/index`

`Youngo_checkout_coupon_usage::index()` passes `page_name`, `page_title`, filter counts, and report rows to:

`application/views/backend/index.php`

The report view remains inner admin page content only. It does not contain a full document shell, custom topbar, custom sidebar, or standalone "YounGo Admin" wrapper.

The report query condition was corrected from a malformed builder empty-string comparison to a valid raw read-only condition:

`TRIM(COALESCE(o.coupon_code, '')) <> ''`

## F. Before / After Layout Result

Before:

- Standalone "YounGo Admin" shell.
- Custom mini sidebar.
- Dashboard button outside the real admin header.
- No normal backend top/header/sidebar/content container.

After:

- Uses `backend/index.php`.
- Has the same backend shell markers as `/admin/dashboard`.
- Shows the normal backend content container and real admin sidebar.
- Does not show `YounGo Admin`, `fallback-sidebar`, or `fallback-topbar`.

## G. Navigation Result

The existing YounGo navigation group remains in `application/views/backend/admin/navigation.php`.

Authenticated render checks confirmed:

- YounGo group appears.
- Coupon Checkout Usage link appears.
- The report page is included under the real backend sidebar context.

## H. Report Content Result

The report content is preserved and remains readable:

- Filters: All coupon checkout usage, Zero amount coupon only, Course purchases, Subscription purchases.
- Table groups: Order, User, Item, Coupon, Amounts, Method / Gateway, Access, Status, Dates.
- Empty state remains clean.
- Payment Settings link remains in the page header as a normal admin button.
- Zero amount filter returned `HTTP 200`, active filter state, and rows/empty-state markup.

## I. Access Control Result

Authenticated root-admin HTTP checks confirmed `/admin/dashboard` and `/admin/youngo/checkout-coupon-usage/` share the same admin shell markers and return `HTTP 200`.

Learner and guest checks confirmed:

- Learner does not receive the report/admin shell.
- Guest redirects to login through existing admin behavior.
- Temporary learner session used for the check was deleted immediately.

## J. Payment Safety Result

No payment execution logic was changed. No Paymob enablement, card/wallet exposure, Instapay approval/access behavior, zero-coupon completion behavior, entitlement issuance behavior, coupon usage creation behavior, or schema was changed.

Checks run:

- `php -l application\config\routes.php` PASS
- `php -l application\controllers\Youngo_checkout_coupon_usage.php` PASS
- `php -l application\models\Youngo_checkout_model.php` PASS
- `php -l application\views\backend\admin\youngo_checkout_coupon_usage.php` PASS
- `php -l scripts\phase_2\youngo_payment_checkout_coverage_fix_1_diagnostic.php` PASS
- `php scripts\phase_2\youngo_payment_checkout_coverage_fix_1_diagnostic.php` PASS
- `php scripts\phase_2\youngo_payment_zero_amount_coupon_access_qa_1.php` PASS
- `php scripts\phase_2\youngo_payment_manual_instapay_full_qa_1.php` PASS

The runtime QA scripts cleaned up temporary rows and reported no persistent payment/access/enrolment drift.

## K. Remaining Issues

The in-app browser connector exposed no browser instances, so screenshot-level browser automation could not be performed. Verification used authenticated HTTP render checks against the running local site instead.

The local worktree contains many unrelated existing changes outside this phase; they were not touched.

## L. Next Step

Use a real authenticated admin browser session for final visual confirmation at desktop and narrow viewport widths.
