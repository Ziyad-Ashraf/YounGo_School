# PAYMENT.PAYMOB.CONFIG.DASHBOARD.SAVE.NONPRIVATE.1 - Non-Private Paymob Dashboard Save Flow

Date: 2026-07-22

Scope: Root-Admin-only editable save for non-private Paymob dashboard configuration fields. No deployment, push, real Paymob private values, private value output/save, real payment enablement, Paymob network request, checkout CTA exposure, Root Admin data modification, legacy `payment_gateways` use, production default enablement, or sandbox payment execution was performed.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
476bfa7 Plan YounGo Paymob dashboard save flow
f2bb2f8 Add read-only YounGo Paymob dashboard config summary
7b0864d Add YounGo Paymob dashboard config schema foundation
7694e2c Plan YounGo Paymob dashboard configuration
022f936 Plan YounGo Paymob sandbox execution
0d663c1 QA gated YounGo checkout CTA clickthrough
68b9b28 Add gated YounGo checkout CTA helper
bda9cb1 Plan YounGo checkout CTA exposure
b59eaee QA local YounGo checkout smoke flow
bcb01b7 Fix YounGo checkout HTTP DB access
```

The expected branch and clean starting worktree were confirmed. The latest commit includes `PAYMENT.PAYMOB.CONFIG.DASHBOARD.SAVE.PLAN.1`.

## B. Backup Created

Fresh local DB backup created before diagnostic save-flow DB writes:

```text
Path: D:\Work\YounGo\backups\youngo_school_before_paymob_config_dashboard_save_nonprivate_1_2026_07_22_215425.sql
Size: 628138 bytes
SHA256: BBEC0DA6441FB1CD4E5D12660A9FF90FBCEDBD5EA199A3090663EAB5759E0CA2
```

The backup used the local CodeIgniter database config without printing database credentials.

## C. Files Inspected

Required reports:

- `docs/qa/youngo_payment_paymob_config_dashboard_save_plan_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_ui_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_schema_1_report.md`

Source inspected:

- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `application/models/Youngo_payment_config_model.php`
- `application/config/routes.php`
- `application/views/backend/admin/navigation.php`
- `application/config/config.php`
- `application/controllers/Youngo_subscription_plans.php`
- `application/views/backend/admin/youngo_subscription_plan_form.php`

## D. Files Changed

- `application/models/Youngo_payment_config_model.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `scripts/phase_2/youngo_payment_paymob_config_dashboard_ui_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_paymob_config_dashboard_save_nonprivate_1_diagnostic.php`
- `docs/qa/youngo_payment_paymob_config_dashboard_save_nonprivate_1_report.md`

## E. Save Flow Behavior

The existing dashboard route remains:

```text
/admin/youngo/payment-settings
```

The controller now supports:

- `GET`: render the dashboard summary and non-private sandbox form.
- `POST`: call `Youngo_payment_config_model::upsert_dashboard_non_private_config()`.
- Any other method: reject with HTTP 405 and redirect safely.

The dashboard form can save only:

- `mode`
- `currency`
- `amount_multiplier`
- `card_integration_id_egp`
- `api_base_url`
- `checkout_base_url`
- `return_url`
- `notification_url`

The form is Root-Admin-only through the existing admin session guard plus `youngo_is_root_admin()`. No Root Admin data was changed.

## F. Validation Behavior

Added strict dashboard-save validation in `Youngo_payment_config_model::upsert_dashboard_non_private_config()`:

- Provider must be `paymob`.
- Mode must be `sandbox` only.
- Currency must be `EGP` only.
- Amount multiplier must be `100`.
- Card integration ID may be blank or numeric.
- URLs may be blank or valid `http`/`https` URLs only.
- Unsupported non-empty fields are rejected.
- Private fields and activation gates are rejected.

Validation errors return structured safe result arrays and do not include submitted secret values.

## G. Private-Value Blocking

Private and blocked fields remain unsavable in this phase:

- `secret_key`
- `hmac_secret`
- `api_key`
- `client_secret`
- `authorization`
- `auth_header`
- `public_key` in this dashboard-save phase

The dashboard has no password inputs and no private field inputs. Private Paymob fields are shown only through safe presence/readiness summaries such as `missing` or `configured_redacted`.

Current encryption/key finding remains:

```text
application/config/config.php
$config['encryption_key'] = '';
```

