# PAYMENT.TRANSACTION.BLOCK.1 - Local Transaction Recording and Fixture Webhook Processing Report

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
b96a7b9 Add disabled YounGo Paymob webhook route skeleton
c0d7796 Add YounGo Paymob webhook verification skeleton
4fd5f8c Add disabled YounGo Paymob adapter skeleton
fda6f91 Runtime test YounGo checkout order service
be4e139 Add YounGo checkout order service foundation
685494a Add non-secret YounGo Paymob config foundation
f172db6 Apply YounGo local payment schema
6111cf7 Plan YounGo payment implementation phases
20c21f0 Design YounGo payment architecture and schema
dd601bd Plan YounGo Paymob sandbox integration
```

The expected baseline was confirmed: branch `analysis/cms-audit`, clean worktree, and latest commit `b96a7b9` includes PAYMENT.WEBHOOK.ROUTE.1.

## B. Backup Created

A fresh local DB backup was created before diagnostic DB writes.

```text
Path: D:\Work\YounGo\backups\youngo_school_before_payment_transaction_block_1_2026_07_21_043153.sql
Size: 929724 bytes
SHA256: 69c8e7676d33d62dc5422b70a3a22042972fda36bf4cf1f1de2a19492355c333
Tables dumped: 65
```

Backup notes:

- The backup is outside Git.
- No credentials were printed.
- `mysqldump` was not available on PATH, so the backup used a temporary PHP `mysqli` SQL dump helper outside the repository, then deleted that helper.

## C. Files Inspected

Required reports:

- `docs/qa/youngo_payment_config_1_paymob_research_and_sandbox_plan.md`
- `docs/qa/youngo_payment_config_2_architecture_schema_design.md`
- `docs/qa/youngo_payment_config_3_implementation_plan.md`
- `docs/qa/youngo_payment_schema_1_apply_report.md`
- `docs/qa/youngo_payment_order_2_runtime_test_report.md`
- `docs/qa/youngo_payment_paymob_adapter_1_report.md`
- `docs/qa/youngo_payment_webhook_1_report.md`
- `docs/qa/youngo_payment_webhook_route_1_report.md`

Reusable logic inspected before creating new code:

- `application/models/Youngo_checkout_model.php`
- `application/models/Payment_model.php`
- `application/models/Youngo_entitlement_write_model.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/libraries/Youngo_paymob_webhook.php`
- `application/libraries/Youngo_paymob_adapter.php`
- `application/controllers/Youngo_payment_webhook.php`
- `application/config/routes.php`
- `scripts/phase_2/youngo_payment_order_2_runtime_test.php`
- `scripts/phase_2/fixtures/paymob/`

Reuse decision:

- Reused `Youngo_checkout_model` for order creation, lookup, status transitions, and safe order summaries.
- Reused `Youngo_paymob_webhook` for payload normalization, fixture HMAC calculation/verification, event classification, gateway reference extraction, and safe payload summaries.
- Reused `Youngo_paymob_config` for disabled/sandbox/EGP config checks.
- Did not reuse Academy `Payment_model` because it is legacy session/gateway-network oriented and writes legacy enrol/payment rows after gateway success.
- Did not call `Youngo_entitlement_write_model` issuance stubs; entitlement issuance remains deferred.

## D. Files Changed

Created:

- `application/models/Youngo_payment_model.php`
- `application/libraries/Youngo_paymob_fixture_processor.php`
- `scripts/phase_2/fixtures/paymob/success_verified_payload.json`
- `scripts/phase_2/fixtures/paymob/duplicate_success_payload.json`
- `scripts/phase_2/fixtures/paymob/failed_payment_payload.json`
- `scripts/phase_2/fixtures/paymob/malformed_payload.json`
- `scripts/phase_2/youngo_payment_transaction_block_1_runtime_test.php`
- `docs/qa/youngo_payment_transaction_block_1_report.md`

Updated:

- `scripts/phase_2/youngo_payment_order_1_diagnostic.php`

The ORDER.1 diagnostic update only changes an obsolete route assertion so it still blocks checkout-start routes while allowing the disabled webhook route added in PAYMENT.WEBHOOK.ROUTE.1.

## E. Transaction Model Behavior

New model:

```text
application/models/Youngo_payment_model.php
```

Implemented methods:

- `record_received_transaction($order_id, $provider, $payload_summary, $gateway_refs)`
- `mark_transaction_verified($transaction_id, $verification_data = array())`
- `mark_transaction_rejected($transaction_id, $reason)`
- `mark_transaction_duplicate($transaction_id, $original_transaction_id = null)`
- `mark_order_webhook_seen($order_id, $gateway_refs = array(), $hmac_verified = false)`
- `get_transaction($transaction_id)`
- `get_transaction_by_gateway_reference($provider, $gateway_transaction_id)`
- `get_transactions_for_order($order_id)`
- `is_duplicate_gateway_event($provider, $gateway_transaction_id, $event_type)`
- `get_safe_transaction_summary($transaction)`
- `get_schema_readiness()`

Behavior:

- Requires local `youngo_payment_transactions` schema.
- Requires matching local checkout order.
- Restricts currency to `EGP`.
- Requires positive gateway amount.
- Requires provider transaction ID for idempotency.
- Uses provider, environment, transaction ID, and event type for duplicate detection.
- Stores only redacted safe payload summaries, gateway refs, and payload hash.
- Does not store raw secrets, HMAC secrets, API keys, client secrets, or full card data.
- Does not issue entitlements.
- Does not write legacy `payment` or `enrol`.
- Does not call Paymob or any remote service.

## F. Fixture Webhook Processing Behavior

New fixture-only processor:

```text
application/libraries/Youngo_paymob_fixture_processor.php
```

Processing flow:

1. Normalize payload through `Youngo_paymob_webhook`.
2. Verify HMAC using the injected fake diagnostic secret.
3. Classify Paymob transaction event.
4. Extract gateway refs.
5. Build safe payload summary.
6. Find local checkout order by merchant order reference.
7. Record a received transaction through `Youngo_payment_model`.
8. Mark success fixtures as `verified`.
9. Mark failed fixtures as `rejected`.
10. Detect duplicate provider transaction/event without adding another row.
11. Update order webhook metadata only: `last_webhook_at`, `last_hmac_verified`, provider refs, and `awaiting_webhook` if still earlier.

The public webhook route remains disconnected from this processor. This phase processes fixtures only through the runtime diagnostic script.

## G. Idempotency/Duplicate Handling Result

Runtime duplicate result:

- First success fixture created one transaction.
- The transaction was marked `verified`.
- Duplicate success fixture reused the same fake provider transaction ID and event type.
- Duplicate was detected as `fixture_duplicate_detected`.
- Transaction count did not increase.
- No duplicate success state was created.
- The original verified transaction was not overwritten or marked duplicate.

Before cleanup, the diagnostic order had:

```text
verified: 1
rejected: 1
```

## H. Runtime Diagnostic Result

Created and ran:

```text
php scripts/phase_2/youngo_payment_transaction_block_1_runtime_test.php
```

Result: PASS.

Runtime fixture:

- Learner: user `8`, `qa.learner@youngo.local`
- Course: course `9`, `Robotics and AI Explorers`
- Course mode: `subscription_and_purchase`
- Amount: `1000.00 EGP`
- Diagnostic order was created through `Youngo_checkout_model`.
- Diagnostic order was moved to `pending_gateway`, then `awaiting_webhook`.

Fixture processing results:

- Success fixture: `fixture_webhook_processed`, transaction `verified`.
- Duplicate fixture: `fixture_duplicate_detected`, no new row.
- Failed fixture: `fixture_webhook_processed`, transaction `rejected`.
- Malformed fixture: `payload_shape_invalid`, no row.
- Order was not marked `paid`.
- Order had only webhook/HMAC metadata updated during the diagnostic.

## I. DB Write/Cleanup Summary

Controlled local diagnostic writes:

- One diagnostic checkout order was created.
- Two diagnostic transaction rows were created.
- No rows were left behind.

Protected counts before:

```text
youngo_checkout_orders: 0
youngo_payment_transactions: 0
youngo_course_access: 0
youngo_user_subscriptions: 0
youngo_manual_grants: 0
youngo_coupon_usages: 0
payment: 0
enrol: 1
watch_histories: 0
watched_duration: 0
```

Protected counts after cleanup:

```text
youngo_checkout_orders: 0
youngo_payment_transactions: 0
youngo_course_access: 0
youngo_user_subscriptions: 0
youngo_manual_grants: 0
youngo_coupon_usages: 0
payment: 0
enrol: 1
watch_histories: 0
watched_duration: 0
```

Cleanup:

- Diagnostic transaction rows were deleted by checkout order ID.
- Diagnostic checkout order was deleted by exact ID/reference.
- Checkout and transaction auto-increments were reset to `1` because both tables were empty before the test.

## J. Public Route Fail-Closed Result

The public route remains:

```text
payment/paymob/webhook -> youngo_payment_webhook/paymob
```

Safety result:

- `Youngo_payment_webhook` is still POST-only.
- Default config still has `webhook_testing_enabled = false`.
- Public route still returns `webhook_testing_disabled` by default.
- Public route is not wired to `Youngo_payment_model`.
- Public route is not wired to `Youngo_paymob_fixture_processor`.
- Public route performs no DB writes.
- Public route does not issue entitlements.

## K. What Was Not Changed

- No deployment.
- No push.
- No live cPanel/server access.
- No live DB access.
- No real payment activation.
- No real credentials.
- No printed secrets.
- No Root Admin changes.
- No public checkout CTA exposure.
- No Paymob network requests.
- No real Paymob intentions.
- No entitlement issuance.
- No legacy gateway DB row changes.
- No persistent checkout/payment rows.
- No real order was marked paid.
- No legacy `payment` or `enrol` rows were created.
- No public webhook DB processing was enabled.

## L. Remaining Risks/Blockers

- HMAC field order still needs confirmation against actual Paymob sandbox callbacks before production use.
- This phase does not perform transaction inquiry/reconciliation.
- The public webhook route remains disabled and has not been tested through an external HTTPS tunnel.
- Order `paid` transition remains intentionally unimplemented.
- Entitlement issuance remains deferred.
- Real sandbox values, tunnel URL, and owner-approved test flow are still required before Paymob sandbox E2E.

## M. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.ORDER.STATUS.1 - Verified-payment order status transition planning and local-only implementation
```

