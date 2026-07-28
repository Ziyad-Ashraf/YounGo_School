# PAYMENT.PAYMOB.CONFIG.DASHBOARD.SAVE.UI.FIX.1 - Fix Paymob Settings Page HTTP 500 and Rerun Save Form QA

Date: 2026-07-22

Scope: diagnose/fix authenticated `/admin/youngo/payment-settings` HTTP 500 and rerun Root-Admin authenticated server-session QA for the non-private Paymob dashboard save form. No deployment, push, Root Admin password output/storage, real Paymob private values, private value output/save, real payment enablement, Paymob network request, checkout CTA exposure, Root Admin data modification, legacy `payment_gateways` use, or persistent debug file was left behind.

## A. Current Branch/Status

Start command results:

```text
git branch --show-current
analysis/cms-audit

git status --short
<clean>

git log --oneline -10
41dd2f4 QA Paymob dashboard save form blocker
2dfcf0e Add non-private YounGo Paymob dashboard save flow
476bfa7 Plan YounGo Paymob dashboard save flow
f2bb2f8 Add read-only YounGo Paymob dashboard config summary
7b0864d Add YounGo Paymob dashboard config schema foundation
7694e2c Plan YounGo Paymob dashboard configuration
022f936 Plan YounGo Paymob sandbox execution
0d663c1 QA gated YounGo checkout CTA clickthrough
68b9b28 Add gated YounGo checkout CTA helper
bda9cb1 Plan YounGo checkout CTA exposure
```

The expected branch, clean starting worktree, and latest blocker report commit were confirmed.

## B. Error Reproduced

Authenticated Root Admin reproduction before the fix:

```text
/login: 200
/admin/dashboard: 200
/admin/youngo/payment-settings: 500
```

The payment settings response body was empty in production-mode error handling.

## C. Root Cause

Captured with temporary local-only debug via `.htaccess` `SetEnv CI_ENV development`, then immediately removed.

Fatal error:

```text
Type: Error
Message: Call to a member function table_exists() on null
Filename: application/models/Youngo_payment_config_model.php
Line Number: 302
Backtrace:
Youngo_payment_config_model.php line 38 -> schema_ready()
Youngo_payment_settings.php line 42 -> get_provider_config()
```

Root cause:

- `Youngo_payment_config_model` declared its own public `$db` property for CLI diagnostic compatibility.
- In normal HTTP CodeIgniter runtime, the model did not initialize that property from the framework DB handle.
- Calls such as `$this->db->table_exists(...)` therefore executed against `null` in the authenticated dashboard page.
- CLI diagnostics manually injected `$model->db = $db`, so they did not catch the HTTP runtime issue.

Temporary debug state:

- `.htaccess` was changed only long enough to capture the fatal.
- The debug line was removed before the fix/QA completion.
- No debug mode remains enabled.

## D. Files Changed

- `application/models/Youngo_payment_config_model.php`
- `docs/qa/youngo_payment_paymob_config_dashboard_save_ui_fix_1_report.md`

## E. Fix Summary

Added `Youngo_payment_config_model::__construct()`:

```text
parent::__construct();
$CI = get_instance();
if (isset($CI->db)) {
    $this->db = $CI->db;
}
```

This preserves CLI diagnostic compatibility while binding the normal CodeIgniter DB handle during HTTP controller usage.

## F. Page Render QA Result

Authenticated Root Admin server-session QA after the fix:

```text
/login: 200
/admin/dashboard: 200
/admin/youngo/payment-settings: 200
```

Verified:

- Page title rendered.
- Non-private sandbox form rendered.
- Secret/network/payment/CTA disabled warning rendered.
- No private form inputs rendered.
- No password input rendered.
- No private Paymob value rendered.

Browser automation note:

- Node Playwright was not installed.
- Python Playwright and Selenium were not installed.
- Microsoft Edge is installed, but no automation driver/package was available.
- QA used an authenticated `requests.Session` against the same local routes.

## G. Save/Validation QA Result

Valid placeholder save used only sandbox-safe non-private values:

- `mode = sandbox`
- `currency = EGP`
- `amount_multiplier = 100`
- Numeric placeholder card integration ID
- Placeholder `http`/`https` URLs only

Result:

- Save POST returned the app's expected CodeIgniter `Refresh` redirect response.
- Follow-up GET rendered the payment settings page.
- Stored values were confirmed in `youngo_payment_provider_configs`.
- Safe summary rendered.
- No secret values were rendered.

Invalid submissions tested:

- `mode = live`: rejected.
- `currency = USD`: rejected.
- Invalid `ftp://` URL: rejected.
- Non-numeric card integration ID: rejected.
- Private field attempt: rejected without echoing the submitted value.

All invalid submission checks passed.

## H. Gate/Default Safety

Confirmed DB gates stayed disabled after valid and invalid submissions:

- `enabled = 0`
- `network_enabled = 0`
- `sandbox_network_testing_enabled = 0`
- `webhook_testing_enabled = 0`
- `checkout_routes_enabled = 0`
- `checkout_cta_enabled = 0`
- `live_mode_allowed = 0`

Public CTA safety checked:

- `http://school.local/`
- `http://school.local/home/courses`
- `http://school.local/home/course/robotics-and-ai-explorers/9`
- `http://school.local/home/my_wishlist`

Result:

- All returned below HTTP 500.
- No `/youngo/checkout/start` links were detected.

No Paymob network request code was executed.

## I. DB Cleanup

The QA created one temporary local `paymob`/`sandbox` config row with placeholder non-private values, then deleted it.

Counts:

```text
youngo_payment_provider_configs: 0 -> 0
paymob_sandbox_rows: 0 -> 0
payment_gateways: 15 -> 15
payment: 0 -> 0
enrol: 1 -> 1
```

No persistent Paymob config row, checkout row, payment row, enrol row, entitlement row, or legacy gateway change was left behind.

## J. What Was Not Changed

- No deployment.
- No push.
- No Root Admin password printed or stored.
- No real Paymob private values.
- No private values printed.
- No private Paymob values saved.
- No real payments enabled.
- No Paymob calls.
- No checkout CTAs exposed.
- No Root Admin data changed.
- No legacy `payment_gateways` use.
- No legacy payment/enrol writes.
- No debug mode left enabled.
- No temporary debug files committed.

## K. Remaining Risks/Blockers

- Browser automation packages/drivers remain unavailable in the local environment, so QA was server-session based.
- `application/config/config.php` still has an empty `encryption_key`, so private Paymob DB storage remains blocked.
- Public key save remains intentionally blocked in this phase.
- No payment-config audit log table exists yet.
- Real Paymob sandbox values and webhook/tunnel setup remain future owner-provided inputs.

## L. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.CONFIG.DASHBOARD.AUDIT.SCHEMA.1
```

Suggested scope:

- Add a dedicated YounGo payment config audit table and rollback SQL.
- Reuse the subscription audit-log pattern.
- Store redacted before/after snapshots only.
- Continue blocking private secret values, activation gates, Paymob network calls, and checkout CTAs.

## M. Git Status

Expected final git status after this phase:

```text
 M application/models/Youngo_payment_config_model.php
?? docs/qa/youngo_payment_paymob_config_dashboard_save_ui_fix_1_report.md
```
