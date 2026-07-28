# PAYMENT.PAYMOB.SETUP.FORM.UI.1 - Legacy-Style YounGo Paymob Setup Form UI

## A. Current Branch/Status

- Branch at start: `analysis/cms-audit`
- Start worktree status: clean
- Recent latest commit at start: `366ae00 Document YounGo Paymob client setup checklist`
- This phase was source/UI only.

## B. Files Inspected

- `docs/qa/youngo_payment_paymob_setup_wizard_1_report.md`
- `docs/qa/youngo_payment_paymob_setup_wizard_ui_qa_1_report.md`
- `docs/qa/youngo_payment_paymob_setup_preflight_1_report.md`
- `docs/qa/youngo_payment_reconcile_audit_1_report.md`
- `docs/qa/youngo_payment_secrets_hybrid_config_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_save_nonprivate_1_report.md`
- `application/views/backend/admin/payment_settings.php`
- `application/views/backend/admin/payment_gateway.php` was requested but is not present locally.
- `application/views/backend/admin/youngo_payment_settings.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/models/Youngo_payment_config_model.php`
- `application/models/Youngo_payment_config_audit_model.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/config/routes.php`
- `application/views/backend/admin/navigation.php`
- `scripts/phase_2/youngo_payment_paymob_setup_wizard_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_paymob_setup_preflight_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_paymob_config_dashboard_save_nonprivate_1_diagnostic.php`

## C. Files Changed

- Updated `application/views/backend/admin/youngo_payment_settings.php`
- Added `scripts/phase_2/youngo_payment_paymob_setup_form_ui_1_diagnostic.php`
- Added this report: `docs/qa/youngo_payment_paymob_setup_form_ui_1_report.md`

## D. UI Redesign Summary

`/admin/youngo/payment-settings` was reorganized into a legacy-style dashboard setup form page using the existing Academy admin card/table/form conventions as layout inspiration only.

The redesigned page now groups the setup into clear sections:

- Paymob basic setup: `mode`, `currency`, `amount_multiplier`, `card_integration_id_egp`
- URLs: `api_base_url`, `checkout_base_url`, `return_url`, `notification_url`
- Private credentials: field names and server-config-only status placeholders
- Readiness/status: dashboard config, private config presence, DB private storage block, and sandbox-test availability
- Payment/network/CTA gates: displayed as disabled status only
- Setup checklist and required fields
- Audit log summary
- Next steps with sandbox test explicitly disabled

The page keeps the existing Root/Admin controller flow and does not add a new controller route or menu behavior.

## E. Non-Private Save Behavior

Existing save behavior was kept unchanged:

- POST still goes to `/admin/youngo/payment-settings`.
- Save still uses `Youngo_payment_config_model::upsert_dashboard_non_private_config(...)`.
- Allowed dashboard fields remain:
  - `mode`
  - `currency`
  - `amount_multiplier`
  - `card_integration_id_egp`
  - `api_base_url`
  - `checkout_base_url`
  - `return_url`
  - `notification_url`
- Validation still enforces sandbox-only, EGP-only, and amount multiplier `100`.
- Activation/network/CTA gates remain blocked by the model in this phase.

## F. Private Credential Handling

Private credential saving remains blocked.

The page shows these private field names only:

- `public_key`
- `secret_key`
- `hmac_secret`

Private credential controls are disabled/non-editable placeholders and do not use submit `name` attributes. They are labeled as ignored server-config-only values. The page states that DB private storage remains blocked while CodeIgniter `encryption_key` is empty and that private values must not be entered through this dashboard phase.

No private values, password inputs, raw keys, or credential submit names were added.

## G. Readiness/Status Behavior

The readiness copy was aligned to the hybrid configuration decision:

