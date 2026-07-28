# PAYMENT.PAYMOB.SETUP.FORM.UI.QA.2 - Manual Browser Rendering Smoke for Paymob Setup Form

## A. Current Branch/Status

- Branch at start: `analysis/cms-audit`
- Start worktree status: clean
- Latest commit at start: `3905afd QA YounGo Paymob setup form safety`
- Scope: rendering/usability smoke for `/admin/youngo/payment-settings`

## B. QA Method

QA used the normal Root/Admin login flow against `http://school.local`.

The page HTML was loaded from the authenticated local session and rendered with headless Microsoft Edge for visual inspection. Temporary rendered HTML and screenshots were created only under the local backups area for inspection, then removed before final validation.

No source code was changed for the UI.

## C. URLs Tested

- `/login`
- `/admin/youngo/payment-settings`
- `/`
- `/home/courses`
- `/home/course/robotics-and-ai-explorers/9`
- `/home/my_wishlist`

## D. Render Result

`/admin/youngo/payment-settings` rendered HTTP 200.

The page is visually usable as a Paymob setup form for a Root/Admin user. The layout follows the existing Academy-style admin dashboard: left sidebar, page title card, alert notices, white cards, compact tables, form controls, and badges.

## E. Visual QA Summary

Confirmed visually usable:

- Paymob basic setup section is clear and appears first in the main form column.
- URLs section is clear and includes helpful return URL / notification URL guidance.
- Private credentials section is visibly separate and clearly blocked/server-config-only.
- Readiness/status panel is readable and summarizes current hybrid readiness.
- Payment/network/CTA gates are understandable and clearly disabled/blocked.
- Audit panel is readable, with no audit rows in the current clean baseline.
- Setup checklist is useful for owner/client setup sequencing.
- Required field list is understandable and separates dashboard fields from private server-config-only fields.
- Next steps are clear, and the sandbox test button is visibly disabled.

No critical rendering or safety issue was found, so no source code changes were made.

## F. Safety Summary

Confirmed:

- No private Paymob values are visible.
- `public_key`, `secret_key`, and `hmac_secret` render only as disabled/non-editable placeholders.
- Private placeholder inputs have no submit `name` attributes.
- No password inputs are present.
- Sandbox Test control remains disabled/non-functional.
- No Paymob call patterns were present in the rendered settings page.
- No form submission was performed in this phase.
- No DB writes were performed in this phase.
- No Root Admin data was modified.

## G. CTA Safety

Checked public surfaces:

- `/`
- `/home/courses`
- `/home/course/robotics-and-ai-explorers/9`
- `/home/my_wishlist`

Result:

- All checked pages returned safe non-500 responses.
- No `youngo/checkout/start` link was detected.
- No production checkout CTA exposure was detected.

## H. UI Polish Recommendations

Recommended later polish, none blocking:

- The page is long. Consider collapsing `Readiness details` into an advanced section after the setup flow stabilizes.
- The right-column readiness/checklist content is useful but dense. Later browser QA could test whether the right column should become sticky or be split into tabs.
- Some labels are technical by necessity, such as `amount_multiplier`, `notification_url`, and `card_integration_id_egp`. Consider adding a short client-facing glossary or tooltip copy later.
- The audit panel is readable, but it may be more useful near the bottom after the full setup checklist once real client use begins.
- Mobile/narrow dashboard rendering was not inspected in this smoke and should be covered in a later responsive QA pass if the client will configure Paymob from smaller screens.

## I. What Was Not Changed

- No deployment.
- No push.
- No real Paymob values added.
- No private values printed or saved.
- No `encryption_key` change.
- No payment activation.
- No Paymob calls.
- No checkout CTA exposure.
- No Root Admin modification.
- No legacy `payment` or `enrol` writes.
- No source code changes.
- No browser/session artifacts left in the repository.

## J. Remaining Risks/Blockers

- Private Paymob config remains server-config-only until encryption/key management is approved.
- Sandbox payment execution remains blocked until explicit Paymob sandbox values and local flags are provided in a later phase.
- A responsive/mobile dashboard visual pass remains optional future QA.

## K. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.PAYMOB.SANDBOX.CONFIG.INPUT.QA.1 - Verify Owner-Provided Paymob Sandbox Configuration Inputs
```

This should run only after the owner/client has created the Paymob sandbox account and provided the required non-private and private values through safe channels.

## L. Validation

Validation commands run:

- `php scripts/phase_2/youngo_payment_paymob_setup_form_ui_1_diagnostic.php` - PASS
- `php scripts/phase_2/youngo_payment_paymob_setup_wizard_1_diagnostic.php` - PASS
- `git diff --check` - PASS
- `git status --short` - shows only this QA report

## M. Git Status

Final git status:

```text
?? docs/qa/youngo_payment_paymob_setup_form_ui_qa_2_report.md
```
