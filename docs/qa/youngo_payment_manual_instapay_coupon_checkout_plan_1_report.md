# YounGo Manual Instapay Coupon Checkout Plan 1

Phase: `PAYMENT.MANUAL.INSTAPAY.COUPON.CHECKOUT.PLAN.1`
Date: 2026-07-26
Mode: planning and static audit only. No deployment, push, SQL, DB writes, payment data changes, user/course/subscription edits, Paymob activation, checkout route creation, entitlement behavior change, or Root Admin change was performed.

## A. Current Branch/Status

- Branch at start: `analysis/cms-audit`
- Worktree at start: clean
- Latest commits included the completed Arabic/public readiness work, including `f19fb89 Localize Arabic auth browser titles`, `b1e43a7 Add Arabic public demo readiness QA`, and the preceding Arabic public/subscription readiness commits.

## B. Files Inspected

- Required context and rules: `YOUNGO_PROJECT_CONTEXT.md`, `docs/design/youngo_style_direction.md`, `docs/planning/`, `docs/planning/youngo_master_plan_v2.md`, `docs/planning/youngo_phase_2_hybrid_access_subscriptions_roles_architecture.md`, `docs/agents/implementation_rules.md`, `docs/reference/`
- Prior QA/planning reports: `docs/qa/youngo_payment_reconcile_audit_1_report.md`, `docs/qa/youngo_payment_checkout_local_ui_qa_3_report.md`, `docs/qa/youngo_payment_checkout_cta_local_ui_qa_1_report.md`, `docs/qa/youngo_payment_paymob_client_handoff_1.md`, `docs/qa/youngo_dynamic_content_arabic_subscriptions_public_qa_1_report.md`, `docs/qa/youngo_demo_arabic_public_readiness_qa_1_report.md`
- Additional payment foundation reports: `docs/qa/youngo_payment_schema_1_apply_report.md`, `docs/qa/youngo_payment_order_1_checkout_order_service_report.md`, `docs/qa/youngo_payment_order_status_1_report.md`, `docs/qa/youngo_payment_entitlement_block_1_report.md`, `docs/qa/youngo_payment_checkout_route_plan_1_report.md`
- Source inspected: `application/models/Youngo_checkout_model.php`, `application/models/Youngo_payment_model.php`, `application/models/Youngo_entitlement_write_model.php`, `application/models/Youngo_subscription_model.php`, `application/models/Crud_model.php`, `application/controllers/Youngo_checkout.php`, `application/controllers/Youngo_payment_webhook.php`, `application/controllers/Admin.php`, `application/controllers/Youngo_payment_settings.php`, `application/models/Youngo_payment_config_model.php`, `application/helpers/youngo_checkout_cta_helper.php`, `application/helpers/youngo_capability_helper.php`, `application/views/frontend/youngo/`, `application/views/backend/admin/`, `application/config/youngo_paymob.php`, `application/config/routes.php`, `database/phase_2/`, and `scripts/phase_2/`

## C. Existing Checkout/Order Foundation

- Main YounGo order table: `youngo_checkout_orders`.
- Related payment table: `youngo_payment_transactions`, currently Paymob/HMAC oriented.
- Related coupon accounting table: `youngo_coupon_usages`.
- Existing order fields include `id`, `user_id`, `order_reference`, `order_type`, `status`, `course_id`, `plan_id`, `subtotal_amount`, `discount_amount`, `tax_amount`, `total_amount`, `total_amount_cents`, `currency`, `coupon_id`, `coupon_code`, `payment_gateway`, `gateway_environment`, provider reference fields, `idempotency_key`, HMAC and entitlement flags, entitlement link fields, failure fields, `metadata`, and order lifecycle timestamps.
- Current order statuses present in code/reports include `draft`, `pending_gateway`, `awaiting_webhook`, `paid`, `failed`, `cancelled`, and `expired`. These are existing order lifecycle states, not recommended Instapay review statuses.
- Course purchase support exists for `purchase_only` and `subscription_and_purchase` courses through `Youngo_checkout_model::create_draft_order()` and `Youngo_checkout::start_course()`.
- Subscription purchase support is not implemented. The order schema has `plan_id`, and `Youngo_entitlement_write_model::issue_subscription_purchase()` exists only as a checkout issuance stub.
- Existing amount/currency handling uses EGP, decimal amounts, and Paymob cents conversion.
- Existing checkout routes are `youngo/checkout/start/(:num)`, `youngo/checkout/order/(:any)`, `youngo/checkout/return/(:any)`, and `youngo/checkout/status/(:any)`.
- Existing checkout view is `application/views/frontend/youngo/checkout_order.php`; it shows local order status and Paymob-disabled messaging. It has no coupon entry, Instapay submission, upload, or payment method card UI.
- Checkout/CTA gates remain disabled by Paymob config defaults. Current reports confirm checkout routes/CTA were local-gated and Paymob network activation remains blocked by configuration.
- Safe reuse: order reference generation, EGP amount normalization, course eligibility checks, learner identity checks, route gating patterns, existing order table fields, entitlement write-service boundary, subscription plan lookup, and admin capability patterns.

