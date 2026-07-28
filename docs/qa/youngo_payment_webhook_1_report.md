# PAYMENT.WEBHOOK.1 - Paymob Webhook and HMAC Verification Skeleton Report

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
4fd5f8c Add disabled YounGo Paymob adapter skeleton
fda6f91 Runtime test YounGo checkout order service
be4e139 Add YounGo checkout order service foundation
685494a Add non-secret YounGo Paymob config foundation
f172db6 Apply YounGo local payment schema
6111cf7 Plan YounGo payment implementation phases
20c21f0 Design YounGo payment architecture and schema
dd601bd Plan YounGo Paymob sandbox integration
dda25bc Harden YounGo legacy payment entry points
a2e1f81 Document YounGo payment DB baseline
```

The expected baseline was confirmed: branch `analysis/cms-audit`, clean worktree, and latest commit `4fd5f8c` includes PAYMENT.PAYMOB.ADAPTER.1.

## B. Files Inspected

- `docs/qa/youngo_payment_config_1_paymob_research_and_sandbox_plan.md`
- `docs/qa/youngo_payment_config_2_architecture_schema_design.md`
- `docs/qa/youngo_payment_config_3_implementation_plan.md`
- `docs/qa/youngo_payment_paymob_adapter_1_report.md`
- `application/libraries/Youngo_paymob_config.php`
- `application/libraries/Youngo_paymob_adapter.php`
- `application/models/Youngo_checkout_model.php`
- `application/config/routes.php`
- `application/controllers/Payment.php`
- `application/controllers/Youngo_manual_grants.php`
- `application/controllers/Youngo_role_assignments.php`
- `application/controllers/Youngo_subscription_plans.php`

## C. Files Changed

Created:

- `application/libraries/Youngo_paymob_webhook.php`
- `scripts/phase_2/fixtures/paymob/valid_shape_success_payload.json`
- `scripts/phase_2/fixtures/paymob/missing_hmac_payload.json`
- `scripts/phase_2/fixtures/paymob/invalid_shape_payload.json`
- `scripts/phase_2/youngo_payment_webhook_1_diagnostic.php`
- `docs/qa/youngo_payment_webhook_1_report.md`

## D. Webhook/HMAC Skeleton Behavior

`Youngo_paymob_webhook` is a standalone library for fixture-only Paymob webhook handling.

Implemented methods:

- `normalize_payload($payload)`
- `get_hmac_source_fields($payload)`
- `calculate_hmac($payload, $hmac_secret)`
- `verify_hmac($payload, $provided_hmac)`
- `classify_event($payload)`
- `extract_gateway_refs($payload)`
- `get_safe_payload_summary($payload)`

Behavior:

- Normalizes array/object payloads.
- Supports the Paymob transaction callback HMAC field order used in current planning.
- Uses SHA-512 HMAC for deterministic fixture verification.
- Fails closed if the HMAC secret is missing.
- Fails closed if the provided HMAC is missing.
- Rejects incomplete payload shapes.
- Classifies transaction status from `success`, `pending`, `error_occured`, `is_voided`, and `is_refunded`.
- Extracts Paymob transaction/order/integration references.
- Redacts HMAC and PAN-like source data in safe summaries.
- Does not use DB, routes, controllers, network calls, or credentials.

## E. Fixture Payload Summary

Fixtures created under:

```text
scripts/phase_2/fixtures/paymob/
```

Fixtures:

- `valid_shape_success_payload.json`
  - Fake successful card transaction shape.
  - Fake transaction ID `987654321`.
  - Fake Paymob order ID `24681012`.
  - Fake merchant order reference `YGO-DIAGNOSTIC-WEBHOOK-1`.
  - Fake integration ID `123456`.
  - Amount `100000` cents / piasters.
  - Currency `EGP`.
  - HMAC generated with fake diagnostic-only secret `youngo-paymob-webhook-diagnostic-secret`.

- `missing_hmac_payload.json`
  - Same safe transaction shape class, but without an `hmac` field.
  - Used to prove missing HMAC fails closed.

- `invalid_shape_payload.json`
  - Intentionally incomplete payload.
  - Used to prove required transaction fields are rejected.

No fixture contains real credentials, real Paymob IDs, real card details, or live URLs.

## F. Diagnostic Result

Created and ran:

```text
php scripts/phase_2/youngo_payment_webhook_1_diagnostic.php
```

Result: PASS.

Key checks passed:

- Webhook library loads.
- Fixture payloads load.
- Normalization works.
- HMAC source fields build deterministically.
- Valid fake fixture HMAC calculates and verifies with the fake diagnostic secret.
- Missing local HMAC secret fails closed.
- Missing HMAC payload fails closed.
- Invalid payload shape is rejected.
- Event classifies as `success`.
- Gateway references are extracted.
- Safe summary redacts HMAC and PAN-like values.
- Webhook library has no network code.
- Webhook library has no DB write code.
- No Paymob webhook route was added.
- Public checkout CTA boundary markers remain present.
- Protected DB table counts stayed unchanged.

Protected counts before and after the diagnostic:

```text
youngo_checkout_orders: 0
youngo_payment_transactions: 0
youngo_course_access: 0
youngo_user_subscriptions: 0
youngo_manual_grants: 0
youngo_coupon_usages: 0
payment: 0
enrol: 1
```

## G. Route/Controller Decision

No route or controller was added in this phase.

Reason:

- `Payment.php` is unsafe for Paymob webhooks because its constructor requires learner session payment state and redirects when that state is absent.
- Adding a new public webhook controller/route would expose a reachable endpoint before endpoint gating, request parsing, transaction recording, response semantics, and local tunnel strategy are approved.
- The current phase goal can be met safely through a library plus fixture diagnostics.

Future webhook route work should use a new webhook-safe controller, not `Payment.php`, and should remain disabled until local fixture tests, config flags, and owner-approved sandbox callback strategy are ready.

## H. What Was Not Changed

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
- No checkout/payment/order DB rows.
- No payment transaction rows.
- No entitlement issuance.
- No legacy gateway DB row changes.
- No route/controller/view changes.

## I. Risks/Blockers

- The HMAC field order is implemented from current Paymob transaction callback planning, but must be rechecked against the exact sandbox callback payload before production use.
- No real Paymob webhook request has been received.
- No webhook route/controller exists yet.
- No transaction rows are recorded yet.
- No order status is updated yet.
- Entitlement issuance remains deferred.
- Owner-provided sandbox HMAC secret and public HTTPS tunnel strategy are still required for sandbox QA.

## J. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.WEBHOOK.ROUTE.1 - Add disabled webhook-safe controller and route
```

Suggested scope:

- Add a new `Youngo_paymob` webhook-safe controller.
- Add a disabled route such as `/payment/paymob/webhook`.
- Return safe disabled JSON until config explicitly enables local webhook testing.
- Do not write transaction rows unless a backup-first diagnostic permits it.
- Do not issue entitlements.
- Do not expose public checkout CTAs.

## K. Git Status

Expected final status before commit:

```text
?? application/libraries/Youngo_paymob_webhook.php
?? docs/qa/youngo_payment_webhook_1_report.md
?? scripts/phase_2/fixtures/paymob/invalid_shape_payload.json
?? scripts/phase_2/fixtures/paymob/missing_hmac_payload.json
?? scripts/phase_2/fixtures/paymob/valid_shape_success_payload.json
?? scripts/phase_2/youngo_payment_webhook_1_diagnostic.php
```
