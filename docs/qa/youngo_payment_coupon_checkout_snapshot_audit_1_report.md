# YounGo Coupon Checkout Snapshot Audit 1

Phase: `PAYMENT.COUPON.CHECKOUT.SNAPSHOT.AUDIT.1`
Date: 2026-07-26
Mode: audit/planning only. No deployment, push, SQL, DB writes, coupon/order/payment data edits, checkout behavior changes, route creation, Paymob activation, checkout/payment CTA exposure, Root Admin changes, or credential output were performed.

## A. Current Branch/Status

- Branch at start: `analysis/cms-audit`
- Worktree at start: clean
- Latest commit at start: `80f8ccc Plan manual Instapay coupon checkout review flow`
- Recent history also includes the Arabic/public readiness work requested in the previous phase context.

## B. Files Inspected

- Reports: `docs/qa/youngo_payment_manual_instapay_coupon_checkout_plan_1_report.md`, `docs/qa/youngo_payment_checkout_local_ui_qa_3_report.md`, `docs/qa/youngo_payment_reconcile_audit_1_report.md`
- Checkout/payment source: `application/models/Youngo_checkout_model.php`, `application/models/Youngo_payment_model.php`, `application/controllers/Youngo_checkout.php`, `application/controllers/Home.php`, `application/controllers/Payment.php`, `application/models/Payment_model.php`
- Coupon/admin source: `application/controllers/Admin.php`, `application/models/Crud_model.php`, `application/views/backend/admin/coupon_add.php`, `application/views/backend/admin/coupon_edit.php`, `application/views/backend/admin/coupons.php`
- Frontend source: `application/views/frontend/youngo/checkout_order.php`, `application/views/frontend/youngo/shopping_cart_inner_view.php`, other `application/views/frontend/youngo/` payment/cart references
- Schema/scripts/config: `database/phase_2/youngo_phase_2e_schema_up.sql`, `database/phase_2/youngo_phase_2e_schema_down.sql`, `scripts/phase_2/payment_config_2_youngo_payment_schema_proposed.sql`, `scripts/phase_2/`, `application/config/youngo_paymob.php`, `application/config/routes.php`

## C. Legacy Coupon System Summary

Legacy tables and fields:

- Base table: `coupons`.
- Active legacy fields used by code/views: `id`, `code`, `discount_percentage`, `expiry_date`, `created_at`.
- Phase 2 additive YounGo fields documented in schema: `discount_type`, `discount_value`, `scope`, `max_usage_count`, `status`, `updated_at`.
- YounGo coupon linkage/scope tables documented in Phase 2 schema: `youngo_coupon_usages`, `youngo_coupon_courses`, and `youngo_coupon_subscription_plans`.

Models/controllers:

- `Crud_model::get_coupons()`
- `Crud_model::get_coupon_details_by_code()`
- `Crud_model::add_coupon()`
- `Crud_model::edit_coupon()`
- `Crud_model::delete_coupon()`
- `Crud_model::check_coupon_validity()`
- `Crud_model::get_discounted_price_after_applying_coupon()`
- `Admin::coupons()` and `Admin::coupon_form()`
- `Home::apply_coupon()`, `Home::refreshShoppingCart()`, and `Home::coupon_offer_100_percent()`
- `Payment_model::configure_course_payment()`
- `Crud_model::course_purchase()`

Views:

- Backend: `coupon_add.php`, `coupon_edit.php`, `coupons.php`
- Frontend: `shopping_cart_inner_view.php`

Supported coupon type in active UI/code:

- Percentage only through `discount_percentage`.
- Fixed amount is present only as a Phase 2 additive schema concept through `discount_type = fixed` and `discount_value`; it is not wired to current coupon admin UI or YounGo checkout.

Validation rules found:

- Coupon code must exist.
- `discount_percentage` must be posted and greater than zero; admin input has min/max `1` to `100`.
- `expiry_date` must be present and must be greater than or equal to the current day in `check_coupon_validity()`.
- The cart view also checks expiry and applies the percent discount in the view fragment.

