# PAYMENT.PAYMOB.CONFIG.DASHBOARD.UI.1 — Read-Only Paymob Dashboard Config Summary

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Start status: clean before this phase.
- Latest starting commit: `7b0864d Add YounGo Paymob dashboard config schema foundation`
- Scope: source/report only. No deploy, push, commit, Paymob calls, DB schema change, credential entry, or payment activation.

## B. Files Inspected

- `docs/qa/youngo_payment_paymob_config_dashboard_schema_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_plan_1_report.md`
- `application/models/Youngo_payment_config_model.php`
- `application/controllers/`
- `application/views/backend/`
- `application/views/backend/admin/`
- `application/config/routes.php`
- `application/helpers/youngo_capability_helper.php`
- `application/views/backend/admin/navigation.php`

## C. Files Changed

- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `application/config/routes.php`
- `application/views/backend/admin/navigation.php`
- `scripts/phase_2/youngo_payment_paymob_config_dashboard_ui_1_diagnostic.php`
- `docs/qa/youngo_payment_paymob_config_dashboard_ui_1_report.md`

## D. Dashboard Location

The read-only page is available at:

```text
/admin/youngo/payment-settings
```

It is routed to:

```text
Youngo_payment_settings::index
```

The navigation entry appears under the backend YounGo group as `Payment Settings`, guarded by the same YounGo capability helper file but restricted to Root Admin detection for this first read-only phase.

## E. Access Control Behavior

- The controller uses the existing admin session guard: `check_session_data('admin')`.
- The controller then requires `youngo_is_root_admin()` to return true for the current session user.
- If the YounGo capability helper is missing, access fails closed and redirects to the admin dashboard.
- No Root Admin account data, role rows, or permissions were modified.
- Broader admin/client access is intentionally deferred until a dedicated payment-settings capability and owner approval exist.

## F. Read-Only UI Behavior

The page shows:

- Provider: Paymob.
- Mode and currency.
- Payment/network/webhook/checkout/CTA flag status.
- Credential presence only, using safe model labels such as `missing` or `configured_redacted`.
- URL presence only, using safe presence labels.
- Readiness status and missing readiness fields.
- Encryption/key-management warning while `encryption_key` is empty.
- A clear message that save/edit, secret entry, network tests, payment activation, and checkout CTA activation are not enabled yet.

The page does not include:

- Forms.
- Inputs.
- Password fields.
- POST save behavior.
- Activation buttons.
- Paymob network calls.
- Legacy `payment_gateways` dependency.

## G. Readiness Summary Behavior

The page uses `Youngo_payment_config_model`:

- `get_safe_config_summary('paymob', 'sandbox')`
- `get_readiness_summary('paymob', 'sandbox')`
- `schema_ready()`
- `encryption_key_is_ready()`

Current local readiness remains blocked because required sandbox values are not configured and CodeIgniter `encryption_key` is empty. The model reports `ready_for_network = false` and `ready_for_cta = false`.

Private Paymob values are never rendered. The UI only displays whether secret/private fields are missing or configured/redacted.

## H. Diagnostic Result

Command:

```text
php scripts/phase_2/youngo_payment_paymob_config_dashboard_ui_1_diagnostic.php
```

Result: PASS.

Verified:

- Controller, view, route, navigation entry, and config model exist.
- Route is `admin/youngo/payment-settings`.
- Navigation entry is Root Admin guarded.
- Controller uses admin session guard and Root Admin guard.
- Controller is GET/read-only and rejects non-GET requests.
- No POST input/save path exists.
- No form, input, or private secret field input exists in the view.
- No Paymob network call patterns exist.
- No legacy `payment_gateways` dependency exists in the UI files.
- Safe config summary and readiness summary load.
- Private fields are rendered as status only.
- Payment/network/CTA flags remain disabled.
- Readiness blocks network and CTA.
- No DB writes were detected.

Regression command:

```text
php scripts/phase_2/youngo_payment_paymob_config_dashboard_schema_1_diagnostic.php
```

Result: PASS.

## I. What Was Not Changed

- No real Paymob values were added.
- No private values were saved or printed.
- No DB schema changes were made in this phase.
- No SQL was executed by this phase beyond the existing schema diagnostic's controlled insert/update/delete cleanup.
- No payment activation flags were enabled.
- No Paymob network calls were added or executed.
- No checkout CTAs were exposed.
- No Root Admin account data was modified.
- No legacy `payment_gateways` rows were used for YounGo Paymob config.
- No legacy payment/enrol rows were written.
- No editable dashboard save flow was implemented.

## J. Remaining Risks/Blockers

- CodeIgniter `encryption_key` is empty, so private payment value storage must remain blocked.
- The current access model is Root Admin only. A dedicated `manage_payment_settings` capability is still needed before any broader admin handoff.
- The dashboard is read-only and cannot configure Paymob yet.
- Sandbox network execution remains blocked until credential storage, local-only flags, webhook tunnel strategy, HMAC validation, and QA gates are approved.

## K. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.CONFIG.DASHBOARD.SAVE.PLAN.1
```

Plan encrypted secret handling, a dedicated payment-settings capability, masked save behavior, audit logging, validation rules, and rollback before implementing any editable dashboard fields.

## L. Git Status

Expected final status after this phase:

```text
 M application/config/routes.php
 M application/views/backend/admin/navigation.php
?? application/controllers/Youngo_payment_settings.php
?? application/views/backend/admin/youngo_payment_settings.php
?? docs/qa/youngo_payment_paymob_config_dashboard_ui_1_report.md
?? scripts/phase_2/youngo_payment_paymob_config_dashboard_ui_1_diagnostic.php
```