- The page is a setup/readiness page, not a full secret-entry wizard.
- Non-private sandbox settings are stored in the dedicated YounGo DB config table.
- Private values, including the public key for this phase, come from ignored local/server config only.
- `notification_url` is the canonical dashboard field.
- `webhook_url` is documented as a tracked config alias for the same endpoint.
- Sandbox testing remains unavailable until readiness and explicit local/sandbox gates pass in a later approved phase.

Gate values are displayed for visibility only and are not editable from the form.

## H. Audit/Next-Step Behavior

The recent audit panel remains visible and redacted. It displays only safe audit metadata such as time, actor id, action, changed fields, and secret-presence transitions.

The next-step area keeps the expected owner flow:

- Save non-private settings.
- Review audit log.
- Sandbox test remains disabled and non-functional.

No sandbox execution button, Paymob request action, credential entry action, or production activation control was added.

## I. Diagnostic Result

Added diagnostic:

```text
scripts/phase_2/youngo_payment_paymob_setup_form_ui_1_diagnostic.php
```

Diagnostic coverage:

- Confirms the redesigned view exists.
- Confirms expected legacy-style YounGo Paymob sections exist.
- Confirms non-private form fields exist.
- Confirms private fields are disabled/non-editable/server-config-only.
- Confirms private fields are not submitted through POST.
- Confirms save flow remains non-private only.
- Confirms activation/network/CTA gates remain disabled.
- Confirms the audit panel remains redacted.
- Confirms no Paymob network call patterns were added.
- Confirms no YounGo dependency on legacy gateway configuration was added.
- Confirms the Root/Admin guard remains in the controller.

Validation result: PASS.

The existing save non-private diagnostic performed its controlled diagnostic DB write/cleanup and restored protected counts.

Validation commands run:

- `php -l application/views/backend/admin/youngo_payment_settings.php` - PASS
- `php -l scripts/phase_2/youngo_payment_paymob_setup_form_ui_1_diagnostic.php` - PASS
- `php scripts/phase_2/youngo_payment_paymob_setup_form_ui_1_diagnostic.php` - PASS
- `php scripts/phase_2/youngo_payment_paymob_setup_wizard_1_diagnostic.php` - PASS
- `php scripts/phase_2/youngo_payment_paymob_setup_preflight_1_diagnostic.php` - PASS
- `php scripts/phase_2/youngo_payment_paymob_config_dashboard_save_nonprivate_1_diagnostic.php` - PASS
- `git diff --check` - PASS, with the existing Git LF-to-CRLF normalization warning for `application/views/backend/admin/youngo_payment_settings.php`

## J. What Was Not Changed

- No deployment.
- No push.
- No commit.
- No DB schema changes.
- No SQL execution.
- No real Paymob values added.
- No private values saved to DB.
- No `encryption_key` change.
- No payment activation.
- No Paymob network calls.
- No production checkout CTA exposure.
- No Root Admin data modification.
- No legacy gateway row or legacy gateway setup was added for YounGo Paymob.
- No legacy `payment` or `enrol` writes.
- No controller/model save behavior change beyond the existing read/render flow.

## K. Remaining Risks/Blockers

- This is still not a full client-facing private credential wizard.
- Private Paymob values still require ignored local/server config because encrypted DB private storage is blocked.
- Sandbox execution remains blocked until owner-provided Paymob sandbox values exist and a later explicit sandbox execution phase enables the required local flags.
- Browser QA for the redesigned form was not part of this source/UI diagnostic phase.

## L. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.SETUP.FORM.UI.QA.1 - Browser QA for Legacy-Style Paymob Setup Form UI
```

Scope should be Root/Admin browser QA only: render the redesigned page, submit placeholder non-private values, confirm private fields remain blocked, confirm audit display remains redacted, and confirm no Paymob execution or checkout CTA exposure.

## M. Git Status

Final git status:

```text
 M application/views/backend/admin/youngo_payment_settings.php
?? docs/qa/youngo_payment_paymob_setup_form_ui_1_report.md
?? scripts/phase_2/youngo_payment_paymob_setup_form_ui_1_diagnostic.php
```
