# PAYMENT.COUPON.EVALUATOR.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start worktree status: clean
- Latest commit at start: `0fd7dc1 Add coupon checkout snapshot schema support`
- Phase type: read-only evaluator model plus fixture-cleaning diagnostic. No checkout wiring.

## B. Backup Created Or No-Backup Rationale

Local coupon tables were empty, so reliable evaluator diagnostics required temporary coupon fixtures.

- Backup path: `D:/Work/YounGo/backups/youngo_school_before_payment_coupon_evaluator_1_2026_07_26_125332.sql`
- Size: `1154767` bytes
- SHA256: `d364f45ef2ba5e5184616b7e0b23615daf819244df80a7d230cfb3c1760a608e`

## C. Files Inspected

- `docs/qa/youngo_payment_coupon_checkout_snapshot_schema_1_report.md`
- `docs/qa/youngo_payment_coupon_checkout_snapshot_audit_1_report.md`
- `docs/qa/youngo_payment_manual_instapay_coupon_checkout_plan_1_report.md`
- `application/models/Crud_model.php`
- `application/models/Payment_model.php`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_payment_model.php`
- `application/controllers/Home.php`
- `application/controllers/Payment.php`
- `application/controllers/Admin.php`
- `application/views/backend/admin/coupon_add.php`
- `application/views/backend/admin/coupon_edit.php`
- `application/views/backend/admin/coupons.php`
- `application/config/youngo_paymob.php`
- `application/config/routes.php`
- `database/phase_2/youngo_phase_2e_schema_up.sql`
- `scripts/phase_2/`

## D. Files Changed

- `application/models/Youngo_coupon_evaluator_model.php`
- `scripts/phase_2/youngo_payment_coupon_evaluator_1_diagnostic.php`
- `docs/qa/youngo_payment_coupon_evaluator_1_report.md`

## E. Coupon Data Shape Confirmed

Local `coupons` table fields:

- `id` `bigint(20) unsigned`
- `code` `varchar(255)`
- `discount_percentage` `varchar(255)`
- `created_at` `int(11)`
- `expiry_date` `int(11)`
- `discount_type` `varchar(50)` default `percentage`
- `discount_value` `decimal(10,2)`
- `scope` `varchar(50)` default `both`
- `max_usage_count` `int(11)`
- `status` `varchar(50)` default `active`
- `updated_at` `int(11) unsigned`

Phase 2 coupon tables present locally:

- `youngo_coupon_courses`: `id`, `coupon_id`, `course_id`, `rule_type`
- `youngo_coupon_subscription_plans`: `id`, `coupon_id`, `plan_id`
- `youngo_coupon_usages`: `id`, `coupon_id`, `coupon_code`, `user_id`, `checkout_order_id`, `payment_id`, `discount_amount`, `used_at`

Current local coupon-related row counts at baseline were all `0`.

## F. Evaluator Behavior Summary

Added `Youngo_coupon_evaluator_model::evaluate_coupon_for_checkout($user_id, $item_type, $item_id, $original_amount, $coupon_code, $currency)`.

Behavior:

- accepts `course` and `subscription`
- requires EGP
- requires valid user and item rows
- trims/normalizes coupon codes to uppercase without whitespace
- reads `coupons` only by code
- enforces `status = active` when the field exists
- enforces non-expired `expiry_date` when the field exists
- enforces Phase 2 `scope`
- enforces course include/exclude rows when configured
- enforces subscription plan include rows when configured
- enforces `max_usage_count` using `youngo_coupon_usages` when configured
- calculates deterministic discount/final amount
- clamps discount to the original amount
- never writes DB rows
- never reads/writes cart or session
- never grants access, creates payments, creates orders, or changes checkout behavior

## G. Supported Coupon Types

Supported now:

- `percentage` through Phase 2 `discount_type = percentage` and `discount_value`
- legacy percentage fallback through `discount_percentage` when `discount_value` is missing
- `fixed` through Phase 2 `discount_type = fixed` and `discount_value`

Unsupported/deferred:

- user-specific restrictions, because no safe local user restriction schema exists
- minimum/maximum order amount rules, because no local fields exist
- per-user usage limits, because no local per-user limit field exists
- consuming coupon usage at evaluation time
- auto-free checkout/access behavior for zero-final-amount coupons

## H. Unsupported/Deferred Coupon Rules

The evaluator does not rely on legacy `Crud_model::get_discounted_price_after_applying_coupon()`, `Home::apply_coupon()`, cart fragments, `applied_coupon`, `cart_items`, or `Payment_model::configure_course_payment()`.

Usage accounting should be written later only at approval/payment completion, not during evaluator preview.

## I. Zero-Final-Amount Policy Handling

If a coupon reduces the final amount to `0.00`, the evaluator may still return the coupon as valid, but it sets:

- `zero_final_amount_policy_not_enabled = true`
- `snapshot.zero_final_amount_policy_not_enabled = true`

This explicitly prevents interpreting a zero final amount as free access. Later checkout/admin approval phases must decide policy before issuing access.

## J. Snapshot Output Shape

Evaluator output includes:

- `valid`
- `coupon_id`
- `coupon_code`
- `discount_type`
- `discount_value`
- `discount_amount`
- `final_amount`
- `currency`
- `message`
- `error_code`
- `zero_final_amount_policy_not_enabled`
- `snapshot`

Snapshot includes:

- `evaluator_version`
- `valid`
- `error_code`
- `message`
- `coupon_id`
- `coupon_code`
- `item_type`
- `item_id`
- `original_amount`
- `discount_type`
- `discount_value`
- `discount_amount`
- `final_amount`
- `currency`
- `scope`
- `usage`
- `calculation.formula`
- `calculation.discount_clamped`
- `zero_final_amount_policy_not_enabled`

## K. Diagnostic Result

PHP lint:

```text
php -l application/models/Youngo_coupon_evaluator_model.php
php -l scripts/phase_2/youngo_payment_coupon_evaluator_1_diagnostic.php
```

Result: `PASS`

Command:

```text
php scripts/phase_2/youngo_payment_coupon_evaluator_1_diagnostic.php
```

Result: `PASS`

Regression diagnostic:

```text
php scripts/phase_2/youngo_payment_coupon_checkout_snapshot_schema_1_diagnostic.php
```

Result: `PASS`

Whitespace validation:

```text
git diff --check
```

Result: `PASS`

Verified:

- evaluator model loads
- evaluator has no session/cart dependency
- evaluator has no DB write calls
- valid percentage coupon calculation works
- missing coupon returns invalid safely
- expired coupon returns invalid
- inactive coupon returns invalid
- fixed over-discount clamps to original amount
- zero-final-amount policy flag is set
- usage limit returns invalid when reached
- course include scope works for matching course
- subscription fixed coupon works for matching plan
- non-EGP currency is rejected
- session stub was not mutated
- no checkout order mutation
- no payment/Paymob activation
- no entitlement/access changes

## L. DB Impact/Cleanup

The diagnostic inserted temporary rows only in:

- `coupons`
- `youngo_coupon_usages`
- `youngo_coupon_courses`
- `youngo_coupon_subscription_plans`

All temporary rows used a `YCEVAL1_*` prefix or captured IDs and were deleted by the diagnostic.

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

## M. Checkout/Payment Behavior Safety

- `Youngo_checkout` controller was not modified.
- `Youngo_payment_model` was not modified.
- Routes were not modified.
- Checkout UI was not modified.
- Paymob config remains disabled.
- No coupons are applied to real checkout yet.
- No Instapay submission table or workflow was created.
- No access, enrolment, subscription, payment, or checkout order behavior was changed.

## N. Remaining Risks/Blockers

- Evaluator is not wired into checkout yet.
- Checkout UI still needs coupon entry and preview behavior.
- Checkout order creation still needs an explicit snapshot write phase.
- Coupon usage recording policy must be implemented later at approval/payment completion.
- Admin review must use snapshotted coupon data, not recalculate discounts at approval time.
- Zero-final-amount policy needs an explicit business decision before any checkout/access path can act on it.

## O. Recommended Next Phase

Recommended next phase: `PAYMENT.COUPON.CHECKOUT.UI.1` or `PAYMENT.COUPON.CHECKOUT.SNAPSHOT.WRITE.1`

If the next phase writes snapshot data, keep it local-gated and continue avoiding access/payment side effects.

## P. Git Status

Final status:

```text
?? application/models/Youngo_coupon_evaluator_model.php
?? docs/qa/youngo_payment_coupon_evaluator_1_report.md
?? scripts/phase_2/youngo_payment_coupon_evaluator_1_diagnostic.php
```
