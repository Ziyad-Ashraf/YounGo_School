# PAYMENT.CHECKOUT.CTA.LOCAL.BLOCK.1 - Checkout CTA Decision Helper and Gated Course Detail Integration

Date: 2026-07-22

Scope: source-only local CTA foundation. No deployment, push, DB modification, SQL execution, credentials, Root Admin changes, real payment enablement, public CTA exposure by default, Paymob network calls, Paymob intentions, legacy gateway/cart/payment route use, or legacy payment/enrol writes.

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree status: clean
- Latest commit at start: `bda9cb1 Plan YounGo checkout CTA exposure`

Recent commit context:

```text
bda9cb1 Plan YounGo checkout CTA exposure
b59eaee QA local YounGo checkout smoke flow
bcb01b7 Fix YounGo checkout HTTP DB access
92fd18e QA authenticated YounGo checkout start blocker
cf98079 QA disabled YounGo checkout route safety
c26a933 Add controlled local YounGo checkout flow
a943b64 Add disabled YounGo checkout route skeleton
5ea5f16 Plan YounGo checkout route boundaries
96790ac Issue YounGo course access from verified payments
a393278 Add YounGo payment order status transitions
```

## B. Files Inspected

Reports read:

- `docs/qa/youngo_payment_checkout_cta_plan_1_report.md`
- `docs/qa/youngo_payment_checkout_local_ui_qa_3_report.md`
- `docs/qa/youngo_payment_checkout_local_block_1_report.md`

Source inspected:

- `application/views/frontend/youngo/`
- `application/controllers/Home.php`
- `application/controllers/Youngo_checkout.php`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_entitlement_model.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/helpers/youngo_entitlement_helper.php`
- existing CTA boundary diagnostics under `scripts/phase_2/`

## C. Files Changed

Created:

- `application/helpers/youngo_checkout_cta_helper.php`
- `scripts/phase_2/youngo_payment_checkout_cta_local_block_1_diagnostic.php`
- `docs/qa/youngo_payment_checkout_cta_local_block_1_report.md`

Updated:

- `application/views/frontend/youngo/course_page.php`

## D. CTA Helper Behavior

Added `youngo_checkout_cta_decision($course, $user_id = 0, $options = array())`.

The helper returns structured decisions:

- `show_cta`
- `reason_code`
- `state`
- `label`
- `label_text`
- `target_url`
- `disabled_message`
- `login_url` only for login-safe guest state
- `amount` and `currency` only for allowed local CTA decisions

Fail-closed rules:

- Default tracked config returns `show_cta = false` with `reason_code = checkout_cta_disabled`.
- Requires `checkout_cta_enabled = true`.
- Requires `checkout_routes_enabled = true`.
- Requires `checkout_local_testing_enabled = true` for this phase.
- Requires `mode = sandbox`.
- Requires `currency = EGP`.
- Requires `network_enabled = false` in this phase.
- Requires active YounGo-managed purchase-compatible course mode: `purchase_only` or `subscription_and_purchase`.
- Rejects `subscription_only`.
- Rejects free courses.
- Rejects invalid course IDs.
- Rejects non-positive checkout amount.
- Rejects already-access state using the passed/reused YounGo entitlement access decision.
- Guests get `login_required` state and a login URL, not a legacy cart/payment URL.
- Logged-in learner eligible state returns only `youngo/checkout/start/{course_id}`.

The helper accepts an injected config reader and access state for diagnostics and for course-detail reuse. This avoids duplicating entitlement access reads where the course detail page has already calculated access state.

## E. Course Detail Integration Behavior

Only `application/views/frontend/youngo/course_page.php` was wired.

Behavior:

- Loads the new CTA helper if present.
- Passes the existing `$youngo_access_state` into the helper.
- Keeps the existing `Start Now` behavior for active access.
- Keeps existing managed-access disabled fallback when CTA flags are off or course state is not allowed.
- Shows the local checkout CTA only when the helper returns `show_cta = true`.
- Uses `data-youngo-checkout-cta="local-course-detail"` as the course-detail-only marker.
- Guest state can render a safe login link when local CTA flags are enabled, without using legacy cart/payment routes.

No homepage, course listing, wishlist, My Courses, My Access, shopping cart, controller route, config, or model CTA behavior was changed.

## F. Default-Hidden Safety

Tracked `application/config/youngo_paymob.php` was not changed and still defaults to:

```text
enabled = false
network_enabled = false
checkout_routes_enabled = false
checkout_local_testing_enabled = false
checkout_cta_enabled = false
mode = sandbox
currency = EGP
```

The diagnostic confirmed the default config keeps CTA hidden.

## G. Local Flag QA Result

Static/helper diagnostic:

```text
php scripts/phase_2/youngo_payment_checkout_cta_local_block_1_diagnostic.php
PASS
```

Simulated local flags:

- `checkout_cta_enabled = true`
- `checkout_routes_enabled = true`
- `checkout_local_testing_enabled = true`
- `network_enabled = false`
- `mode = sandbox`
- `currency = EGP`

Results:

- Eligible course 9 decision returned `show_cta = true`.
- Target URL was exactly `/youngo/checkout/start/9`.
- Guest returned login-safe state.
- Already-access returned hidden state.
- Subscription-only returned hidden state.
- Free course returned hidden state.
- Invalid course returned hidden state.
- No legacy cart/payment URLs were returned.

Optional browser click-through was skipped because this phase explicitly says not to modify the DB, and clicking the CTA would create a local checkout order.

## H. CTA Surface Safety

Diagnostic confirmed:

- Course detail is the only YounGo frontend view with the new CTA marker.
- Course listing has no `youngo/checkout/start` link.
- Wishlist page has no `youngo/checkout/start` link.
- Wishlist AJAX partial has no `youngo/checkout/start` link.
- Homepage featured courses has no `youngo/checkout/start` link.
- Course detail keeps the existing managed-access disabled fallback.
- CTA phase files contain no Paymob network call patterns.
- CTA helper and course detail contain no DB write patterns.

Existing route skeleton diagnostic also passed:

```text
php scripts/phase_2/youngo_payment_checkout_route_skeleton_1_diagnostic.php
PASS
```

## I. DB Cleanup

No browser click-through was performed and no checkout order was created in this phase.

Protected counts were read before/after the CTA diagnostic and remained unchanged:

```text
youngo_checkout_orders       0 -> 0
youngo_payment_transactions  0 -> 0
youngo_course_access         0 -> 0
payment                      0 -> 0
enrol                        1 -> 1
```

No ignored local config was created.

## J. What Was Not Changed

- No deployment.
- No push.
- No DB modification.
- No SQL execution.
- No credentials.
- No printed secrets.
- No Root Admin modification.
- No real payment enablement by default.
- No public checkout CTA exposure by default.
- No Paymob network calls.
- No real Paymob intentions.
- No legacy payment/enrol writes.
- No legacy Academy gateway/cart/payment routes returned by the CTA helper.
- No homepage/listing/wishlist/My Courses/My Access CTA exposure.

## K. Remaining Risks/Blockers

- Authenticated browser click-through of the visible local course-detail CTA remains pending because this phase disallowed DB writes.
- Course detail now has the first gated CTA integration, but listing, wishlist, and homepage remain intentionally unwired.
- Production CTA exposure still needs a separate production-approved flag and QA phase.
- Paymob network execution remains intentionally disabled and unimplemented.
- Subscription checkout and renewal CTA behavior remain deferred.

## L. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.CHECKOUT.CTA.LOCAL.UI.QA.1 - Browser QA for Course Detail Local CTA With Controlled DB Cleanup
```

