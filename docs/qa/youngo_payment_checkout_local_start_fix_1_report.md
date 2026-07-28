# PAYMENT.CHECKOUT.LOCAL.START.FIX.1 - Authenticated Checkout Start HTTP 500 Fix

Date: 2026-07-22

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree status: clean
- Latest commit at start: `92fd18e QA authenticated YounGo checkout start blocker`

Recent commit context:

```text
92fd18e QA authenticated YounGo checkout start blocker
cf98079 QA disabled YounGo checkout route safety
c26a933 Add controlled local YounGo checkout flow
a943b64 Add disabled YounGo checkout route skeleton
5ea5f16 Plan YounGo checkout route boundaries
96790ac Issue YounGo course access from verified payments
a393278 Add YounGo payment order status transitions
b1d6977 Add YounGo fixture payment transaction recording
b96a7b9 Add disabled YounGo Paymob webhook route skeleton
c0d7796 Add YounGo Paymob webhook verification skeleton
```

## B. Error Reproduced

Fresh local DB backup created before authenticated checkout QA writes:

```text
Path: D:\Work\YounGo\backups\youngo_school_before_payment_checkout_local_start_fix_1_2026_07_22_191922.sql
Size: 626072 bytes
SHA256: 038d060ccd346e88ded95b501605041d6a64edcbfce147183ab4c0d3bca70150
```

Temporary ignored local config was used only during reproduction and QA:

- `checkout_routes_enabled = true`
- `checkout_local_testing_enabled = true`
- `checkout_cta_enabled = false`
- `enabled = false`
- `network_enabled = false`
- `currency = EGP`
- `mode = sandbox`

The authenticated QA learner was resolved by email as an active learner/student. The learner password was not printed or stored.

The HTTP 500 was reproduced on:

```text
GET http://school.local/youngo/checkout/start/9
```

Local-only development error output identified the fatal as:

```text
Call to a member function where() on null
application\models\Youngo_checkout_model.php
```

After the first fix, the order page/status path exposed the same model-load pattern in:

```text
Call to a member function where() on null
application\models\Youngo_payment_model.php
```

Temporary debug output was enabled only through local `.htaccess` during error capture and was removed immediately after capture. No debug config was committed.

## C. Root Cause

`Youngo_checkout_model` and `Youngo_payment_model` both declare a public `$db` property for CLI diagnostic injection. In CodeIgniter controller-loaded model instances, that declared property shadowed CodeIgniter's normal magic access to the loaded database object. Because HTTP controller loading did not inject a DB object, `$this->db` remained `null`, and normal model calls such as `$this->db->where()` caused HTTP 500 fatals.

CLI diagnostics did not catch this because they instantiate the models with an explicit diagnostic DB object.

## D. Files Changed

Updated:

- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_payment_model.php`

Created:

- `docs/qa/youngo_payment_checkout_local_start_fix_1_report.md`

Temporary and removed:

- `application/config/youngo_paymob.local.php`
- local-only `.htaccess` debug `SetEnv CI_ENV development`

## E. Fix Summary

Both affected models now initialize their public `$db` property from CodeIgniter when no diagnostic DB object is provided:

- If `$params['db']` is provided and is an object, use it for CLI diagnostics.
- Otherwise, assign `$this->db = $this->db_instance()`.

This keeps the existing diagnostic injection behavior while making normal controller-loaded HTTP paths use the application DB object safely.

## F. Authenticated Checkout QA Result

Authenticated learner QA was rerun against:

```text
http://school.local
```

URLs tested:

- `/login`
- `/login/validate_login`
- `/youngo/checkout/start/9`
- `/youngo/checkout/order/{diagnostic_order_reference}`
- `/youngo/checkout/status/{diagnostic_order_reference}`
- `/youngo/checkout/return/{diagnostic_order_reference}`
- `/youngo/checkout/start/999999`
- `/youngo/checkout/order/fake-reference`

Result:

- `/youngo/checkout/start/9` no longer returns HTTP 500.
- The start route created/reused a local draft checkout order for course 9.
- CodeIgniter returned its existing refresh-style redirect to the order page.
- The order page rendered HTTP 200.
- The order page showed course title, amount, EGP currency, order reference, and local sandbox-only state.
- The order page showed Paymob redirect disabled in this phase.
- No real payment button was shown.
- No gateway selector was shown.
- No Paymob secrets or credential placeholders were exposed.

## G. Status/Return Result

The status endpoint and return page were verified for the diagnostic order:

- `/youngo/checkout/status/{order_reference}` returned HTTP 200 JSON with `ok: true`.
- Status reported Paymob network as disabled.
- `/youngo/checkout/return/{order_reference}` rendered HTTP 200.
- Return handling remained UX-only and did not mutate the order.

Safety cases:

- Invalid course start was handled safely.
- Fake order reference was handled safely.
- Guest status access was handled safely.

## H. CTA Safety

Checked pages:

- `/home/courses`
- `/home/course/robotics-and-ai-explorers/9`
- `/home/my_wishlist`
- `/`

Result:

- No public `youngo/checkout/start` links were found.
- No public checkout CTA was exposed.
- Legacy gateway selection was not exposed.

## I. DB Cleanup

Protected baseline after cleanup:

```text
youngo_checkout_orders       0
youngo_payment_transactions  0
youngo_course_access         0
payment                      0
enrol                        1
```

Cleanup performed:

- Deleted the local diagnostic checkout order created for QA learner/course 9.
- Confirmed no payment transaction rows were created.
- Confirmed no YounGo course access rows were created.
- Confirmed legacy `payment` remained unchanged.
- Confirmed legacy `enrol` count remained unchanged.
- Removed ignored local config `application/config/youngo_paymob.local.php`.

The QA learner account was not changed.

## J. What Was Not Changed

- No deployment.
- No push.
- No live/cPanel access.
- No real credentials added.
- No Root Admin changes.
- No public checkout CTA exposure.
- No Paymob network calls.
- No real Paymob intentions.
- No entitlement issuance from UI.
- No legacy payment/enrol writes.
- No legacy gateway row changes.

## K. Remaining Risks/Blockers

- The checkout flow still relies on ignored local config flags for route access; committed defaults remain disabled.
- The start route uses CodeIgniter's refresh-style redirect, which browsers follow but simple HTTP scripts must parse explicitly.
- Paymob network execution remains intentionally unimplemented.
- Production checkout exposure remains blocked until explicit QA approval.

## L. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.CHECKOUT.LOCAL.UI.QA.3 - Final Browser Smoke and Regression Check
```

Scope:

- Repeat authenticated local checkout route QA after commit.
- Verify disabled defaults without ignored local config.
- Keep public CTAs suppressed.
- Keep Paymob network disabled.
- Confirm no residual diagnostic DB rows.

## M. Git Status

Validation commands run:

```text
php -l application/models/Youngo_checkout_model.php
php -l application/models/Youngo_payment_model.php
php scripts/phase_2/youngo_payment_checkout_local_block_1_runtime_test.php
php scripts/phase_2/youngo_payment_checkout_route_skeleton_1_diagnostic.php
git diff --check
git status --short
```

Result:

- PHP syntax checks passed.
- Checkout local block runtime diagnostic passed.
- Checkout route skeleton diagnostic passed.
- `git diff --check` passed with line-ending warnings only.
- Temporary ignored local config was removed.

Final git status:

```text
 M application/models/Youngo_checkout_model.php
 M application/models/Youngo_payment_model.php
?? docs/qa/youngo_payment_checkout_local_start_fix_1_report.md
```
