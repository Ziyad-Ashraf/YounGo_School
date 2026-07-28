# PAYMENT.CHECKOUT.COVERAGE.AUDIT.1

Audit date: 2026-07-27

Scope: current YounGo checkout/payment implementation after recent coupon, zero-amount coupon, and manual Instapay work. This was audit-first. The only added code artifact is a read-only diagnostic script.

## A. Current Branch/Status

Requested starting directory `D:\Work\YounGo\school_prod_2` is not a Git repository.

Actual served/Git repository inspected: `D:\Work\YounGo\school`.

Current branch:

```text
analysis/cms-audit
```

The worktree was already dirty before this audit. It is not clean. This audit added:

```text
?? scripts/phase_2/youngo_payment_checkout_coverage_audit_1_diagnostic.php
?? docs/qa/youngo_payment_checkout_coverage_audit_1_report.md
```

Recent log head:

```text
f7233d4 Add manual Instapay approval access issuance
8680f5a Add zero-amount coupon course completion
43e8047 Plan zero-amount coupon checkout access
8166264 Add manual Instapay admin review inbox
07945ec Add manual Instapay checkout submission upload
c42cb6f Add manual Instapay payment target config
a91669e Add manual Instapay submission schema foundation
dca943a Add coupon apply clear checkout UI
b25c090 Wire coupon evaluator into checkout snapshot writes
34c95ab Add read-only YounGo coupon evaluator
```

## B. Files Inspected

