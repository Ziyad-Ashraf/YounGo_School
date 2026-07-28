# PAYMENT.MANUAL.INSTAPAY.APPROVAL.ACCESS.1 Report

Phase: PAYMENT.MANUAL.INSTAPAY.APPROVAL.ACCESS.1 - Add Manual Instapay Approve Reject and Access Issuance

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree: clean
- Latest expected commits present:
  - `8166264 Add manual Instapay admin review inbox`
  - `8680f5a Add zero-amount coupon course completion`
- Deployment: not performed.
- Push: not performed.
- Root Admin credentials: not printed, stored, or written to this report.

## B. Backup Created

The diagnostic creates a database backup before inserting temporary approval/rejection fixtures.

- Backup path: `D:\Work\YounGo/backups/youngo_school_before_payment_manual_instapay_approval_access_1_2026_07_26_224847.sql`
- Size: `1140028` bytes
- SHA256: `d30bee12b86c8ed72259b4ec5ef949afbb5cd532738da62fed17c4e3927b6508`

## C. Files Inspected

- `docs/qa/youngo_payment_manual_instapay_admin_review_ui_1_report.md`
- `docs/qa/youngo_payment_manual_instapay_submission_upload_1_report.md`
- `docs/qa/youngo_payment_manual_instapay_schema_1_report.md`
- `docs/qa/youngo_payment_zero_amount_coupon_access_implement_1_report.md`
- `application/controllers/Youngo_instapay_payments.php`
- `application/models/Youngo_instapay_payment_model.php`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_entitlement_write_model.php`
- `application/models/Youngo_subscription_model.php`
- `application/views/backend/admin/youngo_instapay_payment_view.php`
- `application/views/backend/admin/youngo_instapay_payments.php`
- `application/config/routes.php`
- `scripts/phase_2/`

## D. Files Changed

- `application/config/routes.php`
- `application/controllers/Youngo_instapay_payments.php`
- `application/models/Youngo_instapay_payment_model.php`
- `application/models/Youngo_entitlement_write_model.php`
- `application/views/backend/admin/youngo_instapay_payment_view.php`
- `application/views/backend/admin/youngo_instapay_payments.php`
- `scripts/phase_2/youngo_payment_manual_instapay_approval_access_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_manual_instapay_admin_review_ui_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_manual_instapay_submission_upload_1_diagnostic.php`
- `docs/qa/youngo_payment_manual_instapay_approval_access_1_report.md`

## E. Approval Behavior

`Youngo_instapay_payment_model::approve_submission($submission_id, $admin_user_id, $admin_note = null)` now:

- requires a valid Root Admin reviewer
- requires submission status `pending_review`
- requires linked checkout order ownership match
- requires EGP and expected amount equal to checkout `total_amount`
- requires a positive final amount, so zero-coupon orders remain separate
- requires order status `draft` with no provider/payment/paid/completed state
- blocks Paymob/HMAC markers
- blocks existing order/access issuance
- blocks subscription submissions with `subscription_instapay_approval_deferred`
- marks submission `approved`
- marks order paid-equivalent with `payment_gateway = instapay_manual` and `selected_payment_method = instapay_manual`
- records `reviewed_by_user_id`, `reviewed_at`, `approved_at`, `admin_note`, `access_issued`, `access_issued_at`, and access reference fields

## F. Rejection Behavior

`Youngo_instapay_payment_model::reject_submission($submission_id, $admin_user_id, $admin_note = null)` now:

- requires a valid Root Admin reviewer
- requires submission status `pending_review`
- marks submission `rejected`
- records reviewer, review timestamp, rejection timestamp, and admin note
- does not mark the checkout order paid
- does not issue access
- preserves evidence and snapshot data

Approved submissions cannot be rejected. Rejected submissions cannot be approved; the learner must create a new submission if needed.

## G. Access Issuance Behavior

Added `Youngo_entitlement_write_model::issue_instapay_manual_course_access($checkout_order_id, $actor_context = array())`.

The method:

- supports course purchase checkout orders only
- requires order status `paid`
- requires `payment_gateway = instapay_manual`
- requires `selected_payment_method = instapay_manual`
- requires `last_hmac_verified = 0`
- requires a positive EGP checkout total
- issues one active lifetime course access row through `youngo_course_access`
- starts access at `approved_at`
- records checkout entitlement fields back on the order

## H. Course Support Result

Course purchase approval is implemented and diagnostic-tested. Approval created exactly one `youngo_course_access` row for the temporary checkout order, and the access `start_date` matched `approved_at`.

## I. Subscription Support/Defer Result

Subscription approval is deferred. Existing `Youngo_entitlement_write_model::issue_subscription_purchase()` still returns `checkout_issuance_not_implemented`, so `approve_submission()` blocks subscription submissions before marking paid or issuing access.

## J. Admin UI/Action Summary

Added POST routes:

- `admin/youngo/instapay-payments/(:num)/approve`
- `admin/youngo/instapay-payments/(:num)/reject`

The admin detail page now shows pending-only forms:

- external payment receipt confirmation checkbox
- admin note field
- `Approve payment and issue access`
- `Reject payment`

Approved/rejected submissions show review timestamps, reviewer ID, admin note, and access reference details instead of active action buttons. The inbox shows an access-issued indicator.

## K. Idempotency Result

Duplicate approval returns `submission_already_approved` and does not create duplicate course access. Rejection after approval is blocked, and approval after rejection is blocked.

## L. Browser/Manual QA Result

Separate browser automation was not run in this phase. The local diagnostic covered model-level approval/rejection, POST route/action source checks, pending-only UI controls, evidence preservation, learner/order fixture cleanup, and safety checks without using or storing credentials.

## M. Diagnostic Result

Command:

```text
php scripts/phase_2/youngo_payment_manual_instapay_approval_access_1_diagnostic.php
```

Result: `PASS`

Regression commands:

```text
php scripts/phase_2/youngo_payment_manual_instapay_admin_review_ui_1_diagnostic.php
php scripts/phase_2/youngo_payment_manual_instapay_submission_upload_1_diagnostic.php
php scripts/phase_2/youngo_payment_zero_amount_coupon_access_qa_1.php
```

Result: all `PASS` when run sequentially.

## N. DB/Filesystem Cleanup

Temporary diagnostic writes:

- checkout orders
- Instapay submissions
- one course access row for approved course fixture
- temporary PNG evidence files

Cleanup result from the passing approval diagnostic:

- `youngo_instapay_payment_submissions`: `0 -> 0`
- `youngo_checkout_orders`: `0 -> 0`
- `youngo_payment_transactions`: `0 -> 0`
- `youngo_course_access`: `0 -> 0`
- `youngo_user_subscriptions`: `0 -> 0`
- `youngo_manual_grants`: `0 -> 0`
- `youngo_coupon_usages`: `0 -> 0`
- `payment`: `0 -> 0`
- `enrol`: `1 -> 1`
- temporary evidence files deleted

## O. Payment/Paymob/Card/Wallet Safety

- Paymob config remains disabled.
- No Paymob transaction or legacy payment row was created.
- No card or wallet behavior was added.
- No Paymob/HMAC verification was faked.
- Screenshot upload still grants no access.
- Access is issued only from admin approval.
- Approved Instapay review statuses remain exactly `pending_review`, `approved`, and `rejected`.

## P. Remaining Risks/Blockers

- Subscription checkout approval remains deferred until subscription purchase issuance is implemented and QA-tested.
- Full authenticated browser QA for the new approve/reject forms remains a good final-phase check.
- The implementation relies on model-level idempotency and existing table structure; no new DB constraints were added in this phase.

## Q. Recommended Next Phase

`PAYMENT.MANUAL.INSTAPAY.FULL.QA.1`

Recommended scope:

- end-to-end learner submission plus admin approve/reject route QA
- verify learner status UI after approval/rejection
- verify evidence preview/download remains protected after status changes
- verify course access appears in My Courses/My Access after approval
- verify subscription submissions remain safely deferred

## R. Git Status

Final git status is recorded in the assistant final response.

## S. Full QA Result

Phase: `PAYMENT.MANUAL.INSTAPAY.FULL.QA.1`

QA script:

```text
php scripts/phase_2/youngo_payment_manual_instapay_full_qa_1.php
```

Result: `PASS`

Mode: authenticated-session model/view/route QA using temporary local fixtures. No credentials were printed, stored, or added to this report.

Backup created before temporary full-QA writes:

```text
D:\Work\YounGo/backups/youngo_school_before_payment_manual_instapay_full_qa_1_2026_07_26_231337.sql
```

- Size: `1140009`
- SHA256: `aecf2bb5f6c719ea1319fa65d57762f715db2f9c36e055dfcac7fa5c70471639`

Learner upload result:

- Checkout summary rendered coupon/original/discount/final amount data.
- Instapay target details rendered only after temporary config enablement.
- Screenshot evidence submission created `pending_review` only.
- Duplicate pending submission was blocked.
- Learner pending state hid the upload form.
- Cards and Digital Wallets remained disabled with unavailable messaging.
- No access was issued before admin approval.

Admin approval/access result:

- Admin inbox/detail rendered the pending submission with coupon, expected final amount, transaction reference, user note, and evidence details.
- Protected evidence lookup succeeded for the valid Instapay evidence file.
- Approval set `status = approved`, `reviewed_by_user_id`, `reviewed_at`, and `approved_at`.
- Checkout order was marked paid-equivalent with `selected_payment_method = instapay_manual` and `payment_gateway = instapay_manual`.
- Exactly one course access row was issued.
- Course access `start_date` matched `approved_at`.
- Duplicate approval was idempotent and did not create duplicate access.

Rejection result:

- A second pending submission was rejected with no course access issued.
- Rejected submissions could not be approved.
- Approved submissions could not be rejected.
- Evidence and snapshot data were preserved.

Evidence/security result:

- Learner/admin access guards exist in controller sources.
- Evidence lookup rejects traversal/outside-path records.
- State-changing approve/reject routes are POST-only.
- No GET state-changing approval/rejection path was added.

Regression result:

- Zero-amount coupon completion remained separate and continued to block Instapay submission creation.
- Subscription Instapay approval remained deferred.
- Paymob static config remained disabled.
- No Paymob transaction row or legacy `payment` row was created.
- Card/wallet behavior stayed unavailable.

Cleanup result:

- Temporary checkout orders were deleted.
- Temporary Instapay submissions were deleted.
- Temporary course access rows were deleted.
- Temporary evidence files were deleted.
- Temporary Instapay config was restored.
- Protected count check passed:
  - `ci_sessions`: `1903 -> 1903`
  - `youngo_payment_provider_configs`: `0 -> 0`
  - `youngo_instapay_payment_submissions`: `0 -> 0`
  - `youngo_checkout_orders`: `0 -> 0`
  - `youngo_payment_transactions`: `0 -> 0`
  - `youngo_course_access`: `0 -> 0`
  - `youngo_user_subscriptions`: `0 -> 0`
  - `youngo_manual_grants`: `0 -> 0`
  - `youngo_coupon_usages`: `0 -> 0`
  - `coupons`: `0 -> 0`
  - `youngo_coupon_courses`: `0 -> 0`
  - `youngo_coupon_subscription_plans`: `0 -> 0`
  - `payment`: `0 -> 0`
  - `enrol`: `1 -> 1`

Validation commands after full QA:

```text
php scripts/phase_2/youngo_payment_manual_instapay_approval_access_1_diagnostic.php
php scripts/phase_2/youngo_payment_manual_instapay_submission_upload_1_diagnostic.php
php scripts/phase_2/youngo_payment_zero_amount_coupon_access_qa_1.php
```

Result: all `PASS` when run sequentially after the full-QA script.
