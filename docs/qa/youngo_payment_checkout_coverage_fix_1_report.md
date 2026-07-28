# PAYMENT.CHECKOUT.COVERAGE.FIX.1

Audit/fix date: 2026-07-27

Scope: add admin visibility for coupon-backed checkout completions, expose existing direct course checkout CTAs on public course card surfaces, and preserve current payment safety.

## A. Current Branch/Status

- Active environment tree: `D:\Work\YounGo\school_prod_2`
- Git note: `school_prod_2` has no `.git` metadata. The companion Git checkout inspected for branch/status is `D:\Work\YounGo\school`.
- Branch in companion Git checkout: `analysis/cms-audit`
- Start worktree in companion Git checkout: already dirty with many pre-existing modified/untracked files from earlier phases.
- Deployment: not performed.
- Push: not performed.
- Browser note: the in-app browser connector was unavailable in this session (`iab` list was empty), so browser UI inspection was not possible. Validation used PHP/source/model QA.

## B. Backup Created

The new `PAYMENT.CHECKOUT.COVERAGE.FIX.1` diagnostic is read-only and created no backup.

Regression QA scripts inserted temporary fixtures and created backups before cleanup:

- `D:\Work\YounGo/backups/youngo_school_before_payment_zero_amount_coupon_access_qa_1_2026_07_27_185215.sql`
  - Size: `1450385`
  - SHA256: `de76ef70912bf0c465b19516444d87499f1a001d3a69d14292862937d3b2089a`
- `D:\Work\YounGo/backups/youngo_school_before_payment_manual_instapay_full_qa_1_2026_07_27_185329.sql`
  - Size: `1450380`
  - SHA256: `b99c0711595784764e92192f9d429fd0696b69e96ec14ec9bb8395418843ea79`

Earlier failed validation attempts also created backups and cleaned up temporary rows:

- `D:\Work\YounGo/backups/youngo_school_before_payment_zero_amount_coupon_access_qa_1_2026_07_27_184255.sql`
- `D:\Work\YounGo/backups/youngo_school_before_payment_zero_amount_coupon_access_qa_1_2026_07_27_184537.sql`
- `D:\Work\YounGo/backups/youngo_school_before_payment_zero_amount_coupon_access_qa_1_2026_07_27_184613.sql`
- `D:\Work\YounGo/backups/youngo_school_before_payment_manual_instapay_full_qa_1_2026_07_27_184636.sql`
- `D:\Work\YounGo/backups/youngo_school_before_payment_manual_instapay_full_qa_1_2026_07_27_185218.sql`

## C. Files Inspected

