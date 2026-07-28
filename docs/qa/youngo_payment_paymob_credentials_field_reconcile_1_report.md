# PAYMENT.PAYMOB.CREDENTIALS.FIELD.RECONCILE.1

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Initial status: clean
- Latest visible commit before this phase: `6fdecda QA YounGo Paymob setup form rendering`
- Scope: audit/planning plus safe UI readiness only.
- DB changes: none.
- Paymob network calls: none.
- Real Paymob values: none added, saved, or printed.

## B. Files Inspected

- `application/config/youngo_paymob.php`
- `application/config/youngo_paymob.local.example.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/libraries/Youngo_paymob_adapter.php`
- `application/models/Youngo_payment_config_model.php`
- `application/controllers/Youngo_payment_settings.php`
- `application/views/backend/admin/youngo_payment_settings.php`
- `application/models/Youngo_payment_config_audit_model.php`
- Prior reports:
  - `docs/qa/youngo_payment_paymob_setup_form_ui_qa_2_report.md`
  - `docs/qa/youngo_payment_paymob_sandbox_intention_1_report.md`
  - `docs/qa/youngo_payment_secrets_hybrid_config_1_report.md`
  - `docs/qa/youngo_payment_paymob_sandbox_config_input_plan_1_report.md`
  - `docs/qa/youngo_payment_reconcile_audit_1_report.md`

## C. Paymob Dashboard Fields Observed

Owner-reported Paymob test-mode dashboard fields:

- `api_key` / API key
- `public_key` / Public key
- `secret_key` / Secret key
- `hmac_secret` / HMAC

Documentation/source check:

- Paymob official API docs search result for APIs states the current flow starts with payment Intention creation and then a checkout experience such as Unified Checkout redirect: https://developers.paymob.com/paymob-docs/integration-paths/apis
- Paymob official checkout-experiences docs search result identifies Intention API as the initial step and says intention creation returns a client secret: https://developers.paymob.com/paymob-docs/developers/checkout-experiences
- Paymob official docs root search result lists API Secret Key, Public Key, Integration IDs, and API key as integration/account values: https://developers.paymob.com/paymob-docs
- Paymob official QuickLinks/auth-token page search result references Authentication Request / Generate Auth Token and API key usage for that path: https://developers.paymob.com/paymob-docs/developers/quicklink-apis/overview
- Paymob official HMAC page search result references HMAC secret and SHA-512 verification: https://developers.paymob.com/paymob-docs/developers/webhook-callbacks-and-hmac/hmac/hmac-for-card-tokens
- PaymobAccept `paymob-js` README says the client checkout SDK uses a public key and client secret: https://github.com/PaymobAccept/paymob-js

Note: the official Paymob docs pages opened in this environment as JavaScript-rendered/verification shells, so this reconciliation uses the current official search snippets, the already-reviewed Paymob reports, the owner-observed dashboard field list, and the existing YounGo sandbox adapter implementation.

## D. Current YounGo Config Field Map

| Field | Current YounGo Source | Current Use | Dashboard Display | Storage Decision |
| --- | --- | --- | --- | --- |
| `api_key` | ignored local/server config only | Represented for Paymob dashboard reconciliation and old/auth-token compatibility; not used by current Intention adapter | Disabled/private placeholder | Server config only now; future encrypted DB only after key-management approval |
| `public_key` | ignored local/server config only | Used with returned `client_secret` to build Unified Checkout URL | Disabled/private placeholder | Server config only now |
| `secret_key` | ignored local/server config only | Used by current Intention adapter for server-side Authorization token header | Disabled/private placeholder | Server config only now |
| `hmac_secret` | ignored local/server config only | Required for webhook/HMAC verification | Disabled/private placeholder | Server config only now |
| `card_integration_id_egp` | YounGo payment config DB table | Used in Intention payload payment method list | Editable non-private field | DB non-private |
| `return_url` | YounGo payment config DB table | UX-only redirection URL; must not mark paid or issue entitlement | Editable non-private field | DB non-private |
| `notification_url` | YounGo payment config DB table | Canonical Paymob payment notification/webhook URL | Editable non-private field | DB non-private |
| `webhook_url` | ignored local/tracked config alias | Alias for the same webhook endpoint in config placeholders | Not a dashboard save field | Alias only |
| `api_base_url` | YounGo payment config DB table | Sandbox Intention API endpoint base | Editable non-private field | DB non-private |
| `checkout_base_url` | YounGo payment config DB table | Unified Checkout base URL | Editable non-private field | DB non-private |

## E. API Key vs Secret/Public/HMAC Decision

- `secret_key` remains the current server-side Intention API credential for the implemented adapter path.
- `public_key` remains required for Unified Checkout rendering with the Paymob-returned `client_secret`.
- `hmac_secret` remains required for webhook verification.
- `api_key` is now represented in tracked placeholders, ignored config example, dashboard disabled placeholders, and redacted presence summaries.
- `api_key` is not a current Intention readiness gate and is not used by `Youngo_paymob_adapter`.
- `api_key` should be considered required only for old/auth-token/API-key flows unless Paymob account-specific docs later require it for the selected Intention/Unified Checkout implementation.

