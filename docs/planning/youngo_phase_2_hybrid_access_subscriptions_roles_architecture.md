# YounGo Phase 2 Hybrid Access, Subscriptions, and Roles Architecture

## 1. Executive Summary

This document records the approved Phase 2 business and architecture direction for YounGo access, subscriptions, coupons, manual grants, checkout, payments, and roles.

It is a planning document only. It does not implement subscriptions, entitlement checks, coupons, manual grants, payments, database schema, migrations, controllers, models, views, or source code.

Phase 2 must extend Academy LMS carefully. The existing course purchase, enrolment, lesson access, progress, cart/session, wishlist, payment, invoice, profile, instructor, and permission logic must remain intact until a tested compatibility layer exists.

The target Phase 2 direction is:

- Add a hybrid entitlement layer beside existing Academy LMS enrolment logic.
- Add subscriptions beside existing individual course purchase.
- Add auditable manual grants.
- Expand coupon/discount scope while keeping coupons inside checkout/payment flow.
- Prefer direct checkout UX for YounGo while keeping legacy cart logic for compatibility.
- Add a multi-role/capability model without abruptly breaking existing `role_id` and `is_instructor` behavior.

---

## 2. Owner Decisions

### Product Identity

- YounGo targets both children and parents.
- The account is the student/user account.
- Payment may be performed by the parent, but the invoice and access remain under the student account.
- Younger children may have parents operating the account.
- Older children may use the account themselves.
- Parent accounts with multiple children are deferred.
- YounGo is B2C only for now.

### Access Model

YounGo must support these access paths:

- Individual course purchase access.
- Active subscription access.
- Manual grant access by admin.
- Admin and assigned instructor access.

Coupons and discounts are not direct access paths. They must go through checkout/payment flow and result in a valid course purchase access record or a valid subscription access record, including when final price is `0`.

### Phase 2 Compatibility Principle

Do not:

- Remove existing course purchase.
- Remove existing Academy LMS enrolment/payment logic before a safe compatibility layer exists.
- Remove or abruptly replace `enrol` table usage.
- Remove or abruptly replace existing lesson access helpers such as `enroll_status()`.
- Remove cart code, even though direct checkout is the target YounGo UX.
- Change payment behavior until architecture and QA plans are approved.
- Change existing `role_id` or `is_instructor` behavior abruptly.

---

## 3. Hybrid Access Model

The target access model should answer one question consistently:

```text
Can this account access this course and its lessons right now?
```

The answer may come from any valid entitlement:

- Active individual course purchase entitlement.
- Active subscription entitlement for subscription-eligible courses.
- Active manual course grant.
- Active manual subscription grant.
- Admin access.
- Assigned instructor access for assigned courses.

Existing Academy LMS enrolment remains part of compatibility. Phase 2 should add an entitlement compatibility layer that can read legacy purchase/enrolment state and new YounGo access records together.

Recommended behavior:

- Course details may remain visible even when lesson access is locked.
- Lesson access must require a valid entitlement unless the user is admin or an assigned instructor.
- Progress must remain saved after access expires.
- Expired time-limited access may still leave the course visible in My Courses, but locked.

---

## 4. Course Access Modes

When creating or editing a course, the target course access defaults are:

- Default access mode: subscription-only.
- If "available for individual purchase" is enabled, show a purchase/pricing section.
- If individual purchase is enabled, the course can be subscription plus individual purchase, or purchase only.
- Purchase-only courses are excluded from subscription access.
- Subscription access gives full lesson access, not only course details.
- Course purchase defaults to lifetime access.
- Optional time-limited course purchase may be supported.

Access warnings:

- Warn after 80% of subscription duration has elapsed.
- Warn after 80% of any time-limited course purchase duration has elapsed.
- No warning is needed for lifetime course purchase access.

Free-course direction:

- Do not treat "free course" as a core YounGo business concept.
- Existing Academy LMS free-course functionality may remain for legacy compatibility during migration.
- Target free access should happen through checkout with a 100% discount, checkout with a 100% coupon, or manual grant.

---

## 5. Subscription Plans

Initial plans:

- Monthly.
- 3 Months.
- Yearly.

Business rules:

- Main commercial focus is the 3 Months plan.
- No free trial.
- No grace period after expiry.
- Expiry blocks access immediately.
- Renewal is manual, not automatic.
- A user cannot change plan while a subscription is active.
- A subscription is for one user/account only.
- Account page must show subscription status, expiry, and appropriate warnings.

User subscription behavior:

- Active subscription unlocks eligible subscription courses.
- Purchase-only courses are not unlocked by subscription.
- Expired subscription blocks lesson access immediately.
- Progress remains saved after expiry.
- Renewed subscription should restore access to eligible subscription courses without resetting progress.

---

## 6. Coupons And Discounts

Coupons must support:

- Percentage discount.
- Fixed amount discount.
- Expiry date.
- Maximum usage count.
- Scope:
  - Subscription only.
  - Course purchase only.
  - Both.

Subscription coupons:

- Admin can select which subscription plan or plans the coupon applies to.

Course purchase coupons:

- Admin can select included courses.
- Admin can exclude specific courses from eligible purchasable courses.

Pricing rules:

- If a fixed amount discount is greater than the price, final price becomes `0`.
- Do not reject a coupon only because the fixed discount is larger than the price.
- Coupons and discounts must still produce a formal checkout/payment/access record, even when final price is `0`.

Important access rule:

```text
A coupon or discount is not access.
Access is granted only after a valid checkout/payment/access record or a manual grant exists.
```

---

## 7. Manual Grants

Admins can manually grant access without checkout.

Permissions:

- Root Admin can do everything.
- Normal Admin can grant manual access.
- Admin can grant manual access even to themselves.
- Manual grants must be auditable.

Manual grant types:

### Course Purchase Access

Required:

- Select user.
- Select course.
- Default lifetime access.
- Optional custom expiry or duration if supported.

### Subscription Access

Required:

- Select user.
- Select subscription plan, or set custom duration manually.
- Store start date and expiry date.

Audit fields should include at least:

- Who granted access.
- Who received access.
- Access type.
- Course or subscription plan.
- Start date.
- Expiry date or lifetime.
- Reason/note if provided.
- `created_at`.

Manual grants should not create fake payment records unless a later implementation plan explicitly approves a separate accounting/audit strategy.

---

## 8. Direct Checkout Strategy

Academy LMS currently has cart/session logic. Do not delete it.

YounGo target UX should not use a traditional cart as the primary purchase path.

Target behavior:

- Individual course purchase CTA goes directly to checkout/payment for that course.
- Subscription CTA goes directly to checkout/payment for that plan.
- Legacy cart code remains for compatibility and rollback safety.
- Existing cart routes/pages may remain available, but new YounGo UX should prefer direct checkout.

Checkout records are required for:

- Paid individual course purchases.
- Individual course purchases discounted to `0`.
- Paid subscriptions.
- Subscriptions discounted to `0`.

Manual grants are the exception because they are admin-created access records outside checkout.

---

## 9. Paymob Payment Strategy

Paymob is the intended payment provider for future real payment implementation.

Rules:

- Use the latest Paymob integration information when implementation begins.
- Do not decide exact Paymob payment method in this architecture document.
- Any Paymob-supported payment method may be used later after implementation research.
- Real payments must not be enabled until implementation and QA are explicitly approved.
- Payment credentials must not be committed or printed.

Invoices:

- Required for individual course purchases.
- Required for subscriptions.
- Invoice is under the student account name.
- Parent payment does not create a separate parent account in Phase 2.

---

## 10. Multi-Role / Capability Model

Target direction:

- Accounts should support multiple roles/capabilities.
- A single account can be Course Manager plus Content Manager.
- Instructor can also be Course Manager or Content Manager if toggled.
- Learner/User capability should be automatic for all accounts for compatibility.
- Root Admin is a protected special case, not a normal toggle.

Existing compatibility:

- Existing `role_id` and `is_instructor` behavior must not be abruptly removed.
- New capability checks should be introduced as a compatibility layer first.
- Existing admin/instructor/student workflows should continue until replacement behavior is implemented and tested.

Capabilities to support:

- Root Admin.
- Admin.
- Content Manager.
- Course Manager.
- Instructor.
- User/Learner.

---

## 11. Role Definitions

### Root Admin

- Can do everything.
- Must be protected.
- No one can casually modify or downgrade root admin role/status.
- Root Admin is not a normal capability toggle.

### Admin

- Can do almost everything.
- Cannot touch core system settings unless explicitly allowed.
- Cannot modify root admin role/status.
- Can grant manual access.

### Content Manager

- Can manage page content.
- Can manage homepage sections and content.
- Can manage blog, FAQ, testimonials, and static pages if available.
- Can upload/manage media/images related to content.
- Cannot access payments.
- Cannot access user management.
- Cannot access system settings.

### Course Manager

