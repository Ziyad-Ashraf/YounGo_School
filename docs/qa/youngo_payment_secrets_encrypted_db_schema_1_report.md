# PAYMENT.SECRETS.ENCRYPTED.DB.SCHEMA.1

## A. Current Branch/Status

- Branch at start: `analysis/cms-audit`
- Start worktree status: clean
- Latest commits at start included:
  - `299efd6 Report local YounGo encryption key creation`
  - `9119041 QA configured YounGo encryption key readiness`
- Scope: local schema foundation for encrypted Paymob credential storage.

The ignored local encryption key file exists and was not printed.

## B. Backup Created

Fresh local DB backup was created before schema execution:

```text
D:\Work\YounGo\backups\youngo_school_before_payment_secrets_encrypted_db_schema_1_2026_07_25_204523.sql
```

Backup metadata:

```text
size=578964 bytes
sha256=63209017D1F69738EBA98DC5D05097CA197D64DC636615F096691A8C6BD1B4BE
```

## C. Files Inspected

- `application/config/config.php`
- `application/config/youngo_security.local.example.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/models/Youngo_payment_config_model.php`
- `application/models/Youngo_payment_config_audit_model.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `application/config/routes.php`
- `scripts/phase_2/`
- `docs/qa/youngo_payment_secrets_encrypted_db_plan_1_report.md`
- `docs/qa/youngo_payment_encryption_key_setup_1_report.md`
- `docs/qa/youngo_payment_encryption_key_local_create_1_report.md`
- `docs/qa/youngo_payment_encryption_key_local_ui_qa_1_report.md`
- `docs/qa/youngo_payment_paymob_credentials_field_reconcile_1_report.md`

## D. Files Changed

Created:

- `scripts/phase_2/payment_secrets_encrypted_db_schema_1_up.sql`
- `scripts/phase_2/payment_secrets_encrypted_db_schema_1_down.sql`
- `scripts/phase_2/youngo_payment_secrets_encrypted_db_schema_1_diagnostic.php`
- `docs/qa/youngo_payment_secrets_encrypted_db_schema_1_report.md`

Updated:

- `application/models/Youngo_payment_config_model.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `scripts/phase_2/youngo_payment_paymob_setup_form_ui_1_diagnostic.php`

## E. Schema/Table Summary

Applied locally:

```text
youngo_payment_provider_secret_configs
```

Table purpose:

- hold encrypted Paymob credential blobs later;
- store credential presence flags only;
- support provider/mode uniqueness;
- support key/encryption metadata;
- avoid legacy `payment_gateways`.

Key fields:

- `provider`
- `mode`
- `encrypted_api_key`
- `encrypted_public_key`
- `encrypted_secret_key`
- `encrypted_hmac_secret`
- `has_api_key`
- `has_public_key`
- `has_secret_key`
- `has_hmac_secret`
- `encryption_version`
- `key_fingerprint`
- `storage_status`
- `last_rotated_at`
- `updated_by_user_id`
- `created_at`
- `updated_at`

The table has no raw/plain credential columns named `api_key`, `public_key`, `secret_key`, or `hmac_secret`.

No rows were inserted.

## F. Model/Readiness Changes

`Youngo_payment_config_model` now supports read-only encrypted-secret schema readiness:

- `secret_schema_ready()`
- `encrypted_credential_storage_status($provider, $mode)`
- `get_secret_credential_presence($provider, $mode)`

Current local status with the ignored encryption key file present:

```text
key_ready_schema_ready_no_values
```

The model returns only redacted presence/status. It does not save, decrypt, print, or return private credential values to views.

`public_key` is now treated as private in the model and is not accepted as a non-private dashboard save field.

## G. Dashboard Readiness Behavior

`/admin/youngo/payment-settings` readiness wording was updated to distinguish:

- encryption key configured;
- encrypted credential schema ready;
- credential values not entered yet;
- credential entry/save UI still disabled.

The page now reports encrypted DB credential storage as:

```text
key_ready_schema_ready_no_values
```

Private credential fields remain presentation-only.

## H. Private Credential Safety

Private Paymob fields remain:

- disabled;
- readonly;
- no submit `name` attributes;
- no values shown;
- no save route/action enabled.

Fields covered:

- `api_key`
- `public_key`
- `secret_key`
- `hmac_secret`

The ignored key file was not printed. No Paymob private value was added, saved, logged, or displayed.

## I. Diagnostic Result

Validation diagnostic:

```text
php scripts/phase_2/youngo_payment_secrets_encrypted_db_schema_1_diagnostic.php
```

Result:

```text
PASS
storage_status=key_ready_schema_ready_no_values
secret_schema_row_count=0
```

The diagnostic verified:

- encrypted credential table exists;
- expected encrypted columns exist;
- presence flags exist;
- raw/plain credential columns do not exist in the new secret table;
- no credential rows were inserted;
- local key file is ignored;
- key presence is detected only as `configured_redacted`;
- dashboard private fields still have no submit names;
- no credential save action is enabled;
- no Paymob calls were added;
- activation gates remain disabled;
- protected counts were unchanged during the diagnostic.

## J. DB Impact

Schema write performed locally:

- created `youngo_payment_provider_secret_configs`.

No data rows were inserted into the new table.

Protected counts after diagnostic:

```text
youngo_checkout_orders=0
youngo_payment_transactions=0
youngo_course_access=0
payment=0
enrol=1
payment_gateways=15
youngo_payment_provider_secret_configs=0
```

Existing legacy tables were not modified.

Rollback SQL created but not executed:

```text
scripts/phase_2/payment_secrets_encrypted_db_schema_1_down.sql
```

## K. What Was Not Changed

- No deployment.
- No push.
- No real Paymob values added.
- No Paymob private values printed.
- No encryption key printed.
- No `application/config/youngo_security.local.php` contents printed.
- No Paymob credential save UI added.
- No private credential submit names added.
- No payment activation.
- No Paymob calls.
- No checkout CTA exposure.
- No Root Admin data modification.
- No legacy `payment_gateways` use for YounGo Paymob.
- No legacy `payment` or `enrol` writes.

## L. Remaining Risks/Blockers

- Encrypted credential entry/save UI is still not implemented.
- Credential encryption/decryption logic for runtime use is still not implemented.
- Credential save audit logging is still pending.
- Real Paymob sandbox values must not be entered until a later approved credential-save phase.
- Sandbox payment execution remains blocked until credential save, readiness QA, and explicit sandbox execution gates are approved.

## M. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.CONFIG.DASHBOARD.SECRETS.UI.1
```

Purpose:

- Add masked credential input UI only.
- Keep credential save disabled or explicitly guarded until save behavior is approved.
- Continue showing no existing private values.
- Keep payment/network/CTA gates disabled.

## N. Git Status

Expected changed files after this phase:

```text
M  application/controllers/Youngo_payment_settings.php
M  application/models/Youngo_payment_config_model.php
M  application/views/backend/admin/youngo_payment_settings.php
M  scripts/phase_2/youngo_payment_paymob_setup_form_ui_1_diagnostic.php
?? docs/qa/youngo_payment_secrets_encrypted_db_schema_1_report.md
?? scripts/phase_2/payment_secrets_encrypted_db_schema_1_down.sql
?? scripts/phase_2/payment_secrets_encrypted_db_schema_1_up.sql
?? scripts/phase_2/youngo_payment_secrets_encrypted_db_schema_1_diagnostic.php
```

`application/config/youngo_security.local.php` remains ignored and must not be committed.