- `application/config/routes.php`
- `application/controllers/Youngo_checkout.php`
- `application/controllers/Youngo_instapay_payments.php`
- `application/controllers/Youngo_payment_webhook.php`
- `application/controllers/Youngo_payment_return.php`
- `application/controllers/Admin.php`
- `application/controllers/Home.php`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_coupon_evaluator_model.php`
- `application/models/Youngo_entitlement_write_model.php`
- `application/models/Youngo_entitlement_model.php`
- `application/models/Youngo_instapay_payment_model.php`
- `application/models/Youngo_payment_model.php`
- `application/models/Youngo_payment_config_model.php`
- `application/models/Youngo_subscription_model.php`
- `application/helpers/youngo_checkout_cta_helper.php`
- `application/helpers/youngo_entitlement_helper.php`
- `application/views/frontend/youngo/checkout_order.php`
- `application/views/frontend/youngo/subscriptions.php`
- `application/views/frontend/youngo/course_page.php`
- `application/views/frontend/youngo/course_listing/course_card.php`
- `application/views/frontend/youngo/my_wishlist.php`
- `application/views/frontend/youngo/wishlist_items.php`
- `application/views/frontend/youngo/shopping_cart.php`
- `application/views/frontend/youngo/shopping_cart_inner_view.php`
- `application/views/frontend/youngo/payment_return_disabled.php`
- `application/views/backend/admin/youngo_instapay_payments.php`
- `application/views/backend/admin/youngo_instapay_payment_view.php`
- `application/views/backend/admin/youngo_course_entitlement_summary.php`
- `application/views/backend/admin/youngo_user_entitlement_summary.php`
- `application/views/backend/admin/coupons.php`
- `application/views/backend/admin/coupon_add.php`
- `application/views/backend/admin/coupon_edit.php`
- `database/phase_2/youngo_phase_2e_schema_up.sql`
- `database/phase_2/youngo_phase_2m_entitlement_write_schema_up.sql`
- `database/phase_2/youngo_phase_2n_coupon_lifecycle_schema_up.sql`
- `scripts/phase_2/payment_coupon_checkout_snapshot_schema_1_up.sql`
- `scripts/phase_2/payment_manual_instapay_schema_1_up.sql`
- `scripts/phase_2/payment_config_2_youngo_payment_schema_proposed.sql`
- relevant existing diagnostics and reports under `scripts/phase_2/` and `docs/qa/`

## C. Current Checkout/Payment Route Map

Frontend checkout routes:

| Route | Controller action | Current purpose |
|---|---|---|
| `youngo/checkout/start/(:num)` | `Youngo_checkout::start()` -> `start_course()` | Direct course checkout order start |
| `youngo/checkout/subscription/start/(:num)` | `Youngo_checkout::start_subscription()` | Subscription plan checkout order start |
| `youngo/checkout/order/(:any)` | `Youngo_checkout::order()` | Checkout review page |
| `youngo/checkout/return/(:any)` | `Youngo_checkout::return()` | UX-only order return view |
| `youngo/checkout/status/(:any)` | `Youngo_checkout::status()` | JSON status summary |
| `youngo/checkout/coupon/apply/(:any)` | `Youngo_checkout::apply_coupon()` | POST coupon snapshot apply |
| `youngo/checkout/coupon/clear/(:any)` | `Youngo_checkout::clear_coupon()` | POST coupon snapshot clear |
| `youngo/checkout/zero-coupon/complete/(:any)` | `Youngo_checkout::complete_zero_amount_coupon()` | POST complete full-discount order |
| `youngo/checkout/instapay/submit/(:any)` | `Youngo_checkout::submit_instapay()` | POST manual Instapay evidence upload |

Admin/payment routes:

| Route | Controller action | Current purpose |
|---|---|---|
| `admin/youngo/payment-settings` | `Youngo_payment_settings::index()` | YounGo payment configuration |
| `admin/youngo/payment-settings/test` | `Youngo_payment_settings::test()` | Payment settings test center |
| `admin/youngo/instapay-payments` | `Youngo_instapay_payments::index()` | Manual Instapay review inbox |
| `admin/youngo/instapay-payments/(:num)` | `Youngo_instapay_payments::view()` | Manual Instapay submission detail |
| `admin/youngo/instapay-payments/(:num)/approve` | `Youngo_instapay_payments::approve()` | Approve and issue access |
| `admin/youngo/instapay-payments/(:num)/reject` | `Youngo_instapay_payments::reject()` | Reject submission |
| `admin/youngo/instapay-payments/(:num)/evidence` | `Youngo_instapay_payments::evidence(..., preview)` | Evidence preview |
| `admin/youngo/instapay-payments/(:num)/evidence/download` | `Youngo_instapay_payments::evidence(..., download)` | Evidence download |
| `payment/paymob/webhook` | `Youngo_payment_webhook::paymob()` | Disabled/read-only unless testing gate enabled |
| `payment/paymob/return` | `Youngo_payment_return::paymob()` | Disabled no-write return page |

## D. Current Supported Item Types

Live DB column is `order_type`, not `item_type`.

Backend-supported YounGo checkout order types:

- `course_purchase`
- `subscription_purchase`

Coupon evaluator supported normalized item types:

- `course`, including coupon scopes `course`, `course_purchase`, `both`, `all`
- `subscription`, including coupon scopes `subscription`, `subscription_purchase`, `both`, `all`

Live local DB values from the read-only diagnostic:

```json
{
  "checkout_orders_by_order_type_status": [
    {"order_type": "subscription_purchase", "status": "paid", "total": "5"},
    {"order_type": "subscription_purchase", "status": "draft", "total": "2"}
  ]
}
```

There are no live `course_purchase` checkout orders in the current local DB.

## E. Website/Frontend Coverage

Subscription checkout is exposed directly:

- `application/views/frontend/youngo/subscriptions.php` links each plan to `youngo/checkout/subscription/start/{plan_id}`.

Course checkout is only partially exposed:

- `application/views/frontend/youngo/course_page.php` loads `youngo_checkout_cta_helper.php`.
- Course detail can show a CTA with `data-youngo-checkout-cta="local-course-detail"`.
- `application/helpers/youngo_checkout_cta_helper.php` targets `youngo/checkout/start/{course_id}` for eligible purchase-capable courses.

Missing website surfaces:

- `application/views/frontend/youngo/course_listing/course_card.php` does not link purchase-capable courses to direct checkout. Managed-access courses are sent to `subscriptions`.
- `application/views/frontend/youngo/my_wishlist.php` and `application/views/frontend/youngo/wishlist_items.php` do not expose direct checkout start links.
- Homepage featured course cards do not expose direct checkout start links.
- The public site therefore appears subscription-first/subscription-only unless the learner reaches a purchase-capable course detail page and all checkout CTA flags allow the helper to show.

Checkout review page coverage:

- Coupon apply/clear forms exist.
- Zero-amount coupon completion form exists.
- Instapay upload form exists.
- Card and digital wallet placeholders are visible but disabled/not available.

## F. Backend/Model Coverage

`Youngo_checkout_model`:

- Creates/reuses course draft orders with `order_type = course_purchase`.
- Creates/reuses subscription draft orders with `order_type = subscription_purchase`.
- Applies coupon snapshots to both course and subscription orders through `checkout_order_item_context()`.
- Clears coupon snapshots while order is `draft`.
- Completes zero-amount coupon orders for both course and subscription orders.

`Youngo_coupon_evaluator_model`:

- Supports `course` and `subscription`.
- Normalizes `course_purchase` to `course`.
- Normalizes `subscription_purchase` and `subscription_plan` to `subscription`.
- Checks course targeting through `youngo_coupon_courses`.
- Checks subscription plan targeting through `youngo_coupon_subscription_plans`.
- Checks global usage limit through `youngo_coupon_usages`.

`Youngo_instapay_payment_model`:

- Creates pending submissions for eligible positive-total draft checkout orders.
- Blocks zero-total coupon orders from Instapay.
- Admin approval supports both `course_purchase` and `subscription_purchase`.
- Approval writes `selected_payment_method = instapay_manual`, `payment_gateway = instapay_manual`, `status = paid`.
- Approval issues access through `Youngo_entitlement_write_model`.
- Rejection only updates submission status and does not issue access.

`Youngo_entitlement_write_model`:

- Course purchase access method exists.
- Instapay course access method exists.
- Instapay subscription access method exists.
- Zero-amount coupon course access method exists.
- Zero-amount coupon subscription access method exists.
- Generic `issue_subscription_purchase()` still returns `checkout_issuance_not_implemented`.

`Youngo_payment_model` / Paymob:

- Verified Paymob paid entitlement issuance only supports `course_purchase`.
- Subscription Paymob entitlement issuance is not implemented.
- `Youngo_payment_webhook::paymob()` currently validates and returns `validated_no_write` when testing is enabled; otherwise fail-closed.
- `Youngo_payment_return::paymob()` is UX-only and does not write payment/access state.

## G. 100% Coupon Tracking Result

Yes, 100% coupon users are registered somewhere.

For zero-amount coupon completion, `Youngo_checkout_model::complete_zero_amount_coupon_order()` does the following inside a DB transaction:

1. Validates the owned draft order.
2. Requires `subtotal_amount > 0`, `total_amount = 0.00`, `discount_amount = subtotal_amount`, `coupon_id`, `coupon_code`, and `checkout_snapshot_json`.
3. Revalidates the coupon with `Youngo_coupon_evaluator_model`.
4. Updates `youngo_checkout_orders`:
   - `status = paid`
   - `payment_gateway = zero_amount_coupon`
   - `selected_payment_method = zero_amount_coupon`
   - provider/payment fields cleared
   - `last_hmac_verified = 0`
   - `paid_at` and `completed_at`
   - `checkout_snapshot_json` with `zero_amount_coupon_completion`
   - `metadata` with `zero_amount_coupon_completion`
   - entitlement status reset to `not_started` before issuance
5. Issues entitlement:
   - course order: inserts `youngo_course_access` with `checkout_order_id`, `access_source = course_purchase`, active/lifetime.
   - subscription order: inserts `youngo_user_subscriptions` with `checkout_order_id`, `source = checkout`, active, `price_paid = 0.00`.
6. Updates `youngo_checkout_orders` after entitlement:
   - `entitlement_issued = 1`
   - `entitlement_issuance_status = issued`
   - `entitlement_course_access_id` or `entitlement_subscription_id`
   - `entitlement_issued_at`
7. Inserts `youngo_coupon_usages`:
   - `coupon_id`
   - `coupon_code`
   - `user_id`
   - `checkout_order_id`
   - `payment_id = null`
   - `discount_amount`
   - `used_at`

Live local DB tracking counts:

```json
{
  "orders_marked_zero_amount_coupon": [{"c": "2"}],
  "coupon_usage_rows_with_checkout_order": [{"c": "2"}],
  "course_access_rows_with_checkout_order": [{"c": "0"}],
  "subscription_rows_with_checkout_order": [{"c": "5"}]
}
```

Interpretation:

- The two live zero-amount coupon completions are represented in checkout orders and coupon usages.
- The live access rows are subscription rows, not course access rows, because the local data currently contains only subscription checkout orders.
- Course zero-amount coupon access is implemented in code, but no live DB sample currently demonstrates it.

## H. Admin Visibility Result

Admin-visible places that exist:

- Manual Instapay review inbox: visible at `admin/youngo/instapay-payments`.
- Manual Instapay detail: shows learner, order, item type, coupon code, discount, expected amount, submitted amount, admin review fields, payment summary, and access reference for Instapay submissions.
- User entitlement summary: shows counts and latest active subscription.
- Course entitlement summary: shows counts for course access.
- Coupon admin list/edit exists, but it does not show checkout usage history.

Admin page test note:

- The in-app browser connector was unavailable in this session, so a visible browser tab could not be attached.
- Earlier authenticated HTTP testing against the served site confirmed `http://school.local/admin/youngo/instapay-payments` returned HTTP `200` after the parse-error repair in `application/views/backend/admin/youngo_instapay_payments.php`.
- No credentials were stored or printed.

