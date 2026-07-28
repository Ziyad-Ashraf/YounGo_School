# PAYMENT.COUPON.CHECKOUT.SNAPSHOT.SCHEMA.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start status: clean worktree.
- Latest commit at start: `bab2c27 Audit coupon checkout snapshot requirements`
- Phase type: additive schema support and planning-safe helper surface only.

## B. Backup Created

- Path: `D:/Work/YounGo/backups/youngo_school_before_payment_coupon_checkout_snapshot_schema_1_2026_07_26_124731.sql`
- Size: `1154505` bytes
- SHA256: `604778a261eeb83d37e4d5f572338ae8bae66f3e3eff5fe977caa5dd8f18dfd4`

## C. Files Inspected

- `docs/qa/youngo_payment_coupon_checkout_snapshot_audit_1_report.md`
- `docs/qa/youngo_payment_manual_instapay_coupon_checkout_plan_1_report.md`
- `docs/qa/youngo_payment_checkout_local_ui_qa_3_report.md`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_payment_model.php`
- `application/controllers/Youngo_checkout.php`
- `application/models/Youngo_entitlement_write_model.php`
- `application/config/youngo_paymob.php`
- `application/config/routes.php`
- `scripts/phase_2/`

## D. Files Changed

- `application/models/Youngo_checkout_model.php`
- `scripts/phase_2/payment_coupon_checkout_snapshot_schema_1_up.sql`
- `scripts/phase_2/payment_coupon_checkout_snapshot_schema_1_down.sql`
- `scripts/phase_2/youngo_payment_coupon_checkout_snapshot_schema_1_diagnostic.php`
- `docs/qa/youngo_payment_coupon_checkout_snapshot_schema_1_report.md`

## E. Schema Summary

Applied additive nullable columns to `youngo_checkout_orders`:

- `coupon_discount_type` `VARCHAR(50) NULL`
- `coupon_discount_value` `DECIMAL(10,2) NULL`
- `selected_payment_method` `VARCHAR(50) NULL`
- `item_title_snapshot` `VARCHAR(255) NULL`
- `checkout_snapshot_json` `LONGTEXT NULL`

No existing fields were dropped or modified. `subtotal_amount` remains the original amount, `discount_amount` remains the discount amount, `total_amount` remains the final required amount, and `currency` remains `EGP`.

## F. Model Helper Summary

Added helper-only methods to `Youngo_checkout_model`:

- `checkout_snapshot_fields_available()` checks whether the new snapshot fields are present.
- `normalize_selected_payment_method($method)` normalizes safe future method keys such as `instapay_manual`, `card_disabled`, and `wallet_disabled`.
- `prepare_coupon_snapshot_fields($coupon_snapshot)` sanitizes supplied coupon snapshot fields without validating or applying a coupon.
- `build_checkout_snapshot_array(...)` builds a deterministic checkout snapshot array from already-supplied order/item/coupon data.

Existing `create_draft_order()` behavior was not wired to coupons, selected payment methods, Instapay, card/wallet payment, or access issuance.

## G. Diagnostic Result

PHP lint:

```text
php -l application/models/Youngo_checkout_model.php
php -l scripts/phase_2/youngo_payment_coupon_checkout_snapshot_schema_1_diagnostic.php
```

Result: `PASS`

Command:

```text
php scripts/phase_2/youngo_payment_coupon_checkout_snapshot_schema_1_diagnostic.php
```

Result: `PASS`

Whitespace validation:

```text
git diff --check
```

Result: `PASS` with Git's existing Windows line-ending warning for `application/models/Youngo_checkout_model.php`.

Verified:

- new snapshot columns exist and are nullable
- existing amount/currency/coupon/metadata/entitlement columns remain
- model helpers load and normalize expected future-safe method values
- one temporary checkout order insert/read/delete succeeded
- temporary row cleanup completed
- protected counts restored after diagnostic
- no `youngo_instapay_payment_submissions` table exists yet
- Paymob default gates remain disabled
- no checkout route/controller Instapay or coupon behavior was added
- entitlement/access/payment/coupon usage counts remained unchanged

## H. DB Impact/Cleanup

- Required additive schema was applied locally.
- Diagnostic temporarily inserted one `youngo_checkout_orders` row and deleted it.
- Protected counts before and after diagnostic matched:
  - `youngo_checkout_orders`: `0 -> 0`
  - `youngo_payment_transactions`: `0 -> 0`
  - `youngo_course_access`: `0 -> 0`
  - `youngo_user_subscriptions`: `0 -> 0`
  - `youngo_manual_grants`: `0 -> 0`
  - `youngo_coupon_usages`: `0 -> 0`
  - `payment`: `0 -> 0`
  - `enrol`: `1 -> 1`

## I. Checkout/Payment Behavior Safety

- No checkout routes were added.
- `Youngo_checkout` controller was not modified.
- `Youngo_payment_model` was not modified.
- `Youngo_entitlement_write_model` was not modified.
- Paymob config remains disabled by default.
- No coupons are applied in checkout yet.
- No Instapay submission table, upload flow, admin approval flow, or access issuance behavior was added.
- Cards and wallet payment behavior remain unavailable.

## J. Remaining Risks/Blockers

- The dedicated YounGo coupon evaluator is still required before checkout can calculate coupon discounts.
- Checkout UI has not been wired to coupon entry or payment method selection.
- Admin review and Instapay submission schemas are still pending.
- Approval-time access issuance must be implemented later through the existing entitlement/subscription foundation with duplicate issuance protection.
- Snapshot JSON shape should be treated as append-only/auditable once checkout starts writing real orders.

## K. Recommended Next Phase

Recommended next phase: `PAYMENT.COUPON.EVALUATOR.1`

Then continue with:

- `PAYMENT.COUPON.CHECKOUT.UI.1`
- `PAYMENT.MANUAL.INSTAPAY.SCHEMA.1`
- `PAYMENT.MANUAL.INSTAPAY.CHECKOUT.UI.1`
- `PAYMENT.MANUAL.INSTAPAY.SUBMISSION.UPLOAD.1`
- `PAYMENT.MANUAL.INSTAPAY.ADMIN.REVIEW.UI.1`
- `PAYMENT.MANUAL.INSTAPAY.APPROVAL.ACCESS.1`
- `PAYMENT.MANUAL.INSTAPAY.USER.STATUS.UI.1`

## L. Git Status

Final status:

```text
 M application/models/Youngo_checkout_model.php
?? docs/qa/youngo_payment_coupon_checkout_snapshot_schema_1_report.md
?? scripts/phase_2/payment_coupon_checkout_snapshot_schema_1_down.sql
?? scripts/phase_2/payment_coupon_checkout_snapshot_schema_1_up.sql
?? scripts/phase_2/youngo_payment_coupon_checkout_snapshot_schema_1_diagnostic.php
```
