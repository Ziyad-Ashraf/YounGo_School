# PAYMENT.CHECKOUT.LOCAL.UI.QA.2 - Authenticated QA Learner Checkout Browser QA Retry

Date: 2026-07-22

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree status: clean
- Latest commit at start: `cf98079 QA disabled YounGo checkout route safety`

Recent commit context:

```text
cf98079 QA disabled YounGo checkout route safety
c26a933 Add controlled local YounGo checkout flow
a943b64 Add disabled YounGo checkout route skeleton
5ea5f16 Plan YounGo checkout route boundaries
96790ac Issue YounGo course access from verified payments
a393278 Add YounGo payment order status transitions
b1d6977 Add YounGo fixture payment transaction recording
b96a7b9 Add disabled YounGo Paymob webhook route skeleton
c0d7796 Add YounGo Paymob webhook verification skeleton
4fd5f8c Add disabled YounGo Paymob adapter skeleton
```

## B. Backup Created

Fresh local DB backup created before authenticated browser/HTTP QA writes:

```text
Path: D:\Work\YounGo\backups\youngo_school_before_payment_checkout_local_ui_qa_2_2026_07_22_190643.sql
Size: 626072 bytes
SHA256: ee9b7fb0ff6e5f79258958900da94a76df8dba73559365a3729c6bcc8f49aa78
```

The backup was created from the local DB only using XAMPP `mysqldump.exe`. No live/cPanel system was touched.

## C. QA Learner Resolved By Email

QA learner was resolved by email, not by assuming the old fixture ID:

```text
email: qa.learner@youngo.local
resolved_user_id: 10
role_id: 2
status: 1
is_instructor: 0
```

The learner password was used only for the authenticated QA login and was not printed or stored in this report.

Baseline protected counts before QA:

```text
youngo_checkout_orders       0
youngo_payment_transactions  0
youngo_course_access         0
payment                      0
enrol                        1
```

## D. URLs Tested

Local host used:

```text
http://school.local
```

Authenticated learner flow attempted:

- `http://school.local/login`
- `http://school.local/login/validate_login`
- `http://school.local/youngo/checkout/start/9`

Safety and CTA checks:

- `http://school.local/youngo/checkout/status/fake-reference`
- `http://school.local/home/courses`
- `http://school.local/home/course/robotics-and-ai-explorers/9`
- `http://school.local/home/my_wishlist`
- `http://school.local/`

Order/status/return URLs for a created order were not reached because checkout start returned HTTP 500 before creating or redirecting to an order.

## E. Authenticated Learner Checkout Result

Authenticated QA learner login succeeded through the normal frontend login endpoint and redirected as a learner.

Checkout start failed:

```text
GET /youngo/checkout/start/9
Result: HTTP 500 Internal Server Error
Content-Length: 0
```

No checkout order was created before the failure.

Apache logs did not expose a current PHP fatal detail for this request. Existing `application/logs` had no current CodeIgniter log output because the app config has `log_threshold = 0`.

## F. Order/Status/Return Result

Not completed.

The order page, generated order status endpoint, and return page could not be tested because `/youngo/checkout/start/9` failed with HTTP 500 before producing an order reference.

The guest status endpoint still failed safely while local flags were on:

```text
/youngo/checkout/status/fake-reference
HTTP 401
code: learner_login_required
paymob_network: disabled
```

## G. Safety-Case Result

Completed:

- Guest status access rejected safely with `learner_login_required`.
- No Paymob network request was made.
- No real Paymob intention was created.
- No payment button or gateway selector was reached.
- No secrets were printed.

Not completed because checkout start failed first:

- Invalid course after authenticated start.
- Fake order reference after authenticated login.
- Already-access case.
- Created-order status and return URL UX-only behavior.

## H. CTA Safety Result

Public pages were checked for `youngo/checkout/start` links:

```text
/home/courses                              no checkout/start links
/home/course/robotics-and-ai-explorers/9   no checkout/start links
/home/my_wishlist                          no checkout/start links
/                                           no checkout/start links
```

No public checkout CTA exposure was found.

## I. DB Cleanup Result

No order row was created by the failed checkout start request.

The local DB was restored from the pre-QA backup to remove login/session side effects and return the QA learner/session state to the pre-QA snapshot.

Final protected counts after restore:

```text
youngo_checkout_orders       0
youngo_payment_transactions  0
youngo_course_access         0
payment                      0
enrol                        1
```

## J. Temporary Config Cleanup

Temporary ignored local config used:

```text
application/config/youngo_paymob.local.php
```

Safe local-only values:

- `checkout_routes_enabled = true`
- `checkout_local_testing_enabled = true`
- `checkout_cta_enabled = false`
- `enabled = false`
- `network_enabled = false`
- `currency = EGP`
- `mode = sandbox`

All credential fields were `null`.

The file was removed after QA. It was not committed.

## K. What Was Not Changed

- No deployment.
- No push.
- No Root Admin use or modification.
- No password reset.
- No user creation/deletion.
- No real credentials.
- No public checkout CTA exposure.
- No Paymob network calls.
- No real Paymob intentions.
- No entitlement issuance from UI.
- No legacy `payment` writes.
- No legacy `enrol` writes.
- No persistent checkout/payment/access rows.
- No committed local config.

## L. Remaining Risks/Blockers

- Authenticated checkout start currently returns HTTP 500 under the local Apache route before order creation.
- The HTTP 500 blocks authenticated browser QA for generated order page, status endpoint, and return page.
- Current logging did not expose the fatal detail because CodeIgniter logging is disabled and production display errors are off.
- A focused bugfix/diagnostic phase is needed before repeating authenticated checkout UI QA.

## M. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.CHECKOUT.LOCAL.START.FIX.1 - Diagnose and Fix Authenticated Checkout Start HTTP 500
```

Suggested scope:

- Keep Paymob/network disabled.
- Reproduce `/youngo/checkout/start/9` as authenticated QA learner.
- Temporarily enable local diagnostics/logging only if approved and remove afterward.
- Fix the minimal controller/model issue causing HTTP 500.
- Re-run QA2 after the route creates a local draft order and redirects to the order page.

## N. Git Status

Final git status after validation:

```text
?? docs/qa/youngo_payment_checkout_local_ui_qa_2_report.md
```

Validation commands run:

```text
php scripts/phase_2/youngo_payment_checkout_local_block_1_runtime_test.php  PASS
php scripts/phase_2/youngo_payment_checkout_route_skeleton_1_diagnostic.php PASS
git diff --check                                                         PASS
git status --short                                                       report only
```