- `application/config/routes.php`
- `application/controllers/Youngo_checkout.php`
- `application/controllers/Youngo_instapay_payments.php`
- `application/controllers/Youngo_payment_webhook.php`
- `application/controllers/Youngo_payment_return.php`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_coupon_evaluator_model.php`
- `application/models/Youngo_entitlement_write_model.php`
- `application/models/Youngo_instapay_payment_model.php`
- `application/views/backend/admin/navigation.php`
- `application/views/backend/admin/youngo_instapay_payments.php`
- `application/views/backend/admin/youngo_instapay_payment_view.php`
- `application/views/frontend/youngo/checkout_order.php`
- `application/views/frontend/youngo/course_listing/course_card.php`
- `application/views/frontend/youngo/home_sections/featured_courses.php`
- `application/views/frontend/youngo/my_wishlist.php`
- `application/views/frontend/youngo/subscriptions.php`
- `application/views/frontend/youngo/wishlist_items.php`
- `application/helpers/common_helper.php`
- relevant scripts under `scripts/phase_2/`

## D. Files Changed

- `application/config/routes.php`
- `application/controllers/Youngo_checkout_coupon_usage.php`
- `application/models/Youngo_checkout_model.php`
- `application/views/backend/admin/navigation.php`
- `application/views/backend/admin/youngo_checkout_coupon_usage.php`
- `application/views/frontend/youngo/checkout_order.php`
- `application/views/frontend/youngo/course_listing/course_card.php`
- `application/views/frontend/youngo/home_sections/featured_courses.php`
- `application/views/frontend/youngo/my_wishlist.php`
- `application/views/frontend/youngo/subscriptions.php`
- `application/views/frontend/youngo/wishlist_items.php`
- `application/helpers/common_helper.php`
- `scripts/phase_2/youngo_payment_checkout_coverage_fix_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_zero_amount_coupon_access_qa_1.php`
- `scripts/phase_2/youngo_payment_manual_instapay_full_qa_1.php`
- `docs/qa/youngo_payment_checkout_coverage_fix_1_report.md`

## E. Admin Coupon/Zero-Coupon Report Summary

Added read-only route:

- `admin/youngo/checkout-coupon-usage`
- Controller: `Youngo_checkout_coupon_usage::index()`
- View: `backend/admin/youngo_checkout_coupon_usage.php`

The report shows checkout order ID/reference, user, item type/name, original amount, coupon code, discount, final amount, selected payment method, payment gateway, entitlement/access reference, created/completed/paid time, coupon usage time, and status.

Filters:

- All coupon checkout usage
- Zero amount coupon only
- Course purchases
- Subscription purchases

## F. Admin Visibility Result

Root admins now have a dedicated YounGo navigation link: `Coupon Checkout Usage`.

The page exposes the existing source-of-truth joins across:

- `youngo_checkout_orders`
- `youngo_coupon_usages`
- `users`
- `course`
- `youngo_subscription_plans`
- `youngo_course_access`
- `youngo_user_subscriptions`

## G. Course Listing CTA Fix

`course_listing/course_card.php` now loads and uses `youngo_checkout_cta_decision()`.

For purchase-capable courses, direct checkout is shown only when the helper returns `show_cta`. Subscription-only courses keep the subscription plan CTA.

## H. Wishlist CTA Fix

Both wishlist surfaces now use the same helper:

- `my_wishlist.php`
- `wishlist_items.php`

They keep course details visible, show direct checkout when allowed, and show subscription plans only for `subscription_only` or `subscription_and_purchase`.

## I. Homepage/Featured Course CTA Result

`youngo_homepage_resolve_featured_courses()` now passes the checkout eligibility fields needed by the helper:

- `status`
- `youngo_access_mode`
- `is_free_course`
- `price`
- `discount_flag`
- `discounted_price`

`home_sections/featured_courses.php` uses the helper and changes the small card action to direct checkout only when allowed.

## J. Subscription Flow Preservation

Subscription plan checkout remains linked from `subscriptions.php` through:

- `youngo/checkout/subscription/start/{plan_id}`

No subscription CTA was removed.

## K. 100% Coupon Tracking Visibility

100% coupon users were already tracked in DB. This phase exposes them in admin by joining checkout orders, coupon usages, and entitlement rows.

Live diagnostic counts from `school_prod_2`:

- Coupon checkout usage rows: `1`
- Zero-amount coupon usage rows: `1`
- Coupon usage rows linked to checkout orders: `1`
- Course access rows linked to checkout orders: `0`
- Subscription rows linked to checkout orders: `4`

## L. Payment Safety Result

- Paymob runtime execution remains disabled.
- Paymob network execution remains disabled.
- Paymob sandbox network testing remains disabled.
- Webhook/return controller sources remain no-write/fail-closed.
- Card and Digital Wallet placeholders are rendered as disabled/non-actionable.
- Instapay upload still creates `pending_review`.
- Instapay approval remains pending-review gated and entitlement-backed.
- Zero-amount coupon completion stays separate from Instapay.

## M. Diagnostic Result

Created:

- `scripts/phase_2/youngo_payment_checkout_coverage_fix_1_diagnostic.php`

Validation:

```text
php -l scripts/phase_2/youngo_payment_checkout_coverage_fix_1_diagnostic.php
No syntax errors detected

php scripts/phase_2/youngo_payment_checkout_coverage_fix_1_diagnostic.php
RESULT: PASS
```

The diagnostic made no DB writes; protected counts before/after matched.

## N. DB/Filesystem Cleanup

The new diagnostic made no writes.

Regression QA cleanup passed:

- Zero-coupon QA restored protected counts after temporary checkout/coupon/course/subscription fixture rows.
- Manual Instapay full QA restored protected counts after temporary checkout/submission/access/config/evidence fixtures.

## O. Remaining Risks/Blockers

- In-app browser testing was blocked because no in-app browser session was available.
- Paymob subscription entitlement remains outside this phase.
- Public direct course checkout visibility still depends on local checkout gates and `checkout_cta_enabled`; the live local summary currently has Paymob execution disabled and checkout CTA disabled.

## P. Recommended Next Phase

`PAYMENT.CHECKOUT.COVERAGE.BROWSER.QA.1`

Recommended scope:

- authenticated learner browser QA for course listing, wishlist, featured cards, course detail, subscription plans, coupon apply/clear, zero-coupon completion, and Instapay upload
- authenticated root-admin browser QA for `admin/youngo/checkout-coupon-usage` and `admin/youngo/instapay-payments`
- verify visual layout and table overflow on desktop/mobile

## Q. Git Status

`school_prod_2` has no `.git` metadata, so `git status` and `git diff --check` cannot run there.

The companion Git checkout at `D:\Work\YounGo\school` is on `analysis/cms-audit` and remains dirty with many unrelated pre-existing modified/untracked files. The scoped phase files are listed in section D; unrelated dirty files were not intentionally changed for this phase.
