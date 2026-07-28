# PAYMENT.PAYMOB.CONFIG.DASHBOARD.SAVE.PLAN.1 - Paymob Dashboard Save Flow, Secret Handling, and Readiness Plan

Date: 2026-07-22

Scope: planning only for a future editable Paymob dashboard configuration flow. No deployment, push, DB modification, SQL execution, real Paymob values, private value output, private value save, payment enablement, Paymob network request, checkout CTA exposure, Root Admin modification, editable save implementation, or legacy `payment_gateways` use was performed.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
f2bb2f8 Add read-only YounGo Paymob dashboard config summary
7b0864d Add YounGo Paymob dashboard config schema foundation
7694e2c Plan YounGo Paymob dashboard configuration
022f936 Plan YounGo Paymob sandbox execution
0d663c1 QA gated YounGo checkout CTA clickthrough
68b9b28 Add gated YounGo checkout CTA helper
bda9cb1 Plan YounGo checkout CTA exposure
b59eaee QA local YounGo checkout smoke flow
bcb01b7 Fix YounGo checkout HTTP DB access
92fd18e QA authenticated YounGo checkout start blocker
```

The expected branch and clean starting worktree were confirmed. The latest commit includes `PAYMENT.PAYMOB.CONFIG.DASHBOARD.UI.1`.

## B. Files Inspected

Required reports:

- `docs/qa/youngo_payment_paymob_config_dashboard_ui_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_schema_1_report.md`
- `docs/qa/youngo_payment_paymob_config_dashboard_plan_1_report.md`

Source inspected:

- `application/config/config.php`
- `application/models/Youngo_payment_config_model.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `application/views/backend/admin/navigation.php`
- `application/models/Youngo_subscription_model.php` for existing YounGo audit-log pattern

## C. Encryption/Key Finding

Current local CodeIgniter encryption key state:

```text
application/config/config.php
$config['encryption_key'] = '';
```

Finding:

- Key management is not ready.
- DB-backed private Paymob value storage must remain blocked while the key is empty.
- The current `Youngo_payment_config_model` already treats this as `blocked_encryption_key_missing`.
- The model rejects private fields such as `secret_key`, `hmac_secret`, `api_key`, `client_secret`, `authorization`, and `auth_header`.
- The read-only dashboard shows private-field presence only and does not render stored private values.

Decision for implementation planning:

- Do not implement encrypted DB secret writes until the owner chooses and configures a key-management strategy.
- Continue using ignored local config for developer-only sandbox experiments until encrypted storage is approved.
- Treat DB backups containing future encrypted payment config as sensitive even if values are encrypted.

## D. Secret Handling Options

### Option 1: Ignored Local/Server Config Only

Use:

```text
application/config/youngo_paymob.local.php
```

Properties:

- File remains ignored and uncommitted.
- Secrets are supplied manually per environment.
- Dashboard can show readiness/presence by reading a merged safe summary, but not save secrets.
- Best short-term option while encryption key is empty.

Tradeoffs:

- Requires server file access for setup/rotation.
- Less convenient for client handover.
- Needs careful deployment documentation so local sandbox values are never copied to live accidentally.

### Option 2: DB Storage Only After Encryption Key Is Configured

Properties:

- Keep current table for non-private fields and private presence flags.
- Add encrypted private columns only after a non-empty encryption key is configured outside Git/reports.
- Save private values encrypted at rest.
- Show only `configured_redacted` or `missing` after save.

Tradeoffs:

- DB dumps become sensitive artifacts.
- Key rotation/backup/restore rules must be documented before implementation.
- Requires diagnostics proving values are encrypted and never echoed.

### Option 3: Hybrid Storage

Properties:

- Store non-private fields in `youngo_payment_provider_configs`.
- Store secrets in ignored local/server config or an external secret store.
- Store DB presence flags only.
- Dashboard can guide readiness but cannot rotate secrets unless the external secret store is integrated.

Tradeoffs:

- Safest for keeping secrets out of DB.
- Dashboard edit UX is less complete.
- Requires a clear operational process for production setup.

Recommended path:

1. Implement non-private dashboard save first.
2. Keep private fields read-only/masked and unsavable while `encryption_key` is empty.
3. Decide between encrypted DB storage and ignored-server-config storage before adding secret save behavior.

## E. Editable Field Plan

### Editable First: Non-Private Fields

Future `PAYMENT.PAYMOB.CONFIG.DASHBOARD.SAVE.NONPRIVATE.1` may allow editing:

- `mode`
- `currency`
- `amount_multiplier`
- `public_key`
- `card_integration_id_egp`
- `api_base_url`
- `checkout_base_url`
- `return_url`
- `notification_url`

