# PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.WIRE.1 - Wire Paymob Non-Private Dashboard Saves to Audit Logs

Date: 2026-07-23

Scope: wire Root-Admin-only non-private Paymob dashboard config saves into redacted audit logging. No deployment, push, real Paymob private values, private value output/save, payment enablement, Paymob network request, checkout CTA exposure, Root Admin data modification, legacy `payment_gateways` use, secret storage, or sandbox payment execution was performed.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
8351916 Add YounGo Paymob config audit schema
9360b74 Fix YounGo payment DB access regressions
3271d7b Fix YounGo Paymob settings HTTP DB access
41dd2f4 QA Paymob dashboard save form blocker
2dfcf0e Add non-private YounGo Paymob dashboard save flow
476bfa7 Plan YounGo Paymob dashboard save flow
f2bb2f8 Add read-only YounGo Paymob dashboard config summary
7b0864d Add YounGo Paymob dashboard config schema foundation
7694e2c Plan YounGo Paymob dashboard configuration
022f936 Plan YounGo Paymob sandbox execution
```

The expected branch and clean starting worktree were confirmed. The latest commit includes `PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.SCHEMA.1`.

## B. Files Inspected

Required reports:

- `docs/qa/youngo_payment_paymob_config_audit_schema_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_save_nonprivate_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_save_ui_fix_1_report.md`

Source inspected:

- `application/models/Youngo_payment_config_model.php`
- `application/models/Youngo_payment_config_audit_model.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `scripts/phase_2/youngo_payment_paymob_config_audit_schema_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_paymob_config_dashboard_save_nonprivate_1_diagnostic.php`

## C. Files Changed

- `application/controllers/Youngo_payment_settings.php`
- `application/models/Youngo_payment_config_audit_model.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `scripts/phase_2/youngo_payment_paymob_config_audit_schema_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_paymob_config_audit_wire_1_diagnostic.php`
- `docs/qa/youngo_payment_paymob_config_audit_wire_1_report.md`

## D. Audit Wiring Behavior

The existing route remains:

```text
/admin/youngo/payment-settings
```

The settings controller now:

- loads `Youngo_payment_config_audit_model`;
- captures the safe Paymob sandbox config summary before save;
- starts a DB transaction;
- calls the existing `Youngo_payment_config_model::upsert_dashboard_non_private_config()` save wrapper;
- captures the safe config summary after a successful save;
- records a redacted audit row with action `dashboard_non_private_save`;
- uses the current admin session user id as actor id when available;
- commits only after both config save and audit insert succeed.

Invalid saves are rejected before audit logging and do not create success audit rows.

Audit failure behavior:

- Audit failure blocks the config save.
- The controller rolls back the config transaction if audit insert fails.
- The user sees a generic safe error message.
- No secret values are included in the error.

This is deliberately stricter than a best-effort audit because payment settings should not change without an audit trail.

## E. Dashboard Audit Display Behavior

The dashboard page now loads recent safe audit entries:

```text
Youngo_payment_config_audit_model::get_recent_audit_logs('paymob', 'sandbox', 10)
```

The view displays:

- date/time
- actor id
- action
- changed field names
- secret presence transitions, if any

The view does not display:

- private Paymob values
- raw public/private keys
- raw request payloads
- IP addresses
- user-agent values
- gateway credentials

When no logs exist, it shows a safe empty state.

## F. Redaction/Private-Value Safety

`Youngo_payment_config_audit_model` was refined so secret fields are excluded from the general `changed_fields` list and represented only in `secret_presence_changes`.

Private raw fields remain rejected:

- `secret_key`
- `hmac_secret`
- `api_key`
- `client_secret`
- `authorization`
- `auth_header`

Allowed secret states remain:

- `missing`
- `configured_redacted`

The dashboard form still has no private inputs and no password inputs.

## G. Diagnostic Result

Created and ran:

```text
php -l application\controllers\Youngo_payment_settings.php
php -l application\models\Youngo_payment_config_audit_model.php
php -l application\views\backend\admin\youngo_payment_settings.php
php -l scripts\phase_2\youngo_payment_paymob_config_audit_schema_1_diagnostic.php
php -l scripts\phase_2\youngo_payment_paymob_config_audit_wire_1_diagnostic.php
php scripts/phase_2/youngo_payment_paymob_config_audit_wire_1_diagnostic.php
```

Result:

```text
ok: true
failed_checks: []
```

Verified:

- Controller loads the audit model.
- Controller captures before/after summaries.
- Controller writes audit only after successful non-private save.
- Controller uses DB transaction boundaries for save plus audit.
- Dashboard recent audit panel is present.
- Valid non-private save creates one redacted audit row.
- Invalid save does not create a success audit row.
- Private-field attempt is rejected and does not create a success audit row.
- Private raw value is not stored in audit JSON.
- Recent audit rows can be read safely.
- Payment/config gates remain disabled.
- `payment_gateways`, `payment`, and `enrol` counts are unchanged.
- Diagnostic config/audit rows are cleaned up.

Regression diagnostics also passed:

```text
php scripts/phase_2/youngo_payment_paymob_config_audit_schema_1_diagnostic.php
ok: true

