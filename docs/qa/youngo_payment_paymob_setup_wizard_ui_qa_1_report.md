# PAYMENT.PAYMOB.SETUP.WIZARD.UI.QA.1 - Browser QA for Paymob Setup Checklist Page

Date: 2026-07-23

Scope: authenticated local browser/HTTP QA for the Root-Admin Paymob setup checklist/readiness page and public CTA surfaces. No deployment, push, real Paymob values, private value output/save, `encryption_key` change, payment enablement, Paymob request, production CTA exposure, Root Admin data modification, legacy `payment_gateways` use, or legacy `payment`/`enrol` write was performed.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
8fed0cc Add YounGo Paymob setup checklist page
d222ea1 QA YounGo Paymob readiness page gates
9c2891b Align YounGo Paymob setup readiness gates
a7ff09a Audit YounGo payment work against legacy system
769af57 QA blocked YounGo Paymob sandbox readiness
125023c Add controlled YounGo Paymob sandbox intention flow
207fa95 Plan hybrid YounGo Paymob sandbox intention flow
81e6197 Add hybrid YounGo Paymob private config readiness
179bc01 Plan YounGo payment private value handling
73846af QA YounGo Paymob config audit panel
```

The expected branch and clean starting worktree were confirmed. The latest commit includes `PAYMENT.PAYMOB.SETUP.WIZARD.1`.

## B. Files/Reports Read

- `docs/qa/youngo_payment_paymob_setup_wizard_1_report.md`
- `docs/qa/youngo_payment_paymob_sandbox_readiness_ui_qa_1_report.md`
- `application/views/backend/admin/youngo_payment_settings.php`

## C. URLs Tested

Authenticated Root-Admin session:

- `http://school.local/login`
- `http://school.local/login/validate_login`
- `http://school.local/admin/dashboard`
- `http://school.local/admin/youngo/payment-settings`

Public CTA surfaces:

- `http://school.local/`
- `http://school.local/home/courses`
- `http://school.local/home/course/robotics-and-ai-explorers/9`
- `http://school.local/home/my_wishlist`

## D. Setup Checklist QA

`/admin/youngo/payment-settings` rendered successfully:

```text
HTTP status: 200
Final URI: http://school.local/admin/youngo/payment-settings
```

Confirmed checklist content:

- `Paymob Sandbox Setup Checklist`
- `Create/activate Paymob sandbox account`
- `Create/confirm EGP card integration`
- `Prepare return URL`
- `Prepare payment notification URL`
- `Enter non-private values in this dashboard page`
- `Add private values to ignored server config`
- `Sandbox test remains disabled until readiness is complete`

Confirmed non-private field names render:

- `mode`
- `currency`
- `amount_multiplier`
- `card_integration_id_egp`
- `api_base_url`
- `checkout_base_url`
- `return_url`
- `notification_url`

Confirmed private server-config-only field names render:

- `public_key`
- `secret_key`
- `hmac_secret`

Confirmed safe statuses render:

- `complete`
- `missing`
- `pending_server_config`
- `blocked_private_db_storage`
- `disabled_until_approved`

## E. Private-Value Safety

Confirmed:

- `public_key`, `secret_key`, and `hmac_secret` appear as field names only.
- No editable inputs exist for `public_key`, `secret_key`, or `hmac_secret`.
- No raw private-looking Paymob tokens were detected in the rendered admin page.
- Root Admin password was not written to this report.

## F. Sandbox-Test Boundary

Confirmed:

- `Sandbox test not available yet` control renders.
- The control has `disabled` and `aria-disabled="true"`.
- The control is a non-functional button, not a route/action/link.
- The admin page does not include a `/youngo/checkout/start` checkout link.
- No Paymob sandbox execution route was triggered.

## G. CTA/Gate Safety

Public CTA surface results:

```text
/                                      status 200, no youngo/checkout/start link, no CTA marker
/home/courses                         status 200, no youngo/checkout/start link, no CTA marker
/home/course/robotics-and-ai-explorers/9 status 200, no youngo/checkout/start link, no CTA marker
/home/my_wishlist                     status 200, no youngo/checkout/start link, no CTA marker
```

No legacy Buy Now shortcut marker was detected on the checked public surfaces.

No Paymob network request was performed. The QA used local `school.local` requests only.

## H. Validation

Requested diagnostics:

```text
php scripts/phase_2/youngo_payment_paymob_setup_wizard_1_diagnostic.php
ok: true
failed_checks: []

php scripts/phase_2/youngo_payment_paymob_setup_preflight_1_diagnostic.php
ok: true
failed_checks: []
```

Final Git validation is recorded after this report.

## I. What Was Not Changed

- No deployment.
- No push.
- No real Paymob values were added.
- No private values were printed or saved to DB.
- No `encryption_key` change.
- No payment or network gate was enabled.
- No Paymob call was performed.
- No production checkout CTA was exposed.
- No Root Admin data was modified.
- No legacy `payment_gateways` storage was used.
- No legacy `payment` or `enrol` rows were written.

## J. Remaining Risks/Blockers

- The page remains a setup checklist/readiness page, not a full secret-entry wizard.
- Real sandbox Intention browser QA remains blocked until owner-provided Paymob sandbox values are supplied outside Git/reports.
- Private DB storage remains blocked while `encryption_key` is empty.
- Sandbox webhook/tunnel/HMAC real payload QA remains pending.

## K. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.SANDBOX.CONFIG.INPUT.PLAN.1
```

Purpose:

- Define exactly how owner-provided sandbox non-private values should be entered in the dashboard.
- Define how ignored local/server private values should be prepared without printing or committing them.
- Keep sandbox network execution blocked until a separate explicit QA phase.

## L. Git Status

Expected status after this report:

```text
?? docs/qa/youngo_payment_paymob_setup_wizard_ui_qa_1_report.md
```
