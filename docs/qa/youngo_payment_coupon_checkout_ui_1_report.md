# PAYMENT.COUPON.CHECKOUT.UI.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start worktree status: clean
- Latest commit at start: `b25c090 Wire coupon evaluator into checkout snapshot writes`
- Phase type: checkout coupon UI/actions only. No payment, Instapay, Paymob, access, or schema changes.

## B. Backup Created

The diagnostic inserts temporary checkout/coupon fixtures, so it creates a backup first.

- Backup path: `D:\Work\YounGo/backups/youngo_school_before_payment_coupon_checkout_ui_1_2026_07_26_132630.sql`
- Size: `1154849` bytes
- SHA256: `7129fde29e3b5eedc52785c37825a003011f30342290d28e52251e7cc0557ced`

## C. Files Inspected

- `docs/qa/youngo_payment_coupon_checkout_snapshot_write_1_report.md`
- `docs/qa/youngo_payment_coupon_evaluator_1_report.md`
- `docs/qa/youngo_payment_manual_instapay_coupon_checkout_plan_1_report.md`
- `application/controllers/Youngo_checkout.php`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_coupon_evaluator_model.php`
- `application/views/frontend/youngo/checkout_order.php`
- `application/config/routes.php`
- `scripts/phase_2/`

## D. Files Changed

- `application/controllers/Youngo_checkout.php`
- `application/views/frontend/youngo/checkout_order.php`
- `application/config/routes.php`
- `scripts/phase_2/youngo_payment_coupon_checkout_ui_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_coupon_checkout_snapshot_write_1_diagnostic.php`
- `docs/qa/youngo_payment_coupon_checkout_ui_1_report.md`

## E. Checkout UI Summary

The existing YounGo checkout order page is:

- route: `youngo/checkout/order/(:any)`
- controller: `Youngo_checkout::order()` through `render_existing_order()`
- view: `application/views/frontend/youngo/checkout_order.php`

The page now shows:

- coupon code input
- Apply button
- Clear coupon button when a coupon is applied
- original amount from `subtotal_amount`
- discount amount from `discount_amount`
- final amount from `total_amount`
- current coupon code/type/value when present
- zero-final policy warning when present

The UI uses `get_safe_order_review_snapshot()` data and does not use legacy cart/session coupon state.

## F. Controller/Action Summary

Added POST-only actions:

- `Youngo_checkout::apply_coupon($order_reference)`
- `Youngo_checkout::clear_coupon($order_reference)`

Routes:

- `youngo/checkout/coupon/apply/(:any)`
- `youngo/checkout/coupon/clear/(:any)`

Both actions:

- require POST
- require checkout local gates
- require a logged-in learner
- load and verify the owned order
- call only `Youngo_checkout_model` coupon snapshot methods
- redirect back to the checkout order page
- set safe flash success/error messages

## G. Coupon Apply Behavior

Apply uses `apply_coupon_snapshot_to_order()` and therefore:

- keeps `subtotal_amount` unchanged
- updates `total_amount` only through the evaluator snapshot result
- populates coupon ID/code/type/value and discount amount
- writes `checkout_snapshot_json`
- rejects invalid coupons without altering the order
- does not touch payment/provider/access fields

## H. Coupon Clear Behavior

Clear uses `clear_coupon_snapshot_from_order()` and therefore:

- restores `total_amount` from `subtotal_amount`
- resets `discount_amount` to `0.00`
- clears coupon snapshot fields
- clears `checkout_snapshot_json`
- leaves item title review data available
- does not touch payment/provider/access fields

## I. Payment Gate Safety

- Paymob defaults remain disabled.
- Existing checkout local gates remain in place.
- No Paymob redirect was newly enabled.
- No card or wallet payment is exposed.
- No Instapay submission/upload behavior was added.
- No paid status, payment row, entitlement, enrolment, course access, subscription, or manual grant behavior was added.

## J. Placeholder Payment Methods Decision

Added disabled checkout-page placeholders:

- Instapay: `Coming soon`
- Cards: `Not available yet`
- Digital Wallets: `Not available yet`

These are informational only. They are not links, do not submit forms, and do not call Paymob or any payment network.

## K. Diagnostic Result

PHP lint:

```text
php -l application/controllers/Youngo_checkout.php
php -l application/views/frontend/youngo/checkout_order.php
php -l scripts/phase_2/youngo_payment_coupon_checkout_ui_1_diagnostic.php
php -l scripts/phase_2/youngo_payment_coupon_checkout_snapshot_write_1_diagnostic.php
```

Result: `PASS`

UI diagnostic:

```text
php scripts/phase_2/youngo_payment_coupon_checkout_ui_1_diagnostic.php
```

Result: `PASS`

Snapshot write regression:

```text
php scripts/phase_2/youngo_payment_coupon_checkout_snapshot_write_1_diagnostic.php
```

Result: `PASS`

Verified:

- form targets exist
- controller actions are POST/model-backed
- view has coupon input/apply/clear/summary
- view does not use legacy `home/apply_coupon`, `applied_coupon`, `cart_items`, or `coupon_offer_100_percent`
- valid coupon applies through the model-backed path
- invalid coupon does not alter the order
- clear coupon restores total
- no Instapay submission table exists
- Paymob remains disabled
- payment/access counts remain unchanged

## L. DB Impact/Cleanup

The UI diagnostic temporarily inserted:

- one `youngo_checkout_orders` row
- one `coupons` row

All temporary rows were deleted.

Protected counts before and after matched:

- `coupons`: `0 -> 0`
- `youngo_coupon_courses`: `0 -> 0`
- `youngo_coupon_subscription_plans`: `0 -> 0`
- `youngo_coupon_usages`: `0 -> 0`
- `youngo_checkout_orders`: `0 -> 0`
- `youngo_payment_transactions`: `0 -> 0`
- `youngo_course_access`: `0 -> 0`
- `youngo_user_subscriptions`: `0 -> 0`
- `youngo_manual_grants`: `0 -> 0`
- `payment`: `0 -> 0`
- `enrol`: `1 -> 1`

## M. Remaining Risks/Blockers

- The coupon UI is still within the local-gated checkout surface; real payment flow remains unavailable.
- Instapay submission/upload is not implemented yet.
- Coupon usage is still not recorded; it should be recorded only after payment/admin approval.
- Admin review UI still needs to consume the checkout snapshot.
- Zero-final amount still does not grant access and needs an explicit later business policy.

## N. Recommended Next Phase

Recommended next phase: `PAYMENT.MANUAL.INSTAPAY.SCHEMA.1`

Manual Instapay submission/review schema should come before upload/admin approval UI.

## O. Git Status

Final status should show the changed controller, route, checkout view, diagnostics, and this report.