- Can manage courses.
- Can create, edit, and publish courses.
- Can manage lessons.
- Can manage categories if required for course management.
- Can assign existing instructors to courses.
- Cannot create/delete instructor accounts.
- Cannot access payments.
- Cannot access system settings.

### Instructor

- Can add lessons only to courses assigned to them.
- Does not need approval for adding lessons to assigned courses.
- Cannot access all courses unless also given Course Manager capability.

### User / Learner

- Student account.
- Parent may operate the account, but the account belongs to the student.
- Can access content through subscription, course purchase, or manual grant.

---

## 12. Instructor Assignment Rules

- Admin and Course Manager can assign an existing instructor to a course.
- Course Manager cannot create instructor accounts.
- Course Manager cannot delete instructor accounts.
- Instructor can manage/add lessons only in courses assigned to them.
- Instructor does not gain full course-management access unless also given Course Manager capability.

Instructor polish should wait until the multi-role/capability architecture is designed and implemented enough to avoid rebuilding the legacy instructor flow twice.

---

## 13. Data Model Proposal

Final schema requires implementation approval. The conceptual data areas are:

- Subscription plans.
- User subscriptions.
- Course access configuration.
- Course purchase entitlements, including lifetime or expiry.
- Manual grants.
- Manual grant audit records.
- Coupon scope rules for subscriptions and course purchases.
- Coupon usage records.
- Role/capability assignments.
- Compatibility mapping to existing `role_id`, `permissions`, `is_instructor`, `enrol`, and payment tables.

Implementation should prefer additive, backward-compatible structures. Do not remove or rename existing Academy LMS tables/columns during the first Phase 2 implementation pass.

---

## 14. Entitlement Layer Proposal

Introduce a single access decision layer that checks:

1. Admin/root admin bypass where appropriate.
2. Assigned instructor access for the course.
3. Manual course grant.
4. Manual subscription grant.
5. Active user subscription and course subscription eligibility.
6. Individual course purchase entitlement.
7. Legacy Academy LMS enrolment compatibility.

The layer should return structured access state, such as:

- Has access.
- Access source.
- Starts at.
- Expires at or lifetime.
- Is expired.
- Is within warning window.
- Lock reason if access is denied.

Do not scatter new access rules across views. Use a model/helper/service-style layer consistent with the existing CodeIgniter project.

---

## 15. Migration Strategy

Phase 2 should be introduced in steps:

1. Document and review architecture.
2. Inspect existing database and access/payment code.
3. Add read-only compatibility helpers for current access state.
4. Add additive tables/fields only after schema approval.
5. Add admin configuration screens.
6. Add entitlement checks behind compatibility behavior.
7. Add checkout changes in sandbox/test mode only.
8. QA with legacy purchase, subscription, manual grant, instructor, admin, expired access, and coupon scenarios.
9. Enable production payment only after explicit approval.

Existing users, courses, purchases, enrolments, progress, invoices, and uploads must remain usable during migration.

---

## 16. Rollout Phases

Recommended rollout order:

1. Architecture and documentation alignment.
2. Code/database audit for access, purchase, coupon, payment, roles, and instructors.
3. Entitlement compatibility layer.
4. Course access configuration.
5. Subscription plans and user subscriptions.
6. Manual grants and audit trail.
7. Coupon scope improvements.
8. Direct checkout UX.
9. Paymob sandbox integration planning and QA.
10. Multi-role/capability layer.
11. Instructor assignment and instructor UX polish.

---

## 17. Risk Controls

- Keep Academy LMS as the core engine.
- Preserve existing course purchase.
- Preserve existing enrolment/payment behavior until compatibility is tested.
- Preserve existing cart code.
- Preserve existing progress records.
- Preserve existing invoices and purchase history.
- Keep real payments disabled until approved.
- Add new role/capability behavior additively.
- Protect Root Admin.
- Require audit records for manual grants.
- Require checkout/payment/access records for coupon-based zero-price access.

---

## 18. Open Questions

No owner-level business-model questions are open for this planning step.

Implementation planning still needs technical answers after code/database inspection:

- Exact existing schema and helper methods to reuse.
- Exact table/field names for additive Phase 2 structures.
- Exact Paymob method and integration flow.
- Exact compatibility behavior for historical free enrolments.
- Exact UI placement for Phase 2 admin screens.

---

## 19. Deferred Items

- Parent account with multiple children.
- B2B/company/school account model.
- Automatic subscription renewal.
- Free trial.
- Grace period after expiry.
- Public free-course business model.
- Exact Paymob payment method.
- Real production payment activation.
- Full instructor UX rebuild before role/capability architecture.