Rules:

- Provider remains fixed to `paymob`.
- Currency remains fixed to `EGP`.
- Amount multiplier remains fixed to `100`.
- Mode may be `sandbox` only at first; live mode remains blocked until production preflight.
- Public key is not treated as a private server secret, but diagnostics/reports should still avoid printing full account-specific values.
- Integration ID must be numeric.
- URLs must be valid and must match the chosen mode's allowed host policy.

### Deferred: Private Fields

Future private fields:

- `secret_key`
- `hmac_secret`
- `api_key` if still required by the final Paymob flow/account type

Rules:

- Private inputs are blank on render even when a value exists.
- The UI shows only `missing` or `configured`.
- Blank submission means keep existing value.
- New non-empty submission means replace the encrypted stored value or update the ignored secure source, depending on the chosen strategy.
- Clearing a secret requires an explicit separate action and confirmation.
- Full secret values are never displayed after save.
- Secret values are never included in flash messages, validation errors, audit logs, reports, diagnostics, JSON responses, or browser screenshots.

### Activation Fields

Activation should not be bundled into the first save implementation.

Separate future phase only:

- `enabled`
- `network_enabled`
- `sandbox_network_testing_enabled`
- `webhook_testing_enabled`
- `checkout_routes_enabled`
- `checkout_local_testing_enabled`
- `checkout_cta_enabled`
- `live_mode_allowed`
- `transaction_inquiry_enabled`

Rules:

- All activation flags remain false by default.
- Sandbox network testing requires readiness pass and owner-approved phase instructions.
- CTA enablement remains separate from network enablement.
- Live mode and production CTA require a later production preflight approval.

## F. Validation/Readiness Plan

Validation should run on every save attempt and every readiness summary render.

### Base Validation

- Provider must be `paymob`.
- Mode must be `sandbox` or `live`; first editable save should allow sandbox only.
- Currency must be exactly `EGP`.
- Amount multiplier must be exactly `100`.
- Card integration ID must be present and numeric.
- Public key must be present for Unified Checkout readiness if required by current Paymob account/docs.
- Secret key presence must be true before any network call is allowed.
- HMAC secret presence must be true before webhook testing or network flow can be considered ready.
- API key presence must be true only if the final Paymob flow/account still requires it.
- API base URL and checkout base URL must be valid URLs.
- Return URL must be valid and must point to a YounGo return route.
- Notification/webhook URL must be valid and must point to `/payment/paymob/webhook`.
- Sandbox URLs must not point to live hosts.
- Live URLs must not point to sandbox, `localhost`, `school.local`, or temporary tunnel URLs.

### Readiness States

- `not_configured`: one or more required fields are missing/invalid.
- `configured_disabled`: shape checks pass, but all activation/network/CTA gates remain off.
- `sandbox_ready`: sandbox fields are present, valid, and secret presence is confirmed.
- `sandbox_network_testing_enabled`: explicit sandbox-only network flag is on in a later approved phase.
- `production_blocked`: live mode requested without all production approval gates.
- `production_pending_approval`: live shape checks pass but final approval is not recorded.
- `error_security_blocked`: unsafe combination detected, such as non-EGP currency, live URL in sandbox mode, or CTA enabled without routes.

### Gate Separation

Network readiness:

- Requires `enabled = true`.
- Requires `network_enabled = true`.
- Requires sandbox/live mode gate appropriate to environment.
- Requires secret and HMAC presence.
- Requires webhook URL configured.
- Must never be inferred from return URL alone.

CTA readiness:

- Requires checkout route gate.
- Requires CTA gate.
- Requires YounGo-managed purchase-compatible course.
- Requires positive EGP amount.
- Requires learner does not already have access.
- Must never use legacy cart/payment/gateway routes.

Production readiness:

- Requires successful sandbox payment QA.
- Requires failed payment QA.
- Requires duplicate webhook QA.
- Requires invalid HMAC QA.
- Requires return-before-webhook and webhook-before-return QA.
- Requires exactly-once entitlement issuance QA.
- Requires no legacy payment/enrol writes.
- Requires no secrets in logs/reports/git.

## G. Access/Audit Plan

### Access Control

Current UI phase behavior:

- Admin session required.
- Root Admin only via `youngo_is_root_admin()`.
- Missing helper fails closed.

Future access plan:

- Keep Root Admin only for the first non-private save implementation.
- Add a dedicated capability later, recommended key:

```text
manage_payment_settings
```

- Do not reuse the broad legacy `settings` permission.
- Do not expose to learners, instructors, content editors, course managers, or support roles by default.
- Do not modify the Root Admin account.
- If delegated admin access is approved later, seed and QA the capability in a separate phase.

