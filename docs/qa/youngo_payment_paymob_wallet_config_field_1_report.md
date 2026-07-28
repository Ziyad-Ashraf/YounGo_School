# PAYMENT.PAYMOB.WALLET.CONFIG.FIELD.1 — Add Paymob Wallet Integration ID Dashboard Support

## A. Current Branch/Status

- Branch at start and completion: `analysis/cms-audit`
- Starting status: clean
- Latest commit at start: `d5c1ecd Add encrypted Paymob credential dashboard save`
- Local ignored security key file: present locally and ignored; key value was not printed or inspected.

## B. Backup Created

- Backup path: `D:\Work\YounGo\backups\youngo_school_before_paymob_wallet_config_field_1_2026_07_25_212824.sql`
- Size: `576576` bytes
- SHA256: `C29B14E0525FE70E7A3D683F4E4F247C592593C9BCDE2E977D83B6D287F31335`

## C. Files Inspected

- `application/models/Youngo_payment_config_model.php`
- `application/models/Youngo_payment_config_audit_model.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `application/config/youngo_paymob.php`
- `application/config/youngo_paymob.local.example.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/libraries/Youngo_paymob_adapter.php`
- `scripts/phase_2/`

## D. Files Changed

- `application/models/Youngo_payment_config_model.php`
- `application/models/Youngo_payment_config_audit_model.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `application/config/youngo_paymob.php`
- `application/config/youngo_paymob.local.example.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/libraries/Youngo_paymob_adapter.php`
- `scripts/phase_2/payment_paymob_wallet_config_field_1_up.sql`
- `scripts/phase_2/payment_paymob_wallet_config_field_1_down.sql`
- `scripts/phase_2/youngo_payment_paymob_wallet_config_field_1_diagnostic.php`
- Updated related diagnostics for non-private save, audit-wire, setup form, setup wizard, setup preflight, and sandbox intention runtime readiness.

## E. Schema Changes

Added an additive local schema change for:

- Table: `youngo_payment_provider_configs`
- New column: `wallet_integration_id_egp`
- Type: `VARCHAR(100) DEFAULT NULL`
- Purpose: non-private Paymob Mobile Wallet integration ID for EGP sandbox/live-ready configuration.

SQL files:

- Up: `scripts/phase_2/payment_paymob_wallet_config_field_1_up.sql`
- Down: `scripts/phase_2/payment_paymob_wallet_config_field_1_down.sql`

The up SQL was applied locally only after backup. It does not insert values, credentials, URLs, activation flags, or legacy gateway rows.

## F. Dashboard Form Changes

`/admin/youngo/payment-settings` now supports both non-private integration fields:

- Card integration ID EGP: `card_integration_id_egp`
- Mobile wallet integration ID EGP: `wallet_integration_id_egp`

The dashboard now shows the wallet field in:

- Paymob basic setup form
- Readiness/status card
- Sandbox setup checklist
- Required dashboard field list

Private credential fields remain separate, masked, and encrypted-only through the existing credential save flow.

## G. Card/Wallet Save Behavior

`Youngo_payment_config_model` now accepts and validates `wallet_integration_id_egp` through the non-private dashboard save path.

Validation remains strict:

- `mode`: sandbox only
- `currency`: EGP only
- `amount_multiplier`: 100 only
- `card_integration_id_egp`: blank or numeric
- `wallet_integration_id_egp`: blank or numeric
- URL fields: blank or valid HTTP/HTTPS
- Activation/network/CTA/live flags are rejected
- Private fields are not accepted through the non-private save path

No real Paymob values were committed or written into source.

## H. Audit/Readiness Behavior

Audit logging now includes `wallet_integration_id_egp` as a safe changed field name when its redacted presence changes.

Readiness now reports card and wallet integration status separately:

- Card integration ID: missing/configured
- Mobile wallet integration ID: missing/configured

Sandbox readiness now treats wallet integration ID presence as part of full setup readiness. The Paymob adapter payload builder can include both card and wallet integration IDs when configured, but no Paymob requests were executed in this phase.

## I. Browser QA Summary

Authenticated local HTTP/browser-style QA was run against:

- `http://school.local/admin/dashboard`
- `http://school.local/admin/youngo/payment-settings`
- `http://school.local/`
- `http://school.local/home/courses`
- `http://school.local/home/course/robotics-and-ai-explorers/9`
- `http://school.local/home/my_wishlist`

Results:

- Admin dashboard returned HTTP 200.
- YounGo Paymob settings page returned HTTP 200.
- Card and Mobile wallet integration fields rendered.
- Readiness/status and audit sections rendered.
- Non-private form save produced one local Paymob sandbox config row and one redacted audit row.
- Both card and wallet integration IDs were saved as numeric non-private fields during QA.
- Audit row recorded card and wallet field names only.
- No private values, encrypted blobs, encryption key, or admin password were displayed.
- Sandbox Test remained disabled/non-functional.
- Public pages had zero `youngo/checkout/start` CTA links.