Restrictions not implemented in the active legacy flow:

- No active user-specific restriction.
- No active per-course/per-plan YounGo scope validation.
- No active total usage validation against `max_usage_count`.
- No active per-user usage limit.
- No active minimum/maximum order amount rule.
- No deterministic YounGo order snapshot service.
- No EGP-only coupon evaluator boundary; legacy gateway currency comes from system/gateway settings.

Currency assumptions:

- Legacy coupon math works on course price numbers and renders through `currency()`.
- Legacy payment amount is passed to generic gateway flows through session `payment_details`.
- YounGo checkout is EGP-only, so any reusable coupon evaluator must explicitly reject non-EGP inputs for now.

## D. Existing YounGo Checkout Order Summary

Current YounGo checkout source:

- `Youngo_checkout::start_course()` loads a course, computes a course amount, and calls `Youngo_checkout_model::create_or_reuse_draft_order()`.
- `Youngo_checkout_model::create_draft_order()` creates a course purchase order with EGP-only amount handling.
- `Youngo_payment_model` is currently Paymob/HMAC oriented and can mark orders paid only from verified transactions.

Current order identity and item fields:

- `id`
- `order_reference`
- `order_type`
- `user_id`
- `course_id`
- `plan_id`

Current amount/currency fields:

- `subtotal_amount`
- `discount_amount`
- `tax_amount`
- `total_amount`
- `total_amount_cents`
- `currency`

Current order/payment/provider fields:

- `status`
- `payment_gateway`
- `gateway_environment`
- `provider_intent_id`
- `provider_order_id`
- `provider_transaction_id`
- `payment_id`
- `idempotency_key`
- `last_hmac_verified`
- lifecycle timestamps such as `payment_started_at`, `return_seen_at`, `last_webhook_at`, `completed_at`, `paid_at`, `failed_at`, `cancelled_at`, and `expired_at`

Current entitlement fields:

- `entitlement_issued`
- `entitlement_issuance_status`
- `entitlement_course_access_id`
- `entitlement_subscription_id`
- `entitlement_issued_at`
- `entitlement_issuance_error`

Current coupon/snapshot fields:

- Schema has `coupon_id`, `coupon_code`, and `discount_amount`.
- Model currently initializes `discount_amount` to `0.00`.
- Model does not apply or persist a coupon code yet.
- `metadata` exists and is currently used for source/phase or redacted Paymob summaries.

Missing fields for coupon/admin review:

- `original_amount` alias or a clearly documented mapping from `subtotal_amount`.
- `coupon_discount_type`.
- `coupon_discount_value`.
- `final_amount` alias or a clearly documented mapping to `total_amount`.
- `selected_payment_method`.
- `item_title_snapshot`.
- `checkout_snapshot_json` or a formal `metadata.checkout_snapshot` contract.
- Safe order summary exposure for `subtotal_amount`, `coupon_code`, `coupon_id`, `discount_amount`, coupon type/value, and item title snapshot.

## E. Coupon Reuse/Safety Finding

Direct reuse of the legacy coupon system is unsafe for manual Instapay checkout.

Reasons:

- Legacy coupon calculation depends on `cart_items` and `applied_coupon` session state.
- The YounGo cart view mutates `applied_coupon` while rendering the fragment.
- Legacy `Payment_model::configure_course_payment()` calculates totals from the cart and then writes session `payment_details`.
- Legacy `Payment::success_course_payment()` writes legacy `enrol` and `payment` records after gateway success.
- Legacy coupon admin and calculation are percentage-only, while Phase 2 schema anticipates fixed/percentage coupon types.
- Active validation is expiry-only and does not enforce YounGo scope, plan/course targeting, status, max usage, per-user limits, or checkout-order usage accounting.
- The legacy 100 percent coupon shortcut has been quarantined for YounGo-managed carts and must not be used as an access shortcut.