## D. Existing Coupon Support Finding

- Legacy coupon admin exists under `Admin::coupons()` / `Admin::coupon_form()` with `Crud_model` methods and backend views `coupon_add.php`, `coupon_edit.php`, and `coupons.php`.
- Legacy coupon UI exposes percentage coupons through `discount_percentage` and `expiry_date`.
- Legacy validation checks coupon existence and expiry through `Crud_model::check_coupon_validity()`.
- Legacy cart discount calculation is session/cart based through `Crud_model::get_discounted_price_after_applying_coupon()` and frontend cart views.
- Legacy restrictions are limited in active logic: no complete YounGo checkout service, no robust item scope validation in the active checkout path, no user restriction enforcement, and no safe usage accounting tied to manual review approval.
- Phase 2 schema references additive coupon support for `discount_type`, `discount_value`, `scope`, `max_usage_count`, `youngo_coupon_courses`, `youngo_coupon_subscription_plans`, and `youngo_coupon_usages`, but that is not currently wired into YounGo checkout.
- Coupons do not currently work with YounGo checkout.
- Legacy coupon calculation should not be reused directly for Instapay checkout because it is cart/session centered and partially view-driven. Reuse should be cautious: read the legacy coupon record and additive scope tables through a dedicated YounGo coupon evaluator, then snapshot the result on the checkout order.
- Coupon data must be immutable checkout snapshot data visible to admin review. Recalculate for validation before snapshot creation, not during admin approval.

## E. Checkout Snapshot Recommendation

Use `youngo_checkout_orders` as the authoritative order row and add only the minimal missing queryable snapshot fields in a later schema phase. Keep a structured immutable JSON snapshot in order metadata or a dedicated `checkout_snapshot_json` column if schema approval allows it.

Recommended queryable order fields:

- Reuse existing: `user_id`, `order_reference`, `order_type`, `course_id`, `plan_id`, `subtotal_amount`, `discount_amount`, `tax_amount`, `total_amount`, `total_amount_cents`, `currency`, `coupon_id`, `coupon_code`, `payment_gateway`, `created_at`, `updated_at`
- Add or formalize: `item_title_snapshot`, `coupon_discount_type`, `coupon_discount_value`, `checkout_source`, `selected_payment_method`
- Keep `payment_gateway` or `selected_payment_method` value as `instapay_manual`; do not use Paymob gateway activation for this flow.

Recommended snapshot JSON payload:

- `user_id`
- `order_id` and `order_reference`
- `item_type`: `course` or `subscription`
- `course_id` or `subscription_plan_id`
- item title/name at checkout time
- original amount
- coupon code used, if any
- coupon discount type/value, if available
- discount amount
- final amount required
- currency `EGP`
- selected payment method `instapay_manual`
- checkout source
- submitted screenshot path/status when later available
- transaction reference and user note when later provided
- timestamps

The order final amount should be locked after snapshot creation. Admin approval verifies the external payment against the snapshotted final amount and must not silently recalculate the coupon.

## F. Instapay Submission Schema Recommendation

Create a new manual-review table in a later schema phase:

`youngo_instapay_payment_submissions`

Recommended fields:

- `id`
- `order_id`
- `order_reference_snapshot`
- `user_id`
- `status`: only `pending_review`, `approved`, `rejected`
- `item_type`
- `course_id`
- `subscription_plan_id`
- `item_title_snapshot`
- `original_amount`
- `coupon_code`
- `coupon_discount_type`
- `coupon_discount_value`
- `discount_amount`
- `final_amount`
- `expected_amount`
- `submitted_amount`
- `currency`
- `instapay_target_label`
- `instapay_target_address`
- `instapay_target_link`
- `screenshot_path`
- `screenshot_original_name`
- `screenshot_mime`
- `screenshot_size`
- `transaction_reference`
- `user_note`
- `admin_note`
- `reviewed_by_user_id`
- `reviewed_at`
- `approved_at`
- `rejected_at`
- `access_issue_status`
- `course_access_id`
- `subscription_id`
- `access_issue_error`
- `checkout_snapshot_json`
- `created_at`
- `updated_at`

Rules:

- One active `pending_review` submission per order.
- Screenshot upload is required before creating the review submission.
- Rejected evidence is preserved; no delete path.
- No automatic approval.
- No access is issued from screenshot upload.
- If resubmission is later allowed, use a non-status control such as `is_current` or `superseded_by_submission_id`; do not introduce additional Instapay review statuses.

