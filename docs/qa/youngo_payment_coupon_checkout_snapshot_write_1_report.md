# PAYMENT.COUPON.CHECKOUT.SNAPSHOT.WRITE.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start worktree status: clean
- Latest commit at start: `34c95ab Add read-only YounGo coupon evaluator`
- Phase type: model-level coupon snapshot write support only. No public UI, route, payment, Instapay, or access behavior changes.

## B. Backup Created

The diagnostic inserts temporary checkout/coupon fixtures, so it creates a backup before those writes.

- Backup path: `D:\Work\YounGo/backups/youngo_school_before_payment_coupon_checkout_snapshot_write_1_2026_07_26_130738.sql`
- Size: `1154861` bytes
- SHA256: `2715a049a2601050793d10091163c2ed627d766d6793f95d02c02a8479a7173d`

## C. Files Inspected

- `docs/qa/youngo_payment_coupon_evaluator_1_report.md`
- `docs/qa/youngo_payment_coupon_checkout_snapshot_schema_1_report.md`
- `docs/qa/youngo_payment_manual_instapay_coupon_checkout_plan_1_report.md`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_coupon_evaluator_model.php`
- `application/controllers/Youngo_checkout.php`
- `application/config/youngo_paymob.php`
- `application/config/routes.php`
- `scripts/phase_2/`

## D. Files Changed

- `application/models/Youngo_checkout_model.php`
- `scripts/phase_2/youngo_payment_coupon_checkout_snapshot_write_1_diagnostic.php`
- `docs/qa/youngo_payment_coupon_checkout_snapshot_write_1_report.md`

## E. Methods Added

Added to `Youngo_checkout_model`:

- `apply_coupon_snapshot_to_order($order_id, $user_id, $coupon_code)`
- `clear_coupon_snapshot_from_order($order_id, $user_id)`
- `get_safe_order_review_snapshot($order_id, $user_id = null)`

Internal helpers added for owner validation, draft-safe edit checks, item title resolution, evaluator loading, snapshot JSON building, amount cents conversion, and safe review snapshot formatting.

## F. Coupon Snapshot Write Behavior

`apply_coupon_snapshot_to_order()`:

- loads the checkout order
- verifies owner when `user_id` is provided
- only allows draft/unpaid orders with no payment/provider/entitlement activity
- uses `subtotal_amount` as the original amount
- calls `Youngo_coupon_evaluator_model::evaluate_coupon_for_checkout()`
- leaves the order unchanged when the coupon is invalid
- updates only coupon/amount/snapshot fields when valid:
  - `coupon_id`
  - `coupon_code`
  - `discount_amount`
  - `coupon_discount_type`
  - `coupon_discount_value`
  - `total_amount`
  - `total_amount_cents`
  - `currency`
  - `item_title_snapshot`
  - `checkout_snapshot_json`
  - `updated_at`

It does not mark the order paid, start a provider flow, call Paymob, create a payment transaction, or issue access.

## G. Clear Coupon Behavior

`clear_coupon_snapshot_from_order()`:

- verifies the same owner and draft-safe rules
- restores `total_amount` from `subtotal_amount`
- restores `total_amount_cents` from `subtotal_amount`
- sets `discount_amount` to `0.00`
- clears `coupon_id`, `coupon_code`, `coupon_discount_type`, `coupon_discount_value`, and `checkout_snapshot_json`
- preserves/resolves `item_title_snapshot` because it is item review data, not a coupon discount

## H. Safe Order Review Snapshot Behavior

`get_safe_order_review_snapshot()` returns a structured result containing:

- order identity/status/user
- item type/id/title snapshot
- original amount from `subtotal_amount`
- coupon code/type/value
- discount amount
- final amount from `total_amount`
- currency
- selected payment method
- payment gateway summary
- entitlement summary
- zero-final-amount policy flag
- decoded `checkout_snapshot_json`

This is intended for later admin review/UI use and does not recalculate coupons.

## I. Zero-Final-Amount Handling

Zero-final-amount coupons can be snapshotted, but they do not grant access. The evaluator policy flag is preserved in `checkout_snapshot_json` and surfaced by `get_safe_order_review_snapshot()` as:

- `zero_final_amount_policy_not_enabled = true`

The order remains draft and `entitlement_issued` remains `0`.

## J. Diagnostic Result

PHP lint:

```text
php -l application/models/Youngo_checkout_model.php
php -l scripts/phase_2/youngo_payment_coupon_checkout_snapshot_write_1_diagnostic.php
```

Result: `PASS`

Write diagnostic:

```text
php scripts/phase_2/youngo_payment_coupon_checkout_snapshot_write_1_diagnostic.php
```

Result: `PASS`

Evaluator regression:

```text
php scripts/phase_2/youngo_payment_coupon_evaluator_1_diagnostic.php
```

Result: `PASS`

Verified:

- valid coupon snapshot updates final total
- `subtotal_amount` stays unchanged
- coupon fields populate
- `checkout_snapshot_json` contains original/final/coupon data
- invalid coupon leaves order unchanged
- clearing restores final total and clears coupon fields
- zero-final coupons do not issue access
- safe review snapshot exposes admin review fields
- temporary rows are deleted
- protected counts are restored
- Paymob remains disabled

## K. DB Impact/Cleanup

The diagnostic temporarily inserted:

- one `youngo_checkout_orders` row
- two `coupons` rows

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

## L. Checkout/Payment/Access Safety

- No checkout controller changes.
- No routes added.
- No public checkout coupon UI added.
- No Instapay submissions created.
- No Paymob activation.
- No card/wallet exposure.
- No payment rows created.
- No entitlement, enrolment, course access, subscription, or manual grant behavior changed.
- No legacy cart/session coupon mutation is used.

## M. Remaining Risks/Blockers

- The methods are not exposed through UI or routes yet.
- Subscription order starts are still not implemented; subscription snapshot support is limited to existing `plan_id` orders.
- Coupon usage is not recorded yet; it should be recorded only after payment/admin approval.
- Admin review UI still needs to consume the review snapshot.
- Zero-final-amount business policy remains explicitly disabled for access issuance.

## N. Recommended Next Phase

Recommended next phase: `PAYMENT.COUPON.CHECKOUT.UI.1`

Keep the UI local-gated/disabled from real payments until manual Instapay submission and admin review phases are ready.

## O. Git Status

Final status should show:

```text
 M application/models/Youngo_checkout_model.php
?? docs/qa/youngo_payment_coupon_checkout_snapshot_write_1_report.md
?? scripts/phase_2/youngo_payment_coupon_checkout_snapshot_write_1_diagnostic.php
```
