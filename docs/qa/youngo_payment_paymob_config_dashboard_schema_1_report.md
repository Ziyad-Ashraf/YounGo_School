# PAYMENT.PAYMOB.CONFIG.DASHBOARD.SCHEMA.1 - Local Paymob Dashboard Config Storage Schema

Date: 2026-07-22

Scope: local schema/model foundation for future Paymob dashboard configuration. No deployment, push, real Paymob values, private value output, real payment enablement, Paymob network request, checkout CTA exposure, Root Admin modification, legacy `payment_gateways` config use, dashboard UI implementation, or legacy payment/enrol write was performed.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
7694e2c Plan YounGo Paymob dashboard configuration
022f936 Plan YounGo Paymob sandbox execution
0d663c1 QA gated YounGo checkout CTA clickthrough
68b9b28 Add gated YounGo checkout CTA helper
bda9cb1 Plan YounGo checkout CTA exposure
b59eaee QA local YounGo checkout smoke flow
bcb01b7 Fix YounGo checkout HTTP DB access
92fd18e QA authenticated YounGo checkout start blocker
cf98079 QA disabled YounGo checkout route safety
c26a933 Add controlled local YounGo checkout flow
```

The expected branch and clean starting worktree were confirmed. The latest commit includes `PAYMENT.PAYMOB.CONFIG.DASHBOARD.PLAN.1`.

## B. Backup Created

Fresh local DB backup was created before schema execution:

```text
Path: D:\Work\YounGo\backups\youngo_school_before_paymob_config_dashboard_schema_1_2026_07_22_211749.sql
Size: 627604 bytes
SHA256: E98628BD3E00654A8FC6005A7084445CB31E91AE7A8FAB2690318311DCC9C3EC
```

The backup command used local database config internally without printing database credentials.

## C. Files Inspected

Required reports:

- `docs/qa/youngo_payment_paymob_config_dashboard_plan_1_report.md`
- `docs/qa/youngo_payment_paymob_sandbox_plan_1_report.md`
- `docs/qa/youngo_payment_config_file_1_report.md`

Project guidance:

- `YOUNGO_PROJECT_CONTEXT.md`
- `docs/design/youngo_style_direction.md`
- `docs/planning/README.md`
- `docs/planning/youngo_master_plan_v2.md`
- `docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`
- `docs/agents/implementation_rules.md`
- `docs/reference/README.md`

Source and schema context:

- `application/config/config.php`
- `application/config/youngo_paymob.php`
- `application/config/youngo_paymob.local.example.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/libraries/Youngo_paymob_adapter.php`
- `application/libraries/Youngo_paymob_webhook.php`
- `application/controllers/Youngo_checkout.php`
- `application/controllers/Youngo_payment_webhook.php`
- `application/controllers/Admin.php`
- `application/models/Crud_model.php`
- `application/models/Payment_model.php`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_payment_model.php`
- `application/models/Youngo_entitlement_write_model.php`
- `application/models/Youngo_subscription_model.php`
- `application/helpers/youngo_capability_helper.php`
- `application/views/backend/admin/navigation.php`
- `application/views/backend/admin/payment_settings.php`
- `application/config/routes.php`
- `scripts/phase_2/`
- `database/phase_2/`

## D. Files Changed

Created:

- `scripts/phase_2/payment_paymob_config_dashboard_schema_1_up.sql`
- `scripts/phase_2/payment_paymob_config_dashboard_schema_1_down.sql`
- `application/models/Youngo_payment_config_model.php`
- `scripts/phase_2/youngo_payment_paymob_config_dashboard_schema_1_diagnostic.php`
- `docs/qa/youngo_payment_paymob_config_dashboard_schema_1_report.md`

Local database changed:

- Created table `youngo_payment_provider_configs`.

No existing source config defaults were changed.

## E. Encryption/Key-Management Finding

Current CodeIgniter encryption key state:

```text
application/config/config.php
$config['encryption_key'] = '';
```

Finding:

- The encryption key is empty.
- Private Paymob values must not be saved to DB in this phase.
- `Youngo_payment_config_model` rejects private fields such as `secret_key`, `hmac_secret`, `api_key`, and runtime `client_secret`.
- Private-value storage status defaults to `blocked_encryption_key_missing`.
- The model stores only public/non-private fields and private presence/status flags.

Required future decision:

- Establish an encryption-at-rest strategy with a key supplied outside Git and outside reports before DB-backed private Paymob value storage is implemented.
- Until then, real private Paymob sandbox values must remain in ignored local/server config or another approved secure channel.

## F. Schema Summary

Applied local schema artifact:

```text
scripts/phase_2/payment_paymob_config_dashboard_schema_1_up.sql
```

Created table:

```text
youngo_payment_provider_configs
```

Core fields:

- `provider`
- `mode`
- `currency`
- `amount_multiplier`
- `enabled`
- `network_enabled`
- `sandbox_network_testing_enabled`
- `webhook_testing_enabled`
- `checkout_routes_enabled`
- `checkout_local_testing_enabled`
- `checkout_cta_enabled`
- `live_mode_allowed`

Public/non-private config fields:

- `public_key`
- `card_integration_id_egp`
- `api_base_url`
- `checkout_base_url`
- `return_url`
- `notification_url`
- `transaction_inquiry_enabled`

Private value presence/status only:

- `public_key_present`
- `secret_key_present`
- `hmac_secret_present`
- `api_key_present`
- `private_storage_status`

Readiness/audit support:

- `readiness_status`
- `readiness_errors`
- `last_readiness_checked_at`
- `last_sandbox_test_at`
- `last_sandbox_test_status`
- `updated_by_user_id`
- `created_at`
- `updated_at`

Indexes:

- Primary key on `id`
- Unique key `uniq_yppc_provider_mode` on `provider`, `mode`
- Index `idx_yppc_provider_currency` on `provider`, `currency`
- Index `idx_yppc_readiness_status` on `readiness_status`
- Index `idx_yppc_updated_by` on `updated_by_user_id`

Safety properties:

- Additive `CREATE TABLE IF NOT EXISTS`.
- Default mode is `sandbox`.
- Default currency is `EGP`.
- All activation, network, webhook, checkout route, local testing, CTA, live-mode, and transaction inquiry flags default to `0`.
- No `secret_key`, `hmac_secret`, `api_key`, or `client_secret` value column exists.
- No hard foreign keys were added.
- No legacy `payment_gateways` table is used by the schema.

## G. Model Behavior

Created model:

```text
application/models/Youngo_payment_config_model.php
```

Supported methods:

- `get_provider_config($provider, $mode)`
- `upsert_non_private_config($provider, $mode, $data)`
- `get_readiness_summary($provider, $mode)`
- `get_safe_config_summary($provider, $mode)`
- `schema_ready()`
- `encryption_key_is_ready()`

Behavior:

- Returns structured `ok`, `code`, `message`, `data`, and `errors` arrays.
- Returns disabled EGP/sandbox defaults when no row exists.
- Accepts only non-private config data in this phase.
- Rejects private Paymob fields while encryption is unavailable.
- Rejects attempts to enable activation/testing flags in this phase.
- Rejects non-EGP currency.
- Requires amount multiplier `100`.
- Stores safe public/non-private placeholders only during diagnostics.
- Redacts safe summaries using `missing` or `configured_redacted`.
- Readiness remains `not_configured` and network/CTA readiness remains false until private storage and activation phases are explicitly implemented.

## H. Diagnostic Result

Created and ran:

```text
scripts/phase_2/youngo_payment_paymob_config_dashboard_schema_1_diagnostic.php
```

Result:

```text
ok: true
failed_checks: []
```

Key PASS checks:

- Table exists.
- Expected columns exist.
- Unique provider/mode index exists.
- Defaults are disabled.
- Defaults are `sandbox` and `EGP`.
- Encryption key is empty.
- Private storage status is blocked.
- Private value save is rejected.
- Activation flag enable attempt is rejected.
- Non-private diagnostic config insert/update works locally.
- Safe summary redacts private fields.
- Readiness blocks network and reports encryption blocker.
- Diagnostic row cleanup succeeded.
- Legacy `payment_gateways`, `payment`, and `enrol` counts stayed unchanged.
- No real Paymob values were present in diagnostic output.

Compatibility diagnostic:

```text
php scripts/phase_2/youngo_payment_checkout_cta_local_block_1_diagnostic.php
ok: true
failed_checks: []
```

This confirmed the CTA helper still hides checkout by default, no public checkout CTA regression was detected, no Paymob network calls were introduced, and protected checkout/payment/access/payment/enrol counts remained unchanged.

## I. DB Cleanup/Rollback Summary

Schema apply result:

```text
schema_apply=ok
```

Persistent local DB schema addition:

- `youngo_payment_provider_configs`

Controlled diagnostic DB writes:

- Inserted one diagnostic non-private config row using provider `paymob_schema_diag`.
- Updated that same diagnostic row.
- Deleted the diagnostic row.

Diagnostic cleanup result:

```text
youngo_payment_provider_configs: before=0, after=0
payment_gateways: before=15, after=15
payment: before=0, after=0
enrol: before=1, after=1
```

Rollback SQL created:

```text
scripts/phase_2/payment_paymob_config_dashboard_schema_1_down.sql
```

Rollback note:

- The down SQL was not executed.
- Primary rollback remains restoring the fresh local DB backup listed in section B.
- The down SQL drops only `youngo_payment_provider_configs`.

## J. What Was Not Changed

Not changed:

- No deployment.
- No push.
- No commit.
- No live cPanel server.
- No live/cPanel DB.
- No real Paymob values.
- No private values printed.
- No private Paymob values saved to DB.
- No real payment activation.
- No Paymob network calls.
- No real Paymob intentions.
- No public checkout CTA exposure.
- No dashboard UI.
- No Root Admin modification.
- No legacy `payment_gateways` config use.
- No legacy gateway row modification.
- No legacy `payment` rows.
- No legacy `enrol` rows.
- No YounGo checkout order rows left behind.
- No YounGo payment transaction rows.
- No entitlement/access rows.

## K. Remaining Risks/Blockers

- CodeIgniter `encryption_key` is empty; private DB-backed secret storage remains blocked.
- The new model intentionally rejects activation flags, so dashboard save/activation behavior still needs later explicit phases.
- Public key and integration ID are treated as non-private fields, but real account values must still be handled carefully in reports and screenshots.
- No dashboard UI exists yet.
- No Paymob network calls exist yet.
- Paymob sandbox credentials and dashboard access are still owner-provided future inputs.
- A public HTTPS tunnel is still required for real webhook QA.
- Subscription purchase issuance remains outside this phase.

## L. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.CONFIG.DASHBOARD.UI.1 - Read-Only Paymob Dashboard Config Summary
```

Suggested scope:

- Add a Root/core-owner-only YounGo Payment Settings dashboard page.
- Show file config plus DB config readiness summary.
- Display private fields only as `missing` or `configured_redacted`.
- Do not save private values.
- Do not enable payment/network/CTA flags.
- Do not perform Paymob calls.
- Do not use legacy `payment_gateways`.

## M. Git Status

Expected git status after this phase:

```text
?? application/models/Youngo_payment_config_model.php
?? docs/qa/youngo_payment_paymob_config_dashboard_schema_1_report.md
?? scripts/phase_2/payment_paymob_config_dashboard_schema_1_down.sql
?? scripts/phase_2/payment_paymob_config_dashboard_schema_1_up.sql
?? scripts/phase_2/youngo_payment_paymob_config_dashboard_schema_1_diagnostic.php
```

No tracked production defaults, ignored local config file, credentials, Root Admin records, live server files, public CTAs, or legacy gateway/payment/enrol rows were changed.
