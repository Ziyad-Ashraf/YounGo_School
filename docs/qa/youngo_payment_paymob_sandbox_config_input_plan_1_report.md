# PAYMENT.PAYMOB.SANDBOX.CONFIG.INPUT.PLAN.1 - Paymob Sandbox Configuration Input Plan

Date: 2026-07-23

Scope: operational plan for entering Paymob sandbox configuration after the client creates a Paymob account. This phase is planning only. No deployment, push, DB change, SQL execution, real Paymob value entry, private value output/save, `encryption_key` change, payment enablement, Paymob request, production CTA exposure, Root Admin modification, or legacy `payment_gateways` use was performed.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
a0eca3b QA YounGo Paymob setup checklist page
8fed0cc Add YounGo Paymob setup checklist page
d222ea1 QA YounGo Paymob readiness page gates
9c2891b Align YounGo Paymob setup readiness gates
a7ff09a Audit YounGo payment work against legacy system
769af57 QA blocked YounGo Paymob sandbox readiness
125023c Add controlled YounGo Paymob sandbox intention flow
207fa95 Plan hybrid YounGo Paymob sandbox intention flow
81e6197 Add hybrid YounGo Paymob private config readiness
179bc01 Plan YounGo payment private value handling
```

The expected branch, clean starting worktree, and latest setup-checklist QA commit were confirmed.

## B. Files Inspected

Required project guidance:

- `YOUNGO_PROJECT_CONTEXT.md`
- `docs/design/youngo_style_direction.md`
- `docs/planning/`
- `docs/planning/youngo_master_plan_v2.md`
- `docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`
- `docs/agents/implementation_rules.md`
- `docs/reference/README.md`

Required payment reports:

- `docs/qa/youngo_payment_paymob_setup_wizard_ui_qa_1_report.md`
- `docs/qa/youngo_payment_paymob_setup_wizard_1_report.md`
- `docs/qa/youngo_payment_secrets_hybrid_config_1_report.md`
- `docs/qa/youngo_payment_paymob_sandbox_intention_1_report.md`

Implementation files inspected:

- `application/config/youngo_paymob.local.example.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/models/Youngo_payment_config_model.php`

## C. Client/Owner Setup Workflow

Recommended operational workflow:

1. Client/owner creates or activates a Paymob sandbox account.
2. Client/owner creates or confirms an EGP card integration in the Paymob sandbox dashboard.
3. Client/owner identifies the sandbox Intention/Unified Checkout API and checkout base URLs from current Paymob dashboard/docs.
4. Client/owner prepares the YounGo return URL for the local or staging environment. Return is UX-only and must not be treated as payment proof.
5. Client/owner prepares the Paymob payment notification URL. This maps to the YounGo webhook endpoint and must be publicly reachable for real webhook QA.
6. Root Admin enters only non-private sandbox fields in `/admin/youngo/payment-settings`.
7. Developer/server admin places private Paymob values only in ignored local/server config.
8. Root Admin reviews `/admin/youngo/payment-settings` readiness. Private values must appear only as redacted presence states.
9. If readiness is complete, a later explicit QA phase enables local sandbox gates in ignored config and runs one sandbox Intention/browser QA attempt.

No production checkout CTA should be exposed during this workflow.

## D. Field Split

Dashboard non-private fields:

- `mode`
- `currency`
- `amount_multiplier`
- `card_integration_id_egp`
- `api_base_url`
- `checkout_base_url`
- `return_url`
- `notification_url`

Current validation expectations:

- `mode` must be `sandbox`.
- `currency` must be `EGP`.
- `amount_multiplier` must be `100`.
- `card_integration_id_egp` must be blank or numeric during save, and present/numeric for sandbox readiness.
- URL fields must be blank or valid `http`/`https` during save, and present for sandbox readiness.
- `notification_url` is the canonical dashboard field for Paymob payment notification delivery. The local/server config name `webhook_url` is an alias for the same endpoint.

Ignored server-config private fields:

- `public_key`
- `secret_key`
- `hmac_secret`

Current private-value policy:

- `public_key` is treated as account-specific server-config-only in this phase.
- `secret_key` is runtime-only server config.
- `hmac_secret` is runtime-only server config for webhook verification.
- Private values must not be saved to DB while `encryption_key` is empty.
- Private values must not appear in Git, reports, diagnostics, audit logs, browser output, screenshots, or chat transcripts.

## E. Roles And Responsibilities

Client/owner:

- Creates the Paymob sandbox account.
- Creates/confirms EGP card integration.
- Provides required field values through a secure handoff channel, not Git or reports.
- Approves the later sandbox execution QA phase.

Root Admin:

- Logs into the YounGo dashboard.
- Enters non-private sandbox fields in `/admin/youngo/payment-settings`.
- Reviews readiness and audit logs.
- Does not enter private Paymob values into the dashboard in this phase.

Developer/server admin:

- Creates `application/config/youngo_paymob.local.php` locally or on the target server when approved.
- Copies the tracked example structure from `application/config/youngo_paymob.local.example.php`.
- Places private values only in ignored local/server config.
- Enables explicit local sandbox flags only for an approved sandbox QA run.
- Removes or disables local testing flags after QA.

System:

- Stores non-private config in `youngo_payment_provider_configs`.
- Reads private presence from ignored local/server config through `Youngo_paymob_config`.
- Shows only redacted presence/readiness states.
- Keeps payment behavior disabled unless all explicit sandbox gates pass.

## F. Safe Handover Instructions

Required handling rules:

- Do not commit Paymob values.
- Do not paste private Paymob values into reports.
- Do not paste private Paymob values into chat when avoidable.
- Do not store private Paymob values in the DB in the current phase.
- Do not add screenshots that reveal keys, tokens, HMAC secrets, client secrets, merchant identifiers, or dashboard-only secret details.
- Use a secure handoff channel or direct server entry for private values.
- Keep `application/config/youngo_paymob.local.php` ignored and uncommitted.
- Confirm `.gitignore` still ignores the local override path before entering private values.
- Treat DB backups/exports as containing only non-private Paymob dashboard config and redacted audit metadata.
- Treat server filesystem backups as potentially containing private values if they include ignored server config; restrict and encrypt those backups accordingly.

## G. Readiness Verification Steps

Before sandbox Intention QA:

1. Confirm `/admin/youngo/payment-settings` renders for Root Admin.
2. Save non-private sandbox fields in the dashboard.
3. Confirm the dashboard shows the non-private config as complete.
4. Confirm ignored local/server config exists only outside Git.
5. Confirm private fields appear only as `configured_redacted` or equivalent redacted presence states.
6. Confirm `mode=sandbox`, `currency=EGP`, and `amount_multiplier=100`.
7. Confirm `notification_url` points to a webhook URL reachable by Paymob for webhook QA. For local machine testing, this normally requires an HTTPS tunnel.
8. Confirm default tracked flags remain disabled.
9. Confirm the dashboard Sandbox Test control remains disabled until a later approved execution phase.
10. Confirm no public production checkout CTA appears.
11. Run the existing diagnostics before any network attempt.

Explicit sandbox execution gates for the later QA phase:

- `enabled=true`
- `network_enabled=true`
- `sandbox_network_testing_enabled=true`
- `checkout_routes_enabled=true`
- `checkout_local_testing_enabled=true`
- `checkout_cta_enabled=true` only if testing CTA click-through
- required dashboard non-private fields present
- required ignored server-config private fields present
- eligible learner/course/order fixture selected

All of these gates must be enabled only in ignored local/server config or approved local test context, not in tracked defaults.

## H. Local/Server URL Strategy

Return URL:

- Use an environment-specific YounGo checkout return route.
- Return is UX-only and must not mark payment paid or issue entitlement.
- Placeholder pattern: `<base-url>/youngo/checkout/return/{order_reference}`.

Notification URL:

- Use the canonical dashboard field `notification_url`.
- It should point to the YounGo Paymob webhook route.
- Placeholder pattern: `<public-https-base-url>/payment/paymob/webhook`.
- `school.local` is not reachable by Paymob directly; real webhook QA needs a public HTTPS tunnel or a deployed sandbox/staging URL approved for testing.

Checkout base URL/API base URL:

- Use sandbox Paymob URLs from the current Paymob dashboard/docs.
- Do not invent or hardcode live URLs.
- Keep live/production URLs out of the first sandbox QA pass.

## I. Blocked/Failure Handling

Expected safe blockers:

- Missing dashboard row: fail closed as missing dashboard config.
- Missing private server config: fail closed as missing private config.
- Private DB storage attempt: reject while `encryption_key` is empty.
- `mode=live`: reject in current phase.
- non-EGP currency: reject.
- invalid URL/integration ID: reject.
- sandbox network flags absent: do not call Paymob.
- production CTA request: remain hidden unless separately approved later.

No blocker should print private values.

## J. Next QA Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.SANDBOX.CONFIG.INPUT.QA.1
```