## G. Payment Method UI Plan

Checkout should show:

- Coupon entry area before final payment submission.
- Order summary with item snapshot, original amount, coupon line, discount amount, final EGP amount, and selected payment method.
- Payment method cards:
  - Instapay: active and selectable.
  - Cards: visible but disabled with `Not available yet`.
  - Digital Wallets: visible but disabled with `Not available yet`.

Instapay modal/popup should show:

- Locked checkout snapshot.
- Final amount due in EGP.
- Instapay target label/address/link/instructions from configured non-secret admin content.
- Screenshot upload input.
- Optional transaction reference.
- Optional user note.
- Submit for admin review action.

After submit, the learner should see a pending review state and should not receive access.

## H. Admin Review UI Plan

Recommended admin route family for a later implementation phase:

- `admin/youngo/instapay-payments`
- `admin/youngo/instapay-payments/(:num)`
- POST-only approve/reject endpoints under the same controller

Recommended capability: `review_manual_payments`. This should be additive and should follow existing YounGo capability helper patterns.

List page:

- Inbox-style tabs: Pending Review, Approved, Rejected, All.
- Columns: order number, user, item, original amount, coupon code, discount amount, final amount, submitted at, status, action.
- Default sort: newest pending review first.

Detail page:

- User information.
- Item snapshot.
- Checkout snapshot.
- Coupon/discount snapshot.
- Expected final amount.
- Submitted amount.
- Screenshot preview/download through a controlled admin route.
- Transaction reference and user note.
- Admin note field.
- Approve button.
- Reject button.

Approval confirmation text should require explicit confirmation that payment was verified outside the system before access is issued.

## I. Approval/Access Plan

Approval must run in a transaction in a future implementation phase:

- Validate submission status is `pending_review`.
- Validate order belongs to the same user.
- Validate currency is `EGP`.
- Validate the expected amount matches the locked checkout snapshot.
- Validate screenshot evidence exists.
- Validate order has not already issued access.
- Validate no duplicate active course/subscription entitlement already exists for the same user/item.
- Set submission status to `approved`.
- Set `reviewed_by_user_id`, `reviewed_at`, and `approved_at`.
- Mark the order paid/approved as needed for existing reporting while keeping the Instapay review state in the submission table.
- Issue course or subscription access through `Youngo_entitlement_write_model`, not by direct controller writes.
- Start access duration from `approved_at`.
- Mark entitlement issuance fields and link the created course access or subscription row.
- Record coupon usage only after approval if usage accounting is required, so rejected submissions do not consume coupon usage.

Course access can reuse the existing entitlement write boundary, but the current `issue_course_purchase_access()` is HMAC/Paymob-paid-order oriented. It should be generalized or complemented with a manual-payment approval issuance method that accepts `approved_at` as the start timestamp.

Subscription access requires new entitlement write-service work because subscription checkout issuance is currently stubbed. The subscription duration must be calculated from `approved_at`, not order creation or screenshot submission.

Rejection:

- Validate submission status is `pending_review`.
- Set submission status to `rejected`.
- Store reviewer, `reviewed_at`, `rejected_at`, and admin note.
- Do not issue course access.
- Do not issue subscription access.
- Preserve screenshot and submission evidence.

## J. User Status Plan

Learner-facing status should show:

- Pending review: order number, item, final amount, submitted date, and review-pending message.
- Approved: access available with link to the course, My Courses, or My Access.
- Rejected: rejected status and a safe public note if intended.

Do not expose internal admin-only notes by default. If learner-facing rejection notes are needed, use a separate sanitized/public note field or an explicit visibility flag.

## K. Upload/Security Plan

- Allowed file types: image screenshots only, preferably `jpg`, `jpeg`, `png`, and `webp`.
- MIME validation: use server-side Fileinfo/MIME checks; do not trust extensions or browser-provided MIME.
- Max size: recommend 5 MB unless hosting constraints require less.
- Storage path: prefer a protected non-public directory if available. If project constraints require `uploads/`, use a path such as `uploads/youngo/instapay_submissions/YYYY/MM/` with server rules preventing script execution and direct unsafe browsing.
- File naming: random, non-guessable names using order reference plus random bytes; never use the original filename as the stored filename.
- Store the original filename separately for admin context.
- Anti-overwrite: fail or regenerate if the target name exists.
- Access control: screenshot preview/download only through authenticated learner-owner or admin review routes, with admin preview gated by the payment review capability.
- Validation: require POST, CSRF where available, ownership checks, size checks, MIME checks, extension allowlist, and note/reference length limits.
- Evidence retention: rejection must not delete evidence.
- Privacy: consider EXIF stripping or image re-encoding if feasible in the implementation phase.

