# YounGo Phase 2B Database Schema And Migration Plan

## 1. Executive Summary

This document records the approved Phase 2B database schema and migration plan for YounGo hybrid access, subscriptions, coupons, manual grants, direct checkout, and multi-role/capability support.

It is a planning document only. It does not implement database changes, migrations, SQL, source code, payment behavior, entitlement checks, role changes, or admin screens.

Phase 2B must preserve Academy LMS compatibility. Existing course purchase, enrolment, lesson access, progress, cart/session, payment, invoice, coupon, user, instructor, and permission logic remain in place until a tested compatibility layer is implemented.

The recommended database direction is:

- Add new `youngo_` tables for Phase 2 subscription, entitlement, checkout, coupon targeting, manual grant, and capability data.
- Add only safe, additive fields to existing `course`, `coupons`, and optionally `payment`.
- Do not remove, rename, or abruptly replace legacy tables such as `enrol`, `payment`, `permissions`, `role`, or `users`.
- Do not automatically migrate historical access or course expiry behavior without per-record review.
- Require production or staging verification with real data before any migration is executed outside local development.

---

## 2. Existing DB Verification Summary

The local database was inspected after XAMPP/MySQL was started.

Verification scope:

- Database name: `youngo_school`
- Server: MariaDB `10.4.32`
- Existing `youngo_%` tables: `0`
- Inspection method: read-only schema and count checks only.
- No material schema differences were found against the inspected legacy tables from `uploads/install.sql` and current code assumptions.

Important local data notes:

- Local `enrol`, `payment`, `coupons`, `watch_histories`, and `watched_duration` tables are mostly empty.
- Production or staging verification with real records is still required before running any migration.
- `6` of `7` local courses currently have `is_free_course=1`.
- Existing free-course state must be preserved as Academy LMS legacy compatibility.
- Public free-course classification must not become the future YounGo business model.
- Admin users and `permissions` rows are not one-to-one, so current root/admin compatibility must be protected.
- Do not automatically migrate `course.expiry_period` into new purchase duration fields without per-course review.

---

## 3. Current Relevant Legacy Tables

### `users`

Current role-related fields:

- `role_id`
- `is_instructor`
- `status`
- profile, session, wishlist, and payment-key fields

Compatibility rule:

- Do not remove or abruptly replace `users.role_id` or `users.is_instructor`.
- Learner/user capability should remain automatic for all accounts.

### `role`

Current role records:

- Admin
- User

Compatibility rule:

- Do not use the legacy `role` table as the only future role model.
- Add YounGo role/capability tables beside it.

### `permissions`

Current behavior:

- Stores admin module permissions as JSON.
- Current root admin behavior depends on existing permission-row assumptions.

Compatibility rule:

- Do not overwrite root-admin behavior during seeding.
- Do not replace `has_permission()` behavior abruptly.

### `enrol`

Current behavior:

- Stores legacy course access by `user_id` and `course_id`.
- Stores optional `expiry_date`.
- Existing helpers such as `enroll_status()` depend on this table.

Compatibility rule:

- Keep `enrol` as a compatibility access source.
- Do not delete enrolment rows to expire access.
- Progress must remain preserved after access expiry.

### `payment`

Current behavior:

- Stores purchase history and invoice records.
- Current shape is course-purchase oriented, with one `course_id` per row.

Compatibility rule:

- Keep existing payment and invoice behavior.
- Subscription invoices may require additive linkage or dedicated YounGo invoice handling later.

### `course`

Current access/pricing-related fields:

- `price`
- `discount_flag`
- `discounted_price`
- `is_free_course`
- `expiry_period`
- `user_id`
- `creator`
- `multi_instructor`

Compatibility rule:

- Preserve existing fields and meanings.
- Add new YounGo access-mode fields beside them.

### `coupons`

Current fields:

- `code`
- `discount_percentage`
- `created_at`
- `expiry_date`

Compatibility rule:

- Existing percentage coupons must remain interpretable.
- Add fixed discount, scope, targeting, max usage, and usage tracking additively.

### `watch_histories` And `watched_duration`

Current behavior:

- Store course progress and watched duration.

Compatibility rule:

- Do not delete or reset these records when access expires.

### `lesson` And `section`

Current behavior:

- Store course content structure.
- `lesson.is_free` exists as legacy Academy LMS behavior.

Compatibility rule:

- Do not use `lesson.is_free` or course free flags as the future YounGo business model.
- Subscription and purchase access should unlock full lesson access when valid.

### `settings` And `frontend_settings`

Current behavior:

- Store general system and frontend settings.

Compatibility rule:

- Do not place Phase 2 entitlement records in settings JSON.
- Use explicit YounGo Phase 2 tables for durable business data.

---

## 4. Recommended New `youngo_` Tables

The first migration should use additive tables with indexed references instead of hard foreign keys unless the target live database is confirmed safe for FK enforcement.

### `youngo_subscription_plans`

Purpose:

- Stores Monthly, 3 Months, Yearly, and future subscription plans.

Recommended fields:

- `id`
- `name`
- `slug`
- `duration_days`
- `price`
- `currency`
- `is_active`
- `is_featured`
- `sort_order`
- `created_at`
- `updated_at`

Recommended indexes:

- Unique `slug`
- `is_active`
- `sort_order`

MVP status: required.

### `youngo_user_subscriptions`

Purpose:

- Stores paid, manually granted, active, expired, cancelled, or revoked subscription access for one user account.

Recommended fields:

- `id`
- `user_id`
- `plan_id`
- `source`
- `status`
- `start_date`
- `expiry_date`
- `duration_days`
- `price_paid`
- `currency`
- `checkout_order_id`
- `payment_id`
- `manual_grant_id`
- `created_at`
- `updated_at`
- `revoked_at`

Recommended indexes:

- `user_id`, `status`, `expiry_date`
- `plan_id`
- `checkout_order_id`
- `payment_id`
- `manual_grant_id`

MVP status: required.

### `youngo_course_access`

Purpose:

- Stores individual course purchase access and manual course grants without replacing legacy `enrol`.

Recommended fields:

- `id`
- `user_id`
- `course_id`
- `access_source`
- `status`
- `start_date`
- `expiry_date`
- `is_lifetime`
- `checkout_order_id`
- `payment_id`
- `enrol_id`
- `manual_grant_id`
- `created_at`
- `updated_at`
- `revoked_at`

Recommended indexes:

- `user_id`, `course_id`, `status`
- `course_id`, `status`
- `expiry_date`
- `payment_id`
- `enrol_id`
- `manual_grant_id`

MVP status: required.

### `youngo_manual_grants`

Purpose:

- Stores auditable admin grants for course purchase access or subscription access.

Recommended fields:

- `id`
- `granted_by_user_id`
- `granted_to_user_id`
- `grant_type`
- `course_id`
- `plan_id`
- `custom_duration_days`
- `start_date`
- `expiry_date`
- `is_lifetime`
- `status`
- `note`
- `created_at`
- `revoked_at`
- `revoked_by_user_id`
- `revoke_note`

Recommended indexes:

- `granted_to_user_id`
- `granted_by_user_id`
- `grant_type`, `status`
- `course_id`
- `plan_id`

MVP status: required.

### `youngo_checkout_orders`

Purpose:

- Stores direct checkout intent/order records for course purchases and subscriptions, including zero-price checkout.

Recommended fields:

- `id`
- `user_id`
- `order_type`
- `status`
- `course_id`
- `plan_id`
- `subtotal_amount`
- `discount_amount`
- `tax_amount`
- `total_amount`
- `currency`
- `coupon_id`
- `coupon_code`
- `payment_gateway`
- `provider_intent_id`
- `provider_transaction_id`
- `payment_id`
- `metadata`
- `created_at`
- `updated_at`
- `completed_at`

Recommended indexes:

- `user_id`, `status`
- `order_type`, `status`
- `course_id`
- `plan_id`
- `coupon_id`
- `provider_intent_id`
- `payment_id`

MVP status: required.

### `youngo_coupon_usages`

Purpose:

- Tracks coupon usage for max usage count and audit.

Recommended fields:

- `id`
- `coupon_id`
- `coupon_code`
- `user_id`
- `checkout_order_id`
- `payment_id`
- `discount_amount`
- `used_at`

