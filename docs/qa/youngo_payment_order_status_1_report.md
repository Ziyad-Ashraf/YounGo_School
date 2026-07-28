# PAYMENT.ORDER.STATUS.1 - Verified Transaction Order Status Transition Block

## A. Current Branch/Status

- Date: 2026-07-21
- Branch: `analysis/cms-audit`
- Starting worktree status: clean
- Latest commit at start: `b1d6977 Add YounGo fixture payment transaction recording`

Recent commit context:

```text
b1d6977 Add YounGo fixture payment transaction recording
b96a7b9 Add disabled YounGo Paymob webhook route skeleton
c0d7796 Add YounGo Paymob webhook verification skeleton
4fd5f8c Add disabled YounGo Paymob adapter skeleton
fda6f91 Runtime test YounGo checkout order service
be4e139 Add YounGo checkout order service foundation
685494a Add non-secret YounGo Paymob config foundation
f172db6 Apply YounGo local payment schema
6111cf7 Plan YounGo payment implementation phases
20c21f0 Design YounGo payment architecture and schema
```

## B. Backup Created

Fresh local DB backup was created before diagnostic DB writes:

- Path: `D:\Work\YounGo\backups\youngo_school_before_payment_order_status_1_2026_07_21_045609.sql`
- Size: `929719` bytes
- SHA256: `5d1b2410654f776539c4d9867b6284f33c523e3321c65cbf98bb84c198b9986f`
- Tables dumped: `65`

No live/cPanel DB was touched.

## C. Files Inspected

- `docs/qa/youngo_payment_transaction_block_1_report.md`
- `docs/qa/youngo_payment_order_2_runtime_test_report.md`
- `docs/qa/youngo_payment_schema_1_apply_report.md`
- `docs/qa/youngo_payment_webhook_route_1_report.md`
- `docs/qa/youngo_payment_config_2_architecture_schema_design.md`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_payment_model.php`
- `application/libraries/Youngo_paymob_fixture_processor.php`
- `application/libraries/Youngo_paymob_webhook.php`
- `application/controllers/Youngo_payment_webhook.php`
- `scripts/phase_2/fixtures/paymob/`

## D. Files Changed

- `application/models/Youngo_payment_model.php`
- `application/libraries/Youngo_paymob_fixture_processor.php`
- `scripts/phase_2/youngo_payment_order_status_1_runtime_test.php`
- `docs/qa/youngo_payment_order_status_1_report.md`

## E. Reused Existing Logic

This phase reused the existing YounGo payment foundation instead of adding a parallel payment path:

- Reused `Youngo_checkout_model` for draft order creation and existing safe transitions into `pending_gateway`, `awaiting_webhook`, and `cancelled`.
- Reused `Youngo_payment_model` transaction recording, duplicate detection, verified/rejected transaction state, redacted payload storage, and safe result structures.
- Reused `Youngo_paymob_fixture_processor` for fixture-only webhook normalization, HMAC verification, event classification, local order lookup, and transaction recording.
- Reused `Youngo_paymob_webhook` for fixture HMAC behavior.
- Did not reuse legacy `Payment.php` or legacy `Payment_model` for YounGo order status changes because those paths remain legacy checkout/payment entry points and are not safe for the YounGo-specific Paymob flow.

## F. Order Status Transition Behavior

Added local-only status transition methods in `Youngo_payment_model`:

- `mark_paid_from_verified_transaction($order_id, $transaction_id)`
- `mark_failed_from_rejected_transaction($order_id, $transaction_id, $reason)`
- `can_mark_paid($order)`
- `can_mark_failed($order)`
- `get_payment_status_summary($order_id)`

Paid transition rules:

- The order must exist.
- The transaction must exist and belong to the same order/reference.
- The order must be `draft`, `pending_gateway`, or `awaiting_webhook`.
- The order currency must be `EGP`.
- The transaction must be `verified`.
- The transaction must have `hmac_verified = 1`.
- The transaction amount/currency must match the order amount/currency.
- The order is updated to `paid`.
- `entitlement_issued` remains `0`.
- `entitlement_issuance_status` remains `not_started`.

Failed transition rules:

- The order must exist.
- The transaction must exist and belong to the same order/reference.
- The order must be `draft`, `pending_gateway`, or `awaiting_webhook`.
- The transaction must be `rejected`.
- The order is updated to `failed`.
- `entitlement_issued` remains `0`.
- `entitlement_issuance_status` remains `not_started`.

Invalid transitions fail closed with structured error codes. Already-paid orders cannot be paid twice. Failed, cancelled, and expired orders cannot be marked paid.

## G. Fixture Processing Integration

`Youngo_paymob_fixture_processor` now supports an opt-in constructor flag:

```php
'apply_order_status_transitions' => true
```

When this flag is absent or false, the fixture processor keeps the PAYMENT.TRANSACTION.BLOCK.1 behavior and records/verifies/rejects transactions without changing final order payment status.

When the flag is true in diagnostic context only:

- verified success fixtures can mark the diagnostic order `paid`;
- rejected/failed fixtures can mark the diagnostic order `failed`;
- duplicate fixtures remain duplicate/idempotent and do not create a second success state.

The public webhook route is not wired to this enabled fixture-processing mode.

## H. Idempotency/Duplicate Behavior

Runtime diagnostic results confirmed:

- Duplicate success fixture was detected as `fixture_duplicate_detected`.
- No extra payment transaction row was created for the duplicate.
- The already-paid order did not receive a duplicate paid transition.
- Direct repeat paid transition returned `order_already_paid`.
- Failed-order paid transition attempt returned `invalid_order_status`.
- Cancelled-order paid transition attempt returned `invalid_order_status`.

## I. Runtime Diagnostic Result

Created diagnostic:

- `scripts/phase_2/youngo_payment_order_status_1_runtime_test.php`

Primary runtime diagnostic result:

```text
php scripts/phase_2/youngo_payment_order_status_1_runtime_test.php
PASS
```

Important checks passed:

- Payment config remained disabled by default.
- Webhook testing remained disabled by default.
- No Paymob network code was detected.
- Public webhook route remained fail-closed.
- Fixture user was not Root Admin.
- Diagnostic paid order was marked paid only from a verified fake-HMAC transaction.
- Diagnostic failed order was marked failed only from a rejected fixture transaction.
- Entitlement issuance remained not started.
- Legacy payment/enrol rows were not written.
- Protected counts were restored after cleanup.

Regression diagnostics:

```text
php scripts/phase_2/youngo_payment_transaction_block_1_runtime_test.php
PASS

