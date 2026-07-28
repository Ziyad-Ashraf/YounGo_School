# PAYMENT.CHECKOUT.CTA.PLAN.1 - Checkout CTA Exposure Criteria and Preflight Plan

Date: 2026-07-22

Scope: planning only. No deployment, push, DB changes, SQL execution, checkout CTA exposure, payment enablement, credentials, Paymob network calls, Root Admin changes, or source implementation beyond this report.

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree status: clean
- Latest commit at start: `b59eaee QA local YounGo checkout smoke flow`

Recent commit context:

```text
b59eaee QA local YounGo checkout smoke flow
bcb01b7 Fix YounGo checkout HTTP DB access
92fd18e QA authenticated YounGo checkout start blocker
cf98079 QA disabled YounGo checkout route safety
c26a933 Add controlled local YounGo checkout flow
a943b64 Add disabled YounGo checkout route skeleton
5ea5f16 Plan YounGo checkout route boundaries
96790ac Issue YounGo course access from verified payments
a393278 Add YounGo payment order status transitions
b1d6977 Add YounGo fixture payment transaction recording
```

## B. Files Read/Inspected

Reports read:

- `docs/qa/youngo_payment_checkout_local_ui_qa_3_report.md`
- `docs/qa/youngo_payment_checkout_local_start_fix_1_report.md`
- `docs/qa/youngo_payment_checkout_local_block_1_report.md`
- `docs/qa/youngo_payment_entitlement_block_1_report.md`
- `docs/qa/youngo_payment_config_2_architecture_schema_design.md`

Source inspected:

- `application/views/frontend/youngo/`
- `application/views/frontend/youngo/course_page.php`
- `application/views/frontend/youngo/course_listing/course_card.php`
- `application/views/frontend/youngo/my_wishlist.php`
- `application/views/frontend/youngo/wishlist_items.php`
- `application/views/frontend/youngo/home_sections/featured_courses.php`
- `application/views/frontend/youngo/my_courses.php`
- `application/views/frontend/youngo/my_access.php`
- `application/views/frontend/youngo/shopping_cart_inner_view.php`
- `application/controllers/Home.php`
- `application/controllers/Youngo_checkout.php`
- `application/config/youngo_paymob.php`
- `application/libraries/Youngo_paymob_config.php`

Existing reusable diagnostics identified:

- `scripts/phase_2/youngo_phase_2s_route_cta_boundary_diagnostic.php`
- `scripts/phase_2/youngo_demo_payment_cta_boundary_diagnostic.php`
- `scripts/phase_2/youngo_payment_checkout_route_skeleton_1_diagnostic.php`
- `scripts/phase_2/youngo_payment_checkout_local_block_1_runtime_test.php`
- `scripts/phase_2/youngo_payment_schema_1_diagnostic.php`

## C. Current CTA Locations Summary

Potential CTA surfaces:

| Surface | Current behavior | Future CTA risk |
|---|---|---|
| Homepage hero/final CTA | CMS/content links to general browsing/contact routes. No checkout start link. | Low unless CMS content is allowed to point directly to checkout. |
| Homepage featured courses | Course cards link to course detail or course-list CTA, not checkout. | Medium if featured cards later add direct buy buttons. |
| Course listing cards | Accessed through `course_listing/course_card.php`. YounGo-managed no-access courses show disabled managed-access/subscription-not-ready state. | High. This is a likely future CTA placement. |
| Course detail action card | Accessed through `course_page.php`. Existing primary action is Start Now for access, disabled managed-access message for YounGo-managed no-access courses, legacy free enrol for non-managed free courses, or Contact fallback. | Highest. This should be the first controlled CTA candidate. |
| Wishlist page | `my_wishlist.php` shows Course details plus Start Now, disabled managed-access message, legacy free enrol for non-managed free courses, or Contact fallback. | Medium. Should only receive checkout CTA after course detail/listing pass QA. |
| Wishlist AJAX partial | `wishlist_items.php` mirrors wishlist item actions and must stay consistent with `my_wishlist.php`. | Medium. Easy to miss during partial reloads. |
| My Courses | `my_courses.php` shows active courses, Start/Continue, Course details, My Access, and Browse courses. No purchase action. | Low. Do not add purchase CTA here until renewal/rebuy UX is designed. |
| My Access | `my_access.php` is visibility-only and explicitly says checkout/renewal actions are unavailable. | Low now, high later for subscription renewal; should remain out of scope for course-purchase CTA. |
| Legacy cart | `shopping_cart_inner_view.php` still has legacy cart checkout controls, but `Home.php` removes/blocks YounGo-managed courses from legacy cart/payment routes. | High regression risk if YounGo-managed courses are allowed back into legacy cart. |

Current public QA result from QA.3:

- No public `youngo/checkout/start` links on `/`, `/home/courses`, `/home/course/robotics-and-ai-explorers/9`, or `/home/my_wishlist`.
- No payment button or gateway selector on local checkout order page.
- Paymob network remains disabled.

## D. CTA Exposure Criteria

