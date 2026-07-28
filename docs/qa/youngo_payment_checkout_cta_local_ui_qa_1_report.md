# PAYMENT.CHECKOUT.CTA.LOCAL.UI.QA.1 - Course Detail Local CTA Browser QA

Date: 2026-07-22

Scope: local browser-style QA for the course-detail-only YounGo checkout CTA behind temporary ignored local flags. No deployment, push, committed local config, real credentials, Paymob network calls, Paymob intentions, entitlement issuance from UI, legacy payment/enrol writes, Root Admin changes, or public CTA exposure by default.

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree status: clean
- Latest commit at start: `68b9b28 Add gated YounGo checkout CTA helper`

Recent commit context:

```text
68b9b28 Add gated YounGo checkout CTA helper
bda9cb1 Plan YounGo checkout CTA exposure
b59eaee QA local YounGo checkout smoke flow
bcb01b7 Fix YounGo checkout HTTP DB access
92fd18e QA authenticated YounGo checkout start blocker
cf98079 QA disabled YounGo checkout route safety
c26a933 Add controlled local YounGo checkout flow
a943b64 Add disabled YounGo checkout route skeleton
5ea5f16 Plan YounGo checkout route boundaries
96790ac Issue YounGo course access from verified payments
```

## B. Backup Created

Backup was created before checkout QA writes:

- Path: `D:\Work\YounGo\backups\youngo_school_before_payment_checkout_cta_local_ui_qa_1_2026_07_22_172158.sql`
- Size: `629581` bytes
- SHA256: `6e492212b00e9a5d4925f5d99475321d7f181aa75e40a538ebf1cc2d26dc36fd`

## C. URLs Tested

With temporary ignored local flags enabled:

- `http://school.local/home/course/robotics-and-ai-explorers/9`
- `http://school.local/youngo/checkout/start/9`
- generated local order page under `http://school.local/youngo/checkout/order/{order_reference}`
- generated local status route under `http://school.local/youngo/checkout/status/{order_reference}`
- generated local return route under `http://school.local/youngo/checkout/return/{order_reference}`
- `http://school.local/youngo/checkout/order/fake-reference`
- `http://school.local/youngo/checkout/start/999999`
- `http://school.local/`
- `http://school.local/home/courses`
- `http://school.local/home/my_wishlist`

Temporary ignored config used:

- `checkout_routes_enabled = true`
- `checkout_local_testing_enabled = true`
- `checkout_cta_enabled = true`
- `enabled = false`
- `network_enabled = false`
- `currency = EGP`
- `mode = sandbox`

The ignored config was removed after QA.

## D. Course Detail CTA Result

QA learner was resolved as an active learner by email:

- user id: `10`
- role: learner/student (`role_id = 2`)
- active: yes
- instructor: no

The supplied learner credential matched the local QA learner row, but a direct scripted login was intercepted by the existing Academy new-device confirmation flow before learner session data was established. To complete the browser-style QA without resetting passwords or modifying user/device state, an existing valid QA learner `ci_session` already stored for that account was reused.

Course detail result:

- The course-detail page showed `data-youngo-checkout-cta="local-course-detail"`.
- The CTA target was the YounGo-only local checkout route: `youngo/checkout/start/9`.
- No legacy cart, buy-now, gateway, or Academy payment URL was exposed by the CTA.

## E. Click-Through Checkout Result

Clicking the local CTA created/reused one local draft checkout order for course `9` and rendered the local order page.

Verified on the generated order page:

- order reference was visible
- course title `Robotics and AI Explorers` was visible
- amount displayed in `EGP`
- local/sandbox state was visible
- Paymob/network-disabled state was visible
- no real payment button
- no gateway selector
- no Paymob URL
- no secret markers such as Paymob secret/HMAC/client-secret names

Status and return routes:

- status route returned a safe checkout status payload
- return route rendered safely as UX-only
- return route did not mark the order paid
- return route did not issue entitlement

Safety cases:

- fake order reference returned a safe `422` response with no DB writes
- invalid course id returned a safe `422` response with no DB writes
- guest checkout start returned a safe refresh redirect to login with no order creation

## F. CTA Surface Safety

No `youngo/checkout/start` links were found on these non-course-detail surfaces during local flag QA:

- homepage: `/`
- course listing: `/home/courses`
- wishlist: `/home/my_wishlist`

Course detail was the only CTA surface exercised.

## G. DB Cleanup Result

Baseline protected counts before QA:

```text
youngo_checkout_orders = 0
youngo_payment_transactions = 0
youngo_course_access = 0
payment = 0
enrol = 1
```

QA created one local checkout order:

```text
order_reference = YGO-20260722172814-AEF87C62E29A
user_id = 10
course_id = 9
status = draft
currency = EGP
total_amount = 1000.00
```

Cleanup deleted the diagnostic checkout order. No transactions were created.

Final protected counts after cleanup:

```text
youngo_checkout_orders = 0
youngo_payment_transactions = 0
youngo_course_access = 0
payment = 0
enrol = 1
```

The QA learner account was not changed.

## H. Files Changed

Created:

- `docs/qa/youngo_payment_checkout_cta_local_ui_qa_1_report.md`

Temporary ignored file created and removed:

- `application/config/youngo_paymob.local.php`

No source code files were changed in this phase.

## I. Remaining Risks/Blockers

- Direct scripted login for `qa.learner@youngo.local` is intercepted by Academy's existing new-device confirmation flow in this environment. Authenticated QA was completed using an existing valid QA learner session without changing account/device state.
- This phase still does not test real Paymob, real payment button behavior, public CTA exposure, production flags, or entitlement issuance from UI.
- Fake/invalid route checks in the local setup return safe empty-body `422` responses, so future browser automation should assert status/headers as well as page text.

## J. Recommended Next Phase

Recommended next phase:

`PAYMENT.CHECKOUT.CTA.LOCAL.UI.FIX.1 - Stabilize QA Learner Login/Device Test Harness`

Purpose:

- add or document a safe local-only QA login/session approach that does not require Root Admin, password resets, or manual device confirmation
- keep production login behavior unchanged
- rerun the CTA browser QA through a fresh authenticated learner login path

After that, proceed to a locally gated CTA expansion plan only if still approved.

## K. Git Status

Git status after report creation and cleanup:

```text
?? docs/qa/youngo_payment_checkout_cta_local_ui_qa_1_report.md
```