Purpose:

- Confirm dashboard non-private values can be entered and audited safely.
- Confirm ignored local/server private values are detected as presence only.
- Confirm readiness changes from missing to ready without printing private values.
- Keep sandbox test execution disabled unless the next explicit execution phase is approved.
- Confirm no production CTA exposure and no legacy payment/enrol writes.

This should run before another real Paymob sandbox Intention attempt.

## K. What Was Not Changed

- No deployment.
- No push.
- No DB modification.
- No SQL execution.
- No real Paymob values added.
- No private values printed.
- No private values saved to DB.
- No `encryption_key` change.
- No payment behavior enabled.
- No Paymob request performed.
- No production checkout CTA exposed.
- No Root Admin data modified.
- No legacy `payment_gateways` use.

## L. Risks/Blockers

- Client/owner still needs to create or confirm the Paymob sandbox account and EGP card integration.
- Exact Paymob dashboard labels may differ from internal YounGo field names; the setup page should continue using YounGo's canonical field names while mapping them in owner instructions.
- Private DB storage remains blocked while `encryption_key` is empty.
- Real webhook QA requires a public HTTPS endpoint or tunnel; `school.local` is not sufficient for Paymob server-to-server callbacks.
- Real sandbox response shape must still be validated during the first approved network QA run.

## M. Git Status

Expected status after this planning report:

```text
?? docs/qa/youngo_payment_paymob_sandbox_config_input_plan_1_report.md
```

