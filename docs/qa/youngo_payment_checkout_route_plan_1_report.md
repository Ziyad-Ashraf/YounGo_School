# PAYMENT.CHECKOUT.ROUTE.PLAN.1 - Local YounGo Checkout Route/UI Planning With CTA Boundaries

Date: 2026-07-21

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree status: clean
- Latest commit observed: `96790ac Issue YounGo course access from verified payments`
- Recent payment commits reviewed:
  - `96790ac Issue YounGo course access from verified payments`
  - `a393278 Add YounGo payment order status transitions`
  - `b1d6977 Add YounGo fixture payment transaction recording`
  - `b96a7b9 Add disabled YounGo Paymob webhook route skeleton`
  - `c0d7796 Add YounGo Paymob webhook verification skeleton`

## B. Files Inspected

Planning/reference reports:

- `docs/qa/youngo_payment_entitlement_block_1_report.md`
- `docs/qa/youngo_payment_order_status_1_report.md`
- `docs/qa/youngo_payment_transaction_block_1_report.md`
- `docs/qa/youngo_payment_config_2_architecture_schema_design.md`
- `docs/qa/youngo_payment_config_file_1_report.md`

Local architecture files:

- `application/controllers/Home.php`
- `application/controllers/Youngo_payment_webhook.php`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_payment_model.php`
- `application/libraries/Youngo_paymob_config.php`
- `application/libraries/Youngo_paymob_adapter.php`
- `application/helpers/youngo_entitlement_helper.php`
- `application/views/frontend/youngo/course_page.php`
- `application/views/frontend/youngo/course_listing/course_card.php`
- `application/views/frontend/youngo/my_courses.php`
- `application/views/frontend/youngo/my_access.php`
- `application/views/frontend/youngo/reload_my_courses.php`
- `application/views/frontend/youngo/wishlist_items.php`
- `application/config/routes.php`

## C. Existing CTA/Access Behavior

YounGo public course CTAs are currently generated primarily in:

- Course detail: `application/views/frontend/youngo/course_page.php`
- Course cards/listing: `application/views/frontend/youngo/course_listing/course_card.php`
- Wishlist surfaces: `application/views/frontend/youngo/wishlist_items.php`

Current YounGo-managed access modes are:

- `subscription_only`
- `subscription_and_purchase`
- `purchase_only`

For these modes, public legacy payment entry points remain suppressed. Course detail and listing views do not expose inherited Academy `Buy Now`, `Add to cart`, or legacy checkout CTAs for learners without access. Instead, managed courses show disabled access messaging such as subscription checkout not available yet or access managed by the school/admin.

`Home.php` also protects direct legacy routes. YounGo-managed courses are removed from legacy cart/payment paths, direct free-enrol is blocked for managed courses, and coupon/free-payment shortcut behavior is not allowed to create access for YounGo-managed paid/subscription courses.

Logged-in learners receive access state through the YounGo entitlement read layer. Guests receive safe guest/login messaging and should not get an order created until a later authenticated checkout phase explicitly permits it.

`My Courses` and `My Access` remain read-only entitlement surfaces. They detect legacy enrolment and active YounGo course access/subscriptions, but they do not expose checkout, payment, renewal, or gateway actions.

## D. Proposed Route Boundaries

Future local-only checkout routes should be YounGo-specific and separate from inherited Academy cart/payment routes:

- `youngo/checkout/start/(:num)` -> `youngo_checkout/start_course/$1`
- `youngo/checkout/order/(:any)` -> `youngo_checkout/order/$1`
- `youngo/checkout/return/(:any)` -> `youngo_checkout/return/$1`
- `youngo/checkout/status/(:any)` -> `youngo_checkout/status/$1`

Existing webhook route remains separate:

- `payment/paymob/webhook` -> `youngo_payment_webhook/paymob`

Recommended route rules:

- Routes must be fail-closed unless a future local checkout testing flag is enabled.
- Payment config must remain disabled by default.
- The public webhook route must remain disabled by default and must not write transactions outside a later explicit local testing phase.
- Guest users should be login-gated before order creation.
- Root Admin and admin/instructor bypass users should not be used as learner checkout purchasers.
- Order lookup routes should use `order_reference`, not numeric IDs, and must verify the current learner owns the order.
- No YounGo checkout route should call `/home/course_payment`, legacy cart, inherited gateway rows, or legacy payment setup.
- Do not add `/en` links. Do not add Arabic checkout aliases until localization route behavior is explicitly planned for checkout.

## E. CTA Exposure Rules

Checkout CTAs may be considered later only when all of these are true:

- The course is YounGo-managed and purchase-compatible: `purchase_only` or `subscription_and_purchase`.
- The course is paid and uses EGP.
- The learner does not already have active YounGo or legacy-compatible access.
- A local checkout testing flag is explicitly enabled.
- Paymob/YounGo config remains sandbox/local-safe.
- The CTA points only to a YounGo checkout start route, never to Academy cart/payment routes.
- The CTA is hidden or disabled when payment config is disabled, network execution is disabled, or checkout testing is off.

Checkout CTAs must not appear for:

- `subscription_only` direct course purchase.
- Free courses where payment is not required.
- Guests except as a login-gated future flow.
- Root Admin/admin preview contexts.
- Production/public traffic before explicit QA approval.

Inherited Academy gateway rows must not be used for YounGo payments.

## F. Checkout Page Behavior Plan

A future local checkout page should be simple and explicit:

- Show course title, learner-safe course summary, amount, currency `EGP`, order reference, and current order status.
- Show a visible local/sandbox testing notice only when local checkout testing is enabled.
- Show payment disabled state when Paymob network execution is disabled.
- Never show API keys, HMAC secrets, client secrets, gateway credentials, raw callback payloads, or full stored payloads.
- Never show inherited gateway selection.
- Never redirect to Paymob until a later approved sandbox network phase.
- Treat return URL as UX only. The return page should show the latest local order status and explain that verified webhook processing is required before access is issued.
- The status page should report only safe order/payment state: `draft`, `pending_gateway`, `awaiting_webhook`, `paid`, `failed`, `cancelled`, or `expired`, plus entitlement issuance state when available.

## G. Implementation File Plan

Likely future implementation files:

- Controller: `application/controllers/Youngo_checkout.php`
- Routes: `application/config/routes.php`
- Views:
  - `application/views/frontend/youngo/checkout_order.php`
  - `application/views/frontend/youngo/checkout_return.php` if a separate return view is needed
  - `application/views/frontend/youngo/checkout_status.php` if status is not embedded in the order page
- Existing model reuse:
  - `application/models/Youngo_checkout_model.php`
  - `application/models/Youngo_payment_model.php`
  - `application/models/Youngo_entitlement_write_model.php`
- Existing library reuse:
  - `application/libraries/Youngo_paymob_config.php`
  - `application/libraries/Youngo_paymob_adapter.php`
  - `application/libraries/Youngo_paymob_webhook.php`
- Optional helper only if duplication emerges:
  - `application/helpers/youngo_checkout_helper.php`
- Diagnostics:
  - `scripts/phase_2/youngo_payment_checkout_route_skeleton_1_diagnostic.php`
  - Later browser/manual QA report after UI is exposed locally
- Config:
  - Extend non-secret tracked config later with explicit false defaults such as `checkout_testing_enabled` and `checkout_public_ctas_enabled`.
  - Keep local secret overrides ignored by Git.

No implementation is added in this planning phase.

## H. QA Matrix

Future checkout route/UI QA should cover:

| Scenario | Expected Result |
| --- | --- |
| Guest starts checkout for purchase-compatible course | Login-gated safely; no order write unless later explicitly designed |
| Logged-in learner starts checkout with local flag off | Fail-closed; no order write; no CTA visible |
| Logged-in learner starts checkout with local flag on | Draft local order can be created; no Paymob call |
| Learner already has access | Checkout blocked; no duplicate order/access |
| Free course | Payment not required; no checkout order |
| `subscription_only` course | Direct purchase checkout blocked; subscription checkout remains deferred |
| `purchase_only` course | Eligible for future local checkout when flags allow |
| `subscription_and_purchase` course | Eligible for future local course purchase when flags allow |
| Invalid course ID | Safe 404/redirect; no DB write |
| Payment config disabled | Checkout page shows disabled payment state; no network action |
| Paymob network disabled | No redirect/intention creation; adapter remains fail-closed |
| Mobile course detail/listing | No overlapping CTA text; disabled state readable |
| Localization links | No `/en` checkout links; no `/ar` checkout aliases until planned |
| Legacy cart/payment routes | Managed courses remain stripped/blocked |
| Webhook route | Remains fail-closed by default; no public DB writes |
| Duplicate order attempt | Existing open order reused or rejected safely based on model rule |
| Paid order return page | Return page is UX only; no access issuance from return |

## I. Risks/Blockers

- A dedicated local checkout flag is still only a design recommendation and is not implemented.
- Subscription purchase flow is not ready; direct course purchase should stay limited to `purchase_only` and `subscription_and_purchase`.
- Paymob sandbox network execution remains intentionally unavailable.
- Public CTA exposure requires separate QA approval because even a local/test CTA can create confusing user behavior if leaked.
- Return URL behavior must stay UX-only; payment/access truth must remain webhook/HMAC plus local status verification.
- Browser QA is still required before any checkout page is surfaced.

## J. Recommended Next Phase

Recommended next phase:

`PAYMENT.CHECKOUT.ROUTE.SKELETON.1 - Add Disabled Local YounGo Checkout Controller/Routes Without CTA Exposure`

Suggested scope:

- Add fail-closed `Youngo_checkout` controller.
- Add local-only checkout routes.
- Add no-public-CTA diagnostics.
- Reuse `Youngo_checkout_model`, `Youngo_paymob_config`, and `Youngo_paymob_adapter`.
- Do not add checkout buttons yet.
- Do not call Paymob.
- Do not issue entitlements from return routes.
- Do not write legacy enrol/payment rows.

## K. Git Status

Git status after report creation is expected to show only this new planning report until validation is complete.
