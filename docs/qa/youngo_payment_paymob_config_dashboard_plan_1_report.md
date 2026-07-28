# PAYMENT.PAYMOB.CONFIG.DASHBOARD.PLAN.1 - Paymob Dashboard Configuration Plan

Date: 2026-07-22

Scope: planning only for a future dashboard-based Paymob configuration flow. No deployment, push, DB modification, SQL execution, real Paymob values, private value output, real payment enablement, Paymob network request, production checkout CTA exposure, Root Admin modification, or legacy payment/enrol write was performed.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
022f936 Plan YounGo Paymob sandbox execution
0d663c1 QA gated YounGo checkout CTA clickthrough
68b9b28 Add gated YounGo checkout CTA helper
bda9cb1 Plan YounGo checkout CTA exposure
b59eaee QA local YounGo checkout smoke flow
bcb01b7 Fix YounGo checkout HTTP DB access
92fd18e QA authenticated YounGo checkout start blocker
cf98079 QA disabled YounGo checkout route safety
c26a933 Add controlled local YounGo checkout flow
a943b64 Add disabled YounGo checkout route skeleton
```

The expected branch and clean starting worktree were confirmed. The latest commit includes `PAYMENT.CHECKOUT.PAYMOB.SANDBOX.PLAN.1`.

## B. Files Inspected

Required project guidance:

- `YOUNGO_PROJECT_CONTEXT.md`
- `docs/design/youngo_style_direction.md`
- `docs/planning/youngo_master_plan_v2.md`
- `docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`
- `docs/agents/implementation_rules.md`
- `docs/reference/README.md`

Required payment reports:

- `docs/qa/youngo_payment_paymob_sandbox_plan_1_report.md`
- `docs/qa/youngo_payment_config_file_1_report.md`
- `docs/qa/youngo_payment_checkout_cta_local_ui_qa_1_report.md`
- `docs/qa/youngo_payment_config_2_architecture_schema_design.md`

Source inspected:

- `application/config/youngo_paymob.php`
- `application/config/youngo_paymob.local.example.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/libraries/Youngo_paymob_adapter.php`
- `application/libraries/Youngo_paymob_webhook.php`
- `application/controllers/Youngo_checkout.php`
- `application/controllers/Youngo_payment_webhook.php`
- `application/controllers/Admin.php`
- `application/controllers/Youngo_manual_grants.php`
- `application/controllers/Youngo_subscription_plans.php`
- `application/controllers/Youngo_role_assignments.php`
- `application/models/Crud_model.php`
- `application/models/Payment_model.php`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_payment_model.php`
- `application/models/Youngo_entitlement_write_model.php`
- `application/helpers/common_helper.php`
- `application/helpers/youngo_capability_helper.php`
- `application/config/routes.php`
- `application/views/backend/admin/navigation.php`
- `application/views/backend/admin/payment_settings.php`
- `application/views/backend/index.php`

## C. Dashboard Configuration Rationale

Dashboard-based Paymob configuration is preferred for the later production handover because:

- Real Paymob credentials must not be hardcoded in tracked PHP config, committed reports, SQL files, screenshots, or diagnostics.
- A dashboard flow lets the owner/client enter and rotate values without developer file edits or Git churn.
- Masked fields can prevent accidental full-secret disclosure after values are saved.
- Readiness checks can separate "configured" from "enabled" and "network-enabled".
- Payment activation can remain explicit and staged: configured, sandbox-ready, sandbox network testing, then production approval later.
- The system can show missing field/errors to the owner without exposing private values.
- Sandbox and live values can be kept physically and logically separate.
- A dashboard model can enforce EGP, integration ID, callback URL, HMAC, and live-mode guardrails before any checkout CTA or network call is allowed.

The existing ignored local override remains useful for developer-only sandbox experiments, but it is not sufficient for client handover or production operation.

## D. Recommended Dashboard Location

Recommended future location:

```text
/admin/youngo/payment-settings
```

Recommended controller/view shape:

```text
application/controllers/Youngo_payment_settings.php
application/models/Youngo_payment_config_model.php
application/views/backend/admin/youngo_payment_settings.php
```

Recommended navigation placement:

- Add under the existing YounGo admin group in `application/views/backend/admin/navigation.php`.
- Show only when the current admin has the future payment-management capability or is the protected Root Admin.
- Do not place the first YounGo Paymob configuration UI inside the existing `admin/payment_settings` legacy gateway page.

