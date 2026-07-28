# PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.UI.QA.1 - Browser QA for Paymob Config Audit Panel

Date: 2026-07-23

Scope: authenticated local QA for the Root-Admin Paymob settings audit panel after a non-private sandbox placeholder save. No deployment, push, real Paymob values, private value save/output, payment enablement, Paymob network request, checkout CTA exposure, Root Admin data modification, or legacy `payment_gateways` use was performed.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
8b7fa78 Wire YounGo Paymob config saves to audit logs
8351916 Add YounGo Paymob config audit schema
9360b74 Fix YounGo payment DB access regressions
3271d7b Fix YounGo Paymob settings HTTP DB access
41dd2f4 QA Paymob dashboard save form blocker
2dfcf0e Add non-private YounGo Paymob dashboard save flow
476bfa7 Plan YounGo Paymob dashboard save flow
f2bb2f8 Add read-only YounGo Paymob dashboard config summary
7b0864d Add YounGo Paymob dashboard config schema foundation
7694e2c Plan YounGo Paymob dashboard configuration
```

The expected branch, clean worktree, and latest `PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.WIRE.1` commit were confirmed.

## B. Backup Created

Created before authenticated save/audit QA writes:

```text
Path: D:\Work\YounGo\backups\youngo_school_before_paymob_config_audit_ui_qa_1_2026_07_23_002429.sql
Size: 925794 bytes
SHA256: efe67de0f3a31c3e8a18066dd827d28eb02d14d3d68570928b7422e5078be112
```

## C. URLs Tested

Authenticated Root-Admin session checks:

```text
/login                                          200
/login/validate_login                          200
/admin/dashboard                               200
/admin/youngo/payment-settings                 200
/admin/youngo/payment-settings POST valid      200
/admin/youngo/payment-settings after save      200
/admin/youngo/payment-settings POST invalid    200
/admin/youngo/payment-settings POST private    200
```

Public CTA safety checks:

```text
/                                                200
/home/courses                                    200
/home/course/robotics-and-ai-explorers/9         200
/home/my_wishlist                                200
```

Browser automation note: local Playwright/Selenium browser drivers were unavailable in prior payment QA. This pass used an authenticated HTTP browser-session equivalent against the same local routes and server-rendered pages.

## D. Audit Panel QA Result

The Root-Admin settings page rendered successfully and showed the `Recent Configuration Audit` panel.

A valid placeholder non-private sandbox save succeeded using:

- `mode = sandbox`
- `currency = EGP`
- `amount_multiplier = 100`
- numeric placeholder card integration ID
- placeholder `http`/`https` URLs only

The save created one local audit row with:

```text
action: dashboard_non_private_save
changed_fields: card_integration_id_egp and other non-private field names
```

The audit panel rendered the recent `dashboard_non_private_save` row and showed field names only, not raw private values. Invalid live-mode and private-field attempts were rejected by the existing save flow and did not create additional success audit rows.

## E. Redaction/Private-Value Safety

Verified:

- no private input fields for `secret_key`, `hmac_secret`, or `api_key`;
- no password input on the Paymob settings page;
- no private test value persisted in audit JSON;
- no private test value rendered in the page;
- no Root Admin password rendered;
- no `sk_live`, `sk_test`, or bearer-token style values rendered or stored in audit summaries;
- audit panel did not display IP/user-agent fields.

The normal non-private form displayed placeholder non-private URL/config values after save, which is expected for this phase.

## F. Gate/Default Safety

Confirmed all payment behavior gates remained disabled after valid and invalid submissions:

```text
enabled = 0
network_enabled = 0
sandbox_network_testing_enabled = 0
webhook_testing_enabled = 0
checkout_routes_enabled = 0
checkout_cta_enabled = 0
live_mode_allowed = 0
```

No Paymob network request code path was exercised. Static scan of the changed payment settings/controller/model/view source found no Paymob network execution patterns such as `curl_exec` or remote `file_get_contents()`.

## G. DB Cleanup

Controlled local DB writes during QA:

- one temporary `paymob`/`sandbox` provider config row with placeholder non-private values;
- one temporary redacted audit row.

Cleanup removed the temporary config and audit rows and restored the baseline row state.

Counts:

```text
youngo_payment_provider_configs: 0 -> 0
youngo_payment_config_audit_logs: 0 -> 0
payment_gateways: 15 -> 15
payment: 0 -> 0
enrol: 1 -> 1
youngo_checkout_orders: 0 -> 0
youngo_payment_transactions: 0 -> 0
youngo_course_access: 0 -> 0
```

Hashes for `payment_gateways`, `payment`, and `enrol` matched before cleanup and after cleanup.

## H. Files Changed

- `docs/qa/youngo_payment_paymob_config_audit_ui_qa_1_report.md`

No source code changes were made in this QA phase.

## I. What Was Not Changed

- No deployment.
- No push.
- No real Paymob private values.
- No private Paymob values saved.
- No private values printed in this report.
- No payment or network behavior enabled.
- No Paymob calls.
- No checkout CTA exposure.
- No Root Admin data modification.
- No legacy `payment_gateways` use.
- No legacy `payment` or `enrol` writes.
- No checkout/order/transaction/access rows left behind.

## J. Remaining Risks/Blockers

- Full visual browser automation remains blocked until Playwright/Selenium and a browser driver are installed locally.
- Private Paymob DB storage remains blocked while `application/config/config.php` has an empty `encryption_key`.
- The current dashboard save flow is intentionally non-private only; secret/key handling and sandbox payment execution remain future phases.
- Public checkout CTA exposure remains intentionally disabled by default.

## K. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.UI.FOLLOWUP.1
```

or, if visual browser tooling is installed, rerun this phase with real browser automation screenshots before moving to secret/key-management design.

## L. Validation

Ran:

```text
php scripts/phase_2/youngo_payment_paymob_config_audit_wire_1_diagnostic.php
php scripts/phase_2/youngo_payment_paymob_config_dashboard_save_nonprivate_1_diagnostic.php
git diff --check
git status --short
```

Result:

```text
PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.WIRE.1 diagnostic: ok true, failed_checks []
PAYMENT.PAYMOB.CONFIG.DASHBOARD.SAVE.NONPRIVATE.1 diagnostic: ok true, failed_checks []
git diff --check: PASS
git status --short: ?? docs/qa/youngo_payment_paymob_config_audit_ui_qa_1_report.md
```

## M. Git Status

Final status:

```text
git status --short
?? docs/qa/youngo_payment_paymob_config_audit_ui_qa_1_report.md
```
