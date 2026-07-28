# PAYMENT.ENTITLEMENT.BLOCK.1 - Verified Payment Entitlement Issuance Design and Local Runtime Test

## A. Current Branch/Status

- Date: 2026-07-21
- Branch: `analysis/cms-audit`
- Starting worktree status: clean
- Latest commit at start: `a393278 Add YounGo payment order status transitions`

Recent commit context:

```text
a393278 Add YounGo payment order status transitions
b1d6977 Add YounGo fixture payment transaction recording
b96a7b9 Add disabled YounGo Paymob webhook route skeleton
c0d7796 Add YounGo Paymob webhook verification skeleton
4fd5f8c Add disabled YounGo Paymob adapter skeleton
fda6f91 Runtime test YounGo checkout order service
be4e139 Add YounGo checkout order service foundation
685494a Add non-secret YounGo Paymob config foundation
f172db6 Apply YounGo local payment schema
6111cf7 Plan YounGo payment implementation phases
```

## B. Backup Created

Fresh local DB backup created before diagnostic DB writes:

```text
Path: D:\Work\YounGo\backups\youngo_school_before_payment_entitlement_block_1_2026_07_21_135606.sql
Size: 932395 bytes
SHA256: 4acbb0faf2433934ad2185b46159671e9e95f2d45cb6d48c674ccee4bd179313
Tables dumped: 65
```

No live/cPanel database was touched. No credentials were printed.

## C. Files Inspected

Required phase reports:

- `docs/qa/youngo_payment_order_status_1_report.md`
- `docs/qa/youngo_payment_transaction_block_1_report.md`
- `docs/qa/youngo_payment_order_2_runtime_test_report.md`
- `docs/qa/youngo_payment_config_2_architecture_schema_design.md`
- `docs/qa/youngo_payment_schema_1_apply_report.md`

Project guardrails:

- `YOUNGO_PROJECT_CONTEXT.md`
- `docs/design/youngo_style_direction.md`
- `docs/planning/README.md`
- `docs/planning/youngo_master_plan_v2.md`
- `docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`
- `docs/agents/implementation_rules.md`
- `docs/reference/README.md`

Code inspected:

- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_payment_model.php`
- `application/models/Youngo_entitlement_write_model.php`
- `application/models/Youngo_entitlement_model.php`
- `application/helpers/youngo_entitlement_helper.php`
- `application/controllers/Home.php`
- `application/controllers/Youngo_payment_webhook.php`
- `application/libraries/Youngo_paymob_fixture_processor.php`
- `application/libraries/Youngo_paymob_webhook.php`
- `scripts/phase_2/fixtures/paymob/`

`application/models/Youngo_manual_grant_model.php` was requested for inspection but does not exist in the local repository. Manual grant writes are handled through `Youngo_entitlement_write_model` and the manual grant controller/model flow already documented in prior phases.

## D. Files Changed

- `application/models/Youngo_entitlement_write_model.php`
- `application/models/Youngo_payment_model.php`
- `application/models/Youngo_entitlement_model.php`
- `scripts/phase_2/youngo_payment_entitlement_block_1_runtime_test.php`
- `docs/qa/youngo_payment_entitlement_block_1_report.md`

## E. Reused Entitlement Logic

This phase reused the existing YounGo entitlement system:

- `Youngo_entitlement_write_model` remains the entitlement write boundary.
- The existing checkout issuance stub `issue_course_purchase_access($checkout_order_id, $actor_context = array())` was implemented instead of creating a separate payment-only access writer.
- Existing helpers in the write model are reused for schema readiness, user/course validation, active access duplicate prevention, insert filtering, transactions, and structured result arrays.
- `youngo_course_access` is reused with `access_source = course_purchase` and `checkout_order_id` linkage.
- `Youngo_entitlement_model::get_course_access_state()` is reused to verify the issued row is visible to the existing access read layer.
- No legacy `Payment.php`, legacy gateway model, legacy `payment`, or legacy `enrol` write path was used.

Small CLI support change:

- `Youngo_entitlement_model` now accepts an optional `db` constructor parameter, matching the checkout/payment model pattern used by phase diagnostics. Normal CodeIgniter model loading remains compatible.

## F. Payment-To-Entitlement Bridge Behavior

Added payment bridge methods in `Youngo_payment_model`:

- `issue_paid_order_entitlement($order_id, $transaction_id)`
- `can_issue_entitlement_for_order($order)`
- `get_entitlement_issuance_summary($order_id)`

Bridge rules:

- Order must exist.
- Transaction must exist.
- Order must be `paid`.
- Order type must be `course_purchase`.
- Order currency must be `EGP`.
- Order must have `last_hmac_verified = 1`.
- Order must not already have `entitlement_issued = 1`.
- Transaction must belong to the same order and order reference.
- Transaction must be `verified`.
- Transaction must have `hmac_verified = 1`.
- Transaction amount/currency must match the checkout order.
- The bridge calls `Youngo_entitlement_write_model::issue_course_purchase_access()`.

Implemented checkout issuance behavior in `Youngo_entitlement_write_model`:

- Verifies the checkout order is paid, EGP, HMAC-verified, and `course_purchase`.
- Blocks Root Admin as a recipient.
- Blocks duplicate active course access.
- Inserts one active lifetime `youngo_course_access` row linked by `checkout_order_id`.
- Marks the checkout order:
  - `entitlement_issued = 1`
  - `entitlement_issuance_status = issued`
  - `entitlement_course_access_id = <new access id>`
  - `entitlement_issued_at = <timestamp>`
- Uses a DB transaction for the access row and order flag update.

Subscription purchase issuance remains deferred.

## G. Runtime Entitlement Issuance Result

Created diagnostic:

```text
scripts/phase_2/youngo_payment_entitlement_block_1_runtime_test.php
```

Runtime fixture:

```text
user_id: 8
user_email: qa.learner@youngo.local
course_id: 9
course_title: Robotics and AI Explorers
course_mode: subscription_and_purchase
amount: 1000.00
currency: EGP
```

Successful runtime flow:

1. Created one diagnostic checkout order.
2. Moved it to `pending_gateway`.
3. Moved it to `awaiting_webhook`.
4. Processed fake Paymob success fixture with a fake diagnostic HMAC secret.
5. Recorded one verified transaction.
6. Marked the order `paid`.
7. Issued course access through the payment-to-entitlement bridge.
8. Confirmed the order entitlement flags were updated.
9. Confirmed the `youngo_course_access` row existed with `access_source = course_purchase`.
10. Confirmed `Youngo_entitlement_model::get_course_access_state()` detected active course-purchase access.

Result:

```text
php scripts/phase_2/youngo_payment_entitlement_block_1_runtime_test.php
PASS
```

## H. Duplicate Issuance Behavior

Duplicate issuance was tested by calling `issue_paid_order_entitlement()` again for the same paid order and verified transaction.

Result:

```text
duplicate_issue_code: entitlement_already_issued
```

No second course access row was created.

## I. DB Write/Cleanup Summary

Controlled local diagnostic writes:

- Created 1 diagnostic checkout order.
- Created 1 diagnostic payment transaction.
- Created 1 diagnostic course access row.
- Deleted the diagnostic course access row by `checkout_order_id`.
- Deleted the diagnostic payment transaction row by `checkout_order_id`.
- Deleted the diagnostic checkout order row by exact ID.
- Reset checkout, transaction, and course-access auto-increments to `1` because baseline table counts were zero.

Protected counts before and after:

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

No persistent checkout, transaction, entitlement, manual grant, subscription, coupon, legacy payment, or legacy enrol rows were left behind.

## J. Public Route Safety

The public webhook route remains:

```text
payment/paymob/webhook -> youngo_payment_webhook/paymob
```

Safety checks passed:

- Public webhook route remains fail-closed by default.
- Webhook testing remains disabled by default.
- Public controller is not wired to entitlement issuance.
- Public controller does not write DB.
- Public controller does not call Paymob.
- Public checkout CTAs remain suppressed by existing YounGo boundary markers.

## K. What Was Not Changed

- No deployment.
- No push.
- No commit.
- No live/cPanel server access.
- No live DB access.
- No payment enablement.
- No real credentials.
- No printed secrets.
- No Paymob network calls.
- No real Paymob intentions.
- No Root Admin modification.
- No public checkout CTA exposure.
- No legacy gateway DB row changes.
- No legacy `payment` writes.
- No legacy `enrol` writes.
- No persistent test checkout/order/payment/access rows.
- No subscription entitlement issuance.

## L. Remaining Risks/Blockers

- Subscription purchase entitlement issuance remains deferred.
- Full My Courses/My Access HTML rendering was not browser-tested in this phase; the core read model detected the active access row, and rendering QA should occur when checkout routes/UI exist.
- Real Paymob sandbox payload mapping still requires owner sandbox credentials and a tunnel/webhook strategy in a later phase.
- The public webhook remains disabled; live-style processing requires a separate explicit local testing flag phase.
- No admin payment review/retry UI exists for paid orders whose entitlement issuance fails.

## M. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.CHECKOUT.ROUTE.PLAN.1 - Local YounGo Checkout Route/UI Planning With CTA Boundaries
```

Suggested scope:

- Plan learner checkout start/pay/return/status routes.
- Keep public CTAs suppressed until explicitly approved.
- Define where the paid-order entitlement bridge will be called in real local sandbox processing.
- Keep Paymob network calls disabled unless a later sandbox phase explicitly enables them.

## N. Git Status

At report creation time:

```text
 M application/models/Youngo_entitlement_model.php
 M application/models/Youngo_entitlement_write_model.php
 M application/models/Youngo_payment_model.php
?? docs/qa/youngo_payment_entitlement_block_1_report.md
?? scripts/phase_2/youngo_payment_entitlement_block_1_runtime_test.php
```
