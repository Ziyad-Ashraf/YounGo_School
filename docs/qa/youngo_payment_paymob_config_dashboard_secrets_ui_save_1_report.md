# PAYMENT.PAYMOB.CONFIG.DASHBOARD.SECRETS.UI.SAVE.1

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting latest commit: `14890a8 Add encrypted YounGo Paymob credential schema`
- Starting worktree: clean before this phase's source/report changes.
- Local ignored encryption key file: present and ignored by Git; key value was not printed or inspected in output.

## B. Backup Created

- Backup path: `D:\Work\YounGo\backups\youngo_school_before_paymob_config_dashboard_secrets_ui_save_1_2026_07_25_210527.sql`
- Size: `580893` bytes
- SHA256: `5144F7E5CE06D4A90271206091AF16607168FF904E31CE370A51929CDB03FC4A`

## C. Files Inspected

- `application/config/config.php`
- `application/config/youngo_security.local.example.php`
- `application/config/youngo_paymob.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/models/Youngo_payment_config_model.php`
- `application/models/Youngo_payment_config_audit_model.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `scripts/phase_2/payment_secrets_encrypted_db_schema_1_up.sql`
- `scripts/phase_2/youngo_payment_secrets_encrypted_db_schema_1_diagnostic.php`

## D. Files Changed

- `application/models/Youngo_payment_config_model.php`
- `application/models/Youngo_payment_config_audit_model.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `scripts/phase_2/youngo_payment_paymob_config_dashboard_secrets_ui_save_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_paymob_setup_form_ui_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_secrets_encrypted_db_schema_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_paymob_config_dashboard_save_nonprivate_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_paymob_config_audit_wire_1_diagnostic.php`
- `docs/qa/youngo_payment_paymob_config_dashboard_secrets_ui_save_1_report.md`

## E. Encrypted Save Implementation Summary

`Youngo_payment_config_model` now supports encrypted Paymob credential storage for `api_key`, `public_key`, `secret_key`, and `hmac_secret` in `youngo_payment_provider_secret_configs`.

Implemented behavior:

- Requires provider `paymob`.
- Allows `sandbox` mode only in this phase.
- Requires configured CodeIgniter encryption key from the ignored local/server key pattern.
- Requires encrypted credential schema to exist.
- Encrypts values with OpenSSL `aes-256-gcm`.
- Stores encrypted JSON payloads only in `encrypted_*` columns.
- Stores presence flags: `has_api_key`, `has_public_key`, `has_secret_key`, `has_hmac_secret`.
- Stores `encryption_version` and a non-displayed key fingerprint.
- Leaves blank submitted fields unchanged.
- Rejects too-short, empty, oversized, unsupported, or invalid-control-character values.
- Provides `decrypt_credentials_for_runtime()` for future internal adapter use only; it is not wired to views and was only tested in-memory by diagnostics.

No raw credential columns were added or used.

## F. Dashboard Credential UI Summary

`/admin/youngo/payment-settings` now has a separate encrypted credential form for Root Admin only:

- API key
- Public key
- Secret key
- HMAC secret

UI behavior:

- Inputs are password/masked.
- Existing values are never prefilled.
- Credential fields have submit names only under the `credentials[...]` namespace when key/schema are ready.
- No flat `api_key`, `public_key`, `secret_key`, or `hmac_secret` POST names are used.
- Page shows only `missing` / `configured_redacted` statuses.
- Credential save is separate from non-private dashboard settings save.
- Non-private settings save remains unchanged and still saves only sandbox/EGP non-private fields.

## G. Validation Behavior

Credential validation:

- `api_key`, `public_key`, `secret_key`, `hmac_secret`: accepted only as non-empty strings when submitted.
- Blank field: keep existing encrypted value.
- Very short values: rejected.
- Control characters: rejected.
- Network/API validation: not performed in this phase.

Non-private validation remains:

- `mode`: sandbox only.
- `currency`: EGP only.
- `amount_multiplier`: 100 only.
- `card_integration_id_egp`: blank or numeric.
- URLs: blank or valid `http` / `https`.
- Activation/network/CTA flags are not editable and remain disabled.

## H. Audit/Redaction Behavior

Credential saves create a redacted audit action:

- Action: `dashboard_secret_credentials_save`
- Actor id: current Root Admin user id when available.
- Changed fields: credential field names only.
- Secret presence changes: `missing` to `configured_redacted`, or equivalent presence-only transitions.
- Raw values are rejected from audit input.
- Encrypted blobs are not rendered in audit output.

Controller behavior is fail-safe: if audit insert fails, the credential save transaction is rolled back and a generic error is shown.

## I. Readiness Behavior

The readiness page now distinguishes:

- Encryption key configured: yes/no only.
- Encrypted credential schema: ready/missing.
- Encrypted DB credential storage: redacted storage status only.
- Credential presence: redacted presence only.
- Private server-config fallback: still shown for compatibility with the hybrid config path.

