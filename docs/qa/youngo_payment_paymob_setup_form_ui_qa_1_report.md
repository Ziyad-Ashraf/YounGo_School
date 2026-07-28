# PAYMENT.PAYMOB.SETUP.FORM.UI.QA.1 - Browser QA for Legacy-Style Paymob Setup Form UI

## A. Current Branch/Status

- Branch at start: `analysis/cms-audit`
- Start worktree status: clean
- Latest commit at start: `1b3df58 Redesign YounGo Paymob setup form UI`
- Scope: Root/Admin Paymob setup page UI QA with safe placeholder non-private save.

## B. Backup Created

- Backup path: `D:\Work\YounGo\backups\youngo_school_before_paymob_setup_form_ui_qa_1_2026_07_23_052825.sql`
- Size: `1238368` bytes
- SHA256: `609C26C9214470626B140F0BBACFC6AADAE949492341481D91F0BC830F83BDF2`

## C. QA Method

Authenticated browser automation was attempted with the available `npx` Playwright tooling, but the local package could not resolve the test API from repo-local specs without adding a project dependency. No dependency was added.

The QA was completed as authenticated local HTTP-session QA against `http://school.local/`, using the normal login route and the same server-rendered pages. This verified HTTP status, rendered HTML, form fields, POST save behavior, redirects, audit rendering, gate display, and public CTA absence. Pixel/layout rendering remains a follow-up browser QA item.

Temporary Playwright files/output were removed and are not present in the worktree.

## D. URLs Tested

- `/login`
- `/admin/dashboard`
- `/admin/youngo/payment-settings`
- `/`
- `/home/courses`
- `/home/course/robotics-and-ai-explorers/9`
- `/home/my_wishlist`

## E. Form UI QA Summary

`/admin/youngo/payment-settings` rendered HTTP 200 after Root/Admin login.

Confirmed rendered setup sections:

- Paymob basic setup
- URLs
- Private credentials
- Readiness / status
- Payment/network/CTA gates
- Recent Configuration Audit
- Paymob Sandbox Setup Checklist
- Next steps

Confirmed editable non-private dashboard fields exist:

- `mode`
- `currency`
- `amount_multiplier`
- `card_integration_id_egp`
- `api_base_url`
- `checkout_base_url`
- `return_url`
- `notification_url`

## F. Non-Private Save Result

Submitted safe placeholder non-private sandbox values only:

- `mode`: sandbox
- `currency`: EGP
- `amount_multiplier`: 100
- `card_integration_id_egp`: numeric placeholder
- URL fields: safe placeholder HTTP/HTTPS values

Result:

- Save returned HTTP 200 after redirect.
- Success message rendered: payments and checkout activation remain disabled.
- Saved non-private placeholder fields rendered back safely.
- Audit log showed a recent `dashboard_non_private_save` row.

## G. Private Credential Safety

Confirmed private credential placeholders render for:

- `public_key`
- `secret_key`
- `hmac_secret`

Safety result:

- Private placeholders are disabled/non-editable.
- Private placeholders do not use submit `name` attributes.
- No password inputs are present.
- No private Paymob values were entered, saved, or rendered.
- Dashboard still states private values are server-config-only while DB private storage is blocked.

## H. Gate/Sandbox-Test Safety

Confirmed all behavior gates remained disabled after save:

- Payment enabled: disabled
- Network enabled: disabled
- Sandbox network testing: disabled
- Webhook testing: disabled
- Checkout routes: disabled
- Checkout CTA: disabled
- Live mode allowed: blocked

Confirmed the Sandbox Test control remains disabled and non-functional.

No Paymob network call was performed by this QA flow. The HTTP-session method does not execute browser subresources, and static diagnostics continued to verify no Paymob call patterns were introduced on the settings page path.

## I. CTA Safety

Checked public surfaces:

- `/`
- `/home/courses`
- `/home/course/robotics-and-ai-explorers/9`
- `/home/my_wishlist`

Result:

- All checked pages returned safe non-500 responses.
- No `youngo/checkout/start` links were present.
- No production checkout CTA exposure was detected.

## J. DB Cleanup

Baseline counts before QA:

- `youngo_payment_provider_configs`: 0
- `youngo_payment_config_audit_logs`: 0
- `payment_gateways`: 15
- `payment`: 0
- `enrol`: 1
- `youngo_checkout_orders`: 0
- `youngo_payment_transactions`: 0
- `youngo_course_access`: 0

Cleanup action:

- Removed the temporary Paymob sandbox config row created by the non-private save test.
- Removed the temporary Paymob sandbox audit row created by the save test.

Counts after cleanup:

- `youngo_payment_provider_configs`: 0
- `youngo_payment_config_audit_logs`: 0
- `payment_gateways`: 15
- `payment`: 0
- `enrol`: 1
- `youngo_checkout_orders`: 0
- `youngo_payment_transactions`: 0
- `youngo_course_access`: 0

Protected counts returned to baseline.

## K. What Was Not Changed

- No deployment.
- No push.
- No real Paymob values added.
- No private Paymob values saved to DB.
- No `encryption_key` change.
- No payment activation.
- No Paymob request.
- No production checkout CTA exposure.
- No Root Admin data modification.
- No legacy gateway configuration use for YounGo Paymob.
- No legacy `payment` or `enrol` writes.
- No browser/session artifacts committed or left in the repo.

## K1. Validation

Validation commands run:

- `php scripts/phase_2/youngo_payment_paymob_setup_form_ui_1_diagnostic.php` - PASS
- `php scripts/phase_2/youngo_payment_paymob_setup_wizard_1_diagnostic.php` - PASS
- `php scripts/phase_2/youngo_payment_paymob_setup_preflight_1_diagnostic.php` - PASS
- `git diff --check` - PASS
- `git status --short` - shows only this QA report

## L. Remaining Risks/Blockers

- Pixel-level browser rendering remains unverified because Playwright browser automation could not run without adding a project dependency.
- The current page is still setup/readiness plus non-private save only, not private credential entry or sandbox execution.
- Real sandbox readiness still depends on owner-provided Paymob sandbox values and ignored server config.

## M. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.SETUP.FORM.UI.QA.2 - Browser Rendering Smoke for Legacy-Style Paymob Setup Form UI
```

Scope should be a true browser-render QA once Playwright or another browser automation tool is available without changing project dependencies. It should focus on layout, visible disabled private placeholders, audit panel readability, and responsive admin rendering.

## N. Git Status

Final git status:

```text
?? docs/qa/youngo_payment_paymob_setup_form_ui_qa_1_report.md
```
