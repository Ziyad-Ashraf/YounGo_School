# PAYMENT.SECRETS.ENCRYPTED.DB.PLAN.1

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Initial worktree status: clean
- Latest visible commit before this phase: `1d06a54 Reconcile YounGo Paymob credential field mapping`
- Scope: planning only for future editable Paymob credentials with encrypted DB storage.
- DB changes: none.
- SQL execution: none.
- Paymob network calls: none.
- Real Paymob values: none added, saved, or printed.
- `encryption_key` change: none.

## B. Files Inspected

- `application/config/config.php`
- `application/config/youngo_paymob.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/models/Youngo_payment_config_model.php`
- `application/models/Youngo_payment_config_audit_model.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `scripts/phase_2/payment_paymob_config_dashboard_schema_1_up.sql`
- `scripts/phase_2/payment_paymob_config_audit_schema_1_up.sql`
- `docs/qa/youngo_payment_paymob_credentials_field_reconcile_1_report.md`
- `docs/qa/youngo_payment_secret_keymgmt_plan_1_report.md`
- `docs/qa/youngo_payment_secrets_hybrid_config_1_report.md`
- `docs/qa/youngo_payment_paymob_setup_form_ui_1_report.md`

Reference checked:

- CodeIgniter 3 official Encryption Library docs indicate the encryption library depends on a configured `encryption_key`: https://codeigniter.com/userguide3/libraries/encryption.html

## C. Encryption/Key Finding

Current local state in `application/config/config.php`:

```php
$config['encryption_key'] = '';
```

Meaning:

- CodeIgniter encrypted DB storage is not ready.
- `Youngo_payment_config_model::encryption_key_is_ready()` currently returns false.
- Existing dashboard save logic correctly blocks private Paymob fields while the key is empty.
- Existing dashboard UI correctly shows private credentials as disabled/server-config-only.
- The current hybrid path remains the only safe runtime option until an explicit key-management phase is approved.

## D. Editable Credential Strategy

Future editable credentials:

- `api_key`
- `public_key`
- `secret_key`
- `hmac_secret`

Recommended strategy:

1. Configure a real CodeIgniter encryption key first, outside Git.
2. Add encrypted DB storage in a dedicated YounGo payment credential table.
3. Keep all credential form fields masked.
4. Never show full values after save.
5. Use blank POST values as "keep current value".
6. Update a stored credential only when a new value is entered.
7. Allow clearing only through a separate explicit action, not by blank form submission.
8. Store and display only presence/readiness states such as `missing`, `configured_redacted`, `encrypted_configured`, or `cleared`.
9. Keep payment, network, webhook testing, checkout route, and CTA activation flags disabled after credential save.
10. Never make a Paymob API call during credential save.

Field classification:

| Field | Future Input | Future Storage | Current/Future Use |
| --- | --- | --- | --- |
| `api_key` | Masked editable field after key setup | encrypted DB | Needed only for old/auth-token or future API flows unless Paymob requires it later |
| `public_key` | Masked editable field after key setup | encrypted DB | Unified Checkout URL rendering with returned client secret |
| `secret_key` | Masked editable field after key setup | encrypted DB | Server-side Intention request authorization |
| `hmac_secret` | Masked editable field after key setup | encrypted DB | Webhook/HMAC verification |

## E. Encryption Key Approach

Plan:

- Generate a high-entropy key outside Git and reports.
- Store it only in a server/local configuration mechanism that is not committed.
- Document the key placement in a private deployment checklist, not in QA reports.
- Keep one key per environment unless there is a deliberate key-rotation plan.
- Do not copy local sandbox keys into production.
- Do not rotate/change the key casually after credentials are stored.

Operational rules:

- If the key is lost, encrypted Paymob credentials cannot be recovered.
- DB backups containing encrypted credential blobs are sensitive because the same environment key can decrypt them.
- File/server backups containing the encryption key are sensitive.
- A restore must include both the DB backup and the matching encryption key.
- Key rotation needs its own phase: decrypt with old key, re-encrypt with new key, audit presence-only changes, and verify sandbox readiness.

Implementation preference:

- Do not directly commit a real key into `application/config/config.php`.
- Prefer an ignored local/server override or deployment-time configuration pattern before enabling encrypted DB storage.
- If the project keeps using `config.php`, the key update must be a server-only change and must not be pushed.

## F. DB/Schema Plan

Preferred schema design: add a dedicated table instead of mixing encrypted blobs into `youngo_payment_provider_configs`.

Proposed future table:

```sql
CREATE TABLE `youngo_payment_provider_encrypted_credentials` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider` VARCHAR(50) NOT NULL DEFAULT 'paymob',
  `mode` VARCHAR(20) NOT NULL DEFAULT 'sandbox',
  `encrypted_api_key` LONGTEXT DEFAULT NULL,
  `encrypted_public_key` LONGTEXT DEFAULT NULL,
  `encrypted_secret_key` LONGTEXT DEFAULT NULL,
  `encrypted_hmac_secret` LONGTEXT DEFAULT NULL,
  `api_key_present` TINYINT(1) NOT NULL DEFAULT 0,
  `public_key_present` TINYINT(1) NOT NULL DEFAULT 0,
  `secret_key_present` TINYINT(1) NOT NULL DEFAULT 0,
  `hmac_secret_present` TINYINT(1) NOT NULL DEFAULT 0,
  `encryption_version` VARCHAR(50) NOT NULL DEFAULT 'ci3_encryption_v1',
  `key_fingerprint` VARCHAR(128) DEFAULT NULL,
  `last_rotated_at` INT(11) UNSIGNED DEFAULT NULL,
  `updated_by_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` INT(11) UNSIGNED DEFAULT NULL,
  `updated_at` INT(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_yppc_secret_provider_mode` (`provider`, `mode`),
  KEY `idx_yppc_secret_updated_by` (`updated_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Keep `youngo_payment_provider_configs` for:

- non-private fields;
- activation/network/checkout gates;
- readiness summary;
- non-secret operational state.

Future model changes:

- Add `Youngo_payment_credentials_model`, or extend `Youngo_payment_config_model` only if the added methods stay clearly separated.
- Suggested methods:
  - `get_credential_presence($provider, $mode)`
  - `save_encrypted_credentials($provider, $mode, $credential_input, $actor_user_id = null)`
  - `decrypt_credentials_for_runtime($provider, $mode)`
  - `clear_credential($provider, $mode, $field, $actor_user_id = null)`
  - `get_safe_credential_summary($provider, $mode)`

Runtime decryption should be limited to Paymob adapter/webhook code paths that actually need credentials. Diagnostics and views must never receive decrypted values.

## G. Dashboard UI Plan

Keep current page structure and add a future "Credentials" save section only after key setup/schema approval.

Page sections:

- Editable non-private fields: existing behavior remains.
- Editable credential fields:
  - `api_key`
  - `public_key`
  - `secret_key`
  - `hmac_secret`
- Readiness/status panel.
- Audit log panel.
- Next steps panel.

Credential field behavior:

- Use masked/password-style fields only for new entry.
- Do not prefill real values.
- Placeholder text: `Configured - leave blank to keep current value` or `Missing - enter value to configure`.
- Blank submission keeps the existing encrypted value.
- A separate "clear" action is required to remove a credential.
- Private values must not be included in flash messages, validation errors, logs, or reports.
- No activation button appears in this phase.
- Sandbox test remains disabled until a separate approved sandbox execution phase.

Form handling plan:

- Keep non-private save and credential save as separate POST actions.
- Require Root Admin guard for both.
- Use CSRF protections if enabled by the current Academy/CodeIgniter setup.
- Reject unknown fields.
- Reject activation flag attempts in credential save payloads.

## H. Audit/Redaction Plan

Existing `Youngo_payment_config_audit_model` already supports redacted summaries and private presence changes. It should be reused and extended where needed.

Audit entries for credential changes should record:

- provider
- mode
- action, such as `dashboard_encrypted_credentials_save` or `dashboard_encrypted_credential_clear`
- actor user id
- actor role/type where available
- timestamp
- changed secret field names
- before/after presence states only
- non-sensitive validation/readiness state changes

Audit entries must never record:

- raw credential values;
- encrypted blobs;
- decrypted values;
- Root Admin password;
- Paymob dashboard screenshots;
- request payloads containing private values.

Recommended presence states:

- `missing`
- `configured_redacted`
- `encrypted_configured`
- `cleared`
- `unchanged`
- `invalid_rejected`

Audit failure behavior:

- Credential save should fail if its audit insert fails. This keeps credential changes accountable.
- Error output must remain generic and must not expose which raw value failed.

## I. Validation Plan

Server-side validation:

- Require configured encryption key before accepting any credential save.
- Provider must be `paymob`.
- Mode must be `sandbox` until a later production phase.
- Reject activation flags in credential save payloads.
- Reject legacy gateway fields.
- Reject unsupported credential field names.
- Store only encrypted values and presence flags.
- Keep existing encrypted values when submitted fields are blank.
- Enforce max lengths before encryption to prevent oversized payloads.
- Optionally perform light shape checks only:
  - `card_integration_id_egp` remains numeric in non-private save.
  - `public_key`, `secret_key`, `api_key`, and `hmac_secret` may have length/minimum-character checks, but should avoid brittle checks until Paymob confirms exact formats.

Runtime validation:

- Decrypt only inside adapter/webhook runtime methods.
- If decryption fails, fail closed with safe error codes.
- Never log decrypted or encrypted values.
- Sandbox readiness should report only presence/readiness state.

QA gates:

- No private values in Git.
- No private values in reports.
- No private values in logs.
- No encrypted blobs displayed in dashboard/audit.
- Save with blank credential fields preserves existing encrypted values.
- Clear action requires explicit separate POST.
- Audit logs show only presence changes.
- All activation/network/CTA gates remain disabled after credential save.
- No Paymob call happens on save.
- Legacy `payment_gateways`, `payment`, and `enrol` remain unchanged.

## J. Implementation Phases

Recommended future phases:

1. `PAYMENT.SECRETS.ENCRYPTION.KEY.SETUP.1`
   - Decide and implement the server-local encryption key loading pattern.
   - Do not store real Paymob values yet.
   - Verify key readiness only.

2. `PAYMENT.SECRETS.ENCRYPTED.DB.SCHEMA.1`
   - Add encrypted credential storage schema locally after backup.
   - Add rollback SQL.
   - No real credential values.

3. `PAYMENT.PAYMOB.CONFIG.DASHBOARD.SECRETS.UI.1`
   - Add masked credential input UI.
   - Keep save disabled or stubbed if schema/key not ready.
   - No Paymob calls.

4. `PAYMENT.PAYMOB.CONFIG.DASHBOARD.SECRETS.SAVE.1`
   - Save placeholder/test-shaped private values encrypted in local DB only.
   - Mask display.
   - Redacted audit.
   - Gates remain disabled.

5. `PAYMENT.PAYMOB.CONFIG.DASHBOARD.SECRETS.UI.QA.1`
   - Browser QA for masked save/keep/clear behavior.
   - Confirm audit redaction and no activation.

6. Later, after explicit approval: `PAYMENT.PAYMOB.SANDBOX.CONFIG.INPUT.QA.1`
   - Enter real sandbox values through the approved secure path.
   - Verify readiness without printing values.

## K. Risks/Blockers

- `encryption_key` is currently empty, so encrypted DB storage cannot be implemented safely yet.
- Changing `encryption_key` has broad implications for any current or future CodeIgniter encryption usage.
- If the encryption key is lost, encrypted Paymob credentials are unrecoverable.
- DB backups become sensitive after encrypted credential blobs are stored.
- A direct `config.php` key change could be accidentally committed unless a safer ignored/server override pattern is introduced first.
- Public key is account-specific and currently treated as private/server-config-only; this should remain consistent unless the owner approves a different classification.
- Paymob exact key formats should be confirmed from the owner dashboard/docs before adding strict format validation.

## L. Recommended Next Phase

Recommended next phase:

`PAYMENT.SECRETS.ENCRYPTION.KEY.SETUP.1`

Goal:

- Choose and implement the non-committed environment/server key loading pattern.
- Confirm CodeIgniter encryption can initialize.
- Keep payment behavior disabled.
- Do not save Paymob credentials yet.

Do not proceed to encrypted credential schema/UI save work until key setup has its own backup, diagnostics, and report.

## M. Git Status

Expected after this planning phase:

- One new report file only.
- No source changes.
- No DB changes.
- No SQL execution.

Final `git status --short` should show this report as untracked or staged by the owner later.
