# PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.SCHEMA.1 - Paymob Config Audit Logging Schema

Date: 2026-07-22

Scope: local audit logging foundation for YounGo Paymob dashboard configuration changes. No deployment, push, real Paymob values, private value output/save, payment enablement, Paymob network request, checkout CTA exposure, Root Admin data modification, legacy `payment_gateways` use, secret storage implementation, or sandbox payment execution was performed.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
9360b74 Fix YounGo payment DB access regressions
3271d7b Fix YounGo Paymob settings HTTP DB access
41dd2f4 QA Paymob dashboard save form blocker
2dfcf0e Add non-private YounGo Paymob dashboard save flow
476bfa7 Plan YounGo Paymob dashboard save flow
f2bb2f8 Add read-only YounGo Paymob dashboard config summary
7b0864d Add YounGo Paymob dashboard config schema foundation
7694e2c Plan YounGo Paymob dashboard configuration
022f936 Plan YounGo Paymob sandbox execution
0d663c1 QA gated YounGo checkout CTA clickthrough
```

The expected branch and clean starting worktree were confirmed. The latest commit includes `PAYMENT.DB.ACCESS.SWEEP.1`.

## B. Backup Created

Fresh local DB backup created before schema execution:

```text
Path: D:\Work\YounGo\backups\youngo_school_before_paymob_config_audit_schema_1_2026_07_22_205203.sql
Size: 924710 bytes
SHA256: 0ED9E549FB46B501E2AE442B27133DB89AF3F5BF77113E1CBFEC930B328FE168
```

The backup used local CodeIgniter database config internally without printing database credentials.

## C. Files Inspected

Required reports:

- `docs/qa/youngo_payment_db_access_sweep_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_save_plan_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_save_nonprivate_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_schema_1_report.md`

Source and phase context:

- `application/models/Youngo_payment_config_model.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `scripts/phase_2/payment_paymob_config_dashboard_schema_1_up.sql`
- `scripts/phase_2/payment_paymob_config_dashboard_schema_1_down.sql`
- `scripts/phase_2/youngo_payment_paymob_config_dashboard_schema_1_diagnostic.php`

## D. Files Changed

Created:

- `scripts/phase_2/payment_paymob_config_audit_schema_1_up.sql`
- `scripts/phase_2/payment_paymob_config_audit_schema_1_down.sql`
- `application/models/Youngo_payment_config_audit_model.php`
- `scripts/phase_2/youngo_payment_paymob_config_audit_schema_1_diagnostic.php`
- `docs/qa/youngo_payment_paymob_config_audit_schema_1_report.md`

Local database changed:

- Created table `youngo_payment_config_audit_logs`.

## E. Audit Schema Summary

Applied local schema artifact:

```text
scripts/phase_2/payment_paymob_config_audit_schema_1_up.sql
```

Created table:

```text
youngo_payment_config_audit_logs
```

Fields:

- `provider`
- `mode`
- `action`
- `actor_user_id`
- `actor_role`
- `actor_type`
- `changed_fields_json`
- `before_summary_json`
- `after_summary_json`
- `secret_presence_changes_json`
- `ip_address`
- `user_agent`
- `created_at`

Indexes:

- Primary key on `id`
- `idx_ypcal_provider_mode_created`
- `idx_ypcal_actor_created`
- `idx_ypcal_action_created`

Safety properties:

- Additive `CREATE TABLE IF NOT EXISTS`.
- No raw private Paymob columns such as `secret_key`, `hmac_secret`, `api_key`, or `client_secret`.
- No hard foreign keys.
- No inherited Academy `payment_gateways` dependency.
- No payment/network/webhook/CTA activation fields were changed.

Rollback SQL created:

```text
scripts/phase_2/payment_paymob_config_audit_schema_1_down.sql
```

The down SQL was not executed.

## F. Audit Model Behavior

Created model:

```text
application/models/Youngo_payment_config_audit_model.php
```

Supported methods:

- `record_config_audit($provider, $mode, $action, $before, $after, $actor_user_id = null)`
- `get_recent_audit_logs($provider, $mode, $limit = 20)`
- `get_safe_audit_summary($row)`
- `schema_ready()`

Behavior:

- Returns structured `ok`, `code`, `message`, `data`, and `errors` arrays.
- Supports injected DB for CLI diagnostics and CodeIgniter DB fallback for HTTP runtime.
- Stores redacted before/after summaries.
- Stores changed field names.
- Stores secret presence transitions only, such as `missing` to `configured_redacted`.
- Redacts IP/user-agent in safe summaries.
- Does not call Paymob.
- Does not activate payment behavior.
- Does not write legacy payment/enrol/gateway rows.