php scripts/phase_2/youngo_payment_paymob_config_dashboard_save_nonprivate_1_diagnostic.php
ok: true
```

Final repository checks:

```text
git diff --check
PASS

git status --short
expected phase files only
```

Git reported LF-to-CRLF working-copy warnings for modified PHP files during diff checks; no whitespace errors were reported.

## H. DB Cleanup

Controlled diagnostic DB writes:

- one temporary `paymob`/`sandbox` config save using placeholder non-private values;
- one redacted audit row;
- cleanup deleted the temporary audit row;
- cleanup deleted the temporary config row because no baseline Paymob config row existed.

Counts:

```text
youngo_payment_provider_configs: 0 -> 0
youngo_payment_config_audit_logs: 0 -> 0
payment_gateways: 15 -> 15
payment: 0 -> 0
enrol: 1 -> 1
```

No persistent diagnostic config/audit rows were left behind.

## I. What Was Not Changed

- No deployment.
- No push.
- No real Paymob private values.
- No private values printed.
- No private Paymob values saved.
- No real payments enabled.
- No Paymob calls.
- No checkout CTAs exposed.
- No Root Admin data changed.
- No legacy `payment_gateways` use.
- No secret storage implementation.
- No sandbox payment execution.
- No legacy `payment` rows.
- No legacy `enrol` rows.
- No YounGo checkout/order/transaction/access rows.

## J. Remaining Risks/Blockers

- Browser QA of the audit panel/save flow remains a later phase.
- Private Paymob credential storage is still blocked until encryption/key management is approved.
- Audit logging currently applies to non-private dashboard saves only.
- Future secret-presence transitions need a separate secret handling phase before they can occur through the dashboard.

## K. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.UI.QA.1
```

Suggested scope:

- Root-Admin authenticated browser/server-session QA for `/admin/youngo/payment-settings`.
- Submit placeholder non-private values.
- Confirm one safe audit row appears in the dashboard.
- Confirm invalid/private attempts do not create success audit rows.
- Cleanup/restore local config and audit rows.
- Confirm all gates remain disabled and no Paymob calls occur.

## L. Git Status

Expected final git status after this phase:

```text
 M application/controllers/Youngo_payment_settings.php
 M application/models/Youngo_payment_config_audit_model.php
 M application/views/backend/admin/youngo_payment_settings.php
 M scripts/phase_2/youngo_payment_paymob_config_audit_schema_1_diagnostic.php
?? docs/qa/youngo_payment_paymob_config_audit_wire_1_report.md
?? scripts/phase_2/youngo_payment_paymob_config_audit_wire_1_diagnostic.php
```