Safe reuse:

- Reuse the `coupons` table as the coupon source.
- Reuse Phase 2 additive fields and scope tables where present.
- Reuse `youngo_coupon_usages` only after approval/payment completion, not at coupon preview time.
- Do not reuse cart/session calculations, view-side calculations, legacy `Payment_model::configure_course_payment()`, `Payment.php`, `Crud_model::course_purchase()`, or `Crud_model::enrol_student()` for YounGo manual Instapay checkout.

## F. Recommended Coupon Evaluator

Recommended component:

- `application/models/Youngo_coupon_evaluator_model.php` or equivalent checkout-domain model.

Recommended method:

```php
evaluate_coupon_for_checkout($user_id, $item_type, $item_id, $original_amount, $coupon_code, $currency = 'EGP')
```

Input:

- `user_id`
- `item_type`: `course` or `subscription`
- `item_id`: course ID or subscription plan ID
- `original_amount`
- `coupon_code`
- `currency`, EGP only for now

Output:

- `valid`: true/false
- `coupon_id`
- `coupon_code`
- `discount_type`: `percentage` or `fixed`
- `discount_value`
- `discount_amount`
- `final_amount`
- `currency`
- `message`
- `error`
- `coupon_snapshot_metadata`

Rules:

- Do not read or write cart/session state.
- Do not write `youngo_coupon_usages`.
- Do not create payment, access, enrolment, or order side effects.
- Require EGP for now.
- Normalize amount to two decimals and cents where needed.
- Reject invalid user/item IDs.
- Reject empty coupon code as no-coupon state, not as a fatal checkout failure.
- Load coupon by normalized code.
- Require active/non-expired coupon.
- Prefer Phase 2 `discount_type`/`discount_value` when present; fall back to legacy `discount_percentage` as `percentage`.
- Support `percentage` and `fixed`.
- Clamp discount to `0 <= discount_amount <= original_amount`.
- Ensure `final_amount = original_amount - discount_amount` and never below zero.
- Enforce `scope` for `course` vs `subscription`.
- Enforce `youngo_coupon_courses` and `youngo_coupon_subscription_plans` targeting when rows exist for the coupon.
- Enforce `max_usage_count` using approved/used coupon usage rows, not pending review submissions.
- Return deterministic snapshot metadata: coupon row id/code, source fields used, applied scope, item target check, original amount, discount calculation formula, final amount, and evaluator version/phase.

Recommended output example:

```php
array(
    'valid' => true,
    'coupon_id' => 12,
    'coupon_code' => 'SAVE20',
    'discount_type' => 'percentage',
    'discount_value' => '20.00',
    'discount_amount' => '200.00',
    'final_amount' => '800.00',
    'currency' => 'EGP',
    'message' => 'Coupon applied.',
    'error' => null,
    'coupon_snapshot_metadata' => array(
        'evaluator_version' => 'PAYMENT.COUPON.EVALUATOR.1',
        'scope' => 'course_purchase',
        'calculation' => '1000.00 * 20.00%',
    ),
)
```

## G. Recommended Checkout Snapshot Fields

Recommended minimal queryable fields for a later schema phase:

- `original_amount` or formal reuse of existing `subtotal_amount`
- `coupon_code`
- `coupon_discount_type`
- `coupon_discount_value`
- `discount_amount`
- `final_amount` or formal reuse of existing `total_amount`
- `currency`
- `selected_payment_method`
- `item_title_snapshot`
- `checkout_snapshot_json`

Recommended mapping:

- `original_amount`: use existing `subtotal_amount` as the canonical stored field unless a clearer alias is approved.
- `final_amount`: use existing `total_amount` as the canonical stored field unless a clearer alias is approved.
- `selected_payment_method`: store `instapay_manual` for this manual flow.
- `checkout_snapshot_json`: immutable payload copied from the evaluator/order context and visible to admin review.

