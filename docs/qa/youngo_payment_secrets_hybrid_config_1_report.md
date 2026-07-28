# PAYMENT.SECRETS.HYBRID.CONFIG.1 - Hybrid Paymob Private Config Readiness

Date: 2026-07-23

Scope: implement hybrid Paymob configuration readiness where non-private settings stay in the YounGo dashboard DB table and private Paymob values come only from ignored local/server config. No deployment, push, real Paymob values, private value output/save, `encryption_key` change, payment enablement, Paymob network request, checkout CTA exposure, Root Admin modification, sandbox execution, or legacy `payment_gateways` use was performed.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
179bc01 Plan YounGo payment private value handling
73846af QA YounGo Paymob config audit panel
8b7fa78 Wire YounGo Paymob config saves to audit logs
8351916 Add YounGo Paymob config audit schema
9360b74 Fix YounGo payment DB access regressions
3271d7b Fix YounGo Paymob settings HTTP DB access
41dd2f4 QA Paymob dashboard save form blocker
2dfcf0e Add non-private YounGo Paymob dashboard save flow
476bfa7 Plan YounGo Paymob dashboard save flow
f2bb2f8 Add read-only YounGo Paymob dashboard config summary
```

The expected branch, clean starting worktree, and latest `PAYMENT.SECRETS.KEYMGMT.PLAN.1` commit were confirmed.

## B. Backup Created

Fresh local DB backup created before diagnostic DB writes:

```text
Path: D:\Work\YounGo\backups\youngo_school_before_payment_secrets_hybrid_config_1_2026_07_23_004220.sql
Size: 917065 bytes
SHA256: 33e63ec504feaf759ed45dc18305386b2b6e1e1906ffad825f98ae1d9a4c6267
```

The backup used local CodeIgniter DB config internally and did not print database credentials.

## C. Files Inspected

Required reports:

- `docs/qa/youngo_payment_secret_keymgmt_plan_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_save_nonprivate_1_report.md`
- `docs/qa/youngo_payment_paymob_config_audit_ui_qa_1_report.md`
- `docs/qa/youngo_payment_paymob_sandbox_plan_1_report.md`

Source inspected:

- `application/config/youngo_paymob.php`
- `application/config/youngo_paymob.local.example.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/models/Youngo_payment_config_model.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `.gitignore`

## D. Files Changed

- `application/config/youngo_paymob.php`
- `application/config/youngo_paymob.local.example.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/models/Youngo_payment_config_model.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `scripts/phase_2/youngo_payment_secrets_hybrid_config_1_diagnostic.php`
- `docs/qa/youngo_payment_secrets_hybrid_config_1_report.md`

## E. Hybrid Config Behavior

Tracked defaults now include the explicit sandbox network testing gate:

```text
sandbox_network_testing_enabled = false
```

The ignored local override example now documents private/server config placeholders for:

- `PAYMOB_SECRET_KEY`
- `PAYMOB_PUBLIC_KEY`
- `PAYMOB_HMAC_SECRET`

The example still contains only `null` values and remains safe to commit.

`Youngo_paymob_config` now supports:

- `is_network_enabled()`
- `is_sandbox_network_testing_enabled()`
- `is_webhook_testing_enabled()`
- `get_secret_key_for_runtime()`
- `get_public_key_for_runtime()`
- `get_hmac_secret_for_runtime()`
- `get_private_presence_summary()`

Runtime private getters return values only when the ignored local/server override is loaded. Diagnostics and dashboard summaries consume only redacted presence states.

`Youngo_payment_config_model` now supports:

- `get_hybrid_readiness_summary($provider, $mode, $server_config_summary)`
- `get_safe_hybrid_config_summary($provider, $mode, $server_config_summary)`

The existing dashboard save path remains non-private only through `upsert_dashboard_non_private_config()`.

## F. Private-Value Redaction Behavior

Allowed display/status labels are limited to:

```text
missing
configured_redacted
server_config_required
db_private_storage_blocked
```

Private DB storage remains blocked while:

```text
application/config/config.php
$config['encryption_key'] = '';
```

Private dashboard save attempts remain rejected. No secret input fields or password fields were added to the dashboard.

## G. Dashboard Readiness Behavior

`/admin/youngo/payment-settings` now receives hybrid summaries from:

- DB-backed non-private Paymob config;
- redacted private presence from `Youngo_paymob_config`, which reads the ignored override if present.

The dashboard now shows:

- hybrid storage mode;
- non-private DB config status;
- private values source;
- private DB storage status;
- whether ignored local/server config is loaded;
- credential presence as redacted/source-required state only;
- readiness storage notes.

Network and CTA readiness remain false in this phase. The page still states that secret entry, network tests, payment activation, and checkout CTA activation are not enabled.

## H. Diagnostic Result

Created and ran:

```text
php scripts/phase_2/youngo_payment_secrets_hybrid_config_1_diagnostic.php
```

Result:

```text
ok: true
failed_checks: []
```

Verified:

- local override path is ignored by Git;
- default gates remain disabled;
- sandbox network testing defaults false;
- example config has placeholder-only private fields;
- config reader exposes runtime getters and redacted private presence;
- model exposes hybrid readiness and safe hybrid summary;
- controller uses hybrid readiness;
- dashboard shows hybrid storage;
- dashboard has no private inputs;
- no Paymob network calls were added;
- no legacy `payment_gateways` dependency was added;
- model loads and schema is ready;
- `encryption_key` remains empty;
- non-private DB save works;
- private DB save is rejected;
- fake ignored local private placeholders are detected only as `configured_redacted`;
- safe reader/model summaries do not print raw placeholder values;
- hybrid readiness remains disabled for network and CTA;
- all behavior gates remain disabled;
- diagnostic DB counts were restored;
- temporary ignored local config was removed.

Regression diagnostics also passed:

```text
php scripts/phase_2/youngo_payment_paymob_config_dashboard_save_nonprivate_1_diagnostic.php
ok: true

