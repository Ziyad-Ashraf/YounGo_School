# PAYMENT.CHECKOUT.LOCAL.BLOCK.1 - Controlled Local Checkout Start, Order Page, and Status Flow

Date: 2026-07-21

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree status: clean
- Latest commit at start: `a943b64 Add disabled YounGo checkout route skeleton`

Recent commit context:

```text
a943b64 Add disabled YounGo checkout route skeleton
5ea5f16 Plan YounGo checkout route boundaries
96790ac Issue YounGo course access from verified payments
a393278 Add YounGo payment order status transitions
b1d6977 Add YounGo fixture payment transaction recording
b96a7b9 Add disabled YounGo Paymob webhook route skeleton
c0d7796 Add YounGo Paymob webhook verification skeleton
4fd5f8c Add disabled YounGo Paymob adapter skeleton
fda6f91 Runtime test YounGo checkout order service
be4e139 Add YounGo checkout order service foundation
```

## B. Backup Created

Fresh local DB backup created before diagnostic DB writes:

```text
Path: D:\Work\YounGo\backups\youngo_school_before_payment_checkout_local_block_1_2026_07_21_113156.sql
Size: 929737 bytes
SHA256: 29b66b42dc0b53dc1fa8baf9d110a7c3dd929ed6b9894450781421b69d81a740
Tables dumped: 65
```

The backup was created from the local database configuration only. No live/cPanel system was touched and no credentials were printed.

## C. Files Inspected

Required phase reports:

- `docs/qa/youngo_payment_checkout_route_skeleton_1_report.md`
- `docs/qa/youngo_payment_checkout_route_plan_1_report.md`
- `docs/qa/youngo_payment_entitlement_block_1_report.md`
- `docs/qa/youngo_payment_order_status_1_report.md`
- `docs/qa/youngo_payment_order_2_runtime_test_report.md`

Code inspected:

- `application/controllers/Youngo_checkout.php`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_payment_model.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/libraries/Youngo_paymob_adapter.php`
- `application/views/frontend/youngo/checkout_disabled.php`
- `application/config/routes.php`

## D. Files Changed

Updated:

- `application/config/routes.php`
- `application/controllers/Youngo_checkout.php`
- `application/models/Youngo_checkout_model.php`
- `application/views/frontend/youngo/checkout_disabled.php`
- `scripts/phase_2/youngo_payment_checkout_route_skeleton_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_schema_1_diagnostic.php`

Created:

- `application/views/frontend/youngo/checkout_order.php`
- `scripts/phase_2/youngo_payment_checkout_local_block_1_runtime_test.php`
- `docs/qa/youngo_payment_checkout_local_block_1_report.md`

## E. Reused Logic Summary

Reused existing YounGo payment foundation:

- `Youngo_paymob_config` remains the checkout flag and safety configuration reader.
- `Youngo_paymob_adapter` remains disabled and is used only for safe disabled-state reporting.
- `Youngo_checkout_model` remains the only checkout order creation/lookup service.
- `Youngo_payment_model::get_payment_status_summary()` is reused for order/status display.
- Existing public CTA boundary files were not modified.

Added one small reuse wrapper:

- `Youngo_checkout_model::create_or_reuse_draft_order()` validates through existing `can_start_checkout()` and reuses an existing open order instead of creating a duplicate.

`Youngo_checkout_model::can_start_checkout()` now also rejects inactive courses.

## F. Checkout Start Behavior

Route:

```text
youngo/checkout/start/(:num) -> youngo_checkout/start/$1
```

Behavior:

- Requires `checkout_routes_enabled = true`.
- Requires `checkout_local_testing_enabled = true`.
- Requires sandbox mode and `EGP`.
- Requires Paymob `network_enabled = false` in this phase.
- Defaults remain disabled in tracked config.
- Guest users are login-gated by existing `login` route behavior only after local flags are enabled.
- Root Admin, admins, and instructors are blocked from learner checkout purchaser use.
- Invalid, missing, inactive, free, and non-purchase-compatible courses are rejected safely.
- Purchase-compatible courses use the existing checkout model to create or reuse one open local order.
- Successful local start redirects to the YounGo order page by `order_reference`.
- No Paymob network request or real intention is created.

## G. Order/Status/Return Behavior

Order route:

```text
youngo/checkout/order/(:any)
```

Return route:

```text
youngo/checkout/return/(:any)
```

Status route:

```text
youngo/checkout/status/(:any)
```

Behavior:

- Disabled flags render or return safe disabled state.
- Enabled local testing requires signed-in learner ownership of the order.
- Order lookup uses `order_reference`.
- Order page shows course title, order reference, amount, currency `EGP`, order status, and entitlement-issued status.
- Status endpoint returns safe JSON with order/payment summary.
- Return route is UX-only and does not mutate order state.
- No entitlement issuance is called from start/order/status/return.
- No legacy cart/payment route is used.

## H. DB Write/Cleanup Summary

Runtime diagnostic:

```text
php scripts/phase_2/youngo_payment_checkout_local_block_1_runtime_test.php
PASS
```

Fixture:

```text
user_id: 8
user_email: qa.learner@youngo.local
course_id: 9
course_title: Robotics and AI Explorers
course_mode: subscription_and_purchase
amount: 1000.00
currency: EGP
```

Controlled local writes:

- Created 1 diagnostic checkout order through `create_or_reuse_draft_order()`.
- Verified a second start attempt reused the open order instead of creating a duplicate.
- Deleted the diagnostic order by exact ID/reference.
- Reset `youngo_checkout_orders` auto-increment to `1` because the table was empty before the test.

Protected counts before and after cleanup:

```text
youngo_checkout_orders       0 -> 0
youngo_payment_transactions  0 -> 0
youngo_course_access         0 -> 0
youngo_user_subscriptions    0 -> 0
youngo_manual_grants         0 -> 0
youngo_coupon_usages         0 -> 0
payment                     0 -> 0
enrol                       1 -> 1
watch_histories             0 -> 0
watched_duration            0 -> 0
```

Invalid course and subscription-only course checks were rejected without writes. Free-course rejection was skipped because no active free course fixture was found locally.

## I. CTA Safety

No public CTA files were changed.

Diagnostics confirmed no `youngo/checkout/start` links exist in:

- `application/views/frontend/youngo/course_page.php`
- `application/views/frontend/youngo/course_listing/course_card.php`
- `application/views/frontend/youngo/my_wishlist.php`
- `application/views/frontend/youngo/wishlist_items.php`

Existing YounGo managed-access CTA suppression remains the active public behavior.

## J. What Was Not Changed

- No deployment.
- No push.
- No live/cPanel access.
- No live DB access.
- No real payment enablement by default.
- No real credentials.
- No printed secrets.
- No Root Admin modification.
- No public checkout CTA exposure.
- No Paymob network requests.
- No real Paymob intentions.
- No public Paymob redirects.
- No entitlement issuance from UI routes.
- No legacy `payment` writes.
- No legacy `enrol` writes.
- No legacy gateway DB row changes.
- No persistent diagnostic rows left behind.

## K. Remaining Risks/Blockers

- Browser QA of the disabled and local order views is still needed before any internal CTA is introduced.
- Route-level local testing still requires an ignored local override or controlled environment setup to enable flags outside diagnostics.
- Paymob sandbox network execution remains deferred.
- Subscription purchase checkout remains deferred.
- Free-course rejection was not runtime-tested because no active free-course fixture existed locally.
- Already-access checkout rejection was not runtime-tested with an access fixture in this phase to avoid creating entitlement rows outside the checkout test scope.

## L. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.CHECKOUT.LOCAL.UI.QA.1 - Browser QA for Disabled and Local Checkout Routes Without Public CTA Exposure
```

Suggested scope:

- Use ignored local-only flags, not tracked config changes.
- Browser-test disabled route behavior.
- Browser-test authenticated learner local checkout start/order/status.
- Keep public CTAs hidden.
- Keep Paymob network disabled.
- Do not issue entitlements from UI routes.
- Restore local DB baseline after testing.

## M. Git Status

Git status before final validation:

```text
 M application/config/routes.php
 M application/controllers/Youngo_checkout.php
 M application/models/Youngo_checkout_model.php
 M application/views/frontend/youngo/checkout_disabled.php
 M scripts/phase_2/youngo_payment_checkout_route_skeleton_1_diagnostic.php
 M scripts/phase_2/youngo_payment_schema_1_diagnostic.php
?? application/views/frontend/youngo/checkout_order.php
?? docs/qa/youngo_payment_checkout_local_block_1_report.md
?? scripts/phase_2/youngo_payment_checkout_local_block_1_runtime_test.php
```