### Audit Logging

Reuse the existing YounGo audit pattern from `Youngo_subscription_model`:

- Dedicated audit table.
- `actor_user_id`.
- `action`.
- `before_data`.
- `after_data`.
- `created_at`.
- Safe snapshots only.

Recommended future table:

```text
youngo_payment_provider_config_audit_log
```

Recommended fields:

- `id`
- `provider_config_id`
- `provider`
- `mode`
- `actor_user_id`
- `action`
- `changed_fields`
- `before_data`
- `after_data`
- `created_at`

Audit rules:

- Log who changed config and when.
- Log non-sensitive field changes.
- For private fields, log only presence transitions, such as `missing_to_configured`, `configured_to_configured`, or `configured_to_missing`.
- Never log raw secret values.
- Never log request payloads containing secrets.
- Use redacted snapshots before and after save.
- If an audit write fails during save, rollback the config write.

## H. Implementation Phases

Recommended sequence:

1. `PAYMENT.PAYMOB.CONFIG.DASHBOARD.SAVE.NONPRIVATE.1`
   - Add POST route/controller handling for non-private fields only.
   - Keep private values unsavable.
   - Keep all activation/network/CTA flags disabled.
   - Add CSRF/session/root guard validation.
   - Add local DB backup before QA writes.
   - Add diagnostics proving no secret save path exists.

2. `PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.SCHEMA.1`
   - Add local audit table and rollback SQL.
   - Reuse subscription audit-log pattern.
   - Store redacted before/after snapshots only.
   - Do not store private values.

3. `PAYMENT.PAYMOB.CONFIG.DASHBOARD.READINESS.1`
   - Expand readiness model to report field-level status and gate-specific readiness.
   - Keep readiness non-network by default.
   - Keep activation blocked.

4. `PAYMENT.PAYMOB.SECRET.STORAGE.DECISION.1`
   - Decide encrypted DB storage vs ignored server config/external secret store.
   - Configure `encryption_key` outside Git/reports if encrypted DB storage is selected.
   - Define key rotation and backup handling.

5. `PAYMENT.PAYMOB.CONFIG.DASHBOARD.SECRET.SAVE.1`
   - Implement masked private field save only after key-management approval.
   - Never render full secret values.
   - Audit presence transitions only.
   - Add diagnostics for encryption and redaction.

6. `PAYMENT.PAYMOB.CONFIG.DASHBOARD.SANDBOX.FLAGS.1`
   - Add controlled sandbox activation toggles after readiness passes.
   - Keep live mode blocked.
   - Keep CTA activation separate.

7. `PAYMENT.PAYMOB.SANDBOX.TEST.BUTTON.1`
   - Add an explicitly gated sandbox-only test action.
   - No real checkout CTA exposure.
   - Redact all Paymob request/response diagnostics.

8. `PAYMENT.PAYMOB.SANDBOX.INTENTION.1`
   - First real sandbox Intention API execution.
   - Use Unified Checkout URL.
   - Webhook/HMAC remains source of truth.
   - No entitlement from return URL.

## I. Risks/Blockers

- `encryption_key` is empty, so DB-backed private secret save must not be implemented yet.
- Existing `youngo_payment_provider_configs` has private presence/status fields but no encrypted secret value columns.
- A separate schema phase is needed if encrypted private DB columns or audit log tables are approved.
- The current config model intentionally rejects activation flags; a later phase must relax this only under strict readiness gates.
- The read-only dashboard is Root Admin only; delegated access requires a deliberate `manage_payment_settings` capability phase.
- Real Paymob dashboard field names and account capabilities must be confirmed by the owner before final field mapping.
- A public HTTPS tunnel or deployed staging URL is required for real sandbox webhook QA.
- DB backups containing future encrypted secrets still need sensitive handling.
- Legacy Academy `payment_gateways` remains unsuitable for YounGo Paymob config and must stay unused.

## J. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.CONFIG.DASHBOARD.SAVE.NONPRIVATE.1
```

Recommended scope:

- Add editable dashboard POST handling for non-private fields only.
- Keep private fields masked and unsavable.
- Keep all activation/network/CTA flags disabled.
- Add local backup before DB-write QA.
- Add diagnostics proving secret fields are rejected, readiness remains blocked, and no legacy `payment_gateways` use exists.

## K. Git Status

Expected git status after this planning report:

```text
?? docs/qa/youngo_payment_paymob_config_dashboard_save_plan_1_report.md
```

No source files, config defaults, DB rows, SQL files, credentials, ignored local config files, Root Admin records, live server files, checkout CTAs, Paymob network code, or legacy gateway/payment/enrol rows were changed.
