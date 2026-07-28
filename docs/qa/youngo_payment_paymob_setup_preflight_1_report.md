# PAYMENT.PAYMOB.CONFIG.SETUP.PREFLIGHT.1 - Hybrid Paymob Sandbox Setup Checklist and CTA/Readiness Alignment

Date: 2026-07-23

Scope: source alignment, diagnostics, and report only. No deployment, push, DB schema change, SQL execution, real Paymob value entry, private value output, private DB storage, encryption key change, Paymob network request, production CTA exposure, Root Admin modification, legacy `payment_gateways` usage, or legacy `payment`/`enrol` write was performed.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
a7ff09a Audit YounGo payment work against legacy system
769af57 QA blocked YounGo Paymob sandbox readiness
125023c Add controlled YounGo Paymob sandbox intention flow
207fa95 Plan hybrid YounGo Paymob sandbox intention flow
81e6197 Add hybrid YounGo Paymob private config readiness
179bc01 Plan YounGo payment private value handling
73846af QA YounGo Paymob config audit panel
8b7fa78 Wire YounGo Paymob config saves to audit logs
8351916 Add YounGo Paymob config audit schema
9360b74 Fix YounGo payment DB access regressions
```

The expected branch and clean starting worktree were confirmed. The latest commit includes `PAYMENT.RECONCILE.AUDIT.1`.

## B. Files Inspected

Project and planning context:

- `YOUNGO_PROJECT_CONTEXT.md`
- `docs/design/youngo_style_direction.md`
- `docs/planning/README.md`
- `docs/planning/youngo_master_plan_v2.md`
- `docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`
- `docs/agents/implementation_rules.md`
- `docs/reference/README.md`

Payment reports:

- `docs/qa/youngo_payment_reconcile_audit_1_report.md`
- `docs/qa/youngo_payment_secrets_hybrid_config_1_report.md`
- `docs/qa/youngo_payment_paymob_sandbox_intention_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_save_nonprivate_1_report.md`
- `docs/qa/youngo_payment_checkout_cta_local_ui_qa_1_report.md`

Source files:

- `application/helpers/youngo_checkout_cta_helper.php`
- `application/controllers/Youngo_checkout.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/libraries/Youngo_paymob_adapter.php`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_payment_config_model.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `application/views/frontend/youngo/course_page.php`
- `application/views/frontend/youngo/checkout_order.php`
- `application/config/youngo_paymob.php`
- `application/config/youngo_paymob.local.example.php`

## C. Files Changed

- `application/helpers/youngo_checkout_cta_helper.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `scripts/phase_2/youngo_payment_paymob_setup_preflight_1_diagnostic.php`
- `docs/qa/youngo_payment_paymob_setup_preflight_1_report.md`

## D. CTA/Network Gate Alignment

The CTA helper previously blocked any `network_enabled = true` state with `paymob_network_must_remain_disabled`. That was safe before the controlled sandbox Intention phase, but it no longer matched the adapter/controller gate model.

The helper now:

- Remains hidden by default.
- Allows the existing local no-network CTA path when `checkout_cta_enabled`, `checkout_routes_enabled`, `checkout_local_testing_enabled`, sandbox mode, EGP, course eligibility, learner eligibility, and access checks pass.
- Allows a sandbox-network CTA decision only when `network_enabled`, `enabled`, and `sandbox_network_testing_enabled` are true and Paymob sandbox readiness passes.
- Fails closed for live mode, non-EGP currency, missing sandbox network gate, missing dashboard/private readiness, or unavailable readiness checks.
- Returns only `/youngo/checkout/start/{course_id}` as a checkout target.
- Never returns legacy cart/payment/gateway URLs.

No production/live network CTA path was opened.

## E. Naming/Readiness Wording Alignment

Dashboard wording now explicitly states:

- `/admin/youngo/payment-settings` is a Paymob setup/readiness page, not a full secret-entry wizard.
- Non-private Paymob sandbox settings are stored in the dedicated YounGo DB config table.
- Private Paymob values must come from ignored local/server config in this phase.
- Private DB storage is blocked while CodeIgniter `encryption_key` is empty.
- Sandbox tests are not ready until all required fields and explicit local gates pass.
- Production payment activation and production CTA exposure remain blocked.

Naming decision:

- Canonical dashboard DB field: `notification_url`.
- Meaning: Paymob payment notification URL / YounGo webhook endpoint.
- Local/server config alias: `webhook_url`.

This keeps the existing schema stable while clarifying that `notification_url` and `webhook_url` refer to the same webhook endpoint concept in different config sources.

## F. Public Key/Private Source Labeling

The dashboard now labels public key presence as `Public key (server config only)`.

