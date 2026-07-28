# PAYMENT.PAYMOB.SANDBOX.READINESS.UI.QA.1 - Admin Paymob Readiness Page and Gate QA

Date: 2026-07-23

Scope: authenticated local UI/HTTP QA for the Root-Admin Paymob settings/readiness page and public CTA surfaces. No deployment, push, real Paymob values, private value output/save, `encryption_key` change, Paymob call, production CTA exposure, Root Admin data modification, legacy `payment_gateways` use, or legacy `payment`/`enrol` write was performed.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
9c2891b Align YounGo Paymob setup readiness gates
a7ff09a Audit YounGo payment work against legacy system
769af57 QA blocked YounGo Paymob sandbox readiness
125023c Add controlled YounGo Paymob sandbox intention flow
207fa95 Plan hybrid YounGo Paymob sandbox intention flow
81e6197 Add hybrid YounGo Paymob private config readiness
179bc01 Plan YounGo payment private value handling
73846af QA YounGo Paymob config audit panel
8b7fa78 Wire YounGo Paymob config saves to audit logs
8351916 Add YounGo Paymob config audit schema
```

The expected branch and clean starting worktree were confirmed. The latest commit includes `PAYMENT.PAYMOB.CONFIG.SETUP.PREFLIGHT.1`.

## B. Files/Reports Read

- `docs/qa/youngo_payment_paymob_setup_preflight_1_report.md`
- `docs/qa/youngo_payment_reconcile_audit_1_report.md`
- `docs/qa/youngo_payment_secrets_hybrid_config_1_report.md`
- `application/controllers/Login.php`
- `application/models/User_model.php`
- `application/views/frontend/youngo/login.php`
- `application/config/config.php`
- `application/config/routes.php`

## C. URLs Tested

Authenticated Root-Admin session:

- `http://school.local/login`
- `http://school.local/login/validate_login`
- `http://school.local/admin/dashboard`
- `http://school.local/admin/youngo/payment-settings`

Unauthenticated/public CTA surfaces:

- `http://school.local/`
- `http://school.local/home/courses`
- `http://school.local/home/course/robotics-and-ai-explorers/9`
- `http://school.local/home/my_wishlist`

## D. Readiness Page QA

`/admin/youngo/payment-settings` rendered successfully:

```text
HTTP status: 200
Final URI: http://school.local/admin/youngo/payment-settings
```

Confirmed wording/state:

- The page identifies itself as a Paymob setup/readiness page, not a full private secret-entry wizard.
- Non-private sandbox settings are described as stored in the YounGo DB config table.
- Private Paymob values are described as coming from ignored local/server config only.
- Private Paymob DB storage is blocked because `encryption_key` is empty.
- `notification_url` is shown as the canonical dashboard field / Paymob payload field.
- `webhook_url` is described as the ignored local/server config alias for the same endpoint.
- `public_key` is labeled as server-config-only in this phase.
- No sandbox test or Continue execution button was rendered.

## E. Private-Value Redaction

Confirmed:

- No editable `secret_key`, `hmac_secret`, or `public_key` inputs were present.
- No raw private-looking Paymob tokens were detected in the rendered page.
- Private fields were represented only through safe presence/readiness wording.
- Root Admin password was not stored in the report.

## F. CTA/Gate Safety

Dashboard gate state:

- Payment enabled: disabled.
- Network enabled: disabled.
- Checkout CTA: disabled.
- Sandbox test/Continue execution controls: absent/disabled.

Public CTA surfaces:

```text
/                                      status 200, no youngo/checkout/start link, no CTA marker
/home/courses                         status 200, no youngo/checkout/start link, no CTA marker
/home/course/robotics-and-ai-explorers/9 status 200, no youngo/checkout/start link, no CTA marker
/home/my_wishlist                     status 200, no youngo/checkout/start link, no CTA marker
```

No legacy Buy Now shortcut markers were detected on those checked surfaces.

## G. Paymob/Legacy Safety

- No Paymob network request was performed by this QA.
- No real Paymob intention was created.
- No private Paymob value was added or saved.
- No legacy `payment_gateways` dependency was used.
- No legacy `payment` or `enrol` write was performed by the YounGo payment flow.
- Public production checkout CTA exposure remains off.

## H. Validation

Requested validations:

```text
php scripts/phase_2/youngo_payment_paymob_setup_preflight_1_diagnostic.php
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
```

Final Git checks are recorded in section L after validation.

## I. What Was Not Changed

- No deployment.
- No push.
- No source code behavior change beyond this QA report.
- No ignored local config was created.
- No real Paymob private values were added.
- No private values were saved to DB.
- No `encryption_key` change.
- No Paymob network call.
- No production checkout CTA exposure.
- No Root Admin data modification.
- No legacy `payment_gateways` use.
- No legacy `payment`/`enrol` writes.

## J. Remaining Risks/Blockers

- Real sandbox Intention browser QA remains blocked until owner-provided Paymob sandbox values are present outside Git/reports.
- Dashboard non-private sandbox config still needs approved placeholder/sandbox values before a real sandbox readiness pass.
- Private DB storage remains blocked while CodeIgniter `encryption_key` is empty.
- Webhook/tunnel/HMAC real sandbox callback QA remains future work.

## K. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.SANDBOX.READINESS.CONFIG.QA.1
```

Purpose:

- Add only approved placeholder/sandbox non-private dashboard values through the Root-Admin page.
- Keep private Paymob values out of DB and reports.
- Confirm readiness changes safely without attempting Paymob network execution unless the owner explicitly approves the real sandbox Intention browser QA phase.

## L. Git Status

Expected status after this report:

```text
?? docs/qa/youngo_payment_paymob_sandbox_readiness_ui_qa_1_report.md
```
