# PAYMENT.SECRETS.KEYMGMT.PLAN.1 - Payment Private Value Storage and Encryption Key Decision Plan

Date: 2026-07-23

Scope: planning only for private Paymob value handling and encryption key decision before sandbox payment execution. No deployment, push, DB modification, SQL execution, real Paymob values, private value output/save, `encryption_key` change, real payment enablement, Paymob network request, checkout CTA exposure, Root Admin modification, or legacy `payment_gateways` use was performed.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
73846af QA YounGo Paymob config audit panel
8b7fa78 Wire YounGo Paymob config saves to audit logs
8351916 Add YounGo Paymob config audit schema
9360b74 Fix YounGo payment DB access regressions
3271d7b Fix YounGo Paymob settings HTTP DB access
41dd2f4 QA Paymob dashboard save form blocker
2dfcf0e Add non-private YounGo Paymob dashboard save flow
476bfa7 Plan YounGo Paymob dashboard save flow
f2bb2f8 Add read-only YounGo Paymob dashboard config summary
7b0864d Add YounGo Paymob dashboard config schema foundation
```

The expected branch, clean starting worktree, and latest `PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.UI.QA.1` commit were confirmed.

## B. Files Inspected

Required reports:

- `docs/qa/youngo_payment_paymob_config_dashboard_plan_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_schema_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_save_plan_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_save_nonprivate_1_report.md`
- `docs/qa/youngo_payment_paymob_config_audit_ui_qa_1_report.md`
- `docs/qa/youngo_payment_paymob_sandbox_plan_1_report.md`

Source inspected:

- `application/config/config.php`
- `application/config/youngo_paymob.php`
- `application/config/youngo_paymob.local.example.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/models/Youngo_payment_config_model.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `.gitignore`

## C. Current Key/Private-Storage State

Current key state:

```text
application/config/config.php
$config['encryption_key'] = '';
```

Current implementation behavior:

- `Youngo_payment_config_model::encryption_key_is_ready()` returns false while the key is empty.
- `Youngo_payment_config_model` rejects private fields such as `secret_key`, `hmac_secret`, `api_key`, `client_secret`, `authorization`, and `auth_header`.
- The dashboard save wrapper also blocks `public_key` in the current non-private-save phase.
- Safe summaries return presence labels such as `missing` or `configured_redacted`, not raw private values.
- Readiness currently includes `private_storage_blocked_encryption_key_missing`.
- `private_storage_status` defaults to `blocked_encryption_key_missing`.
- Dashboard copy explicitly states that private Paymob values must stay outside DB storage until a later approved phase.
- The dashboard has no private secret inputs and no password inputs.
- `application/config/youngo_paymob.local.php` is ignored by Git and is the current safe place for local-only override values.

Current tracked defaults:

- payment disabled;
- network disabled;
- webhook testing disabled;
- checkout routes disabled;
- checkout CTA disabled;
- live mode blocked;
- mode `sandbox`;
- currency `EGP`;
- Paymob placeholders are null or empty only.

## D. Storage Options Comparison

### Option A: Private Values in Ignored Local/Server Config Only

Private Paymob values stay in environment-specific ignored config such as:

```text
application/config/youngo_paymob.local.php
```

Security impact:

- Lowest short-term risk of leaking secrets through DB dumps, SQL exports, dashboard save bugs, screenshots, or audit logs.
- Requires strict server file permission and deployment discipline.
- Secrets remain outside Git if `.gitignore` and deployment packaging are respected.

Deployment impact:

- Requires manually creating the ignored config file on local/sandbox/cPanel environments.
- Values are not moved by Git deploys.
- Production and sandbox values can be kept separate by per-environment files.

Client handover impact:

- Less convenient for a non-technical client because they cannot fully self-manage secrets from the dashboard.
- Handover requires a short operations checklist for who can update server config.

Backup/export impact:

- Normal DB backups do not contain Paymob private values.
- File-system backups can contain private values if the ignored config is backed up.
- Support exports and phpMyAdmin dumps are safer.

Ease of support:

- Simple to implement for first sandbox network testing.
- Secret rotation requires server/file access.
- Dashboard can show presence/readiness but not edit private values.

Risk level:

- Low for current local/sandbox stage.
- Medium operational risk later if client expects dashboard-based rotation.

### Option B: Generate and Set Encryption Key, Then Allow Encrypted DB Storage Later

Configure a real CodeIgniter encryption key outside Git/reports, then implement encrypted DB storage for private fields.

Security impact:

- Can support dashboard-managed secrets if encryption and masking are implemented correctly.
- DB dumps become sensitive because encrypted secret blobs and presence metadata are stored there.
- Key compromise exposes all stored values.
- Key loss can make stored secrets unrecoverable.

Deployment impact:

- Requires setting a stable encryption key per environment outside Git.
- Requires a careful cPanel deployment procedure and backup of the key through an owner-approved secure channel.
- Requires an explicit key rotation strategy before production.

Client handover impact:

- Better long-term dashboard UX.
- Client/admin can update values without file access.
- More training is needed around masked inputs, key backup, and secret rotation.

Backup/export impact:

- DB dumps containing encrypted values must be treated as sensitive.
- Restoring DB without the matching key breaks secret usage.
- Support exports must redact encrypted private columns or be handled as sensitive artifacts.

Ease of support:

- More convenient after implementation.
- More code and QA required: encrypted columns, save/update behavior, masking, audit logging, diagnostics, restore testing, and key rotation policy.

Risk level:

- Medium to high until key management, encrypted storage, and restore procedures are designed and QA-tested.

### Option C: Hybrid Approach

Store non-private settings in DB, keep private values in ignored server config or a secret store, and show dashboard readiness/presence only.

Security impact:

- Keeps the highest-risk values out of DB and reports.
- Preserves auditable dashboard management for non-private operational settings.
- Reduces accidental exposure in SQL exports and dashboard screenshots.

Deployment impact:

- Uses current `youngo_payment_provider_configs` table for non-private config.
- Requires per-environment ignored config for secrets.
- Production setup still needs a secure server-side step.

Client handover impact:

- Dashboard can show what is configured, what is missing, and whether payment remains disabled.
- Full self-service secret entry is deferred until the owner approves encrypted DB storage or an external secret store.
- Clearer and safer for an initial client demo because no real private fields are accepted in the browser.

Backup/export impact:

- DB backups contain non-private settings and redacted presence/readiness/audit data only.
- Ignored config backups must still be treated as sensitive.
- SQL exports remain safer than with encrypted DB secret columns.

Ease of support:

- Fits the code already built.
- Allows first sandbox network execution using ignored local config.
- Leaves a clean path to encrypted DB storage later.

Risk level:

- Low for current project stage.
- Medium if production operations rely too heavily on file-based secret updates without documentation.

## E. Recommended Path

Recommended current path: **Option C, hybrid storage**.

Use the dedicated dashboard DB table for non-private Paymob configuration and audit logs, but keep private Paymob values in ignored local/server config until a separate approved key-management phase.

Reasons:

- The project is still pre-sandbox-network-execution and has no production payments.
- The current CodeIgniter `encryption_key` is empty and must not be changed casually.
- The dashboard/non-private/audit foundation is already designed around DB-safe summaries and redacted readiness.
- The first real sandbox execution can be tested locally with ignored config without exposing secrets through Git, reports, SQL exports, or dashboard saves.
- cPanel deployment is safer when production defaults remain disabled and secret files are created intentionally per environment.
- Encrypted DB storage can still be added later after key generation, storage, backup, restore, and rotation rules are approved.

Do not enable Option B yet. It is a better future dashboard UX, but it needs an explicit key decision and additional QA. Do not use Option A alone long term because it leaves dashboard readiness and client handover too manual.

## F. Required Implementation Steps

### Hybrid Path: Next Steps

1. Update tracked local example placeholders.
   - Add all fields required for first sandbox network testing.
   - Keep values null/empty or obviously fake.
   - Include `sandbox_network_testing_enabled = false` if the runtime adapter needs a distinct gate.
   - Keep `enabled`, `network_enabled`, `webhook_testing_enabled`, `checkout_routes_enabled`, and `checkout_cta_enabled` false by default.