Recommended indexes:

- `coupon_id`
- `coupon_code`
- `user_id`
- `checkout_order_id`
- `payment_id`

MVP status: required.

### `youngo_coupon_subscription_plans`

Purpose:

- Maps subscription coupons to allowed subscription plans.

Recommended fields:

- `id`
- `coupon_id`
- `plan_id`

Recommended indexes:

- Unique `coupon_id`, `plan_id`
- `plan_id`

MVP status: required.

### `youngo_coupon_courses`

Purpose:

- Maps course purchase coupons to included or excluded courses.

Recommended fields:

- `id`
- `coupon_id`
- `course_id`
- `rule_type`

Recommended indexes:

- Unique `coupon_id`, `course_id`, `rule_type`
- `course_id`

MVP status: required.

### `youngo_roles`

Purpose:

- Stores YounGo role bundles without replacing the legacy `role` table.

Recommended fields:

- `id`
- `role_key`
- `label`
- `description`
- `is_system`
- `is_assignable`
- `created_at`

Recommended indexes:

- Unique `role_key`

MVP status: required.

### `youngo_capabilities`

Purpose:

- Stores atomic capability keys for the new compatibility layer.

Recommended fields:

- `id`
- `capability_key`
- `label`
- `description`
- `is_system`
- `created_at`

Recommended indexes:

- Unique `capability_key`

MVP status: required.

### `youngo_role_capabilities`

Purpose:

- Maps YounGo role bundles to capabilities.

Recommended fields:

- `id`
- `role_id`
- `capability_id`

Recommended indexes:

- Unique `role_id`, `capability_id`
- `capability_id`

MVP status: required.

### `youngo_user_roles`

Purpose:

- Allows one account to hold multiple YounGo roles.

Recommended fields:

- `id`
- `user_id`
- `role_id`
- `assigned_by_user_id`
- `created_at`
- `revoked_at`
- `status`

Recommended indexes:

- `user_id`, `status`
- `role_id`, `status`

MVP status: required.

### `youngo_user_capabilities`

Purpose:

- Allows direct capability overrides when a full role bundle is too broad.

Recommended fields:

- `id`
- `user_id`
- `capability_id`
- `assigned_by_user_id`
- `created_at`
- `revoked_at`
- `status`

Recommended indexes:

- `user_id`, `status`
- `capability_id`, `status`

MVP status: useful soon after MVP; may be deferred if role bundles are enough for the first pass.

### `youngo_entitlement_events`

Purpose:

- Optional audit stream for entitlement state changes.

Recommended fields:

- `id`
- `user_id`
- `course_id`
- `subscription_id`
- `course_access_id`
- `event_type`
- `event_source`
- `actor_user_id`
- `details`
- `created_at`

Recommended indexes:

- `user_id`
- `course_id`
- `subscription_id`
- `course_access_id`
- `event_type`

MVP status: useful soon after MVP; not required if manual grant, order, subscription, and course access records are implemented carefully.

---

## 5. Recommended Additive Changes To Existing Tables

### `course`

Recommended additive fields:

- `youngo_access_mode`
- `youngo_allow_individual_purchase`
- `youngo_purchase_access_type`
- `youngo_purchase_duration_days`
- `youngo_subscription_excluded`

Rules:

- Default target mode is `subscription_only`.
- If individual purchase is enabled, the course may be `subscription_and_purchase` or `purchase_only`.
- `purchase_only` courses are excluded from subscription access.
- Existing `price`, `discount_flag`, `discounted_price`, `is_free_course`, and `expiry_period` remain legacy-compatible fields.
- Do not automatically migrate `expiry_period` into the new duration field without per-course review.

### `coupons`

Recommended additive fields:

- `discount_type`
- `discount_value`
- `scope`
- `max_usage_count`
- `status`
- `updated_at`

Rules:

- Existing rows should be interpreted as percentage coupons using `discount_percentage`.
- Fixed discounts greater than the price must clamp final price to `0`.
- Coupons are not direct access.

### `payment`

Recommended optional additive fields:

- `youngo_order_id`
- `youngo_payment_subject`
- `youngo_subscription_id`

Rules:

- Existing invoice and purchase history behavior must remain compatible.
- Subscription invoice strategy should be finalized during checkout implementation planning.

### Tables Not To Change Initially

Do not alter initially:

- `users`
- `role`
- `permissions`
- `enrol`
- `watch_histories`
- `watched_duration`
- `lesson`
- `section`
- `settings`
- `frontend_settings`

---

## 6. Entitlement / Access Data Model

Later implementation should introduce one compatibility access decision layer:

```text
can_user_access_course(user_id, course_id)
```

The layer should check these sources:

1. Root admin or admin bypass where appropriate.
2. Assigned instructor access through existing course assignment behavior.
3. Valid legacy `enrol` row.
4. Valid `youngo_course_access` row.
5. Active `youngo_user_subscriptions` row covering a subscription-eligible course.
6. Active manual grant represented through course access or subscription records.

Expired access rules:

- Expired access locks lesson access.
- Expired access does not delete enrolment, access, subscription, payment, invoice, or progress records.
- My Courses may continue showing expired courses as locked.

Warning rules:

- Subscriptions warn after 80% of the duration has elapsed.
- Time-limited course purchases warn after 80% of the duration has elapsed.
- Lifetime purchases do not need expiry warnings.

---

## 7. Subscription Data Model

Subscription plans:

- Monthly.
- 3 Months.
- Yearly.
- 3 Months is the main commercial focus.

Subscription behavior:

- No free trial.
- No grace period.
- Expiry blocks access immediately.
- Renewal is manual.
- A user cannot change plan while a subscription is active.
- Subscription belongs to one student/user account.

Data strategy:

- Store plan definitions in `youngo_subscription_plans`.
- Store user subscription periods in `youngo_user_subscriptions`.
- Store paid checkout in `youngo_checkout_orders`.
- Link to `payment` where needed for invoice compatibility.
- Store manual subscription grants through `youngo_manual_grants` plus `youngo_user_subscriptions`.

---

## 8. Course Purchase Data Model

Individual course purchase remains supported beside subscription access. A purchasable course may be `subscription_and_purchase` or `purchase_only`; purchase-only courses are excluded from subscription entitlement. Purchased course access defaults to lifetime. If a course is configured for time-limited purchase, the access row stores `start_date`, `expiry_date`, and `is_lifetime=0`. When time-limited access expires, lesson access is locked, but enrolment/access history and progress records remain preserved. During the compatibility phase, completed purchases may still create or update legacy `enrol` rows so existing lesson access, My Courses, purchase history, and invoice flows continue to work.

Data strategy:

- Store new purchase access in `youngo_course_access`.
- Store checkout intent and totals in `youngo_checkout_orders`.
- Link completed paid purchases to `payment` for compatibility where needed.
- Keep legacy `enrol` compatibility until the entitlement layer is tested.
- Do not rely on public free-course classification as the future model.

---

## 9. Coupon / Discount Data Model

Coupons must support:

- Percentage discount.
- Fixed amount discount.
- Expiry date.
- Maximum usage count.
- Scope: subscription only, course purchase only, or both.
- Subscription plan targeting.
- Course include/exclude targeting.

Data strategy:

- Keep `coupons` as the base legacy coupon table.
- Add fields for discount type, value, scope, max usage, and status.
- Store plan targeting in `youngo_coupon_subscription_plans`.
- Store course include/exclude targeting in `youngo_coupon_courses`.
- Store usage audit in `youngo_coupon_usages`.

Rules:

- A coupon or discount is not access.
- Coupon-based free access must still go through checkout and create formal order/access records.
- Fixed amount discounts greater than price must result in final price `0`, not a rejected coupon.

---

## 10. Manual Grant Data Model

Manual grants are admin-created access records outside checkout.

Rules:

- Root Admin can do everything.
- Normal Admin can grant manual access.
- Admin can grant access to themselves.
- Manual grants must be auditable.
- Manual grants must not create fake payment rows unless a later accounting plan explicitly approves that behavior.

Data strategy:

- Insert an audit header in `youngo_manual_grants`.
- For course access, create a linked `youngo_course_access` row.
- For subscription access, create a linked `youngo_user_subscriptions` row.
- Use status and revocation fields instead of deleting grant records.

Grant audit must record:

- Who granted access.
- Who received access.
- Access type.
- Course or subscription plan.
- Start date.
- Expiry date or lifetime.
- Reason/note if provided.
- Created timestamp.

---

## 11. Multi-Role / Capability Data Model

Target behavior:

- Accounts may have multiple roles/capabilities.
- Learner/user capability is automatic for all accounts.
- Root Admin is protected and not a normal toggle.
- Instructor can also be Course Manager or Content Manager if assigned.
- Course Manager can assign existing instructors but cannot create/delete instructor accounts.

Data strategy:

- Store role bundles in `youngo_roles`.
- Store atomic capabilities in `youngo_capabilities`.
- Store role defaults in `youngo_role_capabilities`.
- Store user role assignments in `youngo_user_roles`.
- Add `youngo_user_capabilities` later for direct overrides if needed.

Compatibility:

- Preserve `users.role_id`.
- Preserve `users.is_instructor`.
- Preserve `permissions` and `has_permission()` during the compatibility phase.
- Seed YounGo roles from existing admin/instructor state only after root-admin protection is reviewed.

---

## 12. Direct Checkout / Order / Payment Data Model

YounGo target UX should prefer direct checkout for:

- Individual course purchase.
- Subscription purchase.

Legacy cart/session logic must remain available for compatibility.

Data strategy:

- Use `youngo_checkout_orders` for new direct checkout records.
- Use `order_type=course_purchase` for course purchase.
- Use `order_type=subscription` for subscription purchase.
- Store subtotal, discount, tax, total, coupon, gateway, provider intent, and completion timestamps.
- Link completed paid orders to existing `payment` rows where needed.
- Create formal order/access records even when total price is `0`.

Payment rules:

- Paymob is the intended provider later.
- Exact Paymob payment method is deferred.
- Real payments must not be enabled until implementation and QA are explicitly approved.
- Invoices are required for both individual course purchases and subscriptions.

---

## 13. Migration Sequence

Recommended safe sequence:

1. Back up database and uploads.
2. Verify live production/staging schema and data, not only local DB.
3. Confirm no existing `youngo_%` table-name conflicts.
4. Create new `youngo_` tables.
5. Add safe additive `course` fields.
6. Add safe additive `coupons` fields.
7. Add optional `payment` linkage fields only when checkout/invoice work is approved.
8. Seed subscription plans.
9. Seed YounGo roles and capabilities.
10. Review root-admin behavior before assigning or syncing admin roles.
11. Backfill instructor/admin YounGo roles only after compatibility rules are confirmed.
12. Do not bulk backfill all legacy enrolments into `youngo_course_access` without a tested script and approval.
13. Implement entitlement compatibility layer in a later phase.
14. Run full access, payment, coupon, role, and invoice regression testing.

---

## 14. Rollback Strategy

Before Phase 2 data is used:

- Rollback can remove newly added `youngo_` tables and additive columns only after a backup is confirmed.
- Prefer restoring from a full database backup if any migration partially applies.

After Phase 2 test data exists:

- Export all `youngo_` tables before rollback.
- Disable Phase 2 code paths before removing schema.
- Preserve manual grant, checkout, subscription, course access, coupon usage, and audit records unless deletion is explicitly approved.

Never drop or truncate without backup:

- `enrol`
- `payment`
- `watch_histories`
- `watched_duration`
- `youngo_manual_grants`
- `youngo_user_subscriptions`
- `youngo_course_access`
- `youngo_checkout_orders`
- `youngo_coupon_usages`

---

## 15. Seed Data Plan

Subscription plans:

- Monthly, active.
- 3 Months, active and featured.
- Yearly, active.

YounGo roles:

- Admin.
- Content Manager.
- Course Manager.
- Instructor.

Initial capabilities:

- Manage homepage/content.
- Manage media.
- Manage courses.
- Manage lessons.
- Publish courses.
- Manage course categories.
- Assign existing instructors.
- Grant manual access.
- View payments.
- Manage users.
- Manage system settings.