Reasoning:

- `Admin::payment_settings()` is tied to Academy's inherited `payment_gateways` table and generic `settings` permission.
- `Crud_model::update_payment_settings()` stores arbitrary posted gateway keys as JSON in `payment_gateways.keys` and does not provide YounGo-specific redaction/readiness semantics.
- Legacy gateway rows are intentionally not the YounGo payment path because prior payment phases identified them as inherited, non-EGP, hidden/unsafe, and incompatible with the new YounGo checkout/order/webhook/entitlement source-of-truth flow.
- Existing YounGo admin tools already use dedicated controllers and routes under `/admin/youngo/...`, such as subscription plans, manual grants, and role assignments.

The future page should be a YounGo Payment Settings section, not a replacement for Academy global payment settings and not a live gateway activation page.

## E. Access Control Plan

Default access:

- Protected Root Admin/core owner only until owner explicitly approves broader access.
- If delegated later, introduce a specific capability such as `manage_payments` or `manage_payment_settings`.
- Do not rely on the broad legacy `settings` permission alone.
- Do not expose this page to learners, instructors, content-only roles, course managers, or support roles by default.

Recommended gate:

- Admin session required via existing `$this->user_model->check_session_data('admin')` convention.
- Load `youngo_capability_helper`.
- Require a payment-specific capability using `youngo_require_capability(...)`.
- Treat missing capability helper/model as access denied.
- Keep Root Admin protected and unchanged; do not downgrade, edit, or repurpose Root Admin.

Optional future delegation:

- A client Admin may receive payment-settings access only after owner approval and after the payment capability has been deliberately seeded and QA-tested.
- Delegate view-only and edit capabilities separately if audit/legal review requires a second-approver workflow.

## F. Field List

Store field names only. Do not store or print values in reports.

Core provider and mode:

```text
provider
mode
currency
enabled
network_enabled
sandbox_network_testing_enabled
webhook_testing_enabled
checkout_routes_enabled
checkout_local_testing_enabled
checkout_cta_enabled
live_mode_allowed
```

Paymob account and credential fields:

```text
public_key
secret_key
hmac_secret
card_integration_id_egp
api_base_url
checkout_base_url
return_url
notification_url
```

Optional/deferred fields if current Paymob account/docs require them:

```text
api_key
merchant_id
iframe_id
wallet_integration_id_egp
transaction_inquiry_enabled
tunnel_public_base_url
last_readiness_checked_at
last_sandbox_test_at
last_sandbox_test_status
updated_by_user_id
created_at
updated_at
```

Notes:

- `notification_url` should map to the public webhook URL used by Paymob.
- `return_url` should map to the learner UX return route.
- `api_base_url` and `checkout_base_url` should be constrained to approved Paymob sandbox/live hosts.
- `public_key` may be browser-facing only where Paymob Unified Checkout requires it.
- `secret_key`, `api_key`, `hmac_secret`, and any private merchant secret are server-side only.
- Runtime Paymob `client_secret` is not a dashboard configuration field.

## G. Safe Storage/Redaction Plan

Preferred storage:

- Add a dedicated YounGo payment provider configuration table in a future schema phase, for example `youngo_payment_provider_configs`.
- Keep provider config separate from:
  - legacy `payment_gateways`
  - legacy `payment`
  - legacy `enrol`
  - generic `settings`

Rationale for a dedicated table:

- The existing `payment_gateways.keys` JSON blob is broad, legacy-oriented, and displayed back as plain form input values in `payment_settings.php`.
- YounGo needs provider/mode-specific readiness checks, activation gates, redacted diagnostics, and audit metadata.
- A dedicated table can store sandbox and live profiles separately and prevent accidental live activation.

Private field handling:

- Mask private values after save, for example `configured_redacted`.
- Never display the full saved `secret_key`, `api_key`, or `hmac_secret`.
- Updating a private field should require entering a new value; blank input should mean "keep existing value".
- Clearing a private field should require an explicit clear action.
- Diagnostics should report only missing/configured/redacted state.
- Logs, reports, HTML responses, JSON status endpoints, browser screenshots, and Git diffs must not contain private values.

Encryption/key-management requirement:

- `application/config/config.php` currently has an empty CodeIgniter `encryption_key`.
- Before DB-backed secret storage is implemented, choose and document an encryption-at-rest approach.
- Recommended approach: application-level encryption with an encryption key supplied outside Git, outside reports, and outside DB dumps.
- If an external encryption key cannot be established safely, keep private values in ignored local/server config and use the dashboard only for non-secret readiness/status until a secure store is approved.

Backup/export implications:

- DB dumps containing encrypted secret rows are still sensitive artifacts.
- Any future export/report diagnostic must redact private fields.
- Production values should be entered on the target environment after deployment, not copied through Git.
- Sandbox and live records should be distinct. Copying sandbox values into live mode should be blocked.

Audit plan:

- Store `updated_by_user_id`, timestamps, changed field names, and redacted before/after presence states.
- Do not store previous secret values in audit logs.
- Consider a later two-step activation approval for production `enabled=true` and `network_enabled=true`.

## H. Readiness Checks

The dashboard should calculate readiness without performing Paymob network calls by default.

Base checks:

- Provider is `paymob`.
- Mode is either `sandbox` or `live`.
- Currency is exactly `EGP`.
- Amount multiplier is `100`.
- `card_integration_id_egp` is present and numeric.
- `return_url` is present and a valid URL.
- `notification_url` is present and a valid URL.
- `api_base_url` is present and allowed for the selected mode.
- `checkout_base_url` is present and allowed for the selected mode.
- `public_key` is present.
- `secret_key` is present.
- `hmac_secret` is present.

Sandbox readiness:

- `mode = sandbox`.
- Required sandbox credential fields are configured.
- `live_mode_allowed = false`.
- `sandbox_network_testing_enabled` remains false until explicitly enabled for a controlled QA phase.
- `checkout_cta_enabled` remains false unless local CTA QA explicitly enables it in a controlled scope.

Network readiness:

- `enabled = true`.
- `network_enabled = true`.
- In sandbox, `sandbox_network_testing_enabled = true`.
- In live mode, a later production approval flag is required; no current phase should allow live network execution.
- Webhook route `/payment/paymob/webhook` exists and remains POST-only.
- HMAC verification is configured.
- Return URL is treated as UX-only.

CTA readiness:

- `checkout_routes_enabled = true`.
- `checkout_cta_enabled = true`.
- Course must be YounGo-managed, purchase-compatible, not free, not subscription-only, active, priced in EGP, and positive amount.
- Learner must not already have active YounGo access.
- CTA target must be `/youngo/checkout/start/{course_id}` only.
- Legacy cart/payment/gateway routes must not be returned.

Production blockers:

- Any missing required private field.
- Currency not EGP.
- Live mode without explicit live approval.
- Notification URL pointing to `localhost`, `school.local`, or an expired tunnel.
- Integration ID not matching EGP/card integration.
- HMAC secret missing.
- Paymob network not sandbox-QA tested.
- Failed duplicate/invalid-HMAC/order-mismatch QA.
- Any secret detected in Git diff, report, log, screenshot, or HTML output.

## I. UI States

Recommended dashboard state model:

| State | Meaning | Allowed action |
| --- | --- | --- |
| `not_configured` | Required fields are missing. | Save masked/new values only. Network and CTA remain disabled. |
| `configured_disabled` | Required fields appear present, but `enabled` is false. | Run non-network readiness diagnostics. |
| `sandbox_ready` | Sandbox fields pass shape checks, EGP is set, and URLs are valid. | Owner may explicitly enable sandbox network testing in a future approved phase. |
| `sandbox_network_testing_enabled` | Sandbox network flags are on for controlled local QA. | Create sandbox intentions only behind explicit local/admin action; no production CTA. |
| `cta_local_testing_enabled` | Local CTA is enabled for controlled QA only. | Course-detail CTA may show only to eligible learner/course fixtures. |
| `production_blocked` | Live configuration exists or is requested but approval/readiness is incomplete. | Display blockers; do not enable live network or production CTA. |
| `production_pending_approval` | Live shape checks pass, but final owner/QA approval is not recorded. | Keep live `enabled`/`network_enabled` false. |
| `error_missing_fields` | One or more required fields are missing/invalid. | Show field-level errors without private values. |
| `error_security_blocked` | Unsafe combination detected, such as live mode without approval or non-EGP currency. | Force disabled state and show redacted reason. |

Recommended UI behavior:

- Private inputs should display empty fields with "configured" status indicators, not saved secret values.
- Public/non-secret fields can display their saved values.
- Activation toggles should be visually separate from credential entry.
- Live-mode controls should be disabled until a later production phase explicitly approves them.
- A readiness panel should list pass/fail checks without printing secrets.
- A warning panel should state that legacy Academy gateways are not used for YounGo checkout.

## J. Future Implementation Phases

Recommended sequence:

1. `PAYMENT.PAYMOB.CONFIG.DASHBOARD.SCHEMA.1`
   - Design/apply local additive schema for provider config and optional redacted audit.
   - Decide encryption-at-rest and external key strategy first.
   - No Paymob network calls.
   - Defaults disabled.

2. `PAYMENT.PAYMOB.CONFIG.DASHBOARD.UI.1`
   - Add Root/core-owner-only read-only dashboard page with current file/default config and DB config readiness summary.
   - No secret save yet.
   - No network calls.
   - No CTA exposure.

3. `PAYMENT.PAYMOB.CONFIG.DASHBOARD.SAVE.1`
   - Add POST save handling for non-secret and secret fields.
   - Mask private fields after save.
   - Add audit entries with redacted presence states only.
   - Keep `enabled`, `network_enabled`, and `checkout_cta_enabled` false unless explicitly approved in phase instructions.

4. `PAYMENT.PAYMOB.SANDBOX.TEST.BUTTON.1`
   - Add a non-mutating dashboard readiness/test panel.
   - No payment intention by default.
   - If a network connectivity check is added, gate it behind explicit sandbox-only flags and redact all request/response data.

5. `PAYMENT.PAYMOB.SANDBOX.INTENTION.1`
   - Implement first real sandbox Create Intention request behind explicit local/admin flags.
   - Use Unified Checkout.
   - Webhook/HMAC remains source of truth.
   - No production defaults changed.

6. `PAYMENT.PAYMOB.SANDBOX.WEBHOOK.E2E.1`
   - Test real sandbox webhook through a public HTTPS tunnel.
   - Verify HMAC, idempotency, paid/failed transitions, and entitlement issuance exactly once.
   - No legacy payment/enrol writes.

7. `PAYMENT.CHECKOUT.PRODUCTION.PREFLIGHT.1`
   - Production readiness review only.
   - Confirm live Paymob account approval, live URLs, HTTPS, credential separation, logs, backups, and support process.
   - Production CTA remains off until final explicit approval.

## K. Risks/Blockers

- Owner must provide Paymob sandbox dashboard access and credential names through a secure channel outside Git/reports.
- The CodeIgniter encryption key is currently empty; DB-backed secret storage needs a key-management decision before implementation.
- Existing Academy payment settings display gateway keys as editable plain inputs and should not be reused for YounGo Paymob secrets.
- Exact Paymob dashboard labels may vary by merchant/account, especially public key, secret key, HMAC secret, integration ID, and Unified Checkout availability.
- A public HTTPS tunnel is required for local server-to-server webhook QA.
- Current `Youngo_checkout::checkout_availability()` correctly rejects `network_enabled = true`; the sandbox intention phase must add a stricter sandbox network gate without weakening defaults.
- Subscription purchase issuance remains unimplemented; first Paymob dashboard/network phases should stay course-purchase-only unless subscription entitlement issuance is designed separately.
- Legacy Academy reporting may not see YounGo-only payment rows until a later read-only reconciliation/admin reporting phase.
- DB dumps containing encrypted payment config would still be sensitive and need owner handling rules.

## L. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.CONFIG.DASHBOARD.SCHEMA.1 - Design/Add Local Paymob Dashboard Config Storage
```

Recommended scope:

- Decide secure secret-storage approach before any DB-backed private fields are added.
- Add an additive local schema for YounGo Paymob config profiles and redacted audit metadata.
- Keep all payment, network, webhook, and CTA defaults disabled.
- Do not add real credentials.
- Do not call Paymob.
- Do not alter legacy `payment_gateways`, `payment`, or `enrol`.
- Add diagnostics that verify schema shape, redaction fields, disabled defaults, and no legacy gateway usage.

## M. Git Status

Git status after creating this report:

```text
?? docs/qa/youngo_payment_paymob_config_dashboard_plan_1_report.md
```

No source files, config defaults, ignored local config files, DB rows, SQL files, credentials, Root Admin records, live server files, public production CTAs, or legacy gateway/payment/enrol rows were changed.
