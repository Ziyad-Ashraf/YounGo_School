# PAYMENT.ORDER.2 - Controlled Local Checkout Order Runtime Test Report

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
be4e139 Add YounGo checkout order service foundation
685494a Add non-secret YounGo Paymob config foundation
f172db6 Apply YounGo local payment schema
6111cf7 Plan YounGo payment implementation phases
20c21f0 Design YounGo payment architecture and schema
dd601bd Plan YounGo Paymob sandbox integration
dda25bc Harden YounGo legacy payment entry points
a2e1f81 Document YounGo payment DB baseline
727ef72 Audit YounGo payment flow with local DB
e5f6a5f Document live cPanel client demo handoff
```

The expected baseline was confirmed: branch `analysis/cms-audit`, clean worktree, and latest commit `be4e139` includes PAYMENT.ORDER.1.

## B. Backup Created

A fresh local DB backup was created before any diagnostic write.

```text
Path: D:\Work\YounGo\backups\youngo_school_before_payment_order_2_runtime_test_2026_07_21_002421.sql
Size: 935748 bytes
SHA256: 0b81dda7efaec4e68b5824168be1d02fc439855b67c816b7b98b139ad01a227f
Tables dumped: 65
```

Backup notes:

- The backup is outside Git.
- No credentials were printed.
- `mysqldump.exe` was present but could not connect through the local TCP/socket path, so the backup was created through a PHP `mysqli` SQL dump using the same local DB configuration.

## C. Fixture Selected

Safe local fixture:

- Learner: user `8`, `qa.learner@youngo.local`
- Role: learner role `2`
- Root Admin: not used
- Course: course `9`, `Robotics and AI Explorers`
- Course mode: `subscription_and_purchase`
- Course status: `active`
- Amount tested: `1000.00`
- Currency tested: `EGP`

No user or course records were created or modified.

## D. Runtime Test Steps

Created:

```text
scripts/phase_2/youngo_payment_order_2_runtime_test.php
```

The runtime script:

- Loads CodeIgniter's standalone database layer.
- Loads `Youngo_paymob_config`.
- Loads `Youngo_checkout_model`.
- Confirms default payment config remains disabled, sandbox, and EGP.
- Confirms the checkout schema is ready.
- Selects a safe existing learner/course fixture.
- Records protected table counts.
- Calls `can_start_checkout()`.
- Creates one diagnostic draft order through `create_draft_order()`.
- Verifies reference format and uniqueness.
- Verifies lookup by ID and order reference.
- Verifies amount/currency and entitlement-not-started state.
- Transitions `draft -> pending_gateway -> awaiting_webhook`.
- Verifies an invalid transition back to pending gateway is rejected.
- Cancels the order for cleanup.
- Deletes the diagnostic order by ID/reference.
- Resets `youngo_checkout_orders` auto-increment to `1` when the table was empty before the test.
- Confirms protected table counts returned to baseline.

## E. Order Creation/Lookup/Transition Result

Final runtime command:

```text
php scripts/phase_2/youngo_payment_order_2_runtime_test.php
```

Result: PASS.

Final validation-run diagnostic order details:

- Diagnostic order ID: `1`
- Diagnostic order reference: `YGO-20260721002814-E1A6EDE0B26D`
- Create result: `draft_order_created`
- Pending transition result: `order_pending_gateway`
- Awaiting webhook transition result: `order_awaiting_webhook`
- Invalid transition result: `invalid_order_status`
- Cleanup cancel result: `order_cancelled`

The order was successfully looked up by both ID and order reference. It stored `EGP`, `1000.00`, and `100000` cents. Entitlement fields remained not started.

## F. Cleanup Result

Cleanup result: PASS.

- The diagnostic order was cancelled first for status-path verification.
- The diagnostic order was then deleted by exact ID and reference.
- Because the table was empty before the test, `AUTO_INCREMENT` was reset to `1`.
- Final `youngo_checkout_orders` count returned to `0`.

## G. Protected Table Count Checks

Counts before final runtime test:

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

Counts after cleanup:

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

No payment transaction, legacy payment, legacy enrolment, entitlement, manual grant, coupon usage, watch history, or watched duration rows were created.

## H. What Was Not Changed

- No deployment.
- No push.
- No live cPanel/server access.
- No live DB access.
- No real payment activation.
- No real credentials.
- No printed secrets.
- No Root Admin modification.
- No user/course modification.
- No public checkout CTA exposure.
- No Paymob API/network calls.
- No entitlement issuance.
- No legacy gateway DB row changes.
- No persistent diagnostic order data.

## I. Risks/Blockers

- This runtime test proves the local order service lifecycle only; it does not validate Paymob Intention API, Unified Checkout, webhook/HMAC handling, or return URL behavior.
- Subscription order creation remains deferred.
- Entitlement issuance after verified payment remains deferred.
- Future diagnostics that create rows should keep backup-first behavior and cleanup auto-increment metadata when testing empty tables.

## J. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.ADAPTER.1 - Add disabled Paymob adapter skeleton without network execution
```

Suggested scope:

- Add adapter method signatures and request builders only.
- Keep network disabled by config.
- Do not expose public checkout CTAs.
- Do not create Paymob intentions yet.
- Do not issue entitlements yet.

## K. Git Status

Expected final status before commit:

```text
 M application/models/Youngo_checkout_model.php
?? docs/qa/youngo_payment_order_2_runtime_test_report.md
?? scripts/phase_2/youngo_payment_order_2_runtime_test.php
```