Admin visibility gaps:

- There is no dedicated admin checkout orders screen.
- There is no dedicated coupon usage report.
- There is no dedicated zero-amount coupon completion report.
- User entitlement summary does not show checkout order reference, coupon code, selected payment method, or zero-amount coupon source for the subscription row.
- Course entitlement summary counts access but does not list checkout-backed users with order/coupon references.
- Instapay inbox does not cover zero-amount coupon completions because no Instapay submission is created for that path.

Practical answer:

- Admin can infer access from entitlement summaries and subscription rows.
- Admin cannot easily find "all users who entered through 100% coupon" from the current UI.
- The source of truth exists in DB (`youngo_checkout_orders` + `youngo_coupon_usages` + entitlement table), but it is not exposed cleanly.

## I. Missing Tracking/Logging

Tracking exists but is incomplete for admin analysis:

- `youngo_coupon_usages` lacks `item_type`, `item_id`, `final_amount`, and a direct `source` column. Those values can be joined/inferred from `youngo_checkout_orders`, but reporting must always join orders.
- `youngo_course_access.access_source` remains `course_purchase` for zero-amount coupon course access. The actual coupon source is recoverable through `checkout_order_id`, but not visible on the access row alone.
- `youngo_user_subscriptions.source` remains `checkout` for zero-amount coupon subscriptions. The actual coupon source is recoverable through `checkout_order_id`, but not visible on the subscription row alone.
- There is no admin UI for checkout orders or coupon usages.