`public_key`, `secret_key`, and `hmac_secret` satisfy the current Intention API / Unified Checkout private-readiness gate when stored encrypted or supplied by safe server config fallback. `api_key` is stored and reconciled because the Paymob dashboard exposes it, but it is not currently a required Intention API readiness gate.

Payment execution, network execution, checkout CTA exposure, and sandbox test controls remain disabled.

## J. Browser QA Summary

Authenticated local HTTP/browser-session QA was run against:

- `/admin/dashboard`
- `/admin/youngo/payment-settings`
- `/`
- `/home/courses`
- `/home/course/robotics-and-ai-explorers/9`
- `/home/my_wishlist`

Results:

- Admin dashboard returned HTTP 200.
- Paymob settings page returned HTTP 200 before and after save.
- Credential form rendered.
- Safe dummy credential values saved successfully.
- Post-save page showed configured redacted status.
- No dummy values rendered.
- No encrypted blobs rendered.
- Credential fields remained masked and had no value attributes.
- Behavior gate sum after save was `0`.
- Public pages did not expose `youngo/checkout/start` links.

## K. Diagnostic Result

Passed:

- `php scripts/phase_2/youngo_payment_paymob_config_dashboard_secrets_ui_save_1_diagnostic.php`
- `php scripts/phase_2/youngo_payment_secrets_encrypted_db_schema_1_diagnostic.php`
- `php scripts/phase_2/youngo_payment_encryption_key_setup_1_diagnostic.php`
- `php scripts/phase_2/youngo_payment_paymob_setup_form_ui_1_diagnostic.php`
- `git check-ignore -v application/config/youngo_security.local.php`
- `git diff --check`

The new diagnostic verified:

- Encrypted credential save works with safe dummy values.
- DB encrypted columns do not contain dummy values as plaintext.
- Decryption works internally without printing values.
- Blank fields keep existing encrypted values.
- Too-short credential values are rejected.
- Audit logs record presence changes only.
- Dummy audit/secret rows are cleaned up.
- Protected payment/access tables remain unchanged.

## L. DB Impact and Cleanup

Controlled local diagnostic/browser writes were performed only against:

- `youngo_payment_provider_secret_configs`
- `youngo_payment_config_audit_logs`

Cleanup restored baseline counts:

- `youngo_payment_provider_configs`: `0`
- `youngo_payment_provider_secret_configs`: `0`
- `youngo_payment_config_audit_logs`: `0`
- `youngo_checkout_orders`: `0`
- `youngo_payment_transactions`: `0`
- `youngo_course_access`: `0`
- `payment`: `0`
- `enrol`: `1`
- `payment_gateways`: `15`

No persistent dummy credential row was left.

## M. Payment/CTA Safety

Not changed:

- No Paymob network requests were added or executed.
- No real Paymob values were added.
- No encryption key value was printed.
- No credentials were printed, rendered, logged, or committed.
- No checkout CTA was exposed publicly.
- No payment execution was enabled.
- No sandbox test button was enabled.
- No legacy `payment_gateways` integration was added for YounGo Paymob.
- No legacy `payment` or `enrol` writes were performed.
- No Root Admin data was modified.

## N. What Was Not Changed

- No DB schema changes in this phase.
- No Paymob Intention execution changes.
- No Paymob webhook processing changes.
- No entitlement issuance changes.
- No production/live payment activation.
- No production checkout CTA activation.
- No `wallet_integration_id_egp` schema support was added; the field remains a later schema/config follow-up if wallet support is approved.

## O. Remaining Risks/Blockers

- Real Paymob credentials still have not been entered; only safe dummy values were used for QA and cleaned up.
- Sandbox execution remains blocked until an explicit later phase enables network gates and uses real sandbox credentials.
- `wallet_integration_id_egp` is mentioned in current context but is not present in the current schema/model; wallet support needs a separate approved schema/config phase if required.
- Encryption key backup/rotation procedure must be finalized before production credential entry.
- Production deployment still needs a server-specific ignored `youngo_security.local.php` key setup outside Git.

## P. Recommended Next Phase

Recommended next phase:

`PAYMENT.PAYMOB.CONFIG.DASHBOARD.SECRETS.UI.QA.1` - Browser QA with safe dummy credentials retained only long enough to verify dashboard status/audit rendering, then cleaned up.

After that, proceed to a controlled sandbox credential input QA using real Paymob sandbox credentials supplied through a private channel and never written to Git/reports.

## Q. Git Status

Expected working tree after this report:

- Modified source/model/controller/view files listed in section D.
- Updated diagnostics listed in section D.
- New diagnostic and this report untracked until staged.
- Ignored `application/config/youngo_security.local.php` remains untracked and excluded by Git.