## L. Disabled Cards/Wallets Plan

- Cards and Digital Wallets should be visible in checkout but disabled.
- Both must show the exact note `Not available yet`.
- Disabled methods must not create paid orders, redirect to Paymob, call external APIs, send network requests, or change hidden activation flags.
- Paymob dashboard/config foundation remains disabled and out of this manual Instapay phase.

## M. Status Model

Instapay review statuses are limited to:

- `pending_review`
- `approved`
- `rejected`

Do not add other Instapay review statuses in this plan. Existing order lifecycle statuses are separate and must not be treated as the Instapay review source of truth.

## N. Reuse vs New Components

Reuse:

- `youngo_checkout_orders` for order identity, amount fields, EGP currency, order references, item IDs, coupon code/discount amount, and entitlement linkage.
- `Youngo_checkout_model` eligibility and amount normalization patterns.
- `Youngo_payment_model` audit patterns where they are not Paymob-specific.
- `Youngo_entitlement_write_model` as the only access issuance boundary.
- `Youngo_subscription_model` for subscription plan lookup and plan snapshots.
- Existing admin navigation/capability patterns.
- Existing Manual Grants list/detail/POST-only review patterns.

New:

- Dedicated YounGo coupon evaluator/snapshot service for checkout.
- `youngo_instapay_payment_submissions` manual review table.
- Instapay checkout UI and upload handling.
- Admin Instapay review controller/views.
- Manual-payment approval issuance methods in the entitlement write service.
- User-facing manual payment status UI.
- Protected screenshot preview/download route.

## O. Implementation Roadmap

1. `PAYMENT.MANUAL.INSTAPAY.COUPON.CHECKOUT.PLAN.1`: current planning and static diagnostic only.
2. `PAYMENT.COUPON.CHECKOUT.SNAPSHOT.AUDIT.1`: design and test a read-only coupon evaluator against legacy and Phase 2 coupon structures.
3. `PAYMENT.MANUAL.INSTAPAY.SCHEMA.1`: add approved order snapshot fields and `youngo_instapay_payment_submissions` with backup, up/down SQL, and no production activation.
4. `PAYMENT.MANUAL.INSTAPAY.CHECKOUT.UI.1`: add coupon/order summary/payment method UI with Instapay active and cards/wallets disabled.
5. `PAYMENT.MANUAL.INSTAPAY.SUBMISSION.UPLOAD.1`: add POST-only upload/submission flow with validation and no access issuance.
6. `PAYMENT.MANUAL.INSTAPAY.ADMIN.REVIEW.UI.1`: add admin inbox/detail pages with capability gate and read-only screenshot preview.
7. `PAYMENT.MANUAL.INSTAPAY.APPROVAL.ACCESS.1`: add approve/reject actions and issue access through the entitlement write service from `approved_at`.
8. `PAYMENT.MANUAL.INSTAPAY.USER.STATUS.UI.1`: add learner status pages/states for pending, approved, and rejected.
9. `PAYMENT.MANUAL.INSTAPAY.FULL.QA.1`: full browser and diagnostic QA, including coupon snapshot, upload validation, approval access timing, rejection no-access, duplicate prevention, and disabled card/wallet boundaries.

## P. Risks/Blockers

- Coupon support is not yet a safe YounGo checkout service. Legacy coupon logic is too cart/session-specific for direct reuse.
- Subscription checkout issuance is currently not implemented.
- Current paid order entitlement issuance is Paymob/HMAC-specific and needs a manual-payment approval path.
- Schema changes require explicit backup/apply/rollback planning in a later phase.
- Instapay target address/link content must be owner-approved and snapshotted without exposing private credentials.
- Upload storage must be protected correctly on the deployed web server.
- Admin review capability needs an additive permission plan before non-Root Admin access.
- Final commercial subscription prices and activation are still owner-gated.
- Resubmission policy after rejection needs an explicit product decision before implementation.

## Q. Diagnostic Result

- `php -l scripts/phase_2/youngo_payment_manual_instapay_coupon_checkout_plan_1_diagnostic.php`: pass.
- `php scripts/phase_2/youngo_payment_manual_instapay_coupon_checkout_plan_1_diagnostic.php`: pass.
- Diagnostic mode: `static_read_only_no_db`.
- Diagnostic summary: 53 checks total, 0 failed.
- Diagnostic confirmed the expected model/config foundation is present, Paymob gates remain false, no Instapay routes/admin routes/schema/source implementation exists yet, and the diagnostic-approved Instapay review statuses are limited to `pending_review`, `approved`, and `rejected`.

## R. Git Status

- Final expected status after this planning phase: only the report and read-only diagnostic are untracked.
- No application controller, model, view, route, config, SQL, payment data, entitlement behavior, user/course/subscription data, or Root Admin files were modified.