Backfill strategy:

- Existing admin-role users may receive YounGo Admin role after root-admin review.
- Existing `is_instructor=1` users may receive YounGo Instructor role.
- Learner capability remains implicit for all users.

---

## 16. MVP vs Later Scope

### Required For Phase 2 MVP

- Subscription plans.
- User subscriptions.
- Course access records.
- Manual grant audit.
- Direct checkout order records.
- Coupon scope and usage tracking.
- Course access-mode fields.
- Role/capability base tables.
- Seed data for plans, roles, and capabilities.

### Useful Soon After MVP

- Direct user capability overrides.
- Entitlement event audit stream.
- Automated expiry status maintenance.
- Dedicated subscription invoice display.
- Payment linkage refinements.

### Deferred

- Parent account with multiple children.
- B2B account model.
- Automatic subscription renewal.
- Free trial.
- Grace period.
- Public free-course business model.
- Replacing legacy `enrol`.
- Replacing legacy `permissions`.
- Replacing legacy cart/session logic.
- Production Paymob activation.

---

## 17. Risks

Key risks:

- Local DB has little purchase, enrolment, coupon, and progress data, so real-data verification is still required.
- Root admin behavior depends on existing `permissions` assumptions.
- Existing free-course flags are common in local data and must remain legacy-only.
- Existing `payment` is course-purchase oriented and may not fit subscription invoices without additive linkage or view changes.
- Existing coupon flow includes a 100% coupon path that grants access without a formal payment record; YounGo checkout must not repeat that behavior.
- Existing instructor assignment uses legacy course fields and should not be replaced abruptly.
- Lack of composite indexes in legacy tables means new YounGo tables need their own lookup indexes.
- Paymob implementation details are deferred and must be researched at implementation time.
- Local DB and server DB may drift.

---

## 18. Validation Plan

Before migration implementation:

- Verify production/staging DB schema and row counts.
- Confirm no `youngo_%` table conflicts.
- Confirm MySQL/MariaDB version compatibility.
- Confirm root-admin and permissions-row state.
- Review real `enrol`, `payment`, `coupons`, and progress records.

After test migration:

- Confirm legacy course access still works.
- Confirm legacy cart/checkout remains usable.
- Confirm existing purchase history and invoice views still work.
- Confirm existing admin/instructor/student role flows still work.
- Confirm new subscription plans and role/capability seed rows exist.
- Confirm no progress records are modified.
- Confirm zero-price checkout can still create formal order/access records.

---

## 19. Recommended Implementation Sequence

Recommended next phases:

1. Phase 2C: entitlement/access compatibility layer implementation plan.
2. Phase 2D: multi-role/capability compatibility layer implementation plan.
3. Phase 2E: subscription plans admin.
4. Phase 2F: user subscription purchase and manual grant implementation.
5. Phase 2G: coupon scope expansion.
6. Phase 2H: direct checkout.
7. Phase 2I: Paymob sandbox integration.
8. Phase 2J: QA, regression, security review, and deployment planning.

---

## 20. Open Questions / Implementation-Time Decisions

No owner-level business-model questions are open for this schema plan.

Implementation-time decisions:

- Exact subscription prices and currency.
- Exact subscription invoice implementation path.
- Whether `payment` rows should be created for zero-price checkout or whether `youngo_checkout_orders` should power those invoices.
- Whether and how to map existing `course.expiry_period` to new purchase duration fields.
- Whether instructor assignment needs a normalized table after capability compatibility exists.
- Exact Paymob method and sandbox flow.
- Production/staging DB differences from local `youngo_school`.

---

## 21. Final Recommendation

Proceed with the additive Phase 2B schema direction only after implementation approval.

Do not implement schema changes from this document automatically. Do not run migrations or SQL until a migration package, backup plan, rollback plan, and QA checklist are explicitly approved.

The safest path is:

- Keep Academy LMS as the core engine.
- Add YounGo Phase 2 business data beside existing LMS tables.
- Preserve existing enrolment, payment, invoice, role, permission, instructor, cart, and lesson access behavior.
- Build compatibility layers before changing user-facing access, checkout, coupon, payment, or role behavior.