The existing dashboard save controller was not wired to audit logging in this phase. That keeps this phase limited to schema/model foundation and diagnostic proof.

## G. Redaction/Private-Value Safety

Private raw fields are rejected before insert:

- `secret_key`
- `hmac_secret`
- `api_key`
- `client_secret`
- `authorization`
- `auth_header`

Allowed secret states in audit summaries:

- `missing`
- `configured_redacted`

Non-private/account-specific fields are also summarized as presence labels where appropriate:

- `public_key`
- `card_integration_id_egp`
- `api_base_url`
- `checkout_base_url`
- `return_url`
- `notification_url`

The diagnostic verified that a raw private-value attempt is rejected and that the stored/read safe audit summary contains no private-value-shaped strings.

## H. Diagnostic Result

Created and ran:

```text
php -l application\models\Youngo_payment_config_audit_model.php
php -l scripts\phase_2\youngo_payment_paymob_config_audit_schema_1_diagnostic.php
php scripts/phase_2/youngo_payment_paymob_config_audit_schema_1_diagnostic.php
```

Result:

```text
ok: true
failed_checks: []
```

Verified:

- Audit up/down SQL files exist.
- Audit model exists and loads.
- Dedicated audit table exists.
- Expected columns exist.
- Expected indexes exist.
- Raw private value is rejected.
- One redacted diagnostic audit row can be inserted.
- Recent audit logs can be read safely.
- Safe summary redacts private fields.
- Secret presence transition is recorded.
- Diagnostic audit row is deleted.
- Provider config count is unchanged.
- Legacy `payment_gateways`, `payment`, and `enrol` counts are unchanged.
- Existing payment gates are unchanged.
- No Paymob network call patterns were introduced.

Requested regression validation also passed:

```text
php scripts/phase_2/youngo_payment_paymob_config_dashboard_save_nonprivate_1_diagnostic.php
ok: true

php scripts/phase_2/youngo_payment_db_access_sweep_1_diagnostic.php
ok: true
```

The DB access sweep now scans 20 payment diagnostic/runtime files and confirms 5 scripts retain manual diagnostic DB injection support.

## I. DB Cleanup

Persistent local DB schema addition:

- `youngo_payment_config_audit_logs`

Controlled diagnostic DB writes:

- Inserted one diagnostic audit row.
- Deleted the diagnostic audit row.

Counts:

```text
youngo_payment_config_audit_logs: 0 -> 0
youngo_payment_provider_configs: 0 -> 0
payment_gateways: 15 -> 15
payment: 0 -> 0
enrol: 1 -> 1
```

No persistent diagnostic audit, config, payment, checkout, entitlement, legacy payment, or legacy enrol rows were left behind.

## J. What Was Not Changed

- No deployment.
- No push.
- No real Paymob values.
- No private values printed.
- No private Paymob values saved.
- No real payments enabled.
- No Paymob calls.
- No checkout CTAs exposed.
- No Root Admin data changed.
- No legacy `payment_gateways` use.
- No secret storage implementation.
- No sandbox payment execution.
- No dashboard UI save behavior change.
- No legacy `payment` rows.
- No legacy `enrol` rows.
- No YounGo checkout/order/transaction/access rows left behind.

## K. Remaining Risks/Blockers

- The dashboard save flow is not yet wired to call the audit model.
- Private Paymob credential storage remains blocked because key management is not ready.
- The audit table currently logs only future-safe redacted summaries and presence transitions; it does not solve secret storage.
- A later phase should decide whether an audit failure blocks config save atomically before wiring it into dashboard POST behavior.
- Browser QA is not part of this schema/model phase.

## L. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.WIRE.1
```

Suggested scope:

- Wire non-private dashboard save to `Youngo_payment_config_audit_model`.
- Capture safe before/after summaries only.
- Roll back the config save if audit insert fails.
- Keep private values blocked.
- Keep all payment/network/webhook/checkout CTA gates disabled.
- Add server-session/dashboard QA for audit row creation and cleanup/restoration.

## M. Git Status

Expected final git status after this phase:

```text
?? application/models/Youngo_payment_config_audit_model.php
?? docs/qa/youngo_payment_paymob_config_audit_schema_1_report.md
?? scripts/phase_2/payment_paymob_config_audit_schema_1_down.sql
?? scripts/phase_2/payment_paymob_config_audit_schema_1_up.sql
?? scripts/phase_2/youngo_payment_paymob_config_audit_schema_1_diagnostic.php
```