php scripts/phase_2/youngo_payment_paymob_config_audit_wire_1_diagnostic.php
ok: true
```

## I. DB/Config Cleanup

Controlled diagnostic writes:

- one temporary `paymob`/`sandbox` non-private config row;
- one temporary ignored local config file with fake placeholders.

Cleanup:

- restored the Paymob config row state;
- removed the temporary ignored local config file;
- left no persistent test config row.

Counts:

```text
youngo_payment_provider_configs: 0 -> 0
payment_gateways: 15 -> 15
payment: 0 -> 0
enrol: 1 -> 1
```

`application/config/youngo_paymob.local.php` was absent after diagnostic cleanup.

## J. What Was Not Changed

- No deployment.
- No push.
- No DB schema change.
- No SQL execution.
- No real Paymob private values.
- No private Paymob value printed.
- No private Paymob value saved to DB.
- No `encryption_key` change.
- No real payment enablement.
- No Paymob network call.
- No real Paymob intention.
- No checkout CTA exposure.
- No Root Admin modification.
- No legacy `payment_gateways` use.
- No sandbox payment execution.
- No ignored local config committed.

## K. Remaining Risks/Blockers

- Real Paymob sandbox values are still owner-provided future inputs and must be supplied outside Git/reports.
- DB-backed private storage remains blocked while `encryption_key` is empty.
- Runtime getters exist for later adapter use, but Paymob network execution is still disabled and unimplemented.
- Public key remains treated as account-specific server/ignored-config presence in this phase; decide later whether it can become dashboard-editable.
- Visual browser QA was not part of this phase.

## L. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.SANDBOX.INTENTION.PLAN.2
```

Purpose:

- confirm exact first sandbox execution gates using the hybrid config path;
- define safe owner credential handoff steps;
- keep CTA off by default;
- prepare the adapter implementation phase without yet calling Paymob.

## M. Validation

Ran PHP lint on every changed PHP file:

```text
php -l application/config/youngo_paymob.php
php -l application/config/youngo_paymob.local.example.php
php -l application/libraries/Youngo_paymob_config.php
php -l application/models/Youngo_payment_config_model.php
php -l application/controllers/Youngo_payment_settings.php
php -l application/views/backend/admin/youngo_payment_settings.php
php -l scripts/phase_2/youngo_payment_secrets_hybrid_config_1_diagnostic.php
```

Result:

```text
No syntax errors detected
```

Ran requested diagnostics sequentially because each diagnostic writes and cleans the same local Paymob config table:

```text
php scripts/phase_2/youngo_payment_secrets_hybrid_config_1_diagnostic.php
php scripts/phase_2/youngo_payment_paymob_config_dashboard_save_nonprivate_1_diagnostic.php
php scripts/phase_2/youngo_payment_paymob_config_audit_wire_1_diagnostic.php
git diff --check
git status --short
```

Result:

```text
PAYMENT.SECRETS.HYBRID.CONFIG.1 diagnostic: ok true, failed_checks []
PAYMENT.PAYMOB.CONFIG.DASHBOARD.SAVE.NONPRIVATE.1 diagnostic: ok true, failed_checks []
PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.WIRE.1 diagnostic: ok true, failed_checks []
git diff --check: PASS
```

## N. Git Status

Final status:

```text
git status --short
 M application/config/youngo_paymob.local.example.php
 M application/config/youngo_paymob.php
 M application/controllers/Youngo_payment_settings.php
 M application/libraries/Youngo_paymob_config.php
 M application/models/Youngo_payment_config_model.php
 M application/views/backend/admin/youngo_payment_settings.php
?? docs/qa/youngo_payment_secrets_hybrid_config_1_report.md
?? scripts/phase_2/youngo_payment_secrets_hybrid_config_1_diagnostic.php
```
