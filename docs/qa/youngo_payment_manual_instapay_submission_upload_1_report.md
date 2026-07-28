# PAYMENT.MANUAL.INSTAPAY.SUBMISSION.UPLOAD.1 Report

Phase: PAYMENT.MANUAL.INSTAPAY.SUBMISSION.UPLOAD.1 - Add Manual Instapay Checkout Submission Upload

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree: clean
- Latest expected commit present: `c42cb6f Add manual Instapay payment target config`
- Scope: learner checkout upload/submission flow only. No admin review, approval/rejection action, Paymob activation, card/wallet payment, payment paid state, enrolment, entitlement, or access issuance.

## B. Backup Created

The diagnostic creates a fresh backup before temporary DB writes.

- Backup path: `D:\Work\YounGo/backups/youngo_school_before_payment_manual_instapay_submission_upload_1_2026_07_26_160025.sql`
- Size: `1146459` bytes
- SHA256: `93e67f79c6a2a0f5ec89674c107e56b94d5772933e9ca2e182e0c1327e046224`

An earlier diagnostic run also created a backup and cleaned up successfully while validating/fixing the snapshot method fallback; the final passing run above is the validation baseline for this report.

## C. Files Inspected

- `docs/qa/youngo_payment_manual_instapay_config_1_report.md`
- `docs/qa/youngo_payment_manual_instapay_schema_1_report.md`
- `docs/qa/youngo_payment_coupon_checkout_ui_1_report.md`
- `docs/qa/youngo_payment_coupon_checkout_snapshot_write_1_report.md`
- `application/controllers/Youngo_checkout.php`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_instapay_payment_model.php`
- `application/models/Youngo_payment_config_model.php`
- `application/views/frontend/youngo/checkout_order.php`
- `application/config/routes.php`
- `application/config/config.php`
- `scripts/phase_2/`

## D. Files Changed

- `application/models/Youngo_instapay_payment_model.php`
- `application/controllers/Youngo_checkout.php`
- `application/models/Youngo_checkout_model.php`
- `application/views/frontend/youngo/checkout_order.php`
- `application/config/routes.php`
- `scripts/phase_2/youngo_payment_manual_instapay_submission_upload_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_manual_instapay_config_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_manual_instapay_schema_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_coupon_checkout_ui_1_diagnostic.php`
- `uploads/youngo/instapay_evidence/.htaccess`
- `uploads/youngo/instapay_evidence/index.html`
- `docs/qa/youngo_payment_manual_instapay_submission_upload_1_report.md`

## E. Model Create Behavior

`Youngo_instapay_payment_model::create_pending_submission($order_id, $user_id, $upload_data, $transaction_reference = null, $user_note = null)` now:

- requires a valid checkout order and learner owner
- requires order status `draft`, EGP currency, no provider/payment activity, and no issued access
- requires Instapay checkout config enabled through `Youngo_payment_config_model`
- requires valid screenshot metadata under `uploads/youngo/instapay_evidence/`
- inserts status `pending_review` only
- stores `expected_amount` from checkout `total_amount`
- blocks duplicate active `pending_review` submissions for the same order
- allows later resubmission only after the existing submission is `rejected`
- does not approve, reject, mark paid, call Paymob, or issue access

## F. Upload Validation/Storage Behavior

Controller upload handling:

- POST-only endpoint
- file input: `instapay_screenshot`
- allowed MIME types: `image/jpeg`, `image/png`, `image/webp`
- matching extensions: JPG/JPEG, PNG, WebP
- max size from Instapay config, default/fallback 5 MB
- randomized non-overwriting filename
- storage path: `uploads/youngo/instapay_evidence/`
- saved DB metadata only: relative path, original name, MIME, and size
- upload directory includes `.htaccess` deny rules and `index.html`
- orphan upload is deleted when the model rejects submission creation

## G. Checkout UI Behavior

`checkout_order.php` now shows the manual Instapay panel only from the configured target snapshot:

- disabled/coming-soon state when Instapay config is not enabled
- final amount to pay in EGP
- configured target label/address/link/instructions when enabled
- screenshot upload field
- optional transaction reference
- optional user note
- `Submit payment for review` action

Coupon controls remain visible only while the order is draft and no pending/approved Instapay review locks the snapshot.

## H. Controller/Action Behavior

Added route/action:

- `youngo/checkout/instapay/submit/(:any)`
- `Youngo_checkout::submit_instapay($order_reference)`

The action:

- requires POST
- uses existing local checkout gates
- requires logged-in learner, not admin/root
- verifies order ownership
- requires Instapay config enabled
- validates/stores image evidence
- calls `create_pending_submission()`
- redirects back to the checkout order page with flash success/error
- does not create payment paid state or access

## I. Submission Status Behavior

Approved Instapay review statuses remain exactly:

- `pending_review`
- `approved`
- `rejected`

Checkout display:

- `pending_review`: shows awaiting admin review and no upload form
- `rejected`: allows resubmission only if the order is still eligible
- `approved`: displays approved status only; approval/access behavior is not implemented in this phase

## J. Snapshot Content

Submission `snapshot_json` includes:

- checkout order review snapshot
- original amount from `subtotal_amount`
- coupon code/type/value
- discount amount
- final/expected amount from `total_amount`
- currency `EGP`
- selected payment method `instapay_manual`
- Instapay target label/address/link/instructions from config helper output
- screenshot metadata
- transaction reference and user note
- zero-final-amount policy flag when present

## K. Diagnostic Result

Validation run:

```text
php scripts/phase_2/youngo_payment_manual_instapay_submission_upload_1_diagnostic.php
RESULT: PASS
cleanup: completed
approved_instapay_review_statuses: pending_review, approved, rejected
```

Regression diagnostics:

```text
php scripts/phase_2/youngo_payment_manual_instapay_config_1_diagnostic.php
status: PASS