The direct POST response is a redirect shell in this app; the saved state was verified through the rendered settings page and DB state.

## J. Diagnostic Result

Passed:

- `php -l` on changed PHP files
- `php scripts/phase_2/youngo_payment_paymob_wallet_config_field_1_diagnostic.php`
- `php scripts/phase_2/youngo_payment_paymob_config_dashboard_secrets_ui_save_1_diagnostic.php`
- `php scripts/phase_2/youngo_payment_paymob_setup_form_ui_1_diagnostic.php`
- `php scripts/phase_2/youngo_payment_secrets_encrypted_db_schema_1_diagnostic.php`
- `php scripts/phase_2/youngo_payment_paymob_config_dashboard_save_nonprivate_1_diagnostic.php`
- `php scripts/phase_2/youngo_payment_paymob_config_audit_wire_1_diagnostic.php`
- `php scripts/phase_2/youngo_payment_paymob_sandbox_intention_1_runtime_test.php`

Note: the old `PAYMENT.PAYMOB.ADAPTER.1` skeleton diagnostic still encodes earlier no-network-code/no-checkout-route assumptions and is obsolete after the later sandbox Intention implementation. The current sandbox intention runtime diagnostic was used for adapter readiness validation.

## K. DB Cleanup

Browser QA temporarily created:

- 1 row in `youngo_payment_provider_configs`
- 1 row in `youngo_payment_config_audit_logs`

Cleanup deleted the temporary rows and restored counts:

- `youngo_payment_provider_configs`: 0
- `youngo_payment_config_audit_logs`: 0
- `youngo_payment_provider_secret_configs`: 0
- `youngo_checkout_orders`: 0
- `youngo_payment_transactions`: 0
- `youngo_course_access`: 0
- `payment`: 0
- `enrol`: 1
- `payment_gateways`: 15

## L. Payment/CTA Safety

Unchanged:

- No Paymob network request was performed.
- No real Paymob intention was created.
- No real Paymob values were added.
- No private Paymob values were saved or printed.
- The local encryption key was not printed.
- `application/config/youngo_security.local.php` remains ignored and untracked.
- No checkout CTAs were exposed publicly.
- No payment/network/sandbox/CTA/live gates were enabled.
- No legacy `payment_gateways` row was added or modified.
- No legacy `payment` or `enrol` writes occurred.
- Root Admin data was not modified.

## M. Remaining Risks/Blockers

- Paymob sandbox execution is still blocked until owner-provided real Paymob values are entered through the approved secure path.
- Wallet checkout behavior still needs a dedicated sandbox test-center phase; this phase only added configuration/readiness support.
- The adapter now supports both card and wallet IDs in the payload shape, but no real Paymob call was executed here.
- Some old phase-specific diagnostics encode historical expectations and should be updated or retired if they are no longer part of the active validation contract.

## N. Recommended Next Phase

Recommended next phase:

`PAYMENT.PAYMOB.SANDBOX.TEST.CENTER.PLAN.1` — plan the Root-Admin sandbox test center for explicitly gated card/wallet test execution, including payment-method selection, readiness gating, safe logs, and cleanup rules.

## O. Git Status

Git status at report creation:

- Modified source/config files:
  - `application/config/youngo_paymob.local.example.php`
  - `application/config/youngo_paymob.php`
  - `application/libraries/Youngo_paymob_adapter.php`
  - `application/libraries/Youngo_paymob_config.php`
  - `application/models/Youngo_payment_config_audit_model.php`
  - `application/models/Youngo_payment_config_model.php`
  - `application/views/backend/admin/youngo_payment_settings.php`
  - `scripts/phase_2/youngo_payment_paymob_config_audit_wire_1_diagnostic.php`
  - `scripts/phase_2/youngo_payment_paymob_config_dashboard_save_nonprivate_1_diagnostic.php`
  - `scripts/phase_2/youngo_payment_paymob_sandbox_intention_1_runtime_test.php`
  - `scripts/phase_2/youngo_payment_paymob_setup_form_ui_1_diagnostic.php`
  - `scripts/phase_2/youngo_payment_paymob_setup_preflight_1_diagnostic.php`
  - `scripts/phase_2/youngo_payment_paymob_setup_wizard_1_diagnostic.php`
- New files:
  - `docs/qa/youngo_payment_paymob_wallet_config_field_1_report.md`
  - `scripts/phase_2/payment_paymob_wallet_config_field_1_up.sql`
  - `scripts/phase_2/payment_paymob_wallet_config_field_1_down.sql`
  - `scripts/phase_2/youngo_payment_paymob_wallet_config_field_1_diagnostic.php`
- No deploy, push, or commit was performed.