A YounGo checkout CTA must not render unless all of these are true:

1. Config and environment gates:
   - `checkout_cta_enabled = true`.
   - `checkout_routes_enabled = true`.
   - For local-only CTA QA: `checkout_local_testing_enabled = true`.
   - For future production CTA: a separate explicitly approved production/live-safe flag must exist and be true; do not reuse `checkout_local_testing_enabled` for public production exposure.
   - `currency = EGP`.
   - `mode = sandbox` for local/sandbox phases.
   - `enabled` and `network_enabled` must match the phase. For current local disabled-Paymob UI, payment/network remain false. For future sandbox Paymob CTA, network may be true only after an explicit Paymob sandbox phase.
   - `checkout_cta_enabled` must stay false in tracked defaults.

2. Course gates:
   - Course exists.
   - Course is active.
   - Course is YounGo-managed: `youngo_access_mode` in `subscription_and_purchase` or `purchase_only` for individual course purchase CTA.
   - Course is purchase-compatible.
   - Course is not `subscription_only`.
   - Course is not a legacy/free-course direct-enrol case.
   - Course has a positive EGP checkout amount.
   - Course price/discount rules resolve to the same amount that `Youngo_checkout::checkout_amount_for_course()` and `Youngo_checkout_model` accept.

3. Learner gates:
   - Guest users see login-gated CTA copy or a login link that returns to checkout start later.
   - Logged-in learner must be an active student account.
   - Root Admin, admins, instructors, and inactive users must not be treated as learner purchasers.
   - Learner must not already have active legacy enrol, course-purchase, subscription, manual-grant, admin, or instructor access for that course.
   - Expired/revoked/locked access must show the planned locked/renewal state; do not silently create a new purchase CTA until renewal rules are approved.

4. Payment and route gates:
   - CTA target must be `youngo/checkout/start/{course_id}` only.
   - CTA must never target `home/handle_buy_now`, `home/handle_cart_items`, `home/course_payment`, `payment`, `home/shopping_cart`, or inherited Academy gateway routes for YounGo-managed courses.
   - CTA must not reveal gateway selection.
   - CTA must not include Paymob keys, IDs, HMAC values, or any config diagnostic details.
   - CTA must not call Paymob from the view.

5. Source of truth:
   - Reuse `Youngo_paymob_config` for flags.
   - Reuse the existing YounGo entitlement/access helper/model for already-access decisions.
   - Reuse `Youngo_checkout`/`Youngo_checkout_model` for order creation.
   - Do not duplicate payment/order/entitlement logic inside views.

## E. UI States

Guest:

- Show course detail/listing CTA as sign-in required or "Sign in to checkout" only when CTA gates pass except authentication.
- Target should be login-gated safely through existing login behavior or a future explicit return-url mechanism.
- Do not create an order before authentication.

Logged-in learner:

- If all CTA gates pass, show "Continue to checkout" or equivalent.
- Target `youngo/checkout/start/{course_id}`.
- Local testing page must clearly indicate local/sandbox-only status.

Already has access:

- Show `Start Now` or `Continue`.
- Do not show checkout CTA.
- Do not create duplicate checkout orders.

Purchase available:

- Course modes: `purchase_only` and `subscription_and_purchase`.
- Show checkout CTA only after config, learner, amount, currency, and no-access gates pass.
- Display amount in EGP consistently with the order snapshot.

Subscription-only:

- Keep disabled subscription checkout-not-ready messaging for course purchase CTA scope.
- Do not route to course checkout.
- Subscription purchase/renewal CTA requires a separate subscription checkout phase.

Free course:

- For non-YounGo-managed legacy free courses, existing legacy free-enrol behavior may remain.
- For YounGo-managed courses, do not use legacy free enrol as a shortcut.
- Future zero-total checkout/coupon access must still go through formal checkout/payment/access records.

Payment disabled:

- Show disabled local/sandbox state only on local checkout route/page.
- Do not show a public "Pay" action.
- Course surfaces should remain disabled/not-ready unless local CTA QA is explicitly enabled.

Local/sandbox-only mode:

- CTA may be visible only under ignored local config and only to authenticated QA learner during local QA.
- CTA copy should avoid production language such as "Pay now".
- Order page must keep "Paymob redirect disabled" until the Paymob sandbox network phase explicitly enables it.

## F. Preflight Diagnostics Required Before CTA Implementation

Required before any source implementation that adds checkout CTA rendering:

1. Static/source preflight:
   - `git status --short` must be clean at phase start.
   - `rg "youngo/checkout/start" application/views/frontend/youngo` must show no existing public links before implementation.
   - `php scripts/phase_2/youngo_phase_2s_route_cta_boundary_diagnostic.php`
   - `php scripts/phase_2/youngo_demo_payment_cta_boundary_diagnostic.php`
   - `php scripts/phase_2/youngo_payment_checkout_route_skeleton_1_diagnostic.php`

