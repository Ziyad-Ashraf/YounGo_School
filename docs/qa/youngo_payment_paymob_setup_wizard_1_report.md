# PAYMENT.PAYMOB.SETUP.WIZARD.1 - Client-Facing Paymob Setup Checklist Page

Date: 2026-07-23

Scope: improve the Root-Admin Paymob settings/readiness page into a clearer setup checklist/wizard-style page. No deployment, push, DB schema change, SQL execution, real Paymob value entry, private value output/save, `encryption_key` change, payment enablement, Paymob request, production CTA exposure, Root Admin data modification, legacy `payment_gateways` use, or legacy `payment`/`enrol` write was performed.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
d222ea1 QA YounGo Paymob readiness page gates
9c2891b Align YounGo Paymob setup readiness gates
a7ff09a Audit YounGo payment work against legacy system
769af57 QA blocked YounGo Paymob sandbox readiness
125023c Add controlled YounGo Paymob sandbox intention flow
207fa95 Plan hybrid YounGo Paymob sandbox intention flow
81e6197 Add hybrid YounGo Paymob private config readiness
179bc01 Plan YounGo payment private value handling
73846af QA YounGo Paymob config audit panel
8b7fa78 Wire YounGo Paymob config saves to audit logs
```

The expected branch and clean starting worktree were confirmed. The latest commit includes `PAYMENT.PAYMOB.SANDBOX.READINESS.UI.QA.1`.

## B. Files Inspected

Required reports:

- `docs/qa/youngo_payment_paymob_sandbox_readiness_ui_qa_1_report.md`
- `docs/qa/youngo_payment_paymob_setup_preflight_1_report.md`
- `docs/qa/youngo_payment_reconcile_audit_1_report.md`
- `docs/qa/youngo_payment_secrets_hybrid_config_1_report.md`

Source inspected:

- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `application/models/Youngo_payment_config_model.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/config/routes.php`
- `application/views/backend/admin/navigation.php`
- `application/config/youngo_paymob.php`
- `application/views/frontend/youngo/course_listing/course_card.php`
- `application/views/frontend/youngo/my_wishlist.php`
- `application/views/frontend/youngo/wishlist_items.php`

## C. Files Changed

- `application/views/backend/admin/youngo_payment_settings.php`
- `scripts/phase_2/youngo_payment_paymob_setup_wizard_1_diagnostic.php`
- `docs/qa/youngo_payment_paymob_setup_wizard_1_report.md`

## D. Setup Checklist Behavior

`/admin/youngo/payment-settings` now includes a `Paymob Sandbox Setup Checklist` section for Root Admin/owner setup guidance.

The checklist explains these owner/client actions without showing values:

- create or activate the Paymob sandbox account;
- create or confirm an EGP card integration;
- prepare the YounGo checkout return URL;
- prepare the Paymob payment notification URL / YounGo webhook endpoint;
- enter only non-private sandbox values in the dashboard page;
- place private Paymob values only in ignored local/server config;
- keep sandbox testing disabled until a later approved execution phase.

The existing audit panel, readiness panels, and non-private save form remain on the same page.

## E. Required Field/Status Behavior

The page now lists field names only.

Non-private dashboard fields:

- `mode`
- `currency`
- `amount_multiplier`
- `card_integration_id_egp`
- `api_base_url`
- `checkout_base_url`
- `return_url`
- `notification_url`

Private server-config-only fields:

- `public_key`
- `secret_key`
- `hmac_secret`

Safe status labels used:

- `complete`
- `missing`
- `pending_server_config`
- `blocked_private_db_storage`
- `disabled_until_approved`

Statuses are derived from existing safe dashboard/config summaries and private presence labels. No private raw values are used to render the checklist.

## F. Private-Value Handling

Private handling remains unchanged:

- no private input fields were added;
- `public_key`, `secret_key`, and `hmac_secret` remain server-config-only in this phase;
- private DB storage is shown as blocked;
- no private values are rendered, logged, saved, or printed;
- `encryption_key` remains unchanged and empty.

## G. Next-Step Behavior

The new `Next Steps` section shows:

- Save non-private settings with the existing form below.
- Review audit log, linking to the same page audit section.
- Sandbox test not available yet.

The sandbox test control is a disabled `button` with no route, action, href, or execution behavior.

Existing save behavior is unchanged:

- non-private settings only;
- sandbox/EGP validation still enforced by the existing model;
- activation gates remain blocked;
- private fields remain rejected.

## H. Diagnostic Result

Created:

```text
scripts/phase_2/youngo_payment_paymob_setup_wizard_1_diagnostic.php
```

Diagnostic result:

```text
php -l application/views/backend/admin/youngo_payment_settings.php
No syntax errors detected

php -l scripts/phase_2/youngo_payment_paymob_setup_wizard_1_diagnostic.php
No syntax errors detected

php scripts/phase_2/youngo_payment_paymob_setup_wizard_1_diagnostic.php
ok: true
failed_checks: []

php scripts/phase_2/youngo_payment_paymob_setup_preflight_1_diagnostic.php
ok: true
failed_checks: []

git diff --check
passed; emitted only the LF-to-CRLF working-copy warning for application/views/backend/admin/youngo_payment_settings.php
```

The diagnostic verified:

- setup checklist markers and steps exist;
- required non-private field names appear;
- private server-config field names appear without inputs;
- safe status labels are present;
- sandbox test control is disabled and non-functional;
- existing non-private save form remains;
- audit log review anchor exists;
- no Paymob call patterns were added in setup wizard paths;
- Root-only controller guard remains;
- payment settings route remains;
- default tracked CTA/network gates remain false;
- listing and wishlist views still do not expose checkout start links;
- setup view does not expose public checkout start links;
- no private-looking values appear in the setup view.

## I. What Was Not Changed

- No DB schema was modified.
- No SQL was executed.
- No Paymob request was performed.
- No real Paymob values were added.
- No private values were printed or saved to DB.
- No `encryption_key` change.
- No payment or network gate was enabled.
- No production checkout CTA was exposed.
- No Root Admin data was modified.
- No legacy `payment_gateways` storage was used.
- No legacy `payment` or `enrol` rows were written.
- No sandbox execution button or route was added.

## J. Remaining Risks/Blockers

- The page is still a setup checklist/readiness page, not a full Paymob setup wizard with secret entry.
- Real Paymob sandbox browser QA remains blocked until owner-provided sandbox values are supplied outside Git/reports.
- Private DB storage remains blocked while `encryption_key` is empty.
- Sandbox webhook/tunnel/HMAC real payload QA is still pending.
- A future phase must explicitly approve any sandbox execution control.

## K. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.SETUP.WIZARD.UI.QA.1
```

Purpose:

- Browser-QA the Root-Admin setup checklist page.
- Confirm checklist/status rendering with current missing/default readiness.
- Confirm no private input/value rendering.
- Confirm the sandbox test control is disabled and non-functional.
- Confirm public CTA surfaces remain hidden.

## L. Git Status

Expected status after this report:

```text
M application/views/backend/admin/youngo_payment_settings.php
A scripts/phase_2/youngo_payment_paymob_setup_wizard_1_diagnostic.php
A docs/qa/youngo_payment_paymob_setup_wizard_1_report.md
```