Current phase behavior:

- `public_key` is treated as server-config-only/private-like for setup safety.
- `secret_key` and `hmac_secret` are also server-config-only.
- The dashboard does not render editable inputs for these fields.
- Summaries show only safe presence states such as `server_config_required`, `missing`, `configured_redacted`, or `db_private_storage_blocked`.

No DB-backed public/private Paymob value save was added.

## G. Eligibility Duplication Decision

Potential duplication remains between:

- CTA helper course/user/access/amount checks.
- Checkout controller/model order creation eligibility checks.

Decision for this phase: keep the duplication as acceptable defensive validation.

Reason:

- The helper decides whether to show a UI entry point.
- The checkout controller/model enforce the server-side order boundary.
- Extracting a shared eligibility service is useful later, but it is not low-risk enough for this preflight alignment phase.

Recommended later refactor: introduce a small shared YounGo checkout eligibility helper/model only after sandbox browser QA proves the route and dashboard setup flow are stable.

## H. Diagnostic Result

Created:

```text
scripts/phase_2/youngo_payment_paymob_setup_preflight_1_diagnostic.php
```

Diagnostic behavior:

- Uses static and fixture checks only.
- Does not open a DB connection.
- Does not perform Paymob network requests.
- Does not print credentials or private values.
- Simulates default hidden CTA, local no-network CTA, sandbox-network-ready CTA, missing sandbox network gate, missing readiness, and live-mode blocking.
- Checks dashboard readiness terminology.
- Confirms private fields are not editable in the dashboard form.
- Confirms no new network call patterns outside the existing adapter.
- Confirms no new `payment_gateways` dependency in the YounGo helper/controller/model paths.

Validation result:

```text
php scripts/phase_2/youngo_payment_paymob_setup_preflight_1_diagnostic.php
ok: true
failed_checks: []
```

Additional validation:

```text
php -l application/helpers/youngo_checkout_cta_helper.php
No syntax errors detected

php -l application/controllers/Youngo_payment_settings.php
No syntax errors detected

php -l application/views/backend/admin/youngo_payment_settings.php
No syntax errors detected

php -l scripts/phase_2/youngo_payment_paymob_setup_preflight_1_diagnostic.php
No syntax errors detected

php scripts/phase_2/youngo_payment_checkout_cta_local_block_1_diagnostic.php
ok: true
failed_checks: []

php scripts/phase_2/youngo_payment_paymob_sandbox_intention_1_runtime_test.php
ok: true
failed_checks: []
actual_readiness_code: paymob_network_disabled_in_this_phase
protected counts unchanged:
youngo_checkout_orders: 0
youngo_payment_transactions: 0
youngo_course_access: 0
payment: 0
enrol: 1

git diff --check
passed; emitted only LF-to-CRLF working-copy warnings for touched tracked files
```

## I. What Was Not Changed

- No DB schema was modified.
- No SQL was executed.
- No Paymob request was performed.
- No real Paymob values were added.
- No private values were printed or saved to DB.
- `encryption_key` was not changed.
- Production defaults remain disabled.
- Production checkout CTAs were not exposed.
- Root Admin data was not modified.
- Legacy `payment_gateways` was not used for YounGo Paymob config.
- Legacy `payment` and `enrol` tables were not written.
- No entitlement issuance behavior changed.

## J. Remaining Risks/Blockers

- Real sandbox browser QA remains blocked until the owner supplies sandbox values through ignored local/server config and non-private dashboard DB config is filled.
- The dashboard remains a setup/readiness page, not a complete client-facing wizard.
- Private DB storage remains blocked until key management is explicitly approved.
- The public key is still treated as server-config-only in this hybrid phase.
- CTA/helper and checkout server validation remain intentionally duplicated for safety.
- Sandbox webhook/tunnel and real HMAC payload QA are still pending.

## K. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.SANDBOX.READINESS.UI.QA.1
```

Purpose:

- Use placeholder-safe non-private dashboard values and ignored local/server config presence only.
- Browser-check `/admin/youngo/payment-settings` readiness wording and state.
- Confirm default production gates remain disabled.
- Stop before Paymob network execution unless all required real sandbox values are present and explicitly approved for the sandbox Intention QA phase.

Only after that should the project proceed to a real sandbox network/browser test phase.

## L. Git Status

Expected status after this report:

```text
M application/controllers/Youngo_payment_settings.php
M application/helpers/youngo_checkout_cta_helper.php
M application/views/backend/admin/youngo_payment_settings.php
A scripts/phase_2/youngo_payment_paymob_setup_preflight_1_diagnostic.php
A docs/qa/youngo_payment_paymob_setup_preflight_1_report.md
```