Safest fix:

- Do not change entitlement source enums casually if existing access logic depends on `course_purchase`/`checkout`.
- Add an admin checkout/coupon usage report that joins:
  - `youngo_checkout_orders`
  - `youngo_coupon_usages`
  - `users`
  - `course` or `youngo_subscription_plans`
  - `youngo_course_access` / `youngo_user_subscriptions`
- Optionally add additive columns to `youngo_coupon_usages` such as `item_type`, `item_id`, `final_amount`, and `source`, backfilled from order data. This should be additive and backwards-compatible.

## J. Subscription-Only Limitation Source

The limitation is not in order creation. Backend order creation supports course and subscription.

The limitation comes from website entry points and CTA gating:

- `subscriptions.php` always exposes subscription checkout.
- Course listing cards route managed-access courses to `subscriptions`, not course checkout.
- Wishlist surfaces do not route to course checkout.
- Homepage featured course cards do not route to course checkout.
- Course detail can show course checkout only through `youngo_checkout_cta_decision()`.
- `youngo_checkout_cta_decision()` hides direct course purchase if:
  - `checkout_cta_enabled` is false;
  - `checkout_routes_enabled` is false;
  - `checkout_local_testing_enabled` is false;
  - mode/currency are not sandbox/EGP;
  - course mode is `subscription_only`;
  - course is not `purchase_only` or `subscription_and_purchase`;
  - course is free;
  - learner is not logged in as a learner;
  - learner already has active access.

The visible result is that subscriptions are the only consistently exposed checkout path.

## K. Required Fixes

Minimal practical fixes:

1. Add admin visibility for 100% coupon users.
   - Start with a read-only admin report for zero-amount coupon completions.
   - Show order reference, learner, item type, item title, coupon code, original amount, discount, final amount, selected payment method, entitlement status, entitlement reference, completed timestamp.

