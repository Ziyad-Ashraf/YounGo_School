# PAYMENT.ZERO.AMOUNT.COUPON.ACCESS.PLAN.1 Report

## A. Current Branch/Status

- Branch: `analysis/cms-audit`
- Starting worktree status: clean
- Latest relevant commit: `8166264 Add manual Instapay admin review inbox`
- Phase mode: planning/audit only
- Database impact: none
- SQL execution: none
- Checkout behavior changes: none
- Access issuance: none
- Paymob activation: none
- Instapay approve/reject behavior: none

## B. Files Inspected

- `AGENTS.md`
- `YOUNGO_PROJECT_CONTEXT.md`
- `docs/design/youngo_style_direction.md`
- `docs/planning/youngo_master_plan_v2.md`
- `docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`
- `docs/planning/`
- `docs/agents/implementation_rules.md`
- `docs/reference/README.md`
- `docs/qa/youngo_payment_coupon_evaluator_1_report.md`
- `docs/qa/youngo_payment_coupon_checkout_snapshot_write_1_report.md`
- `docs/qa/youngo_payment_coupon_checkout_ui_1_report.md`
- `docs/qa/youngo_payment_manual_instapay_admin_review_ui_1_report.md`
- `application/models/Youngo_coupon_evaluator_model.php`
- `application/models/Youngo_checkout_model.php`
- `application/models/Youngo_instapay_payment_model.php`
- `application/models/Youngo_entitlement_write_model.php`
- `application/models/Youngo_subscription_model.php`
- `application/controllers/Youngo_checkout.php`
- `application/views/frontend/youngo/checkout_order.php`
- `application/config/routes.php`
- `application/config/youngo_paymob.php`
- `database/phase_2/youngo_phase_2e_schema_up.sql`
- `scripts/phase_2/payment_config_2_youngo_payment_schema_proposed.sql`
- `scripts/phase_2/payment_coupon_checkout_snapshot_schema_1_up.sql`

## C. Current Zero-Final Behavior

The current coupon evaluator already supports zero-final calculations safely:

- `Youngo_coupon_evaluator_model::evaluate_coupon_for_checkout()` returns `zero_final_amount_policy_not_enabled` when the computed final amount is zero.
- The evaluator supports percentage and fixed discounts, clamps over-discounts to the original amount, rejects non-`EGP`, and enforces available status, expiry, scope, and usage checks where the schema supports them.
- `Youngo_checkout_model::apply_coupon_snapshot_to_order()` writes coupon snapshot fields to `youngo_checkout_orders`, including `coupon_id`, `coupon_code`, `discount_amount`, `coupon_discount_type`, `coupon_discount_value`, `total_amount`, `total_amount_cents`, and `checkout_snapshot_json`.
- `subtotal_amount` remains the original amount and `total_amount` becomes the final amount.
- `get_safe_order_review_snapshot()` exposes the original amount, discount, final amount, coupon fields, selected payment method, item snapshot, and the zero-final flag.
- The checkout page currently shows: `This coupon brings the final amount to zero, but access is not granted automatically.`
- There is no zero-amount completion button, controller action, route, or access issuance path yet.
- Instapay upload can still be available for a zero-total order if Instapay config is enabled, because the current Instapay context does not explicitly block zero-total orders. This should be changed in the implementation phase.

## D. Eligibility Rules

A zero-amount coupon checkout completion should be allowed only when all checks pass:

- The learner is logged in.
- The order belongs to the learner.
- The order is a YounGo checkout order.
- The order is still unpaid/draft-safe and has not started payment, access, or entitlement issuance.
- The order currency is exactly `EGP`.
- `subtotal_amount` is greater than `0.00`.
- `total_amount` is exactly `0.00`; prefer integer cents comparison through `total_amount_cents === 0` where available.
- A coupon snapshot exists with `coupon_id`, normalized `coupon_code`, `coupon_discount_type`, `coupon_discount_value`, and `discount_amount`.
- The discount covers the full original amount: either `discount_amount == subtotal_amount` after normalization, or evaluator revalidation confirms final amount exactly `0.00`.
- The coupon is revalidated at completion time using `user_id`, `item_type`, `item_id`, `subtotal_amount`, `coupon_code`, and `EGP`.
- The current evaluator result still matches the stored snapshot closely enough for audit: coupon id/code, discount type/value, discount amount, final amount, and currency.
- There is no pending or approved Instapay submission for the order.
- There is no Paymob transaction or provider payment state for the order.
- There is no previous entitlement issued for this order.
- There is no existing active course access or subscription access that would create a duplicate entitlement.

