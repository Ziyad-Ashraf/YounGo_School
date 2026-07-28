# PAYMENT.ORDER.1 - YounGo Checkout Order Service Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree: clean
- Starting latest commit: `685494a Add non-secret YounGo Paymob config foundation`
- Command baseline reviewed:
  - `git branch --show-current`
  - `git status --short`
  - `git log --oneline -10`

## B. Files Inspected

- `docs/qa/youngo_payment_config_1_paymob_research_and_sandbox_plan.md`
- `docs/qa/youngo_payment_config_2_architecture_schema_design.md`
- `docs/qa/youngo_payment_config_3_implementation_plan.md`
- `docs/qa/youngo_payment_schema_1_apply_report.md`
- `docs/qa/youngo_payment_config_file_1_report.md`
- `scripts/phase_2/youngo_payment_schema_1_diagnostic.php`
- `application/models/Youngo_entitlement_write_model.php`
- `application/models/Youngo_entitlement_model.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/config/youngo_paymob.php`
- `application/controllers/Home.php`
- `application/views/frontend/youngo/course_page.php`
- `application/views/frontend/youngo/course_listing/course_card.php`

## C. Files Changed

- Added `application/models/Youngo_checkout_model.php`
- Added `scripts/phase_2/youngo_payment_order_1_diagnostic.php`
- Added `docs/qa/youngo_payment_order_1_checkout_order_service_report.md`

## D. Checkout Order Service Behavior

`Youngo_checkout_model` is a local order foundation only. It does not call Paymob, does not create Paymob intentions, does not expose checkout routes, and does not issue YounGo entitlements.

Implemented methods:

- `create_draft_order($user_id, $course_id, $amount, $currency = 'EGP')`
- `get_order_by_reference($order_reference)`
- `get_order($order_id)`
- `mark_pending_gateway($order_id, $gateway_provider, $gateway_reference = null)`
- `mark_awaiting_webhook($order_id)`
- `mark_cancelled($order_id, $reason = null)`
- `mark_failed($order_id, $reason = null)`
- `can_start_checkout($user_id, $course_id)`
- `generate_order_reference()`
- `get_safe_order_summary($order)`

Draft order creation behavior:

- Rejects non-EGP currency before schema or DB access.
- Rejects non-numeric, zero, or negative amounts before schema or DB access.
- Requires the local payment schema table and columns to exist.
- Requires an existing user and existing course.
- Allows only direct purchase-compatible YounGo course modes for this course-order foundation: `purchase_only` and `subscription_and_purchase`.
- Rejects free courses because payment is not required.
- Rejects learners with active direct YounGo course access.
- Rejects duplicate open draft/pending/awaiting-webhook course orders for the same user/course.
- Creates `course_purchase` draft orders with safe `YGO-...` references and EGP amount cents.
- Leaves entitlement fields in not-started state.

Status foundation:

- `draft` can move to `pending_gateway`.
- `pending_gateway` can move to `awaiting_webhook`.
- `cancelled` and `failed` are supported for unpaid orders.
- `paid` orders are protected from local cancel/fail updates in this model.

## E. Safety Rules Implemented

- EGP-only for this phase.
- Payment remains disabled by config defaults.
- No Paymob network/API calls.
- No public checkout route or CTA exposure.
- No legacy `enrol` writes.
- No legacy `payment` writes.
- No YounGo entitlement/subscription writes.
- No legacy gateway row updates.
- No secrets are printed or represented in model output.
- Safe order summaries omit idempotency keys, payloads, and future credential fields.

## F. Diagnostic Result

Command run:

```text
php scripts/phase_2/youngo_payment_order_1_diagnostic.php
```

Result: PASS.

The diagnostic verified:

- Checkout model file exists and loads.
- Required service methods exist.
- Required payment schema tables and order columns exist.
- Checkout order `currency` default is `EGP`.
- Checkout order `status` default is `draft`.
- Config reader default is disabled, sandbox, and EGP.
- Invalid currency returns `unsupported_currency`.
- Invalid zero amount returns `invalid_amount`.
- Protected table counts were unchanged after invalid checks.
- Public CTA boundary markers remain present.
- No checkout/webhook routes were added.
- No Paymob network-call patterns were introduced.

## G. Local DB Diagnostic Writes and Cleanup

No persistent diagnostic order was created in this phase.

Protected counts before and after invalid-input checks matched:

- `youngo_checkout_orders`: 0 before, 0 after
- `youngo_payment_transactions`: 0 before, 0 after
- `youngo_course_access`: 0 before, 0 after
- `youngo_user_subscriptions`: 0 before, 0 after
- `enrol`: 1 before, 1 after
- `payment`: 0 before, 0 after

## H. What Was Not Changed

- No deployment.
- No push.
- No commit.
- No live cPanel/server access.
- No Root Admin changes.
- No DB schema changes.
- No SQL execution.
- No Paymob API/network implementation.
- No real credentials.
- No payment activation.
- No public checkout CTA exposure.
- No entitlement issuance.
- No legacy gateway DB row changes.

## I. Risks/Blockers

- Valid draft-order creation has not yet been runtime-tested through the model with a real learner/course fixture because this phase avoided persistent local DB writes.
- Subscription checkout orders are not implemented yet; this model currently supports course purchase order foundations only.
- Paymob Intention API and webhook verification remain deferred.
- Entitlement issuance after verified payment remains deferred.
- Future route/controller phases must keep return URL as UX-only and webhook/HMAC verification as source of truth.

## J. Recommended Next Phase

Recommended next phase: `PAYMENT.PAYMOB.ADAPTER.1` or an intermediate controller/service wiring phase that adds a disabled local checkout controller surface behind admin/developer-only controls, still without public CTA exposure or Paymob network calls.

## K. Git Status

Expected after this phase before commit:

```text
?? application/models/Youngo_checkout_model.php
?? docs/qa/youngo_payment_order_1_checkout_order_service_report.md
?? scripts/phase_2/youngo_payment_order_1_diagnostic.php
```