php scripts/phase_2/youngo_payment_manual_instapay_schema_1_diagnostic.php
summary: PASS
checks_total: 112
checks_failed: 0

php scripts/phase_2/youngo_payment_coupon_checkout_ui_1_diagnostic.php
RESULT: PASS
```

## L. DB/Filesystem Cleanup

Temporary diagnostic writes:

- one temporary enabled `instapay_manual/manual` config row, restored/deleted afterward
- one temporary checkout order, deleted afterward
- one rejected temporary Instapay submission and one pending temporary resubmission, deleted afterward
- two temporary PNG evidence files, deleted afterward

Protected counts restored in the final upload diagnostic:

- `youngo_payment_provider_configs`: `0 -> 0`
- `youngo_instapay_payment_submissions`: `0 -> 0`
- `youngo_checkout_orders`: `0 -> 0`
- `youngo_payment_transactions`: `0 -> 0`
- `youngo_course_access`: `0 -> 0`
- `youngo_user_subscriptions`: `0 -> 0`
- `youngo_manual_grants`: `0 -> 0`
- `youngo_coupon_usages`: `0 -> 0`
- `payment`: `0 -> 0`
- `enrol`: `1 -> 1`

## M. Payment/Access Safety

This phase did not:

- enable Paymob
- expose cards or wallets
- create admin review UI
- approve or reject real payments
- mark checkout orders paid
- create payment rows
- create enrolment/access/subscription/manual grant rows
- issue access from screenshot upload
- modify Root Admin identity or credentials

## N. Remaining Risks/Blockers

- Browser QA was not run in this phase.
- Admin review list/detail, protected screenshot preview/download, approve/reject actions, and access issuance from `approved_at` remain unimplemented.
- The upload storage path is guarded by `.htaccess`; nginx/IIS deployments will still need equivalent server-level deny rules or controlled preview routing.
- Actual Instapay target details must be configured by Root Admin before learner use.

## O. Recommended Next Phase

Recommended next phase:

`PAYMENT.MANUAL.INSTAPAY.ADMIN.REVIEW.UI.1`

That phase should add Root/Admin review inbox/detail pages, controlled evidence preview/download, approve/reject actions, and still keep access issuance separated until the explicit approval/access phase if required by sequencing.

## P. Git Status

Final expected status after this phase:

```text
 M application/config/routes.php
 M application/controllers/Youngo_checkout.php
 M application/models/Youngo_checkout_model.php
 M application/models/Youngo_instapay_payment_model.php
 M application/views/frontend/youngo/checkout_order.php
 M scripts/phase_2/youngo_payment_manual_instapay_config_1_diagnostic.php
 M scripts/phase_2/youngo_payment_manual_instapay_schema_1_diagnostic.php
 M scripts/phase_2/youngo_payment_coupon_checkout_ui_1_diagnostic.php
?? docs/qa/youngo_payment_manual_instapay_submission_upload_1_report.md
?? scripts/phase_2/youngo_payment_manual_instapay_submission_upload_1_diagnostic.php
?? uploads/youngo/
```

No deploy or push was performed.