## F. Setup Form/UI Changes

Updated the Root-Admin Paymob setup page to show the Paymob dashboard credential names exactly:

- API key
- Public key
- Secret key
- HMAC secret

All four are displayed as disabled, read-only, server-config-only placeholders with no POST field names.

The setup copy now states that the current Intention API / Unified Checkout path uses:

- Secret key for the server request
- Public key plus returned client secret for checkout rendering
- HMAC secret for webhook verification

It also states that API key is represented for reconciliation and older auth-token compatibility, but is not a current Intention readiness gate.

## G. Private-Value Storage Decision

No private values may be entered through the dashboard in this phase.

Current decision remains hybrid:

- Non-private Paymob settings stay in `youngo_payment_provider_configs`.
- Private Paymob values stay in ignored local/server config.
- CodeIgniter `encryption_key` remains unchanged and empty.
- DB-backed private storage remains blocked until encryption/key-management is approved.
- Diagnostics and dashboard summaries show only `missing`, `configured_redacted`, `server_config_required`, or `db_private_storage_blocked`.

## H. Diagnostic Result

Added:

- `scripts/phase_2/youngo_payment_paymob_credentials_field_reconcile_1_diagnostic.php`

Diagnostic result:

- Paymob dashboard credential labels represented: pass.
- Private fields disabled/non-submitting: pass.
- `api_key`, `public_key`, `secret_key`, and `hmac_secret` placeholders present: pass.
- Config reader reports redacted API-key presence: pass.
- Dashboard save path still blocks private values and activation gates: pass.
- Current adapter does not use API key for Intention execution: pass.
- No Paymob network calls in reconciled setup/config surfaces: pass.
- No legacy `payment_gateways` dependency added: pass.
- No checkout/payment behavior exposed: pass.

Existing setup-form diagnostic also passed after the alignment.

## I. What Was Not Changed

- No DB schema changes.
- No SQL executed.
- No real Paymob values added.
- No private values saved to DB.
- No `encryption_key` change.
- No Paymob network calls.
- No payment activation.
- No checkout CTA exposure.
- No Root Admin data changes.
- No legacy `payment_gateways` use.
- No legacy `payment` or `enrol` writes.
- No change to the current Intention adapter requirement set beyond documenting API key as not currently used.

## J. Remaining Risks/Blockers

- Paymob official docs are JavaScript-rendered in this environment, so direct full-page verification remains limited. Reconfirm the exact selected Paymob account flow from owner-provided docs/dashboard before accepting real values.
- API key may become required if YounGo later adds old auth-token, QuickLinks, transaction inquiry, saved-card, or other Paymob APIs.
- DB private storage is still blocked until an encryption/key-management decision is implemented.
- Sandbox execution still requires real values in ignored server config and explicit local gates.

## K. Recommended Next Phase

Recommended next phase:

`PAYMENT.PAYMOB.SANDBOX.CONFIG.INPUT.QA.1`

Purpose:

- Enter non-private sandbox settings through the dashboard.
- Place private Paymob values only in ignored local/server config.
- Verify readiness without printing values.
- Stop safely if any required Intention fields are missing.

Do not proceed to real sandbox Intention/browser QA until readiness confirms:

- Dashboard non-private config present.
- Server-config private presence for `public_key`, `secret_key`, and `hmac_secret`.
- Explicit local sandbox/network/checkout gates enabled only in ignored config.
- Production defaults still disabled.

## L. Git Status

Validation:

- `php -l` on changed PHP files: passed.
- `php scripts/phase_2/youngo_payment_paymob_credentials_field_reconcile_1_diagnostic.php`: passed.
- `php scripts/phase_2/youngo_payment_paymob_setup_form_ui_1_diagnostic.php`: passed.
- `git diff --check`: passed. Git reported existing line-ending normalization warnings only.

Final `git status --short`:

```text
 M application/config/youngo_paymob.local.example.php
 M application/config/youngo_paymob.php
 M application/libraries/Youngo_paymob_config.php
 M application/models/Youngo_payment_config_model.php
 M application/views/backend/admin/youngo_payment_settings.php
?? docs/qa/youngo_payment_paymob_credentials_field_reconcile_1_report.md
?? scripts/phase_2/youngo_payment_paymob_credentials_field_reconcile_1_diagnostic.php
```

Pending local changes are expected for this phase:

- Modified safe Paymob config defaults/example.
- Modified Paymob config reader redacted presence.
- Modified Paymob config model hybrid redacted presence.
- Modified Root-Admin Paymob setup view wording/disabled fields.
- Added credentials-field reconciliation diagnostic.
- Added this report.