Recommended snapshot JSON keys:

- `snapshot_version`
- `created_at`
- `user_id`
- `order_id`
- `order_reference`
- `item_type`
- `course_id`
- `subscription_plan_id`
- `item_title_snapshot`
- `original_amount`
- `coupon_id`
- `coupon_code`
- `coupon_discount_type`
- `coupon_discount_value`
- `coupon_discount_amount`
- `final_amount`
- `currency`
- `selected_payment_method`
- `checkout_source`
- `coupon_evaluator_result`

## H. Admin Review Coupon Display

Admin review detail must show:

- Order number/reference.
- User.
- Item type and item title snapshot.
- Course ID or subscription plan ID.
- Original amount.
- Coupon code used, or `None`.
- Coupon discount type/value.
- Discount formula, for example `20% of EGP 1000.00` or `EGP 150.00 fixed`.
- Discount amount.
- Final expected amount in EGP.
- Submitted Instapay amount, if collected separately.
- Transaction reference and user note.
- Screenshot preview/download.
- Warning when submitted amount differs from final expected amount.
- Warning when coupon snapshot is missing but discount amount is non-zero.
- Warning when final amount does not equal original amount minus discount amount.

The admin should verify payment manually outside the system. Screenshot upload alone must not grant access. Access issuance remains tied only to admin approval in the later Instapay approval phase.

## I. Risks/Blockers

- Current checkout model creates course purchase orders only; subscription checkout needs a dedicated order-start path.
- Coupon service is not implemented yet.
- Active legacy coupon UI does not expose fixed discount, scope, status, or usage limit fields.
- Existing `Youngo_checkout_model::get_safe_order_summary()` omits coupon/admin snapshot fields.
- `metadata` is already used for Paymob summaries, so the checkout snapshot contract must be explicit to avoid overwriting unrelated metadata.
- Usage accounting policy must decide whether usage is recorded at approval time only. Recommended: approval time only, so rejected manual submissions do not consume coupons.
- Need clear behavior for zero-final-amount coupons. For this manual Instapay phase, zero-total coupon orders should still not auto-grant access unless a separate approved free-order issuance policy exists.
- Schema changes require explicit backup/apply/rollback planning in a later phase.

## J. Recommended Next Phase

Recommended next phases:

1. `PAYMENT.COUPON.CHECKOUT.SNAPSHOT.SCHEMA.1`: add approved snapshot fields or formal metadata contract, with backup and no activation.
2. `PAYMENT.COUPON.EVALUATOR.1`: implement read-only/no-side-effect evaluator with deterministic outputs and diagnostics.
3. `PAYMENT.COUPON.CHECKOUT.UI.1`: add coupon entry and order summary display to the disabled/manual checkout surface without enabling real payments.
4. `PAYMENT.MANUAL.INSTAPAY.SCHEMA.1`: add manual Instapay submission/review schema after coupon snapshot fields are settled.

## K. Diagnostic Result

- `php -l scripts/phase_2/youngo_payment_coupon_checkout_snapshot_audit_1_diagnostic.php`: pass.
- `php scripts/phase_2/youngo_payment_coupon_checkout_snapshot_audit_1_diagnostic.php`: pass.
- Diagnostic mode: `static_read_only_no_db`.
- Diagnostic summary: 70 checks total, 0 failed.
- Diagnostic confirmed legacy coupon model/controller/view detection, YounGo checkout order table/schema detection, current coupon/snapshot gaps, no coupon checkout route, no Instapay route, no checkout/payment behavior implementation change, and Paymob gates remaining false.

## L. Git Status

- `git diff --check`: pass.
- Final status after this audit phase: only the report and read-only diagnostic are untracked.
- No application controller, model, view, route, config, SQL, coupon/order/payment data, checkout behavior, Paymob gate, CTA, user/course/subscription data, or Root Admin files were modified.