2. Add course checkout entry points where purchase is allowed.
   - Reuse `youngo_checkout_cta_decision()`.
   - Wire purchase-capable course listing cards to direct checkout when the helper allows it.
   - Wire wishlist and wishlist partial similarly.
   - Keep subscription-only courses pointing to subscription plans.

3. Normalize checkout item labels.
   - Use clear labels for `purchase_only`, `subscription_and_purchase`, and `subscription_only`.
   - Avoid showing "Subscribe" for direct purchase-only courses.

4. Keep coupon snapshot compatibility.
   - Continue using checkout order coupon snapshot fields.
   - Preserve zero-total orders away from Instapay.

5. Keep Instapay compatible with both current payable order types.
   - Retain positive final amount requirement.
   - Keep admin approval issuing course access or subscription access by `order_type`.

6. Keep Paymob disabled safety.
   - Do not enable network or production defaults in this phase.
   - If Paymob subscription support is later required, implement subscription entitlement issuance before exposing Paymob for subscriptions.

7. Add QA coverage.
   - Course detail purchase checkout CTA.
   - Course listing purchase checkout CTA.
   - Wishlist purchase checkout CTA.
   - Subscription checkout.
   - Coupon apply/clear for both order types.
   - 100% coupon completion for both order types.
   - Instapay upload and admin approval for both order types.
   - Admin report for zero-amount coupon users.

## L. Recommended Implementation Phases

Phase 1: Admin visibility only.

- Add checkout/coupon usage admin report.
- No changes to payment behavior.
- Add diagnostic and HTTP admin smoke.

Phase 2: Website course checkout coverage.

- Extend listing, wishlist, and related course CTAs through the existing helper.
- Preserve subscription-only routing.
- Add UI/HTTP QA for hidden/shown CTA states.

Phase 3: Checkout reporting refinements.

- Add optional additive `youngo_coupon_usages` fields if report joins are too expensive or unclear.
- Backfill from `youngo_checkout_orders`.

Phase 4: Paymob coverage decision.

- Decide whether Paymob should support subscriptions.
- If yes, implement verified subscription entitlement issuance before exposing subscription Paymob network flow.

## M. Diagnostic Result

Created:

```text
scripts/phase_2/youngo_payment_checkout_coverage_audit_1_diagnostic.php
```

Validation:

```text
php -l scripts/phase_2/youngo_payment_checkout_coverage_audit_1_diagnostic.php
No syntax errors detected

php scripts/phase_2/youngo_payment_checkout_coverage_audit_1_diagnostic.php
RESULT: PASS
```

Diagnostic warnings:

```text
paymob_subscription_entitlement_gap
course_listing_checkout_cta_gap
wishlist_checkout_cta_gap
```

Protected table counts before and after were identical:

```json
{
  "youngo_checkout_orders": 7,
  "youngo_coupon_usages": 2,
  "youngo_course_access": 0,
  "youngo_user_subscriptions": 5,
  "youngo_instapay_payment_submissions": 3,
  "youngo_payment_transactions": 0
}
```

## N. Git Status

Current status includes many pre-existing modified/untracked files. New audit artifacts:

```text
?? scripts/phase_2/youngo_payment_checkout_coverage_audit_1_diagnostic.php
?? docs/qa/youngo_payment_checkout_coverage_audit_1_report.md
```

Notable existing dirty payment/access files already present before the report:

```text
 M application/config/routes.php
 M application/controllers/Admin.php
 M application/controllers/Home.php
 M application/controllers/Youngo_checkout.php
 M application/models/Youngo_checkout_model.php
 M application/models/Youngo_coupon_evaluator_model.php
 M application/models/Youngo_entitlement_write_model.php
 M application/models/Youngo_instapay_payment_model.php
 M application/views/frontend/youngo/checkout_order.php
 M application/views/frontend/youngo/course_listing/course_card.php
 M application/views/frontend/youngo/course_page.php
 M application/views/frontend/youngo/my_wishlist.php
 M application/views/frontend/youngo/subscriptions.php
 M application/views/frontend/youngo/wishlist_items.php
 M application/views/backend/admin/youngo_instapay_payment_view.php
 M application/views/backend/admin/youngo_instapay_payments.php
 M application/views/backend/admin/youngo_course_entitlement_summary.php
 M application/views/backend/admin/youngo_user_entitlement_summary.php
```