php scripts/phase_2/youngo_payment_webhook_route_1_diagnostic.php
PASS

php scripts/phase_2/youngo_payment_schema_1_diagnostic.php
PASS
```

## J. DB Write/Cleanup Summary

The PAYMENT.ORDER.STATUS.1 runtime diagnostic performed controlled local-only writes:

- Created 3 diagnostic checkout orders.
- Created 2 diagnostic payment transaction rows.
- Processed success, duplicate, failed, and cancelled/invalid transition cases.
- Deleted all diagnostic transaction rows.
- Deleted all diagnostic checkout order rows.
- Reset local checkout/transaction auto-increment counters because baseline counts were zero.

Protected counts before and after cleanup:

```text
youngo_checkout_orders       0 -> 0
youngo_payment_transactions  0 -> 0
youngo_course_access         0 -> 0
youngo_user_subscriptions    0 -> 0
youngo_manual_grants         0 -> 0
youngo_coupon_usages         0 -> 0
payment                     0 -> 0
enrol                       1 -> 1
watch_histories             0 -> 0
watched_duration            0 -> 0
```

Fixture used:

```text
user_id: 8
user_email: qa.learner@youngo.local
course_id: 9
course_title: Robotics and AI Explorers
course_mode: subscription_and_purchase
amount: 1000.00
currency: EGP
```

No credentials were printed.

## K. Public Route Safety

The public route remains:

```text
payment/paymob/webhook -> youngo_payment_webhook/paymob
```

Safety status:

- GET remains rejected.
- POST remains rejected by default while webhook testing is disabled.
- The public controller does not use session-dependent learner/admin state.
- The public controller does not load the payment model or fixture processor for DB writes.
- The public controller does not issue entitlements.
- The public controller does not call Paymob.
- Public checkout CTAs remain suppressed by existing YounGo CTA boundary markers.

## L. What Was Not Changed

- No deployment.
- No push.
- No commit.
- No live/cPanel server access.
- No real Paymob calls.
- No real Paymob intentions.
- No real credentials added or printed.
- No payment enablement.
- No public checkout CTA exposure.
- No Root Admin changes.
- No entitlement issuance.
- No legacy gateway row changes.
- No legacy `payment` writes.
- No legacy `enrol` writes.
- No persistent diagnostic checkout/payment rows left behind.

## M. Remaining Risks/Blockers

- Order `paid` status is now available locally, but entitlement issuance remains deliberately deferred.
- Real Paymob webhook payloads must be retested after sandbox credentials and a public callback tunnel/URL strategy are approved.
- The public webhook route remains fail-closed; enabling local webhook testing needs a separate explicit phase and config flag.
- The transaction-to-order mapping currently depends on fixture/local reference mapping and must be validated against the exact Paymob sandbox payload fields before real sandbox testing.
- No production reconciliation/admin review UI exists yet.

## N. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.ENTITLEMENT.PLAN.1 — Verified Payment Entitlement Issuance Design and Failure Handling
```

Suggested scope:

- Design how a paid YounGo checkout order should call `Youngo_entitlement_write_model`.
- Define idempotency for entitlement issuance after paid order status.
- Define recovery behavior when payment succeeds but entitlement issuance fails.
- Decide whether legacy enrol sync is required for Academy compatibility pages.
- Keep implementation deferred until the entitlement/payment boundary is reviewed.

## O. Git Status

At report creation time:

```text
 M application/libraries/Youngo_paymob_fixture_processor.php
 M application/models/Youngo_payment_model.php
?? docs/qa/youngo_payment_order_status_1_report.md
?? scripts/phase_2/youngo_payment_order_status_1_runtime_test.php
```