Invalid, expired, out-of-scope, stale, partial-discount, or non-`EGP` coupon states must not qualify.

## E. Completion/Order Status Plan

Recommended implementation phase should add a POST-only learner action, for example:

- `youngo/checkout/zero-coupon/complete/(:any)`
- Controller method: `Youngo_checkout::complete_zero_amount_coupon($order_reference)`
- Model method: `Youngo_checkout_model::complete_zero_amount_coupon_order($order_id, $user_id, $actor_context = array())`

The completion path should:

- Re-fetch the order inside a transaction.
- Re-run the eligibility checks.
- Revalidate the coupon through `Youngo_coupon_evaluator_model`.
- Set `selected_payment_method = zero_amount_coupon`.
- Mark the order as completed/paid-equivalent with a clear non-gateway payment method.
- Set completion timestamp fields such as `completed_at` and `paid_at` where the existing schema supports them.
- Preserve `subtotal_amount`, coupon fields, `total_amount = 0.00`, and `checkout_snapshot_json`.
- Add zero-amount completion metadata into `checkout_snapshot_json` or order metadata.
- Record that no gateway verification occurred; do not set `last_hmac_verified = 1` as a shortcut.
- Do not create an Instapay submission.
- Do not create a Paymob transaction.

Order status decision:

- Safest minimal option: use existing `paid` status as the paid-equivalent terminal order status, but only with `payment_gateway` / `payment_method` / `selected_payment_method = zero_amount_coupon`.
- Safer audit option if downstream code supports it: introduce a distinct completed status such as `completed_zero_amount`.
- Because current course purchase issuance expects `status = paid`, the implementation should either keep `paid` with explicit zero-coupon payment method metadata, or update the entitlement issuance validator to accept an explicit zero-coupon trusted completion context. It should not fake Paymob HMAC verification.

## F. Access Issuance Plan

Course purchases can be supported first because `Youngo_entitlement_write_model::issue_course_purchase_access()` exists and writes through the shared entitlement boundary. However, that method is currently designed for verified paid/gateway orders:

- It requires `status = paid`.
- It requires `last_hmac_verified`.
- It writes course access with `checkout_order_id`.
- It prevents duplicate issuance through existing entitlement/order guards.

Recommended implementation:

- Add a dedicated entitlement write method for zero-amount coupon course access, for example `issue_zero_amount_coupon_course_access($checkout_order_id, $actor_context = array())`, or extend the existing issue validator with a narrow explicit `zero_amount_coupon` path.
- Keep all access writes inside `Youngo_entitlement_write_model`.
- Access start time must be the zero-coupon completion timestamp.
- Course purchase access should follow the existing course purchase duration rules, currently lifetime unless future purchase-duration fields say otherwise.
- Update order entitlement fields after successful issuance: `entitlement_issued`, `entitlement_course_access_id`, `entitlement_issued_at`, and equivalent existing fields.
- Do not write legacy `enrol` rows unless a future compatibility plan explicitly requires it.

Subscription access should be deferred for this phase sequence because `issue_subscription_purchase()` still returns `checkout_issuance_not_implemented`. Zero-amount subscription checkout should remain blocked or display a safe deferred message until subscription purchase issuance is implemented and QA-tested.

## G. UI Plan

Checkout page behavior after implementation:

- If final amount is greater than `0.00`, keep the existing payment method behavior.
- If final amount is exactly `0.00` because of a valid coupon snapshot:
  - Show a clear message that no payment is required because the coupon covers the full amount.
  - Show a POST-only button: `Complete checkout` or `Activate access`.
  - Hide or disable Instapay upload for that order.
  - Keep cards and digital wallets disabled with `Not available yet`.
  - Do not show Paymob redirect actions.
- If the zero coupon is stale or cannot be revalidated:
  - Do not show the activation button.
  - Ask the learner to remove/reapply the coupon or contact support.
- After successful completion:
  - Show completed/access-available status.
  - Link to the course or My Courses/My Access where appropriate.

## H. Coupon Usage/Audit Plan

Coupon usage should be consumed only on successful zero-amount checkout completion, not when the learner previews or applies a coupon snapshot.

Recommended audit writes during the implementation phase:

- Insert one `youngo_coupon_usages` row linked to `checkout_order_id`, `user_id`, `coupon_id`, coupon code, discount amount, original amount/final amount if supported, and `used_at`.
- Preserve the full coupon/evaluator snapshot in `checkout_snapshot_json`.
- Store zero-coupon completion metadata with method `zero_amount_coupon`, completion timestamp, evaluator result summary, and access issuance reference.
- Make coupon usage idempotent with the existing unique `checkout_order_id` coupon usage guard if present; otherwise check before insert inside the transaction.
- If access issuance fails after marking coupon usage, roll back the whole transaction.

## I. Course vs Subscription Support

- Course purchase zero-amount coupon completion can be implemented next, with a dedicated zero-coupon access issuance path in `Youngo_entitlement_write_model`.
- Subscription zero-amount coupon completion should be deferred until subscription checkout issuance is implemented. Current `issue_subscription_purchase()` is intentionally stubbed.
- The UI/controller should not offer a zero-amount activation button for subscription orders until subscription purchase issuance has a tested write path.

## J. Idempotency/Duplicate Prevention

Recommended idempotency strategy:

- Use a database transaction for completion, coupon usage, order status update, and entitlement issuance.
- Re-fetch the order inside the transaction.
- Complete only if the order is still draft/unpaid-safe and `entitlement_issued` is not set.
- Revalidate the coupon inside the transaction.
- Check for existing active course access or subscription access before inserting.
- Check for existing coupon usage linked to `checkout_order_id`.
- Rely on unique/indexed `checkout_order_id` fields where available.
- On duplicate click after successful completion, return an already-completed success response with the existing entitlement reference instead of issuing again.
- On partially failed completion, roll back all writes and leave the order safely retryable.

## K. Risks/Blockers

- `issue_course_purchase_access()` is currently Paymob/HMAC-oriented. Zero-coupon implementation must not set `last_hmac_verified = 1` as a fake gateway proof.
- Subscription issuance is not implemented, so subscription zero-amount coupon checkout must be deferred.
- Current Instapay checkout context does not explicitly block zero-total orders. The implementation phase must prevent Instapay upload for eligible zero-total coupon orders.
- A stored coupon snapshot can become stale if coupon status, expiry, usage, or scope changes after application. Completion must revalidate.
- Coupon usage consumption must be atomic with order completion and access issuance.
- Order status naming needs a conscious decision: use existing `paid` with explicit `zero_amount_coupon` metadata, or add support for a separate completed zero-amount status after downstream compatibility review.
- Read layer compatibility for any new access source value should be checked before choosing `access_source = zero_amount_coupon`. If unsure, keep existing course purchase access source and preserve zero-coupon detail on the order.

## L. Recommended Implementation Phase

Recommended next phase:

`PAYMENT.ZERO.AMOUNT.COUPON.ACCESS.IMPLEMENT.1 — Implement Zero-Amount Coupon Checkout Completion for Course Purchases`

Scope recommendation:

- Course purchase orders only.
- POST-only learner completion route.
- Model-level eligibility and transaction method.
- Coupon revalidation at completion time.
- Atomic coupon usage, order completion, and course access issuance.
- Hide Instapay upload for qualifying zero-total orders.
- No Paymob, no Instapay approval, no subscription issuance.

Subscription support should follow later:

`PAYMENT.ZERO.AMOUNT.COUPON.SUBSCRIPTION.ACCESS.PLAN.1`, after subscription purchase issuance is implemented.

## M. Diagnostic Result

Diagnostic file:

- `scripts/phase_2/youngo_payment_zero_amount_coupon_access_plan_1_diagnostic.php`

Validation result:

```text
php -l scripts\phase_2\youngo_payment_zero_amount_coupon_access_plan_1_diagnostic.php
No syntax errors detected

php scripts\phase_2\youngo_payment_zero_amount_coupon_access_plan_1_diagnostic.php
RESULT: PASS
```

The diagnostic is static/read-only and verifies:

- Required models, controller, view, route, config, and schema artifacts exist.
- Zero-final evaluator flag exists.
- Coupon snapshot fields and review snapshot support exist.
- Current UI warning exists and no completion button exists yet.
- Instapay is not yet zero-total blocked.
- Course purchase issuance exists but is currently paid/HMAC guarded.
- Subscription checkout issuance remains stubbed.
- Coupon usage schema exists.
- No zero-amount routes were added.
- No Instapay approval behavior exists.
- Paymob static gates remain disabled.
- This planning phase changed no checkout behavior.

## N. Git Status

Status after creating the diagnostic and report:

```text
?? docs/qa/youngo_payment_zero_amount_coupon_access_plan_1_report.md
?? scripts/phase_2/youngo_payment_zero_amount_coupon_access_plan_1_diagnostic.php
```