Scope:

- Allow controlled local DB writes with backup.
- Use ignored local config to enable CTA/routes/local testing.
- Login as QA learner.
- Verify course detail CTA appears only for course 9 eligible state.
- Click CTA, confirm local order page works, then cleanup order rows.
- Reconfirm homepage/listing/wishlist have no checkout CTA.

## M. Git Status

Validation commands run:

```text
php -l application/helpers/youngo_checkout_cta_helper.php
php -l application/views/frontend/youngo/course_page.php
php -l scripts/phase_2/youngo_payment_checkout_cta_local_block_1_diagnostic.php
php scripts/phase_2/youngo_payment_checkout_cta_local_block_1_diagnostic.php
php scripts/phase_2/youngo_payment_checkout_route_skeleton_1_diagnostic.php
git diff --check
git status --short
```

Result:

- PHP lint passed for all changed PHP files.
- CTA local block diagnostic passed.
- Checkout route skeleton diagnostic passed.
- `git diff --check` passed with line-ending warnings only.
- `php scripts/phase_2/youngo_payment_checkout_local_block_1_runtime_test.php` was not run because it creates/deletes a local checkout order, and this phase explicitly disallowed DB modification.

Final git status:

```text
 M application/views/frontend/youngo/course_page.php
?? application/helpers/youngo_checkout_cta_helper.php
?? docs/qa/youngo_payment_checkout_cta_local_block_1_report.md
?? scripts/phase_2/youngo_payment_checkout_cta_local_block_1_diagnostic.php
```
