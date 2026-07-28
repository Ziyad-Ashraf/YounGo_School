# PAYMENT.CHECKOUT.LOCAL.UI.QA.1 - Browser QA for Disabled and Local Checkout Routes Without Public CTA Exposure

Date: 2026-07-21

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree status: clean
- Latest commit at start: `c26a933 Add controlled local YounGo checkout flow`

Recent commit context:

```text
c26a933 Add controlled local YounGo checkout flow
a943b64 Add disabled YounGo checkout route skeleton
5ea5f16 Plan YounGo checkout route boundaries
96790ac Issue YounGo course access from verified payments
a393278 Add YounGo payment order status transitions
b1d6977 Add YounGo fixture payment transaction recording
b96a7b9 Add disabled YounGo Paymob webhook route skeleton
c0d7796 Add YounGo Paymob webhook verification skeleton
4fd5f8c Add disabled YounGo Paymob adapter skeleton
fda6f91 Runtime test YounGo checkout order service
```

## B. Local Ignored Config Used

A temporary ignored local override was used only for the flag-on guest/local route checks:

```text
application/config/youngo_paymob.local.php
```

Values used were local-safe only:

- `checkout_routes_enabled = true`
- `checkout_local_testing_enabled = true`
- `checkout_cta_enabled = false`
- `enabled = false`
- `network_enabled = false`
- `currency = EGP`
- `mode = sandbox`

All credential fields stayed `null`. The file was confirmed ignored by Git and removed before final validation.

## C. Browser URLs Tested

Local base URL:

```text
http://localhost/
```

Default-disabled routes tested:

- `http://localhost/youngo/checkout/start/9`
- `http://localhost/youngo/checkout/order/fake-reference`
- `http://localhost/youngo/checkout/status/fake-reference`
- `http://localhost/youngo/checkout/return/fake-reference`

Flag-on guest/local routes tested:

- `http://localhost/youngo/checkout/start/9`
- `http://localhost/youngo/checkout/order/fake-reference`
- `http://localhost/youngo/checkout/status/fake-reference`
- `http://localhost/youngo/checkout/return/fake-reference`
- `http://localhost/youngo/checkout/start/999999`

CTA safety URLs checked:

- `http://localhost/`
- `http://localhost/home/courses`
- `http://localhost/home/course/robotics-and-ai-explorers/9`
- `http://localhost/home/my_wishlist`

## D. Disabled Behavior Result

With no local override present, the checkout routes failed closed:

```text
/youngo/checkout/start/9                 HTTP 403 HTML, reason checkout_routes_disabled
/youngo/checkout/order/fake-reference    HTTP 403 HTML, reason checkout_routes_disabled
/youngo/checkout/status/fake-reference   HTTP 403 JSON, code checkout_routes_disabled
/youngo/checkout/return/fake-reference   HTTP 403 HTML, reason checkout_routes_disabled
```

The disabled HTML states said the skeleton does not create orders, open Paymob, issue access, or use legacy gateway rows. The JSON status response reported `paymob_network = disabled`.

## E. Local Checkout Behavior Result

With the temporary ignored local override enabled, guest access was login-gated:

```text
/youngo/checkout/start/9                 HTTP 200 with Refresh to http://localhost/login
/youngo/checkout/order/fake-reference    HTTP 200 with Refresh to http://localhost/login
/youngo/checkout/return/fake-reference   HTTP 200 with Refresh to http://localhost/login
/youngo/checkout/status/fake-reference   HTTP 401 JSON, code learner_login_required
```

The status JSON kept `mode = sandbox`, `currency = EGP`, and `paymob_network = disabled`.

Authenticated learner route testing was not completed in browser because the QA learner password was not provided in this phase. Root Admin credentials were provided, but they were not used for learner checkout because Root Admin must not be modified or used as the learner purchaser fixture.

## F. Order/Status/Return Result

Order and return pages were verified in disabled mode and as guest login-gated local routes. No browser-created checkout order page was reached because authenticated QA learner access was unavailable.

Status endpoint behavior was verified in both states:

- Default disabled: `403 checkout_routes_disabled`
- Local flags on, guest: `401 learner_login_required`

Return URL behavior remains UX-only by implementation and was not allowed to mutate order state.

## G. CTA Safety Result

Public pages were checked for `youngo/checkout/start` links:

```text
/                                      no checkout/start links
/home/courses                         no checkout/start links
/home/course/robotics-and-ai-explorers/9 no checkout/start links
/home/my_wishlist                     no checkout/start links
```

Static diagnostics also continue to verify no checkout-start links in the YounGo course detail, course card, wishlist, or wishlist item views. No public checkout CTA was exposed.

## H. DB Cleanup Result

The browser/HTTP checks did not create checkout orders, payment transactions, entitlements, coupons, legacy payments, or legacy enrolments.

Post-check schema diagnostic protected counts:

```text
payment                     0
enrol                       1
youngo_checkout_orders       0
youngo_payment_transactions  0
youngo_course_access         0
youngo_user_subscriptions    0
youngo_manual_grants         0
youngo_coupon_usages         0
watch_histories             0
watched_duration            0
```

The temporary ignored local override was removed before final validation.

## I. Screenshots Captured

No screenshots were captured. The local environment had the Playwright CLI available, but the Playwright Node module was not available for inline automation without adding dependencies. No dependency installation was performed.

## J. What Was Not Changed

- No deployment.
- No push.
- No commit.
- No live/cPanel access.
- No real credentials.
- No Paymob network calls.
- No real Paymob intentions.
- No public checkout CTA exposure.
- No entitlement issuance from UI.
- No legacy `payment` writes.
- No legacy `enrol` writes.
- No legacy gateway DB row changes.
- No Root Admin modification.
- No persistent local override file.
- No persistent diagnostic checkout/payment/access rows.

## K. Remaining Risks/Blockers

- Authenticated learner browser checkout start/order page QA remains blocked until the owner provides the QA learner credentials. Root Admin must not be used as the checkout learner fixture.
- Invalid course and already-access route cases could not be fully browser-tested because the current controller login-gates before course/order validation when local flags are enabled.
- Mobile viewport QA was not completed because dependency-free browser automation was unavailable; this remains a visual QA item.
- Paymob network execution, real Unified Checkout redirect behavior, and public CTA exposure remain intentionally deferred.

## L. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.CHECKOUT.LOCAL.UI.QA.2 - Authenticated QA Learner Checkout Browser QA
```

Suggested scope:

- Use QA learner user `8` only after the owner provides the QA learner password.
- Keep ignored local flags enabled only during QA.
- Browser-test `/youngo/checkout/start/9`, generated order page, status JSON, return page, invalid course, and already-access cases.
- Clean all diagnostic checkout rows and re-run protected-count diagnostics.

## M. Git Status

Final git status after report creation:

```text
?? docs/qa/youngo_payment_checkout_local_ui_qa_1_report.md
```

Validation commands run:

```text
php scripts/phase_2/youngo_payment_checkout_local_block_1_runtime_test.php  PASS
php scripts/phase_2/youngo_payment_checkout_route_skeleton_1_diagnostic.php PASS
git diff --check                                                         PASS
git status --short                                                       report only
```
