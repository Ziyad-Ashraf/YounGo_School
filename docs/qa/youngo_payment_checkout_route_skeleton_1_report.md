# PAYMENT.CHECKOUT.ROUTE.SKELETON.1 - Disabled Local YounGo Checkout Routes, Controller, and View Skeleton

Date: 2026-07-21

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree status: clean
- Latest commit at start: `5ea5f16 Plan YounGo checkout route boundaries`

Recent commit context:

```text
5ea5f16 Plan YounGo checkout route boundaries
96790ac Issue YounGo course access from verified payments
a393278 Add YounGo payment order status transitions
b1d6977 Add YounGo fixture payment transaction recording
b96a7b9 Add disabled YounGo Paymob webhook route skeleton
c0d7796 Add YounGo Paymob webhook verification skeleton
4fd5f8c Add disabled YounGo Paymob adapter skeleton
fda6f91 Runtime test YounGo checkout order service
be4e139 Add YounGo checkout order service foundation
685494a Add non-secret YounGo Paymob config foundation
```

## B. Files Inspected

Required reports:

- `docs/qa/youngo_payment_checkout_route_plan_1_report.md`
- `docs/qa/youngo_payment_entitlement_block_1_report.md`
- `docs/qa/youngo_payment_config_file_1_report.md`

Code/config inspected:

- `application/config/routes.php`
- `application/config/youngo_paymob.php`
- `application/config/youngo_paymob.local.example.php`
- `application/controllers/Home.php`
- `application/controllers/Youngo_payment_webhook.php`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_payment_model.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/libraries/Youngo_paymob_adapter.php`
- `application/views/frontend/youngo/`

Existing reusable logic was checked first. The route skeleton reuses the current Paymob config reader and disabled adapter. It does not duplicate checkout order, payment transaction, webhook, or entitlement write logic.

## C. Files Changed

Created:

- `application/controllers/Youngo_checkout.php`
- `application/views/frontend/youngo/checkout_disabled.php`
- `scripts/phase_2/youngo_payment_checkout_route_skeleton_1_diagnostic.php`
- `docs/qa/youngo_payment_checkout_route_skeleton_1_report.md`

Updated:

- `application/config/routes.php`
- `application/config/youngo_paymob.php`
- `application/config/youngo_paymob.local.example.php`
- `application/libraries/Youngo_paymob_config.php`
- `scripts/phase_2/youngo_payment_schema_1_diagnostic.php`

## D. Config Flags Added

Safe non-secret defaults added to tracked config and local example:

- `checkout_routes_enabled = false`
- `checkout_local_testing_enabled = false`
- `checkout_cta_enabled = false`

`Youngo_paymob_config` now normalizes and exposes:

- `is_checkout_routes_enabled()`
- `is_checkout_local_testing_enabled()`
- `is_checkout_cta_enabled()`

The safe diagnostic summary now includes the checkout flags. Payment remains disabled, network execution remains disabled, webhook testing remains disabled, and all credential placeholders remain null.

## E. Routes/Controller Behavior

Routes added:

```text
youngo/checkout/start/(:num)  -> youngo_checkout/start_course/$1
youngo/checkout/order/(:any)  -> youngo_checkout/order/$1
youngo/checkout/return/(:any) -> youngo_checkout/return/$1
youngo/checkout/status/(:any) -> youngo_checkout/status/$1
```

Controller:

```text
application/controllers/Youngo_checkout.php
```

Behavior:

- Reuses `Youngo_paymob_config`.
- Reuses `Youngo_paymob_adapter` only as a disabled dependency boundary.
- Fails closed when checkout routes are disabled.
- Fails closed when local checkout testing is disabled.
- Does not load `Youngo_checkout_model`.
- Does not create orders.
- Does not read or write checkout/payment/entitlement/legacy tables.
- Does not call Paymob.
- Does not redirect to legacy cart/payment routes.
- `status()` returns safe JSON only.
- `start_course()`, `order()`, and `return()` render a disabled frontend page only.

Login-gating is intentionally not implemented yet to avoid surprising redirects while routes are disabled.

## F. Disabled View Behavior

View:

```text
application/views/frontend/youngo/checkout_disabled.php
```

The disabled view shows:

- Checkout is not enabled.
- Local/sandbox-only context.
- No gateway is active.
- Mode and currency only.
- A safe reason code.

The view does not show:

- Payment buttons.
- Gateway selection.
- Paymob credentials.
- HMAC secrets.
- Public checkout CTAs.
- Legacy cart/payment links.

## G. CTA Safety

No course detail, course card, wishlist, cart, or My Access CTA file was changed to expose checkout.

Static diagnostic checks confirmed:

- `course_page.php` does not link to `youngo/checkout/start`.
- `course_card.php` does not link to `youngo/checkout/start`.
- `my_wishlist.php` does not link to `youngo/checkout/start`.
- `wishlist_items.php` does not link to `youngo/checkout/start`.
- Existing YounGo managed-access boundary markers remain present.

Inherited Academy gateway rows remain unused by the YounGo route skeleton.

## H. Diagnostic Result

Created and ran:

```text
php scripts/phase_2/youngo_payment_checkout_route_skeleton_1_diagnostic.php
```

Result:

```text
PASS
```

Key checks passed:

- Controller exists.
- Routes exist.
- Disabled view exists.
- Config flags default false.
- Config reader exposes checkout flag methods.
- Controller fails closed from config flags.
- Controller has no DB write or order creation code.
- Controller has no Paymob network-call code.
- Controller has no entitlement or legacy payment/enrol write usage.
- Status endpoint returns safe JSON.
- No public checkout route is exposed in YounGo CTA views.
- Webhook route remains fail-closed by default.
- Prior webhook route diagnostic still passes.

`scripts/phase_2/youngo_payment_schema_1_diagnostic.php` was updated so its old "no checkout route" guard now accepts this approved disabled skeleton while still rejecting non-skeleton checkout behavior.

## I. What Was Not Changed

- No deployment.
- No push.
- No live/cPanel server access.
- No live DB access.
- No local DB writes.
- No SQL execution.
- No payment enablement.
- No real credentials.
- No printed secrets.
- No Root Admin modification.
- No public checkout CTA exposure.
- No Paymob network requests.
- No real Paymob intentions.
- No checkout/order rows.
- No payment transaction rows.
- No entitlement issuance.
- No legacy `payment` writes.
- No legacy `enrol` writes.
- No legacy gateway DB row changes.

## J. Risks/Blockers

- The route skeleton is intentionally not useful for real checkout until a later local-testing phase enables controlled order creation.
- The local checkout testing flag exists only as disabled config; no owner/local override values should be added until explicitly approved.
- Browser QA of the disabled page is still recommended before any internal CTA is introduced.
- Subscription checkout remains deferred.
- Paymob sandbox network execution remains deferred.

## K. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.CHECKOUT.START.LOCAL.1 - Controlled Local Checkout Start Without Public CTA Exposure
```

Suggested scope:

- Keep public CTAs hidden.
- Use a local-only route/testing flag.
- Login-gate learner checkout safely.
- Reuse `Youngo_checkout_model::create_draft_order()`.
- Create local draft orders only under controlled diagnostics with cleanup.
- Do not call Paymob.
- Do not issue entitlements from checkout routes.
- Do not write legacy payment/enrol rows.

## L. Git Status

Git status before final validation:

```text
 M application/config/routes.php
 M application/config/youngo_paymob.local.example.php
 M application/config/youngo_paymob.php
 M application/libraries/Youngo_paymob_config.php
 M scripts/phase_2/youngo_payment_schema_1_diagnostic.php
?? application/controllers/Youngo_checkout.php
?? application/views/frontend/youngo/checkout_disabled.php
?? docs/qa/youngo_payment_checkout_route_skeleton_1_report.md
?? scripts/phase_2/youngo_payment_checkout_route_skeleton_1_diagnostic.php
```