Because key management is not ready, DB-backed private value storage remains blocked.

## H. Gate/Default Safety

The save flow cannot enable:

- `enabled`
- `network_enabled`
- `sandbox_network_testing_enabled`
- `webhook_testing_enabled`
- `checkout_routes_enabled`
- `checkout_local_testing_enabled`
- `checkout_cta_enabled`
- `live_mode_allowed`
- `transaction_inquiry_enabled`

The model's dashboard-save wrapper rejects these fields when submitted with non-empty values. The lower-level non-private upsert still forces all activation gates to `0` in this phase.

No Paymob network execution code was added. No checkout CTA exposure was added.

## I. Diagnostic Result

Created and ran:

```text
php scripts/phase_2/youngo_payment_paymob_config_dashboard_save_nonprivate_1_diagnostic.php
```

Result:

```text
ok: true
failed_checks: []
```

Verified:

- Controller, view, model, route, navigation, and config files exist.
- Route exists.
- Root-only controller and navigation markers exist.
- Controller uses the strict model save wrapper.
- Non-private form exists.
- Allowed form fields are present.
- No private form inputs exist.
- Model has strict dashboard save wrapper.
- No Paymob network call patterns were added.
- No legacy `payment_gateways` dependency exists in changed source.
- `encryption_key` remains empty.
- Non-private upsert works through the model.
- Saved diagnostic row keeps mode `sandbox` and currency `EGP`.
- Gates remain disabled after save.
- Invalid live mode, non-EGP currency, bad multiplier, bad integration ID, bad URL, private value, public key, and activation gate submissions are rejected.
- Safe summary redacts private fields.
- No secret-shaped values were printed.
- Diagnostic Paymob config row was deleted after the test.
- Table counts were restored.
- Legacy payment tables were unchanged.

Regression diagnostics:

```text
php scripts/phase_2/youngo_payment_paymob_config_dashboard_ui_1_diagnostic.php
ok: true

php scripts/phase_2/youngo_payment_paymob_config_dashboard_schema_1_diagnostic.php
ok: true
```

## J. DB Cleanup

The diagnostic created one controlled local `paymob`/`sandbox` config row, verified the save and validation behavior, then deleted it because no baseline Paymob config row existed.

Counts:

```text
youngo_payment_provider_configs: 0 -> 0
payment_gateways: 15 -> 15
payment: 0 -> 0
enrol: 1 -> 1
```

No persistent diagnostic config, payment, checkout, entitlement, legacy payment, or legacy enrol rows were left behind.

## K. What Was Not Changed

- No deployment.
- No push.
- No real Paymob private values.
- No private values printed.
- No private Paymob values saved.
- No real payments enabled.
- No Paymob calls.
- No real Paymob intentions.
- No checkout CTAs exposed.
- No Root Admin data changed.
- No legacy `payment_gateways` use.
- No production defaults changed to enabled.
- No sandbox payment execution.
- No legacy payment/enrol writes.

## L. Remaining Risks/Blockers

- `encryption_key` is empty, so private Paymob DB storage remains blocked.
- Public key save was intentionally blocked in this phase even though the schema can hold a public key.
- No audit-log table exists yet for payment config changes.
- The form is not browser-QA tested in this phase.
- Real Paymob sandbox values are still owner-provided future inputs and must be handled through a secure channel.
- Sandbox network execution remains blocked until secret handling, sandbox flags, HMAC, and webhook tunnel QA are approved.

## M. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.CONFIG.DASHBOARD.SAVE.UI.QA.1
```

Suggested scope:

- Browser-test Root-Admin-only non-private dashboard save with placeholder-only values.
- Confirm validation messages and masked/private blocked UI.
- Confirm gates remain disabled.
- Cleanup/restoration if diagnostic config rows are created.
- Confirm no Paymob network calls and no CTA exposure.

## N. Git Status

Expected final git status after this phase:

```text
 M application/controllers/Youngo_payment_settings.php
 M application/models/Youngo_payment_config_model.php
 M application/views/backend/admin/youngo_payment_settings.php
 M scripts/phase_2/youngo_payment_paymob_config_dashboard_ui_1_diagnostic.php
?? docs/qa/youngo_payment_paymob_config_dashboard_save_nonprivate_1_report.md
?? scripts/phase_2/youngo_payment_paymob_config_dashboard_save_nonprivate_1_diagnostic.php
```