2. Extend `Youngo_paymob_config` only where needed.
   - Add safe getters for secret/private values for adapter runtime use only.
   - Add `is_network_enabled()` and `is_sandbox_network_testing_enabled()` if not already available.
   - Keep `get_safe_diagnostic_summary()` redacted.
   - Do not print private values in diagnostics.

3. Update dashboard readiness checks.
   - Represent secret requirements as `server_config_required`, `missing`, or `configured_redacted`.
   - Keep DB private save blocked.
   - Show a clear "private values supplied by ignored server config" state when local/server config has presence.
   - Keep all activation gates disabled until an explicit network phase.

4. Preserve DB/audit behavior.
   - Continue saving only non-private values in `youngo_payment_provider_configs`.
   - Continue logging only changed field names and redacted summaries in `youngo_payment_config_audit_logs`.
   - Do not use legacy `payment_gateways`.

5. Add local-only diagnostics.
   - Verify ignored local config is ignored.
   - Verify tracked config/example files contain no real secret-looking values.
   - Verify safe summaries redact secret presence.
   - Verify private DB save attempts are rejected.
   - Verify all behavior gates remain disabled by default.

### If Encrypted DB Storage Is Approved Later

Required before implementation:

- Generate a real CodeIgniter encryption key outside Git and reports.
- Store the key through an owner-approved environment/server-secret process.
- Document backup, restore, and rotation implications.
- Add encrypted private columns or a dedicated encrypted secret table.
- Implement masked private inputs.
- Update secret only when a new value is entered.
- Never show full secret after save.
- Audit only presence transitions, never raw values.
- Treat DB dumps as sensitive once encrypted private values exist.
- QA restore behavior with the key present and absent.

## G. QA Gates

Before first Paymob sandbox payment execution:

- No private Paymob values in Git.
- No private Paymob values in reports.
- No private Paymob values in SQL files or DB unless encrypted storage is separately approved.
- `application/config/youngo_paymob.local.php` remains ignored and uncommitted.
- Tracked config and example files contain only null/empty/fake placeholders.
- Dashboard private fields remain blocked or masked according to chosen storage option.
- Readiness summaries show only presence/redacted states.
- Audit logs store only field names, redacted summaries, and secret-presence transitions.
- `enabled`, `network_enabled`, `sandbox_network_testing_enabled`, `webhook_testing_enabled`, `checkout_routes_enabled`, `checkout_cta_enabled`, and `live_mode_allowed` remain false by default.
- Public checkout CTAs remain hidden until a separate CTA approval phase.
- No Paymob network calls occur until `PAYMENT.PAYMOB.SANDBOX.INTENTION.1` or another explicit approved network phase.
- No legacy `payment_gateways`, `payment`, or `enrol` writes are introduced.

## H. Risks/Blockers

- `encryption_key` is empty, so DB-backed private storage is currently blocked.
- Changing `encryption_key` has broader CodeIgniter implications and must not be done casually.
- Ignored config is safer for current sandbox work but less convenient for client self-service.
- If ignored server config is used on cPanel, deployment docs must specify file placement, file permissions, and rotation ownership.
- If encrypted DB storage is later chosen, DB backups and support exports become sensitive.
- Current dashboard save flow blocks `public_key`; decide whether Paymob public key should remain file-only, become non-private DB-editable, or be masked as account-specific before sandbox execution.
- Browser tooling remains unavailable for fully automated visual dashboard QA, though authenticated server-session QA has passed.

## I. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.SECRETS.HYBRID.CONFIG.1
```

Purpose:

- implement the hybrid ignored-config readiness path;
- keep private DB saves blocked;
- add safe runtime getters for Paymob adapter use;
- update readiness diagnostics to prove secrets are present only through ignored config and always redacted.

Do not start Paymob sandbox network execution until this phase passes and owner supplies sandbox credentials through an approved non-report/non-Git channel.

## J. Git Status

Expected after this report:

```text
git status --short
?? docs/qa/youngo_payment_secret_keymgmt_plan_1_report.md
```

