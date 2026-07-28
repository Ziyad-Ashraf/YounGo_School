# PAYMENT.PAYMOB.SANDBOX.INTENTION.1 - Controlled Hybrid Sandbox Intention Implementation

Date: 2026-07-23

Scope: implement the first controlled Paymob sandbox Intention/Unified Checkout path using hybrid config readiness. Non-private Paymob values are read from the dedicated YounGo dashboard DB table; private Paymob values are read only from ignored local/server config. No deployment, push, commit, real private values in Git, private values printed, private DB storage, `encryption_key` change, production payment enablement, production CTA exposure, Root Admin modification, legacy `payment_gateways` use, legacy `payment`/`enrol` writes, or return-URL entitlement issuance was performed.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
207fa95 Plan hybrid YounGo Paymob sandbox intention flow
81e6197 Add hybrid YounGo Paymob private config readiness
179bc01 Plan YounGo payment private value handling
73846af QA YounGo Paymob config audit panel
8b7fa78 Wire YounGo Paymob config saves to audit logs
8351916 Add YounGo Paymob config audit schema
9360b74 Fix YounGo payment DB access regressions
3271d7b Fix YounGo Paymob settings HTTP DB access
41dd2f4 QA Paymob dashboard save form blocker
2dfcf0e Add non-private YounGo Paymob dashboard save flow
```

The expected branch, clean starting worktree, and latest `PAYMENT.PAYMOB.SANDBOX.INTENTION.PLAN.2` commit were confirmed.

## B. Backup Created

Fresh local DB backup created before diagnostic DB writes:

```text
Path: D:\Work\YounGo\backups\youngo_school_before_paymob_sandbox_intention_1_2026_07_23_011129.sql
Size: 623870 bytes
SHA256: FCAE11644C3B7ACFF9574AD3C3F2997435A6B9F8C579A223EF3F7BD7D591EC76
```

The backup used local DB configuration. No live/cPanel DB was touched.

## C. Files Inspected

Reports:

- `docs/qa/youngo_payment_paymob_sandbox_intention_plan_2_report.md`
- `docs/qa/youngo_payment_secrets_hybrid_config_1_report.md`
- `docs/qa/youngo_payment_checkout_cta_local_ui_qa_1_report.md`
- `docs/qa/youngo_payment_checkout_local_block_1_report.md`
- `docs/qa/youngo_payment_entitlement_block_1_report.md`
- `docs/qa/youngo_payment_config_1_paymob_research_and_sandbox_plan.md`
- `docs/qa/youngo_payment_paymob_sandbox_plan_1_report.md`

Source/config:

- `application/libraries/Youngo_paymob_config.php`
- `application/libraries/Youngo_paymob_adapter.php`
- `application/controllers/Youngo_checkout.php`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_payment_config_model.php`
- `application/views/frontend/youngo/checkout_order.php`
- `application/config/youngo_paymob.php`
- `application/config/youngo_paymob.local.example.php`
- `scripts/phase_2/payment_config_2_youngo_payment_schema_proposed.sql`
- `.gitignore`

Project guardrails were also checked through the required project context/planning/rules files.

## D. Files Changed

Updated:

- `application/config/youngo_paymob.php`
- `application/config/youngo_paymob.local.example.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/libraries/Youngo_paymob_adapter.php`
- `application/controllers/Youngo_checkout.php`
- `application/models/Youngo_checkout_model.php`
- `application/views/frontend/youngo/checkout_order.php`
- `scripts/phase_2/youngo_payment_checkout_local_block_1_runtime_test.php`

Created:

- `scripts/phase_2/youngo_payment_paymob_sandbox_intention_1_runtime_test.php`
- `docs/qa/youngo_payment_paymob_sandbox_intention_1_report.md`

## E. Gate/Fail-Closed Behavior

Sandbox Intention execution is now gated by `Youngo_paymob_adapter::get_sandbox_readiness()` and fails closed unless all of these are true:

- mode is `sandbox`;
- currency is `EGP`;
- `enabled = true`;
- `network_enabled = true`;
- `sandbox_network_testing_enabled = true`;
- `checkout_routes_enabled = true`;
- `checkout_local_testing_enabled = true`;
- dashboard DB non-private config row exists;
- dashboard DB config has numeric EGP card integration ID;
- dashboard DB config has Paymob API and checkout base URLs on the allowed `accept.paymob.com` host;
- dashboard DB config has return and notification URLs;
- ignored local/server config supplies runtime-only `secret_key`, `public_key`, and `hmac_secret`;
- order is EGP, positive amount, and has a safe order reference.

Fail-closed reason codes include:

- `paymob_network_disabled_in_this_phase`
- `paymob_network_must_remain_disabled`
- `blocked_missing_dashboard_config`
- `blocked_missing_private_config`
- `paymob_sandbox_gates_not_ready`

The runtime diagnostic verified missing dashboard config and missing private config fail closed with explicit safe codes.

## F. Adapter Implementation Summary

`Youngo_paymob_adapter` now supports:

- dashboard non-private config injection;
- `get_sandbox_readiness()`;
- `create_sandbox_intention($order, $customer)`;
- Paymob Create Intention endpoint construction for `POST /v1/intention/`;
- `Authorization: Token <runtime secret>` from ignored local/server config only;
- curl-only HTTP execution with connect timeout, request timeout, SSL verification, JSON request, and JSON response parsing;
- safe response parsing for provider intention/order references;
- Unified Checkout URL construction from public key plus returned client secret;
- redacted response summaries;
- structured ok/error arrays.

Private values are not returned in diagnostics. The full secret key, HMAC secret, authorization header value, and raw client secret are not stored in DB or printed by diagnostics/reports.

## G. Checkout/Order Page Behavior

`Youngo_checkout` now:

- loads the dedicated Paymob dashboard config model;
- passes DB non-private config and ignored config reader state into the adapter;
- keeps local checkout routes disabled by default;
- keeps no-network local checkout behavior working when `network_enabled=false`;
- allows sandbox network mode only when `network_enabled=true` and `sandbox_network_testing_enabled=true`;
- creates/reuses a local checkout order through `Youngo_checkout_model`;
- attempts sandbox Intention creation only after a valid learner/course/order exists and the network flag is explicit;
- updates successful sandbox orders through `Youngo_checkout_model::mark_awaiting_webhook_from_paymob_intention()`;
- keeps return/status routes safe and UX-only;
- does not issue entitlement from start/order/status/return.

`checkout_order.php` now shows:

- order reference;
- amount and EGP currency;
- course title;
- order status;
- gateway state;
- local/sandbox state;
- Paymob sandbox checkout action only when a checkout URL is generated;
- no gateway selector;
- no private Paymob values.

## H. Sandbox Execution Result Or Blocked Reason

Actual local sandbox execution result:

```text
blocked_paymob_network_disabled_in_this_phase
```

Reason:

- tracked defaults remain disabled;
- no dashboard Paymob sandbox non-private config row exists locally after diagnostics;
- no ignored local/server private config was present after diagnostics;
- therefore no Paymob API request was made.

Diagnostic details:

```text
actual_readiness_code: paymob_network_disabled_in_this_phase
dashboard_config_exists: false
local_override_loaded: false
```

The diagnostic also simulated:

- missing dashboard config: `blocked_missing_dashboard_config`;
- missing private config with dashboard placeholders present: `blocked_missing_private_config`.

## I. DB Cleanup

New sandbox Intention diagnostic:

- made no DB write in the actual local state because execution was blocked before order creation;
- confirmed protected counts were restored/unchanged:

```text
youngo_checkout_orders       0 -> 0
youngo_payment_transactions  0 -> 0
youngo_course_access         0 -> 0
payment                     0 -> 0
enrol                       1 -> 1
```

Regression diagnostics:

- `youngo_payment_checkout_local_block_1_runtime_test.php` created one diagnostic checkout order, deleted it, and restored counts.
- `youngo_payment_secrets_hybrid_config_1_diagnostic.php` created a temporary non-private config row and fake ignored local config, then restored counts and removed the ignored local config.

No persistent diagnostic checkout, transaction, entitlement, payment, enrol, config, or ignored local config rows/files were left by validation.

## J. Public/Default Safety

Confirmed:

- tracked defaults still have `enabled=false`;
- tracked defaults still have `network_enabled=false`;
- tracked defaults still have `sandbox_network_testing_enabled=false`;
- tracked defaults still have `checkout_cta_enabled=false`;
- `application/config/youngo_paymob.local.php` remains ignored and was absent after diagnostics;
- public CTA files were not changed;
- no public `youngo/checkout/start` links were added to listing, detail, wishlist, or home surfaces in this phase;
- the checkout controller has no direct HTTP execution primitives;
- Paymob HTTP execution lives only in the sandbox-gated adapter method;
- `payment_gateways` is not used;
- legacy `payment` and `enrol` rows were not written;
- return URL remains UX-only and does not mutate payment/access state.

## K. What Was Not Changed

- No deployment.
- No push.
- No commit.
- No live/cPanel access.
- No live DB access.
- No real Paymob values added to Git.
- No private Paymob values printed.
- No private Paymob values saved to DB.
- No `encryption_key` change.
- No production payment activation.
- No production checkout CTA exposure.
- No Root Admin modification.
- No legacy `payment_gateways` usage.
- No legacy `payment` writes.
- No legacy `enrol` writes.
- No entitlement issuance from checkout start/order/status/return.

## L. Remaining Risks/Blockers

- Real sandbox execution is still blocked until the owner supplies sandbox values outside Git/reports and non-private dashboard config is entered.
- A public HTTPS tunnel is still required for real Paymob webhook callback QA against local `school.local`.
- The first real Paymob response shape must be checked during sandbox execution; the adapter handles common `id`, `intention_id`, `order_id`, nested `order.id`, and `client_secret` fields, but account-specific response shape may vary.
- Unified Checkout URL construction uses the documented public key plus returned client secret model and restricts host to `accept.paymob.com`; this should be confirmed against the actual sandbox merchant account during the first real attempt.
- Webhook/HMAC real-payload QA remains a later phase and is still source-of-truth for paid/failed state.
- Transaction inquiry is still deferred.

## M. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.SANDBOX.INTENTION.UI.QA.1 - First Real Sandbox Intention Browser QA
```

Prerequisites:

- Owner enters non-private sandbox config through `/admin/youngo/payment-settings`.
- Owner provides private sandbox values only through ignored `application/config/youngo_paymob.local.php` or server config.
- Local flags are explicitly enabled in ignored config only.
- Public HTTPS webhook tunnel strategy is prepared for later webhook QA.

## N. Git Status

Final status after this phase:

```text
 M application/config/youngo_paymob.local.example.php
 M application/config/youngo_paymob.php
 M application/controllers/Youngo_checkout.php
 M application/libraries/Youngo_paymob_adapter.php
 M application/libraries/Youngo_paymob_config.php
 M application/models/Youngo_checkout_model.php
 M application/views/frontend/youngo/checkout_order.php
 M scripts/phase_2/youngo_payment_checkout_local_block_1_runtime_test.php
?? docs/qa/youngo_payment_paymob_sandbox_intention_1_report.md
?? scripts/phase_2/youngo_payment_paymob_sandbox_intention_1_runtime_test.php
```

Validation commands run:

```text
php -l application/config/youngo_paymob.php
php -l application/config/youngo_paymob.local.example.php
php -l application/libraries/Youngo_paymob_config.php
php -l application/libraries/Youngo_paymob_adapter.php
php -l application/controllers/Youngo_checkout.php
php -l application/models/Youngo_checkout_model.php
php -l application/views/frontend/youngo/checkout_order.php
php -l scripts/phase_2/youngo_payment_checkout_local_block_1_runtime_test.php
php -l scripts/phase_2/youngo_payment_paymob_sandbox_intention_1_runtime_test.php
php scripts/phase_2/youngo_payment_paymob_sandbox_intention_1_runtime_test.php
php scripts/phase_2/youngo_payment_checkout_local_block_1_runtime_test.php
php scripts/phase_2/youngo_payment_secrets_hybrid_config_1_diagnostic.php
git diff --check
git status --short
```

All listed validation commands passed. `git diff --check` emitted line-ending normalization warnings only; no whitespace errors were reported.