Suggested scope:

- Add a guarded order transition from `awaiting_webhook` to `paid` for verified fixture-only transactions.
- Keep public route disabled.
- Require backup before diagnostic writes.
- Do not issue entitlements yet.
- Do not call Paymob.
- Keep public checkout CTAs suppressed.

## N. Git Status

Validation:

```text
php -l application/models/Youngo_payment_model.php
php -l application/libraries/Youngo_paymob_fixture_processor.php
php -l scripts/phase_2/youngo_payment_transaction_block_1_runtime_test.php
php -l scripts/phase_2/youngo_payment_order_1_diagnostic.php
php scripts/phase_2/youngo_payment_transaction_block_1_runtime_test.php
php scripts/phase_2/youngo_payment_webhook_route_1_diagnostic.php
php scripts/phase_2/youngo_payment_webhook_1_diagnostic.php
php scripts/phase_2/youngo_payment_paymob_adapter_1_diagnostic.php
php scripts/phase_2/youngo_payment_order_1_diagnostic.php
php scripts/phase_2/youngo_payment_schema_1_diagnostic.php
git diff --check
```

Result: PASS.

`git diff --check` reported only a CRLF normalization warning for `scripts/phase_2/youngo_payment_order_1_diagnostic.php`; no whitespace errors were found.

Final status:

```text
 M scripts/phase_2/youngo_payment_order_1_diagnostic.php
?? application/libraries/Youngo_paymob_fixture_processor.php
?? application/models/Youngo_payment_model.php
?? docs/qa/youngo_payment_transaction_block_1_report.md
?? scripts/phase_2/fixtures/paymob/duplicate_success_payload.json
?? scripts/phase_2/fixtures/paymob/failed_payment_payload.json
?? scripts/phase_2/fixtures/paymob/malformed_payload.json
?? scripts/phase_2/fixtures/paymob/success_verified_payload.json
?? scripts/phase_2/youngo_payment_transaction_block_1_runtime_test.php
```