2. Config preflight:
   - Tracked `application/config/youngo_paymob.php` defaults remain false for:
     - `enabled`
     - `network_enabled`
     - `checkout_routes_enabled`
     - `checkout_local_testing_enabled`
     - `checkout_cta_enabled`
   - `application/config/youngo_paymob.local.php` is ignored and absent before final status.
   - No real secrets in tracked config or reports.

3. Runtime preflight:
   - `php scripts/phase_2/youngo_payment_checkout_local_block_1_runtime_test.php`
   - `php scripts/phase_2/youngo_payment_schema_1_diagnostic.php`
   - Protected counts remain baseline after diagnostics:
     - `youngo_checkout_orders`
     - `youngo_payment_transactions`
     - `youngo_course_access`
     - `payment`
     - `enrol`

4. Browser preflight after implementation but before commit:
   - With default config: no CTA appears publicly and checkout routes remain disabled.
   - With ignored local flags: CTA appears only in explicitly approved local QA context.
   - Confirm no CTA on disallowed states: subscription-only, free YounGo-managed, already-access, invalid/inactive course, guest without login gate, Root/Admin session.
   - Confirm no legacy cart/payment route is used for YounGo-managed courses.
   - Confirm no Paymob network call unless an explicit later sandbox phase approves it.

## G. Rollback Plan If CTA Appears Publicly

Immediate no-code rollback:

1. Remove `application/config/youngo_paymob.local.php` if present.
2. Confirm tracked config has:
   - `checkout_cta_enabled = false`
   - `checkout_routes_enabled = false`
   - `checkout_local_testing_enabled = false`
   - `enabled = false`
   - `network_enabled = false`
3. Clear application/browser cache if needed.
4. Re-test `/`, `/home/courses`, course detail, and wishlist for absence of `youngo/checkout/start`.

Code rollback if a committed view change exposed CTAs:

1. Revert only the CTA view/helper/controller changes from the CTA phase.
2. Restore disabled managed-access messaging in:
   - `application/views/frontend/youngo/course_page.php`
   - `application/views/frontend/youngo/course_listing/course_card.php`
   - `application/views/frontend/youngo/my_wishlist.php`
   - `application/views/frontend/youngo/wishlist_items.php`
   - any homepage featured-course CTA partial touched by the phase
3. Run CTA boundary diagnostics and browser smoke again.

DB cleanup if an accidental local order was created:

1. Do not delete broad data.
2. Identify exact QA order references created by the accidental CTA.
3. Delete only matching diagnostic checkout/payment rows if local-only and safe, after backup.
4. Confirm no `youngo_course_access`, legacy `payment`, or legacy `enrol` rows were created.

Production/live rollback:

- Not applicable in this phase because no deploy is allowed.
- If this ever occurs on a server, disable flags/config first, remove public links, verify no Paymob live calls, then restore from the last approved deployment package if needed.

## H. Recommended CTA Implementation Shape

Future implementation should add a single reusable presenter/helper decision, not duplicate conditionals in every view.

Recommended component:

```text
Youngo_checkout_cta_model/helper
```

It should return a safe structured result:

```text
show_cta: bool
state: disabled|login_required|already_has_access|purchase_available|subscription_only|free_course|payment_disabled|local_only
label: safe phrase key
url: site_url('youngo/checkout/start/{course_id}') only when allowed
reason_code: diagnostic-safe reason
```

Views should only render the result. They should not decide payment readiness, create orders, inspect secrets, or call Paymob.

First implementation target:

1. Course detail primary action only.
2. Then course listing cards.
3. Then wishlist page and AJAX partial.
4. Homepage featured courses last, and only if CMS-managed URLs cannot accidentally point directly to checkout before gates pass.

My Courses/My Access should remain visibility/access pages until subscription renewal and repurchase UX are separately planned.

## I. Risks/Blockers

- Public CTA exposure is still blocked until a CTA implementation phase is explicitly approved.
- Production/live checkout requires a separate production-safe flag; current local testing flag must not become the production gate.
- Paymob sandbox network execution is still deferred.
- Subscription checkout and renewal are out of scope for this course-purchase CTA plan.
- Already-access, expired/revoked renewal, coupon/zero-total checkout, and mobile/API behavior need separate QA matrices before broad rollout.
- Homepage CMS-managed links could become a bypass if admins can set arbitrary checkout URLs; a later implementation should sanitize or gate CMS CTA URLs.

## J. Recommended Next Phase

Recommended next phase:

```text
PAYMENT.CHECKOUT.CTA.SKELETON.1 - Disabled Checkout CTA Decision Helper
```

Scope:

- Add a reusable CTA decision helper/model with defaults that return disabled/no-CTA.
- Add static diagnostics for all CTA surfaces.
- Do not render public checkout links yet.
- Do not enable Paymob network calls.
- Do not modify DB.

## K. Git Status

Validation commands run:

```text
git diff --check
git status --short
```

Result:

- `git diff --check` passed.
- No DB, config, source, Root Admin, Paymob, or CTA files were modified.

Final git status:

```text
?? docs/qa/youngo_payment_checkout_cta_plan_1_report.md
```
