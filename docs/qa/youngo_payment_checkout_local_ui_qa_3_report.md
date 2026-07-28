# PAYMENT.CHECKOUT.LOCAL.UI.QA.3 - Final Browser Smoke and Regression Check

Date: 2026-07-22

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree status: clean
- Latest commit at start: `bcb01b7 Fix YounGo checkout HTTP DB access`

Recent commit context:

```text
bcb01b7 Fix YounGo checkout HTTP DB access
92fd18e QA authenticated YounGo checkout start blocker
cf98079 QA disabled YounGo checkout route safety
c26a933 Add controlled local YounGo checkout flow
a943b64 Add disabled YounGo checkout route skeleton
5ea5f16 Plan YounGo checkout route boundaries
96790ac Issue YounGo course access from verified payments
a393278 Add YounGo payment order status transitions
b1d6977 Add YounGo fixture payment transaction recording
b96a7b9 Add disabled YounGo Paymob webhook route skeleton
```

## B. Temporary Local Config

Created temporary ignored local override:

```text
application/config/youngo_paymob.local.php
```

Values used:

- `checkout_routes_enabled = true`
- `checkout_local_testing_enabled = true`
- `checkout_cta_enabled = false`
- `enabled = false`
- `network_enabled = false`
- `currency = EGP`
- `mode = sandbox`

No Paymob credentials, HMAC secrets, client secrets, or live URLs were added. The file was removed after QA.

## C. Backup

A fresh local backup was created before smoke QA DB writes:

```text
Path: D:\Work\YounGo\backups\youngo_school_before_payment_checkout_local_ui_qa_3_2026_07_22_164147.sql
Size: 629498 bytes
SHA256: 44ac8db79bf79988082cd3b9895b8be8018c3cd57d8b8e8748c48fbd74425bc0
```

## D. QA Learner

QA learner was resolved by email:

```text
email: qa.learner@youngo.local
resolved_user_id: 10
role_id: 2
status: 1
is_instructor: 0
```

The learner password was not printed or stored.

## E. URLs Tested

Base URL:

```text
http://school.local
```

Authenticated learner smoke:

- `/login`
- `/login/validate_login`
- `/youngo/checkout/start/9`
- `/youngo/checkout/order/{diagnostic_order_reference}`
- `/youngo/checkout/status/{diagnostic_order_reference}`
- `/youngo/checkout/return/{diagnostic_order_reference}`
- `/youngo/checkout/order/fake-reference`
- `/youngo/checkout/start/999999`

Guest/safety smoke:

- `/youngo/checkout/start/9`
- `/youngo/checkout/status/{diagnostic_order_reference}`

Public CTA regression pages:

- `/`
- `/home/courses`
- `/home/course/robotics-and-ai-explorers/9`
- `/home/my_wishlist`

## F. Smoke QA Summary

Result:

- Authenticated checkout start for course 9 no longer returned HTTP 500.
- Start route produced a local draft order and refresh-style redirect to the order page.
- Created diagnostic order reference: `YGO-20260722164324-F2ADBC85DD15`
- Order page rendered HTTP 200.
- Order page showed course title, amount, EGP currency, order reference, and local sandbox-only state.
- Order page showed Paymob redirect disabled for this phase.
- Status route returned HTTP 200 JSON with `ok: true`.
- Status route reported Paymob network as disabled.
- Return route rendered HTTP 200 and remained UX-only.
- Fake order reference was handled safely with HTTP 422.
- Invalid course ID was handled safely with HTTP 422.
- Guest status access was rejected safely with HTTP 401.
- Guest checkout start was login-gated safely.

## G. Payment/Secret Safety

Verified during smoke:

- No real payment button was shown.
- No gateway selector control was shown.
- The order page contained only disabled-state copy explaining there is no gateway selection.
- No Paymob secret, HMAC secret, client secret, or credential-looking value was exposed.
- No Paymob network URL or Intention API marker was present in rendered route responses.

Existing diagnostics continue to verify that the checkout controller and route skeleton contain no Paymob network execution.

## H. CTA Safety

Checked public pages:

- `/`
- `/home/courses`
- `/home/course/robotics-and-ai-explorers/9`
- `/home/my_wishlist`

Result:

- No public `youngo/checkout/start` links were found.
- No public checkout CTA was exposed.
- Existing CTA suppression remains intact.

## I. DB Cleanup

Baseline before QA:

```text
youngo_checkout_orders       0
youngo_payment_transactions  0
youngo_course_access         0
payment                      0
enrol                        1
```

Cleanup result:

```text
cleanup_orders_matched       1
youngo_checkout_orders       1 -> 0
youngo_payment_transactions  0 -> 0
youngo_course_access         0 -> 0
payment                      0 -> 0
enrol                        1 -> 1
```

Final protected counts:

```text
youngo_checkout_orders       0
youngo_payment_transactions  0
youngo_course_access         0
payment                      0
enrol                        1
```

The ignored local config was removed after QA. The QA learner account was not changed.

## J. Files Changed

Created:

- `docs/qa/youngo_payment_checkout_local_ui_qa_3_report.md`

Temporary and removed:

- `application/config/youngo_paymob.local.php`

## K. What Was Not Changed

- No deployment.
- No push.
- No Root Admin modification.
- No committed ignored local config.
- No public checkout CTA exposure.
- No Paymob network calls.
- No real Paymob intentions.
- No entitlement issuance from UI.
- No legacy payment/enrol writes.
- No legacy gateway row changes.

## L. Remaining Risks/Blockers

- Browser smoke used local ignored flags; committed defaults remain disabled.
- Paymob real/sandbox network execution is still intentionally not implemented.
- Public checkout CTA exposure remains blocked until an explicit later approval phase.
- Full visual QA in a real graphical browser/mobile viewport is still recommended before exposing any CTA.

## M. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.CHECKOUT.CTA.PLAN.1 - Checkout CTA Exposure Criteria and Preflight Plan
```

The next phase should define exact approval gates before any public YounGo checkout CTA is added.

## N. Git Status

Validation commands run:

```text
php scripts/phase_2/youngo_payment_checkout_local_block_1_runtime_test.php
php scripts/phase_2/youngo_payment_checkout_route_skeleton_1_diagnostic.php
git diff --check
git status --short
```

Result:

- Checkout local block runtime diagnostic passed.
- Checkout route skeleton diagnostic passed.
- `git diff --check` passed.
- Temporary ignored local config was removed.
- Protected counts remained restored after validation.

Final git status:

```text
?? docs/qa/youngo_payment_checkout_local_ui_qa_3_report.md
```
