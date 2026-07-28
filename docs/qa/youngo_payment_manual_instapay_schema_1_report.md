# PAYMENT.MANUAL.INSTAPAY.SCHEMA.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start worktree status: clean
- Latest commit at start: `dca943a Add coupon apply clear checkout UI`
- Phase type: additive manual Instapay submission schema and inert model foundation only. No upload UI, admin review UI, approval, Paymob activation, card/wallet exposure, payment approval, or access issuance.

## B. Backup Created

Backup created before applying the schema:

- Backup path: `D:\Work\YounGo/backups/youngo_school_before_payment_manual_instapay_schema_1_2026_07_26_134012.sql`
- Size: `1154843` bytes
- SHA256: `37d6772c78519fe6f32d98c6b9316472c3b339926f6533fa04e111c16749c1d6`

## C. Files Inspected

- `docs/qa/youngo_payment_coupon_checkout_ui_1_report.md`
- `docs/qa/youngo_payment_coupon_checkout_snapshot_write_1_report.md`
- `docs/qa/youngo_payment_manual_instapay_coupon_checkout_plan_1_report.md`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_coupon_evaluator_model.php`
- `application/models/Youngo_entitlement_write_model.php`
- `application/controllers/Youngo_checkout.php`
- `application/views/frontend/youngo/checkout_order.php`
- `application/config/routes.php`
- `application/config/youngo_paymob.php`
- `scripts/phase_2/`

## D. Files Changed

- `application/models/Youngo_instapay_payment_model.php`
- `scripts/phase_2/payment_manual_instapay_schema_1_up.sql`
- `scripts/phase_2/payment_manual_instapay_schema_1_down.sql`
- `scripts/phase_2/youngo_payment_manual_instapay_schema_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_coupon_checkout_ui_1_diagnostic.php`
- `docs/qa/youngo_payment_manual_instapay_schema_1_report.md`

`youngo_payment_coupon_checkout_ui_1_diagnostic.php` was updated only so the prior UI regression accepts that the Instapay schema now exists while still verifying no UI, route, screenshot upload, or controller behavior uses it.

## E. Schema Summary

Added table:

- `youngo_instapay_payment_submissions`

Fields:

- identity/linkage: `id`, `order_id`, `user_id`
- review state: `status`
- amounts: `expected_amount`, `submitted_amount`, `currency`
- Instapay target snapshot: `instapay_target_label`, `instapay_target_address`, `instapay_target_link`
- screenshot metadata: `screenshot_path`, `screenshot_original_name`, `screenshot_mime`, `screenshot_size`
- notes/reference: `transaction_reference`, `user_note`, `admin_note`
- review audit: `reviewed_by_user_id`, `reviewed_at`, `approved_at`, `rejected_at`
- future access audit linkage: `access_issued`, `access_issued_at`, `access_reference_type`, `access_reference_id`
- structured evidence: `snapshot_json`
- timestamps: `created_at`, `updated_at`

Indexes:

- `idx_yips_order_id`
- `idx_yips_user_id`
- `idx_yips_status`
- `idx_yips_reviewed_by`
- `idx_yips_created_at`

The up SQL creates only this table. The down SQL drops only this table.

## F. Status Model

Approved Instapay review statuses are exactly:

- `pending_review`
- `approved`
- `rejected`

The table default is `pending_review`, with the allowed values documented in the column comment. The model exposes `allowed_statuses()` and `normalize_status()` and rejects non-review values such as checkout/order lifecycle states.

## G. Model Foundation Summary

Added `Youngo_instapay_payment_model` with inert helper/read methods:

- `table_exists()`
- `allowed_statuses()`
- `normalize_status($status)`
- `build_submission_snapshot($order_review_snapshot, $extra = array())`
- `get_submission_by_id($id)`
- `get_submissions_for_order($order_id)`
- `can_create_submission_for_order($order)`
- `sanitize_expected_amount($amount)`

The model does not expose create/approve/reject methods and does not call entitlement, subscription, enrolment, payment, Paymob, or upload code.

## H. Snapshot Relationship With Checkout Order

`build_submission_snapshot()` consumes the existing checkout review snapshot from `Youngo_checkout_model::get_safe_order_review_snapshot()` and preserves:

- order id/reference/user/status
- item type/id/title
- `subtotal_amount` as original amount
- coupon code/type/value
- discount amount
- `total_amount` as final/expected amount
- EGP currency
- selected payment method when present
- decoded checkout snapshot JSON
- zero-final policy flag

The Instapay row stores `expected_amount` from the locked checkout final amount and stores the full review evidence in `snapshot_json`.

## I. Diagnostic Result

Validation run:

```text
php -l application/models/Youngo_instapay_payment_model.php
php -l scripts/phase_2/youngo_payment_manual_instapay_schema_1_diagnostic.php
php -l scripts/phase_2/youngo_payment_coupon_checkout_ui_1_diagnostic.php
php scripts/phase_2/youngo_payment_manual_instapay_schema_1_diagnostic.php
php scripts/phase_2/youngo_payment_coupon_checkout_ui_1_diagnostic.php
```

Result: `PASS`

Manual Instapay diagnostic verified:

- table exists
- all expected fields exist
- expected field types are present
- expected indexes exist
- status model is limited to `pending_review`, `approved`, `rejected`
- model loads and exposes only inert helper/read methods
- temporary checkout order snapshot final amount `70.00 EGP` becomes submission expected amount
- one pending submission blocks another pending submission for the same order
- no access is issued from submission creation
- Paymob defaults remain disabled
- no Instapay upload/admin routes or controller actions exist

Coupon UI regression verified:

- coupon UI still applies/clears through the existing model path
- checkout UI still has disabled payment placeholders only
- no Instapay upload/schema use appears in routes/controllers/views
- payment/access counts remain unchanged

## J. DB Impact/Cleanup

Permanent additive schema impact:

- created empty table `youngo_instapay_payment_submissions`

Temporary diagnostic writes:

- one temporary `youngo_checkout_orders` row
- one temporary `youngo_instapay_payment_submissions` row
- coupon UI regression also inserted temporary checkout/coupon fixture rows

All temporary rows were deleted.

Protected counts remained stable in the Instapay diagnostic:

- `youngo_instapay_payment_submissions`: `0 -> 0`
- `youngo_checkout_orders`: `0 -> 0`
- `youngo_payment_transactions`: `0 -> 0`
- `youngo_course_access`: `0 -> 0`
- `youngo_user_subscriptions`: `0 -> 0`
- `youngo_manual_grants`: `0 -> 0`
- `youngo_coupon_usages`: `0 -> 0`
- `payment`: `0 -> 0`
- `enrol`: `1 -> 1`

## K. Payment/Access Safety

- No upload UI was added.
- No admin review UI was added.
- No approval/rejection behavior was added.
- No payment route was added.
- No Paymob gateway was enabled.
- No card/wallet behavior was added.
- No checkout controller behavior changed.
- No order paid state is created by this phase.
- No entitlement, course access, subscription, manual grant, enrolment, or legacy payment row was created.

## L. Remaining Risks/Blockers

- Screenshot upload validation/storage is not implemented yet.
- Admin review list/detail and controlled screenshot preview are not implemented yet.
- Approval/rejection logic is not implemented yet.
- Access issuance from `approved_at` is not implemented yet.
- Subscription checkout/access issuance remains a later phase.
- A DB-level CHECK constraint was not added for status compatibility; allowed review statuses are enforced/documented at the model/schema-comment level for this foundation.

## M. Recommended Next Phase

Recommended next phase: `PAYMENT.MANUAL.INSTAPAY.SUBMISSION.UPLOAD.1`

That phase should add POST-only submission creation/upload handling, require screenshot evidence, preserve the locked checkout snapshot, keep status at `pending_review`, and still issue no access.

## N. Git Status

Final status expected:

```text
 M scripts/phase_2/youngo_payment_coupon_checkout_ui_1_diagnostic.php
?? application/models/Youngo_instapay_payment_model.php
?? docs/qa/youngo_payment_manual_instapay_schema_1_report.md
?? scripts/phase_2/payment_manual_instapay_schema_1_down.sql
?? scripts/phase_2/payment_manual_instapay_schema_1_up.sql
?? scripts/phase_2/youngo_payment_manual_instapay_schema_1_diagnostic.php
```
